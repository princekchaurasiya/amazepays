import React, { useMemo, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';
import { Check, CreditCard, Lock, MapPin, Pencil, ShieldCheck, Smartphone, Wallet } from 'lucide-react';

type Product = Record<string, unknown>;
type Order = {
    id?: number;
    grand_payable_amount?: number;
    amount_payable_after_discount?: number;
    quantity?: number;
    denomination?: number | null;
};
type BillingSnapshot = Record<string, string>;
type MethodKey = 'ccavenue' | 'razorpay' | 'unlimit';

export default function CheckoutIndex({
    product,
    order,
    slug,
    billingSnapshot,
    billingReadyByMethod,
    billingMissingFieldsByMethod,
    billingRequiredFieldsByMethod,
    billingRequiredFieldLabelsByMethod,
    enabledPaymentMethods,
}: {
    product: Product;
    order: Order;
    slug: string;
    billingSnapshot?: BillingSnapshot;
    billingReadyByMethod?: Record<MethodKey, boolean>;
    billingMissingFieldsByMethod?: Record<MethodKey, string[]>;
    billingRequiredFieldsByMethod?: Record<MethodKey, string[]>;
    billingRequiredFieldLabelsByMethod?: Record<MethodKey, Record<string, string>>;
    enabledPaymentMethods?: MethodKey[];
}) {
    const page = usePage<{ i18n?: { checkout?: Record<string, string> } }>();
    const appEnv = (page.props as any)?.app?.env as string | undefined;
    const allowMock = appEnv === 'local' || appEnv === 'testing';
    const text = page.props.i18n?.checkout ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;
    const name = String(product.name ?? 'Gift card');
    const img = product.display_image_url as string | undefined;
    const orderId = order.id;
    const readyBy = billingReadyByMethod ?? { ccavenue: false, razorpay: false, unlimit: false };
    const missingBy = billingMissingFieldsByMethod ?? { ccavenue: [], razorpay: [], unlimit: [] };
    const _requiredBy = billingRequiredFieldsByMethod ?? { ccavenue: [], razorpay: [], unlimit: [] };
    const labelsBy = billingRequiredFieldLabelsByMethod ?? { ccavenue: {}, razorpay: {}, unlimit: {} };
    const payableAmount = Number(order.amount_payable_after_discount ?? order.grand_payable_amount ?? 0);
    const quantity = Math.max(1, Number(order.quantity ?? 1));
    const denomination = order.denomination;
    const snap = billingSnapshot ?? {};
    const contactName = snap.billing_name || '—';
    const phone = snap.billing_tel || '—';
    const addressParts = [snap.billing_address, snap.billing_address_two, snap.billing_city, snap.billing_state, snap.billing_zip, snap.billing_country].filter(
        Boolean,
    );
    const deliveryAddress = addressParts.length > 0 ? addressParts.join(', ') : '—';

    const specLine =
        denomination != null && Number.isFinite(denomination)
            ? `₹${Math.round(denomination).toLocaleString('en-IN')} × ${quantity}`
            : `${t('qty_label', 'Qty')}: ${quantity}`;

    const _methodUi: Array<{
        key: MethodKey;
        label: string;
        description: string;
        action: string;
        allowMock?: boolean;
    }> = [
        {
            key: 'razorpay',
            label: 'Razorpay',
            description: 'Fast card + UPI checkout',
            action: paths.paymentRazorpay,
            allowMock: allowMock,
        },
    ];

    const methodCatalog: Record<MethodKey, (typeof _methodUi)[number]> = {
        ccavenue: { key: 'ccavenue', label: 'CCAvenue', description: 'Card / Netbanking / UPI (gateway)', action: paths.paymentCcavenue },
        razorpay: { key: 'razorpay', label: 'Razorpay', description: 'Fast card + UPI checkout', action: paths.paymentRazorpay, allowMock: allowMock },
        unlimit: { key: 'unlimit', label: 'Unlimit', description: 'Card / UPI / Netbanking (gateway)', action: paths.paymentUnlimit },
    };

    const enabled = (enabledPaymentMethods && enabledPaymentMethods.length > 0 ? enabledPaymentMethods : (['razorpay'] as MethodKey[])).filter(
        (m) => Boolean(methodCatalog[m]),
    );
    const methodsToRender = enabled.map((m) => methodCatalog[m]);
    const [selectedMethod, setSelectedMethod] = useState<MethodKey>((methodsToRender[0]?.key as MethodKey | undefined) ?? 'razorpay');
    const selectedMethodConfig = useMemo(() => methodsToRender.find((m) => m.key === selectedMethod) ?? methodsToRender[0], [methodsToRender, selectedMethod]);
    const selectedMethodReady = selectedMethodConfig ? Boolean(readyBy[selectedMethodConfig.key]) : false;
    const selectedMethodMissing = selectedMethodConfig ? missingBy[selectedMethodConfig.key] ?? [] : [];
    const selectedLabels = selectedMethodConfig ? labelsBy[selectedMethodConfig.key] ?? {} : {};
    const selectedMissingLabels = selectedMethodMissing.map((f) => selectedLabels[f] || f);

    return (
        <CheckoutLayout>
            <Head title={t('title', 'Checkout')} />
            <div className="mx-auto w-full max-w-6xl rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div className="flex flex-col justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                    <h1 className="text-xl font-bold text-product-primary md:text-2xl">{t('title', 'Checkout')}</h1>
                    <Link href={paths.product(slug)} className="text-sm font-medium text-gray-600 transition hover:text-product-primary">
                        {t('back_to_shopping', 'Back to shopping')}
                    </Link>
                </div>

                <div className="border-b border-gray-200 bg-gray-50/70 px-4 py-5 sm:px-6">
                    <div className="mx-auto flex w-full max-w-3xl items-center justify-between gap-2">
                        <div className="flex min-w-0 flex-col items-center gap-1 text-center">
                            <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">
                                <Check className="h-4 w-4" strokeWidth={3} aria-hidden="true" />
                            </span>
                            <p className="text-xs font-semibold text-emerald-700">{t('step_shipping', 'Shipping')}</p>
                        </div>
                        <div className="h-px flex-1 bg-gray-200" role="presentation" />
                        <div className="flex min-w-0 flex-col items-center gap-1 text-center">
                            <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-gray-300 bg-white text-xs font-bold text-gray-700">2</span>
                            <p className="text-xs font-semibold text-product-primary">{t('step_payment', 'Payment')}</p>
                        </div>
                        <div className="h-px flex-1 bg-gray-200" role="presentation" />
                        <div className="flex min-w-0 flex-col items-center gap-1 text-center">
                            <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-gray-300 bg-white text-xs font-bold text-gray-400">3</span>
                            <p className="text-xs font-semibold text-gray-400">{t('step_review', 'Review')}</p>
                        </div>
                    </div>
                </div>

                <div className="grid items-start gap-6 p-4 sm:p-6 lg:grid-cols-12 lg:gap-8">
                    {/* Left column */}
                    <div className="space-y-6 lg:col-span-7">
                        <section className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-start justify-between gap-3">
                                <h2 className="text-xl font-bold text-gray-900">{t('shipping_details', 'Shipping Address')}</h2>
                                <Link
                                    href={paths.profile}
                                    className="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-semibold text-product-primary transition hover:bg-product-primary/5"
                                >
                                    <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                    {t('edit', 'Edit')}
                                </Link>
                            </div>
                            <div className="flex items-start justify-between gap-4">
                                <div className="space-y-1">
                                    <p className="text-sm font-semibold text-gray-900">{contactName}</p>
                                    <p className="text-sm text-gray-600">{phone}</p>
                                    <p className="text-sm text-gray-600">{deliveryAddress}</p>
                                </div>
                                <div className="hidden h-16 w-16 items-center justify-center rounded-lg bg-product-primary/10 text-product-primary sm:flex">
                                    <MapPin className="h-7 w-7" aria-hidden="true" />
                                </div>
                            </div>
                        </section>

                        <section className="rounded-xl border-2 border-gray-800 bg-white p-5 shadow-sm">
                            <h2 className="text-3xl font-bold text-gray-900">{t('payment', 'Payment Method')}</h2>

                            <div className="mt-6">
                                {orderId ? (
                                    <div className="space-y-4">
                                        {methodsToRender.map((m) => {
                                            const ready = Boolean(readyBy[m.key]);
                                            const active = selectedMethod === m.key;

                                            return (
                                                <button
                                                    key={m.key}
                                                    type="button"
                                                    onClick={() => ready && setSelectedMethod(m.key)}
                                                    disabled={!ready}
                                                    className={`w-full rounded-lg border p-4 text-left transition ${
                                                        active
                                                            ? 'border-product-primary bg-product-primary/5'
                                                            : 'border-gray-200 bg-white'
                                                    } ${ready ? 'hover:border-product-primary/60' : 'cursor-not-allowed opacity-60'}`}
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="flex items-start gap-3">
                                                            <span
                                                                className={`mt-0.5 inline-flex h-4 w-4 rounded-full border ${
                                                                    active ? 'border-product-primary' : 'border-gray-400'
                                                                }`}
                                                            >
                                                                <span
                                                                    className={`m-auto h-2 w-2 rounded-full ${
                                                                        active ? 'bg-product-primary' : 'bg-transparent'
                                                                    }`}
                                                                />
                                                            </span>
                                                            <div>
                                                                <p className="text-base font-semibold text-gray-900">{m.label}</p>
                                                                <p className="mt-0.5 text-sm text-gray-600">{m.description}</p>
                                                            </div>
                                                        </div>
                                                        <span
                                                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                                ready ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'
                                                            }`}
                                                        >
                                                            {ready ? 'Ready' : 'Profile needed'}
                                                        </span>
                                                    </div>
                                                </button>
                                            );
                                        })}

                                        {!selectedMethodReady && selectedMissingLabels.length > 0 ? (
                                            <p className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                                Missing: {selectedMissingLabels.join(', ')}
                                            </p>
                                        ) : null}

                                        {selectedMethodConfig ? (
                                            <div className="rounded-lg border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-900">
                                                {t(
                                                    'gateway_footer',
                                                    'Payments are securely processed by the selected gateway.',
                                                )}
                                            </div>
                                        ) : null}

                                        {selectedMethodConfig?.key === 'razorpay' && selectedMethodConfig.allowMock ? (
                                            <form action={paths.paymentMockRazorpay} method="post">
                                                <input
                                                    type="hidden"
                                                    name="_token"
                                                    value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content}
                                                />
                                                <button
                                                    type="submit"
                                                    className="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                >
                                                    {t('mock_pay', 'Use Mock Razorpay (local only)')}
                                                </button>
                                            </form>
                                        ) : null}
                                    </div>
                                ) : (
                                    <p className="text-sm text-amber-800">{t('checkout_missing_order', 'Checkout session not found. Please start again.')}</p>
                                )}
                            </div>
                        </section>

                    </div>

                    {/* Order summary — right */}
                    <aside className="lg:col-span-5">
                        <div className="sticky top-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:p-6">
                            <h2 className="text-2xl font-bold text-gray-900">{t('order_summary', 'Order Summary')}</h2>

                            <div className="mt-4 flex gap-3 border-b border-gray-200 pb-4">
                                {img ? (
                                    <img src={img} alt="" className="h-14 w-14 shrink-0 rounded-lg border border-gray-100 object-cover shadow-sm" />
                                ) : (
                                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-bold text-gray-400">
                                        —
                                    </div>
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className="line-clamp-2 text-sm font-semibold text-gray-900">{name}</p>
                                    <p className="mt-0.5 text-xs text-gray-600">{specLine}</p>
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1.5">
                                <input
                                    type="text"
                                    readOnly
                                    disabled
                                    placeholder={t('promo_code', 'Promo code')}
                                    className="w-full min-w-0 border-0 bg-transparent text-sm text-gray-500 placeholder:text-gray-400"
                                />
                                <button type="button" className="rounded bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-500" disabled>
                                    {t('apply', 'Apply')}
                                </button>
                            </div>

                            <div className="mt-5 space-y-2 border-b border-gray-200 pb-4 text-sm">
                                <div className="flex justify-between text-gray-700">
                                    <span>{t('subtotal', 'Subtotal')}</span>
                                    <span>₹{payableAmount.toLocaleString('en-IN')}</span>
                                </div>
                                <div className="flex justify-between text-gray-700">
                                    <span>{t('shipping', 'Shipping')}</span>
                                    <span className="font-semibold text-emerald-600">{t('free', 'FREE')}</span>
                                </div>
                            </div>

                            <div className="mt-4 flex justify-between">
                                <p className="text-3xl font-bold text-gray-900">{t('total', 'Total')}</p>
                                <p className="text-3xl font-extrabold text-product-primary">₹{payableAmount.toLocaleString('en-IN')}</p>
                            </div>

                            {orderId && selectedMethodConfig ? (
                                <form className="mt-5" action={selectedMethodConfig.action} method="post">
                                                            <input
                                                                type="hidden"
                                                                name="_token"
                                                                value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content}
                                                            />
                                                            <button
                                                                type="submit"
                                        disabled={!selectedMethodReady}
                                        className={`flex w-full items-center justify-center gap-2 rounded-lg px-6 py-3 text-sm font-semibold shadow-sm transition ${
                                            selectedMethodReady
                                                ? 'bg-product-primary text-white hover:bg-product-primary/90'
                                                : 'cursor-not-allowed bg-gray-100 text-gray-400'
                                        }`}
                                                            >
                                        {selectedMethodReady
                                            ? `${t('proceed_to_pay', 'Proceed to Pay')} (${selectedMethodConfig.label})`
                                            : `Update profile to use ${selectedMethodConfig.label}`}
                                                            </button>
                                </form>
                            ) : null}

                            {!selectedMethodReady ? (
                                <Link href={paths.profile} className="mt-3 inline-flex text-sm font-semibold text-amber-900 underline">
                                    {t('update_profile', 'Update profile')}
                                </Link>
                            ) : null}

                            <div className="mt-5 flex items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 p-3 text-xs text-gray-700 shadow-sm">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                                    <ShieldCheck className="h-4 w-4 text-emerald-700" aria-hidden="true" />
                                </div>
                                <p>
                                    <span className="font-semibold text-gray-900">{t('finsecure_title', 'Secure checkout')}</span>
                                    {` — ${t('finsecure_body', 'Your transaction is protected with industry-standard encryption.')}`}
                                </p>
                            </div>

                            <p className="mt-4 flex items-center justify-center gap-1.5 text-center text-[11px] text-gray-500">
                                <Lock className="h-3.5 w-3.5 text-gray-400" aria-hidden="true" />
                                {t('secure_badges', 'PCI DSS compliant and SSL secured')}
                            </p>
                            <div className="mt-3 flex justify-center gap-4 opacity-40">
                                <CreditCard className="h-5 w-5" aria-hidden="true" />
                                <Smartphone className="h-5 w-5" aria-hidden="true" />
                                <Wallet className="h-5 w-5" aria-hidden="true" />
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </CheckoutLayout>
    );
}
