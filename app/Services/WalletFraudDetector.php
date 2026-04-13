<?php

namespace App\Services;

use App\Models\SecurityEventLog;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;

class WalletFraudDetector
{
    public function check(Wallet $wallet, float $amount, string $type, array $context = []): FraudCheckResult
    {
        $score = 0;
        $flags = [];
        $actions = [];

        // Rule 1: Velocity check — too many transactions in short window
        $velocityScore = $this->checkVelocity($wallet, $amount);
        if ($velocityScore > 0) {
            $score += $velocityScore;
            $flags[] = 'high_velocity';
        }

        // Rule 2: Drain detection — trying to empty wallet in one shot
        if ($type === 'debit' && $amount >= $wallet->balance * 0.95) {
            $score += 30;
            $flags[] = 'wallet_drain_attempt';
        }

        // Rule 3: Unusual amount (much higher than user's average)
        $unusualScore = $this->checkUnusualAmount($wallet, $amount);
        if ($unusualScore > 0) {
            $score += $unusualScore;
            $flags[] = 'unusual_amount';
        }

        // Rule 4: VPN-based transaction
        if ($context['is_vpn'] ?? false) {
            $score += 25;
            $flags[] = 'vpn_transaction';
        }

        // Rule 5: New device / new IP
        if ($context['is_new_device'] ?? false) {
            $score += 15;
            $flags[] = 'new_device';
        }

        // Rule 6: Off-hours transaction (outside normal activity pattern)
        if ($this->isOffHours($wallet)) {
            $score += 10;
            $flags[] = 'off_hours';
        }

        // Rule 7: Multiple failed previous transactions
        $failedCount = $this->getRecentFailedTransactions($wallet);
        if ($failedCount >= 3) {
            $score += 20;
            $flags[] = 'repeated_failures';
        }

        // Determine action based on score
        $action = $this->determineAction($score, $type);

        // Log if suspicious
        if ($score >= 30) {
            app(SecurityEventService::class)->log(
                SecurityEventLog::EVENT_WALLET_FRAUD_CHECK,
                $score >= 70 ? SecurityEventLog::SEVERITY_CRITICAL : SecurityEventLog::SEVERITY_HIGH,
                null,
                [
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'amount' => $amount,
                    'type' => $type,
                    'score' => $score,
                    'flags' => $flags,
                    'action' => $action,
                ]
            );
        }

        return new FraudCheckResult($score, $action, $flags);
    }

    private function checkVelocity(Wallet $wallet, float $amount): int
    {
        $window = 3600;  // 1 hour
        $maxAmount = 100000;
        $maxCount = 10;
        $cacheKey = "wallet_velocity:{$wallet->id}";

        $recent = Cache::remember($cacheKey, 60, function () use ($wallet, $window) {
            return WalletTransaction::where('wallet_id', $wallet->id)
                ->where('created_at', '>=', now()->subSeconds($window))
                ->selectRaw('COUNT(*) as cnt, SUM(amount) as total')
                ->first();
        });

        if ($recent->cnt >= $maxCount) {
            return 40;
        }

        if ($recent->total >= $maxAmount) {
            return 30;
        }

        return 0;
    }

    private function checkUnusualAmount(Wallet $wallet, float $amount): int
    {
        $avg = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->where('created_at', '>=', now()->subDays(30))
            ->avg('amount');

        if ($avg && $amount > $avg * 5) {
            return 25;
        }

        return 0;
    }

    private function isOffHours(Wallet $wallet): bool
    {
        $hour = now()->hour;

        return $hour < 6 || $hour > 23;
    }

    private function getRecentFailedTransactions(Wallet $wallet): int
    {
        return WalletTransaction::where('wallet_id', $wallet->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(6))
            ->count();
    }

    private function determineAction(int $score, string $type): string
    {
        if ($score >= 80) {
            return 'block';
        }

        if ($score >= 50 && $type === 'debit') {
            return 'require_otp';
        }

        if ($score >= 30) {
            return 'flag';
        }

        return 'allow';
    }
}

class FraudCheckResult
{
    public function __construct(
        public readonly int $score,
        public readonly string $action,
        public readonly array $flags,
    ) {}

    public function isBlocked(): bool
    {
        return $this->action === 'block';
    }

    public function requiresOtp(): bool
    {
        return $this->action === 'require_otp';
    }

    public function isFlagged(): bool
    {
        return $this->action === 'flag' || $this->score >= 30;
    }
}
