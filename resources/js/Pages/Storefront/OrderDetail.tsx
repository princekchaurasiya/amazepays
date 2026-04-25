import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type CardRow = {
    provider?: string | null;
    card_last4?: string | null;
    cardNumber?: string | null;
    cardPin?: string | null;
    amount?: number | string | null;
    validity?: string | null;
    gift_card_id?: number | string | null;
};

function safeString(v: unknown): string {
    if (v === null || v === undefined) return '';
    return String(v);
}

type I18n = {
    storefront?: {
        order_detail?: Record<string, string>;
    };
};

function labelProvider(p: string, t: (k: string, f: string) => string): string {
    switch (p) {
        case 'woohoo':
            return t('provider_woohoo', 'Woohoo');
        case 'vouchagram_pull':
            return t('provider_vouchagram_pull', 'Vouchagram (Pull)');
        case 'vouchagram_send':
            return t('provider_vouchagram_send', 'Vouchagram (Send)');
        case 'vd':
            return t('provider_value_design', 'Value Design');
        case 'kgen':
            return t('provider_kgen', 'KGen');
        case 'lysto':
            return t('provider_lysto', 'Lysto');
        case 'ezpin':
            return t('provider_ezpin', 'EZ Pin');
        case 'gyftrr':
            return t('provider_gyftrr', 'Gyftrr');
        case 'internal':
            return t('provider_internal', 'Internal');
        default:
            return p;
    }
}

export default function OrderDetail({
    cardArray = [],
    productImage,
    order,
}: {
    cardArray: CardRow[];
    productImage?: string | null;
    order: Record<string, unknown>;
}) {
    const page = usePage<{ i18n?: I18n }>();
    const text = page.props.i18n?.storefront?.order_detail ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;

    return (
        <StorefrontLayout>
            <Head title={t('card_details_title', 'Card details')} />
            <div className="mx-auto max-w-3xl px-4 py-10">
                <Link href={paths.myOrders} className="text-sm text-brand-600 hover:underline">
                    ← {t('back_to_orders', 'Back to orders')}
                </Link>
                <h1 className="mt-4 text-2xl font-bold text-gray-900">{t('gift_card_details', 'Gift card details')}</h1>
                {productImage && <img src={productImage} alt="" className="mt-4 h-32 rounded-lg object-cover" />}
                <p className="mt-2 text-sm text-gray-600">
                    {t('order_ref', 'Order ref')}: {String(order.refno ?? order.woohoo_order_id ?? '')}
                </p>
                <div className="mt-6 space-y-4">
                    {cardArray.map((c, i) => (
                        <div key={i} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="text-sm font-semibold text-gray-900">
                                    {c.provider ? labelProvider(c.provider, t) : t('gift_card', 'Gift card')}
                                    {c.gift_card_id ? (
                                        <span className="ml-2 text-xs font-normal text-gray-500">#{safeString(c.gift_card_id)}</span>
                                    ) : null}
                                </div>
                                {c.card_last4 ? (
                                    <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        {t('last4', 'Last4')}: {safeString(c.card_last4)}
                                    </span>
                                ) : null}
                            </div>

                            <div className="mt-3 grid gap-2 text-sm md:grid-cols-2">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-gray-500">{t('amount', 'Amount')}</span>
                                    <span className="font-mono text-gray-900">{c.amount !== null && c.amount !== undefined ? safeString(c.amount) : t('dash', '—')}</span>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-gray-500">{t('validity', 'Validity')}</span>
                                    <span className="font-mono text-gray-900">{c.validity ? safeString(c.validity) : t('dash', '—')}</span>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-gray-500">{t('code', 'Code')}</span>
                                    <span className="font-mono text-gray-900">{c.cardNumber ? safeString(c.cardNumber) : t('dash', '—')}</span>
                                </div>
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-gray-500">{t('pin', 'PIN')}</span>
                                    <span className="font-mono text-gray-900">{c.cardPin ? safeString(c.cardPin) : t('dash', '—')}</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </StorefrontLayout>
    );
}
