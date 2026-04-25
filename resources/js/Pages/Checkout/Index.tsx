import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';
import { Check, CreditCard, Lock, Pencil, ShieldCheck, Smartphone, Wallet } from 'lucide-react';

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
}: {
    product: Product;
    order: Order;
    slug: string;
    billingSnapshot?: BillingSnapshot;
    billingReadyByMethod?: Record<MethodKey, boolean>;
    billingMissingFieldsByMethod?: Record<MethodKey, string[]>;
    billingRequiredFieldsByMethod?: Record<MethodKey, string[]>;
    billingRequiredFieldLabelsByMethod?: Record<MethodKey, Record<string, string>>;
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
    const requiredBy = billingRequiredFieldsByMethod ?? { ccavenue: [], razorpay: [], unlimit: [] };
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

    const methodUi: Array<{
        key: MethodKey;
        label: string;
        description: string;
        action: string;
        allowMock?: boolean;
    }> = [
        {
            key: 'ccavenue',
            label: 'CCAvenue',
            description: 'Card / Netbanking / UPI (gateway)',
            action: paths.paymentCcavenue,
        },
        {
            key: 'razorpay',
            label: 'Razorpay',
            description: 'Fast card + UPI checkout',
            action: paths.paymentRazorpay,
            allowMock: allowMock,
        },
        {
            key: 'unlimit',
            label: 'Unlimit',
            description: 'Card / UPI / Netbanking (gateway)',
            action: paths.paymentUnlimit,
        },
    ];

    return (
        <CheckoutLayout>
            <Head title={t('title', 'Checkout')} />
            <div className="mx-auto w-full max-w-6xl">
                <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <h1 className="text-2xl font-bold text-product-primary md:text-3xl">{t('title', 'Checkout')}</h1>
                        <p className="mt-1 text-sm text-gray-500">{t('subtitle', 'Complete your order securely')}</p>
                    </div>
                    <Link
                        href={paths.product(slug)}
                        className="shrink-0 self-start rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-300"
                    >
                        {t('back_to_shopping', 'Back to shopping')}
                    </Link>
                </div>

                {/* Stepped progress */}
                <div className="mb-8 flex w-full min-w-0 items-center justify-between gap-1 sm:justify-start sm:gap-3">
                    <div className="flex min-w-0 items-center gap-2">
                        <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-product-primary text-white ring-2 ring-product-primary/20">
                            <Check className="h-4 w-4" strokeWidth={3} aria-hidden="true" />
                        </span>
                        <div className="min-w-0">
                            <p className="text-xs font-semibold text-gray-500">{t('step_order', 'Order')}</p>
                            <p className="truncate text-sm font-medium text-gray-800">{t('step_order_done', 'Details')}</p>
                        </div>
                    </div>
                    <div className="h-px min-w-4 flex-1 bg-gray-200 sm:max-w-12" role="presentation" />
                    <div className="flex min-w-0 items-center gap-2">
                        <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-product-accent/15 ring-2 ring-product-accent/30">
                            <Wallet className="h-4 w-4 text-product-accent" aria-hidden="true" />
                        </span>
                        <div className="min-w-0">
                            <p className="text-xs font-semibold text-product-accent">{t('step_payment', 'Payment')}</p>
                            <p className="truncate text-sm font-bold text-gray-900">{t('step_payment_active', 'Choose a gateway')}</p>
                        </div>
                    </div>
                    <div className="h-px min-w-4 flex-1 bg-gray-200 sm:max-w-12" role="presentation" />
                    <div className="flex min-w-0 items-center gap-2 opacity-70">
                        <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-gray-300 bg-white text-gray-400">
                            <span className="text-xs font-bold">3</span>
                        </span>
                        <div className="min-w-0">
                            <p className="text-xs font-semibold text-gray-400">{t('step_confirmation', 'Confirmation')}</p>
                            <p className="truncate text-sm text-gray-500">{t('step_pending', 'Pending')}</p>
                        </div>
                    </div>
                </div>

                <div className="grid items-start gap-6 lg:grid-cols-12 lg:gap-8">
                    {/* Left column */}
                    <div className="space-y-6 lg:col-span-7">
                        <section className="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm md:p-6">
                            <div className="mb-4 flex items-start justify-between gap-3">
                                <h2 className="text-lg font-bold text-product-primary">{t('shipping_details', 'Shipping details')}</h2>
                                <Link
                                    href={paths.profile}
                                    className="inline-flex items-center gap-1.5 rounded-full border border-product-accent/30 bg-white px-3 py-1.5 text-sm font-medium text-product-accent transition hover:bg-product-accent/5"
                                >
                                    <Pencil className="h-3.5 w-3.5" aria-hidden="true" />
                                    {t('edit', 'Edit')}
                                </Link>
                            </div>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-xs font-medium text-gray-500">{t('contact_name', 'Contact name')}</dt>
                                    <dd className="mt-0.5 text-sm font-semibold text-gray-900">{contactName}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs font-medium text-gray-500">{t('phone_number', 'Phone number')}</dt>
                                    <dd className="mt-0.5 text-sm font-semibold text-gray-900">{phone}</dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-xs font-medium text-gray-500">{t('delivery_address', 'Delivery address')}</dt>
                                    <dd className="mt-0.5 text-sm font-semibold text-gray-900">{deliveryAddress}</dd>
                                </div>
                            </dl>
                        </section>

                        <section className="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm md:p-6">
                            <h2 className="text-lg font-bold text-product-primary">{t('payment', 'Payment')}</h2>
                            <p className="mt-1 text-sm text-gray-600">{t('gateway_pick', 'Choose a payment gateway to continue.')}</p>

                            <div className="mt-6">
                                {orderId ? (
                                    <div className="space-y-4">
                                        {methodUi.map((m) => {
                                            const ready = Boolean(readyBy[m.key]);
                                            const missing = missingBy[m.key] ?? [];
                                            const labels = labelsBy[m.key] ?? {};
                                            const missingLabels = missing.map((f) => labels[f] || f);

                                            return (
                                                <div key={m.key} className="rounded-2xl border border-gray-200 p-4">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div>
                                                            <p className="text-sm font-semibold text-gray-900">{m.label}</p>
                                                            <p className="mt-0.5 text-xs text-gray-600">{m.description}</p>
                                                        </div>
                                                        <span
                                                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                                ready ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'
                                                            }`}
                                                        >
                                                            {ready ? 'Ready' : 'Profile needed'}
                                                        </span>
                                                    </div>

                                                    {!ready && missingLabels.length > 0 ? (
                                                        <p className="mt-2 text-xs text-amber-900">
                                                            Missing: {missingLabels.join(', ')}
                                                        </p>
                                                    ) : null}

                                                    <div className="mt-3 space-y-2">
                                                        <form action={m.action} method="post">
                                                            <input
                                                                type="hidden"
                                                                name="_token"
                                                                value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content}
                                                            />
                                                            <button
                                                                type="submit"
                                                                disabled={!ready}
                                                                className={`flex w-full items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition ${
                                                                    ready
                                                                        ? 'bg-product-primary text-white hover:bg-product-primary/90'
                                                                        : 'cursor-not-allowed bg-gray-100 text-gray-400'
                                                                }`}
                                                            >
                                                                {ready ? `Pay with ${m.label}` : `Update profile to use ${m.label}`}
                                                            </button>
                                                        </form>

                                                        {m.key === 'razorpay' && m.allowMock ? (
                                                            <form action={paths.paymentMockRazorpay} method="post">
                                                                <input
                                                                    type="hidden"
                                                                    name="_token"
                                                                    value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content}
                                                                />
                                                                <button
                                                                    type="submit"
                                                                    className="flex w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                                >
                                                                    {t('mock_pay', 'Use Mock Razorpay (local only)')}
                                                                </button>
                                                            </form>
                                                        ) : null}
                                                    </div>
                                                </div>
                                            );
                                        })}

                                        <Link href={paths.profile} className="inline-flex text-sm font-semibold text-amber-900 underline">
                                            {t('update_profile', 'Update profile')}
                                        </Link>
                                    </div>
                                ) : (
                                    <p className="text-sm text-amber-800">{t('checkout_missing_order', 'Checkout session not found. Please start again.')}</p>
                                )}
                            </div>

                            <p className="mt-4 flex items-center justify-center gap-1.5 text-center text-xs text-gray-500">
                                <Lock className="h-3.5 w-3.5 text-gray-400" aria-hidden="true" />
                                {t('gateway_footer', 'Payments are securely processed by the selected gateway.')}
                            </p>
                            <div className="mt-4 flex justify-center gap-4 opacity-40">
                                <CreditCard className="h-6 w-6" aria-hidden="true" />
                                <Smartphone className="h-6 w-6" aria-hidden="true" />
                                <Wallet className="h-6 w-6" aria-hidden="true" />
                            </div>
                        </section>

                    </div>

                    {/* Order summary — right */}
                    <aside className="lg:col-span-5">
                        <div className="sticky top-6 rounded-2xl border border-blue-100 bg-blue-50/80 p-5 shadow-sm md:p-6">
                            <h2 className="text-lg font-bold text-product-primary">{t('order_summary', 'Order summary')}</h2>

                            <div className="mt-4 flex gap-4 border-b border-blue-200/60 pb-4">
                                {img ? (
                                    <img src={img} alt="" className="h-16 w-16 shrink-0 rounded-xl border border-white object-cover shadow-sm" />
                                ) : (
                                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border border-white bg-white text-xs font-bold text-gray-400">
                                        —
                                    </div>
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className="line-clamp-2 text-sm font-semibold text-gray-900">{name}</p>
                                    <p className="mt-0.5 text-xs text-gray-600">{specLine}</p>
                                </div>
                                <p className="shrink-0 text-sm font-bold text-product-accent">₹{payableAmount.toLocaleString('en-IN')}</p>
                            </div>

                            <div className="mt-4 space-y-2 text-sm">
                                <div className="flex justify-between text-gray-700">
                                    <span>{t('subtotal', 'Subtotal')}</span>
                                    <span>₹{payableAmount.toLocaleString('en-IN')}</span>
                                </div>
                                <div className="flex justify-between text-gray-700">
                                    <span>{t('shipping', 'Shipping')}</span>
                                    <span className="font-medium text-emerald-600">{t('free', 'FREE')}</span>
                                </div>
                                <div className="flex justify-between text-xs text-gray-500">
                                    <span>{t('tax_note', 'Taxes')}</span>
                                    <span>{t('tax_included', 'Included in total')}</span>
                                </div>
                            </div>

                            <div className="mt-4 flex items-center gap-2 rounded-xl border border-blue-200/60 bg-white px-3 py-2">
                                <input
                                    type="text"
                                    readOnly
                                    disabled
                                    placeholder={t('promo_code', 'Promo code')}
                                    className="w-full min-w-0 border-0 bg-transparent text-sm text-gray-500 placeholder:text-gray-400"
                                />
                                <button type="button" className="shrink-0 text-sm font-semibold text-product-primary/50" disabled>
                                    {t('apply', 'Apply')}
                                </button>
                            </div>

                            <div className="mt-5 border-t border-blue-200/60 pt-4">
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-500">{t('total_amount_label', 'Total amount')}</p>
                                <p className="mt-0.5 text-2xl font-bold text-product-primary">₹{payableAmount.toLocaleString('en-IN')}</p>
                                <p className="text-xs text-gray-500">{t('all_taxes', 'All taxes included.')}</p>
                            </div>

                            <div className="mt-5 flex items-center gap-2 rounded-xl border border-white bg-white/90 p-3 text-xs text-gray-700 shadow-sm">
                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                                    <ShieldCheck className="h-4 w-4 text-emerald-700" aria-hidden="true" />
                                </div>
                                <p>
                                    <span className="font-semibold text-gray-900">{t('finsecure_title', 'Secure checkout')}</span>
                                    {` — ${t('finsecure_body', 'Your transaction is protected with industry-standard encryption.')}`}
                                </p>
                            </div>

                            <Link href={paths.product(slug)} className="mt-4 block text-center text-sm font-medium text-gray-600 hover:text-gray-900">
                                {t('edit_cart', 'Edit order')}
                            </Link>
                        </div>
                    </aside>
                </div>
            </div>
        </CheckoutLayout>
    );
}
