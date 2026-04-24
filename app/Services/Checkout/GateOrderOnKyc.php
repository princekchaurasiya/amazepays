<?php

namespace App\Services\Checkout;

use App\Exceptions\Checkout\KycRequiredException;
use App\Models\KycProfile;
use App\Models\KycThreshold;
use App\Models\Order;
use App\Models\OrderKycRequirement;
use Illuminate\Support\Carbon;

final class GateOrderOnKyc
{
    public function evaluateOrThrow(Order $order, int $userId): void
    {
        $amountMinor = (int) ($order->grand_total_minor ?? 0);
        $currency = (string) ($order->currency ?? 'INR');

        /** @var KycThreshold|null $threshold */
        $threshold = KycThreshold::query()
            ->where('is_active', true)
            ->where('scope', 'order')
            ->whereIn('channel', ['all', (string) ($order->channel ?? 'storefront')])
            ->where('currency', $currency)
            ->where('threshold_amount_minor', '<=', $amountMinor)
            ->where('effective_from', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', Carbon::today());
            })
            ->orderByDesc('threshold_amount_minor')
            ->first();

        if (! $threshold) {
            OrderKycRequirement::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'kyc_threshold_id' => null,
                    'kyc_profile_id' => null,
                    'requirement_status' => 'not_required',
                    'enforcement' => 'soft_warn',
                    'required_document_types' => null,
                    'evaluated_amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'reason' => 'no_applicable_threshold',
                    'evaluated_at' => now(),
                ]
            );

            return;
        }

        /** @var KycProfile|null $profile */
        $profile = KycProfile::query()
            ->where('user_id', $userId)
            ->where(function ($q) use ($order) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $order->tenant_id);
            })
            ->latest('id')
            ->first();

        $requiredTypes = array_values(array_unique(array_map('strval', (array) ($threshold->required_document_types ?? []))));
        $missing = $this->missingDocumentTypes($profile, $requiredTypes);

        $isApproved = $profile && strtolower((string) $profile->status) === 'approved'
            && ($profile->expires_at === null || $profile->expires_at->isFuture());

        if ($missing === [] && $isApproved) {
            OrderKycRequirement::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'kyc_threshold_id' => $threshold->id,
                    'kyc_profile_id' => $profile?->id,
                    'requirement_status' => 'satisfied',
                    'enforcement' => (string) $threshold->enforcement,
                    'required_document_types' => $requiredTypes,
                    'evaluated_amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'reason' => 'kyc_satisfied',
                    'evaluated_at' => now(),
                    'satisfied_at' => now(),
                ]
            );

            return;
        }

        OrderKycRequirement::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'kyc_threshold_id' => $threshold->id,
                'kyc_profile_id' => $profile?->id,
                'requirement_status' => $threshold->enforcement === 'block_until_verified' ? 'blocked' : 'required',
                'enforcement' => (string) $threshold->enforcement,
                'required_document_types' => $requiredTypes,
                'evaluated_amount_minor' => $amountMinor,
                'currency' => $currency,
                'reason' => $missing === [] ? 'profile_not_approved' : 'missing_required_documents',
                'evaluated_at' => now(),
            ]
        );

        if ($threshold->enforcement === 'block_until_verified') {
            throw new KycRequiredException($threshold, $missing);
        }
    }

    /**
     * @param  list<string>  $requiredTypes
     * @return list<string>
     */
    private function missingDocumentTypes(?KycProfile $profile, array $requiredTypes): array
    {
        if ($requiredTypes === []) {
            return [];
        }
        if (! $profile) {
            return $requiredTypes;
        }

        $verifiedTypes = $profile->documents()
            ->where('status', 'verified')
            ->pluck('document_type')
            ->map(fn ($v) => (string) $v)
            ->unique()
            ->values()
            ->all();

        return array_values(array_diff($requiredTypes, $verifiedTypes));
    }
}

