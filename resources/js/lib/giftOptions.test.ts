import { describe, expect, it } from 'vitest';
import { normalizeGiftOptionPolicy, shouldClearGiftCustomizeMode } from './giftOptions';

describe('normalizeGiftOptionPolicy', () => {
    it('trims and lowercases known policies', () => {
        expect(normalizeGiftOptionPolicy(' Self_Only ')).toBe('self_only');
        expect(normalizeGiftOptionPolicy('GIFT_ONLY')).toBe('gift_only');
    });

    it('defaults invalid values to both', () => {
        expect(normalizeGiftOptionPolicy('')).toBe('both');
        expect(normalizeGiftOptionPolicy('unknown')).toBe('both');
    });
});

describe('shouldClearGiftCustomizeMode', () => {
    it('clears when switching from self to gift', () => {
        expect(shouldClearGiftCustomizeMode('buy_for_self', 'send_as_gift')).toBe(true);
    });

    it('clears when switching from gift to self', () => {
        expect(shouldClearGiftCustomizeMode('send_as_gift', 'buy_for_self')).toBe(true);
    });

    it('does not clear when gift stays selected (e.g. repeated click)', () => {
        expect(shouldClearGiftCustomizeMode('send_as_gift', 'send_as_gift')).toBe(false);
    });

    it('does not clear when self stays selected', () => {
        expect(shouldClearGiftCustomizeMode('buy_for_self', 'buy_for_self')).toBe(false);
    });
});
