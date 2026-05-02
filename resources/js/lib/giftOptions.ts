export type GiftOption = 'buy_for_self' | 'send_as_gift';
export type GiftOptionPolicy = 'both' | 'self_only' | 'gift_only';

export function normalizeGiftOptionPolicy(v: unknown): GiftOptionPolicy {
    const policy = String(v ?? '')
        .trim()
        .toLowerCase();
    if (policy === 'self_only' || policy === 'gift_only') {
        return policy;
    }

    return 'both';
}

/**
 * When toggling self vs gift on the product page, parent "customize" preview mode
 * should reset when leaving gift, and when first entering gift from self so the
 * add-to-cart / customize actions are shown (avoids a stuck customize state).
 */
export function shouldClearGiftCustomizeMode(previous: GiftOption, next: GiftOption): boolean {
    if (previous === next) {
        return false;
    }
    if (next === 'buy_for_self') {
        return true;
    }

    return next === 'send_as_gift' && previous === 'buy_for_self';
}
