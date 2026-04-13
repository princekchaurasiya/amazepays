<?php

namespace App\Services\Order;

use App\Exceptions\OrderCreationException;

/**
 * Defines valid order status transitions.
 *
 * Each key is a current status, and its value lists the statuses it may transition to.
 * Any transition not in this map is illegal and will throw.
 */
class OrderStatusMachine
{
    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled', 'failed'],
        'processing' => ['fulfilled', 'failed', 'partially_fulfilled'],
        'fulfilled' => ['refund_requested'],
        'partially_fulfilled' => ['fulfilled', 'refund_requested', 'failed'],
        'failed' => ['pending'],   // retry
        'cancelled' => [],             // terminal
        'refund_requested' => ['refunded', 'refund_rejected'],
        'refunded' => [],             // terminal
        'refund_rejected' => [],          // terminal
    ];

    /**
     * Assert the transition is valid and return the new status.
     *
     * @throws OrderCreationException
     */
    public static function transition(string $current, string $target): string
    {
        $allowed = self::TRANSITIONS[$current] ?? [];

        if (! in_array($target, $allowed, true)) {
            throw new OrderCreationException(
                "Invalid order status transition: {$current} → {$target}. Allowed: ".implode(', ', $allowed)
            );
        }

        return $target;
    }

    public static function isTerminal(string $status): bool
    {
        return empty(self::TRANSITIONS[$status] ?? []);
    }

    public static function allowedTransitions(string $current): array
    {
        return self::TRANSITIONS[$current] ?? [];
    }
}
