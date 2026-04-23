import React from 'react';
import { usePage } from '@inertiajs/react';
import { Gift, UserRound } from 'lucide-react';

export type GiftOption = 'buy_for_self' | 'send_as_gift';
export type GiftOptionPolicy = 'both' | 'self_only' | 'gift_only';

type Props = {
    policy: GiftOptionPolicy;
    value: GiftOption;
    onChange: (value: GiftOption) => void;
};

function optionState(policy: GiftOptionPolicy) {
    return {
        selfEnabled: policy !== 'gift_only',
        giftEnabled: policy !== 'self_only',
    };
}

export default function GiftOptionSelector({ policy, value, onChange }: Props) {
    const { selfEnabled, giftEnabled } = optionState(policy);
    const page = usePage<{ i18n?: { storefront?: { product?: Record<string, string> } } }>();
    const text = page.props.i18n?.storefront?.product ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;

    return (
        <div className="mt-5">
            <p className="text-sm font-semibold text-gray-900">{t('for_yourself_or_gift', 'For yourself or as a gift?')}</p>
            <div className="mt-2 grid grid-cols-2 gap-3">
                <button
                    type="button"
                    disabled={!selfEnabled}
                    onClick={() => onChange('buy_for_self')}
                    className={`rounded-xl border p-3 text-left transition ${
                        value === 'buy_for_self'
                            ? 'border-gray-900 ring-1 ring-gray-900'
                            : 'border-gray-300'
                    } ${!selfEnabled ? 'cursor-not-allowed opacity-50' : 'hover:border-gray-500'}`}
                >
                    <div className="flex items-center gap-2">
                        <UserRound className="h-4 w-4 text-gray-700" />
                        <p className="text-sm font-semibold">{t('for_myself', 'For Myself')}</p>
                    </div>
                    <p className="mt-1 text-xs text-gray-600">
                        {selfEnabled ? t('for_myself_hint', 'The gift card is instantly added to your account.') : t('option_not_available', 'Not available for this product')}
                    </p>
                </button>

                <button
                    type="button"
                    disabled={!giftEnabled}
                    onClick={() => onChange('send_as_gift')}
                    className={`rounded-xl border p-3 text-left transition ${
                        value === 'send_as_gift'
                            ? 'border-gray-900 ring-1 ring-gray-900'
                            : 'border-gray-300'
                    } ${!giftEnabled ? 'cursor-not-allowed opacity-50' : 'hover:border-gray-500'}`}
                >
                    <div className="flex items-center gap-2">
                        <Gift className="h-4 w-4 text-gray-700" />
                        <p className="text-sm font-semibold">{t('buy_as_gift', 'Buy as Gift')}</p>
                    </div>
                    <p className="mt-1 text-xs text-gray-600">
                        {giftEnabled ? t('buy_as_gift_hint', 'Send this as a gift to someone special.') : t('option_not_available', 'Not available for this product')}
                    </p>
                </button>
            </div>
        </div>
    );
}
