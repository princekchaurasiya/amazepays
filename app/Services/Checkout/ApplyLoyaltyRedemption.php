<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyRedemption;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApplyLoyaltyRedemption
{
    public function apply(Order $order, int $userId, int $pointsToRedeem, ?CarbonImmutable $occurredAt = null): void
    {
        if ($pointsToRedeem <= 0) {
            return;
        }

        $occurredAt = $occurredAt ?? CarbonImmutable::now();

        DB::transaction(function () use ($order, $userId, $pointsToRedeem, $occurredAt): void {
            $order->loadMissing(['items']);

            $program = $this->resolveProgram($order);

            /** @var LoyaltyAccount $account */
            $account = LoyaltyAccount::query()
                ->where('program_id', (int) $program->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $account) {
                $account = LoyaltyAccount::query()->create([
                    'program_id' => (int) $program->id,
                    'user_id' => $userId,
                    'current_tier_id' => null,
                    'points_balance' => 0,
                    'points_lifetime_earned' => 0,
                    'points_lifetime_redeemed' => 0,
                    'points_pending' => 0,
                    'tier_expires_at' => null,
                ]);

                $account->refresh();
                $account->lockForUpdate();
            }

            $available = (int) $account->points_balance;
            if ($pointsToRedeem > $available) {
                throw ValidationException::withMessages([
                    'loyalty_points_to_redeem' => 'Insufficient loyalty points balance.',
                ]);
            }

            $rate = (float) ($program->point_to_currency_rate ?? 1);
            if ($rate <= 0) {
                throw ValidationException::withMessages([
                    'loyalty_points_to_redeem' => 'Loyalty program is misconfigured.',
                ]);
            }

            $maxDiscountMinor = max(0, (int) $order->subtotal_minor - (int) $order->discount_total_minor);
            if ($maxDiscountMinor <= 0) {
                return;
            }

            $requestedDiscountMinor = (int) floor($pointsToRedeem * $rate * 100);
            $discountMinor = min($requestedDiscountMinor, $maxDiscountMinor);
            if ($discountMinor <= 0) {
                return;
            }

            // If we capped discount, adjust points actually redeemed (avoid overdrawing).
            $pointsActuallyRedeemed = (int) min(
                $pointsToRedeem,
                (int) ceil($discountMinor / ($rate * 100))
            );

            $runningBalance = $available - $pointsActuallyRedeemed;

            $pointTxn = LoyaltyPointTransaction::query()->create([
                'account_id' => (int) $account->id,
                'direction' => 'debit',
                'reason' => 'order_redeem',
                'points' => $pointsActuallyRedeemed,
                'running_balance' => $runningBalance,
                'source_type' => Order::class,
                'source_id' => (int) $order->id,
                'reference' => (string) ($order->order_number ?? null),
                'description' => 'Redeemed against order',
                'occurred_at' => $occurredAt,
                'expires_at' => null,
            ]);

            $redemption = LoyaltyRedemption::query()->create([
                'account_id' => (int) $account->id,
                'order_id' => (int) $order->id,
                'point_transaction_id' => (int) $pointTxn->id,
                'points_redeemed' => $pointsActuallyRedeemed,
                'discount_amount_minor' => $discountMinor,
                'currency' => (string) ($order->currency ?? $program->currency ?? 'INR'),
                'status' => 'applied',
                'applied_at' => $occurredAt,
            ]);

            OrderDiscount::query()->create([
                'order_id' => (int) $order->id,
                'order_item_id' => $order->items->count() === 1 ? (int) $order->items->first()->id : null,
                'source_type' => 'loyalty_redemption',
                'source_ref_id' => (int) $redemption->id,
                'code' => null,
                'label' => 'Loyalty redemption',
                'amount_minor' => $discountMinor,
                'currency' => (string) ($order->currency ?? 'INR'),
            ]);

            $account->forceFill([
                'points_balance' => $runningBalance,
                'points_lifetime_redeemed' => (int) $account->points_lifetime_redeemed + $pointsActuallyRedeemed,
            ])->save();

            // Apply to order totals + (single item) line discount.
            $order->forceFill([
                'discount_total_minor' => (int) $order->discount_total_minor + $discountMinor,
                'grand_total_minor' => max(0, (int) $order->grand_total_minor - $discountMinor),
            ])->save();

            if ($order->items->count() === 1) {
                $item = $order->items->first();
                $item->forceFill([
                    'line_discount_minor' => (int) $item->line_discount_minor + $discountMinor,
                ])->save();
            }
        });
    }

    private function resolveProgram(Order $order): LoyaltyProgram
    {
        // Phase 3: seeded loyalty program is attached to the platform tenant.
        $platformTenantId = Tenant::query()->where('slug', 'platform')->value('id');

        $program = LoyaltyProgram::query()
            ->where('status', 'active')
            ->where('slug', 'amazepays_rewards')
            ->when($platformTenantId, fn ($q) => $q->where('tenant_id', (int) $platformTenantId))
            ->first();

        if ($program) {
            return $program;
        }

        // Fallback: any active program in INR.
        $fallback = LoyaltyProgram::query()->where('status', 'active')->where('currency', $order->currency ?? 'INR')->first();
        if ($fallback) {
            return $fallback;
        }

        throw ValidationException::withMessages([
            'loyalty_points_to_redeem' => 'No active loyalty program is configured.',
        ]);
    }
}

