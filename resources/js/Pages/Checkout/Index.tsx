import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';

type Product = Record<string, unknown>;
type Order = {
    id?: number;
    grand_payable_amount?: number;
    amount_payable_after_discount?: number;
};
type BillingSnapshot = Record<string, string>;
type BillingFieldLabels = Record<string, string>;

export default function CheckoutIndex({
    product,
    order,
    slug,
    billingSnapshot,
    billingReady,
    billingMissingFields,
    billingRequiredFields,
    billingRequiredFieldLabels,
    billingRequirementContext,
}: {
    product: Product;
    order: Order;
    slug: string;
    billingSnapshot?: BillingSnapshot;
    billingReady?: boolean;
    billingMissingFields?: string[];
    billingRequiredFields?: string[];
    billingRequiredFieldLabels?: BillingFieldLabels;
    billingRequirementContext?: { payment_method?: string; provider?: string };
}) {
    const page = usePage<{ i18n?: { checkout?: Record<string, string> } }>();
    const text = page.props.i18n?.checkout ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;
    const name = String(product.name ?? 'Gift card');
    const img = product.display_image_url as string | undefined;
    const orderId = order.id;
    const ready = Boolean(billingReady);
    const missing = billingMissingFields ?? [];
    const required = billingRequiredFields ?? [];
    const fieldLabels = billingRequiredFieldLabels ?? {};
    const provider = (billingRequirementContext?.provider ?? 'provider').toUpperCase();
    const paymentMethod = (billingRequirementContext?.payment_method ?? 'payment').toUpperCase();
    const payableAmount = Number(order.amount_payable_after_discount ?? order.grand_payable_amount ?? 0);
    const hasCheckoutOrder = Boolean(orderId);
    const missingLabels = missing.map((field) => fieldLabels[field] || field);
    const requiredLabels = required.map((field) => fieldLabels[field] || field);

    return (
        <CheckoutLayout>
            <Head title={t('title', 'Checkout')} />
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-3xl font-bold text-gray-900">{t('title', 'Checkout')}</h1>
                <Link href={paths.product(slug)} className="text-sm font-medium text-gray-600 hover:text-gray-900">
                    {t('continue_shopping', 'Continue shopping')}
                </Link>
            </div>
            <div className="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div className="space-y-4">
                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h2 className="text-lg font-semibold text-gray-900">{t('payment_method', 'Payment method')}</h2>
                        <div className="mt-3 rounded-xl border border-gray-200 p-4">
                            <p className="text-sm font-semibold text-gray-900">{t('upi', 'UPI')}</p>
                            {ready ? (
                                <div className="mt-1">
                                    <p className="text-sm text-gray-600">{t('using_saved_billing', 'Using saved billing details from your account.')}</p>
                                    <p className="mt-1 text-xs text-gray-500">
                                        {(billingSnapshot?.billing_name || '—')} · {(billingSnapshot?.billing_email || '—')}
                                    </p>
                                </div>
                            ) : (
                                <div className="mt-2 rounded-lg border border-amber-300 bg-amber-50 p-3">
                                    <p className="text-sm font-semibold text-amber-800">{t('billing_unavailable', 'Billing details required before payment')}</p>
                                    <p className="mt-1 text-xs text-amber-800">
                                        {t('billing_unavailable_help', 'Please update your profile billing details to continue.')}
                                    </p>
                                    <p className="mt-1 text-xs text-amber-900">
                                        {t('billing_required_for_context', 'Required for :payment via :provider')
                                            .replace(':payment', paymentMethod)
                                            .replace(':provider', provider)}
                                    </p>
                                    {requiredLabels.length > 0 ? <p className="mt-1 text-xs text-amber-900">{requiredLabels.join(', ')}</p> : null}
                                    {missingLabels.length > 0 ? <p className="mt-1 text-xs font-semibold text-red-700">Missing: {missingLabels.join(', ')}</p> : null}
                                    <Link href={paths.profile} className="mt-2 inline-flex text-xs font-semibold text-amber-900 underline">
                                        {t('update_profile', 'Update profile')}
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p className="text-sm font-semibold text-gray-900">{t('promo_code', 'Promo Code')}</p>
                        <div className="mt-3 flex items-center gap-2">
                            <input
                                type="text"
                                placeholder={t('promo_placeholder', 'Enter promo code')}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                disabled
                            />
                            <button
                                type="button"
                                className="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                                disabled
                            >
                                {t('confirm', 'Confirm')}
                            </button>
                        </div>
                        <p className="mt-3 text-xs text-gray-500">{t('offers_hint', 'Discounts are applied automatically at checkout when eligible.')}</p>
                    </div>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 className="text-2xl font-bold text-gray-900">{t('order_summary', 'Order summary')}</h2>
                    <div className="mt-4 flex items-start gap-4">
                        {img ? <img src={img} alt="" className="h-16 w-16 rounded-lg object-cover" /> : null}
                        <div className="min-w-0">
                            <p className="font-semibold text-gray-900">{name}</p>
                            {hasCheckoutOrder ? (
                                <p className="mt-2 text-2xl font-bold text-gray-900">
                                    {t('payable', 'Payable')}: ₹{payableAmount.toLocaleString('en-IN')}
                                </p>
                            ) : (
                                <p className="mt-2 text-sm text-gray-600">{t('complete_selection_first', 'Complete product selection first.')}</p>
                            )}
                        </div>
                    </div>
                    {orderId && ready ? (
                        <form action="/payment/upi" method="post" className="mt-6">
                            <input type="hidden" name="_token" value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content} />
                            <input type="hidden" name="order_id" value={orderId} />
                            <button type="submit" className="w-full rounded-full bg-gray-900 py-3 text-sm font-semibold text-white hover:bg-gray-800">
                                {t('pay_with_upi', 'Pay Now')}
                            </button>
                        </form>
                    ) : orderId ? (
                        <p className="mt-4 text-sm text-amber-700">{t('billing_unavailable_help', 'Please update your profile billing details to continue.')}</p>
                    ) : (
                        <p className="mt-4 text-sm text-amber-700">{t('complete_selection_first', 'Complete product selection first.')}</p>
                    )}
                    <Link href={paths.product(slug)} className="mt-4 inline-block text-sm text-brand-600 hover:underline">
                        {t('edit_cart', 'Edit cart')}
                    </Link>
                </div>
            </div>
        </CheckoutLayout>
    );
}
