import React from 'react';
import { usePage } from '@inertiajs/react';
import { Gift, UserRound } from 'lucide-react';
import type { GiftOption, GiftOptionPolicy } from '@/lib/giftOptions';

export type { GiftOption, GiftOptionPolicy };

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
        <div className="mb-5">
            <p className="mb-2 text-sm font-medium text-gray-700">{t('for_yourself_or_gift', 'For yourself or as a gift?')}</p>
            <div className="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    disabled={!selfEnabled}
                    onClick={() => onChange('buy_for_self')}
                    aria-pressed={value === 'buy_for_self'}
                    className={`inline-flex items-center justify-center gap-2 rounded-lg border py-2.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-product-primary/40 focus-visible:ring-offset-2 ${
                        value === 'buy_for_self'
                            ? 'border-product-primary bg-blue-50 text-product-primary'
                            : 'border-gray-300 text-gray-600 hover:border-product-primary/40'
                    } ${!selfEnabled ? 'cursor-not-allowed opacity-50' : ''}`}
                >
                    <UserRound className="h-4 w-4 shrink-0" aria-hidden="true" />
                    {t('for_myself', 'For Myself')}
                </button>
                <button
                    type="button"
                    disabled={!giftEnabled}
                    onClick={() => onChange('send_as_gift')}
                    aria-pressed={value === 'send_as_gift'}
                    className={`inline-flex items-center justify-center gap-2 rounded-lg border py-2.5 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-product-primary/40 focus-visible:ring-offset-2 ${
                        value === 'send_as_gift'
                            ? 'border-product-primary bg-blue-50 text-product-primary'
                            : 'border-gray-300 text-gray-600 hover:border-product-primary/40'
                    } ${!giftEnabled ? 'cursor-not-allowed opacity-50' : ''}`}
                >
                    <Gift className="h-4 w-4 shrink-0" aria-hidden="true" />
                    {t('buy_as_gift', 'Buy as Gift')}
                </button>
            </div>
        </div>
    );
}
