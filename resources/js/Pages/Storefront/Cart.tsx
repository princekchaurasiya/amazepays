import React, { useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type CartItem = {
    id: number;
    product_id?: number;
    slug: string;
    sku?: string;
    product_name?: string;
    gift_send_option: 'send_as_gift' | 'buy_for_self';
    denomination: number;
    quantity: number;
    line_total?: number;
    receiver_name?: string;
    receiver_email?: string;
    receiver_mobile?: string;
    receiver_msg?: string;
    gift_theme_id?: number | null;
    gift_message_title?: string;
    sender_first_name?: string;
    gift_delivery_option?: 'send_now' | 'send_later' | string;
    gift_delivery_at?: string | null;
};

function toNum(v: unknown): number {
    const n = Number(v);
    return Number.isFinite(n) ? n : 0;
}

function formatMoney(n: number): string {
    return `\u20B9${Math.round(n).toLocaleString('en-IN')}`;
}

function buildEditHref(item: CartItem): string {
    const params = new URLSearchParams();
    params.set('cart_item', String(item.id));
    return `${paths.checkout(item.slug)}?${params.toString()}`;
}

export default function CartPage({ items = [] }: { items: CartItem[] }) {
    const summary = useMemo(() => {
        const qty = items.reduce((sum, it) => sum + Math.max(0, toNum(it.quantity)), 0);
        const total = items.reduce((sum, it) => sum + Math.max(0, toNum(it.line_total ?? toNum(it.denomination) * toNum(it.quantity))), 0);
        return { qty, total };
    }, [items]);

    return (
        <StorefrontLayout>
            <Head title="Cart" />
            <div className="mx-auto max-w-4xl px-4 py-10">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold text-gray-900">Cart</h1>
                    {items.length > 0 ? (
                        <button
                            type="button"
                            className="rounded-full border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                            onClick={() => router.post(paths.cartClear, {}, { preserveScroll: true })}
                        >
                            Clear cart
                        </button>
                    ) : null}
                </div>

                {items.length === 0 ? (
                    <div className="mt-8 rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                        <p className="text-gray-700">Your cart is empty.</p>
                        <Link href={paths.home} className="mt-4 inline-flex rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                            Continue shopping
                        </Link>
                    </div>
                ) : (
                    <>
                        <div className="mt-6 space-y-4">
                            {items.map((it) => {
                                const denom = toNum(it.denomination);
                                const qty = Math.max(1, toNum(it.quantity));
                                const total = toNum(it.line_total ?? denom * qty);
                                const isGift = it.gift_send_option === 'send_as_gift';

                                return (
                                    <div key={it.id} className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                                        <div className="flex flex-wrap items-start justify-between gap-4">
                                            <div className="min-w-0">
                                                <p className="truncate text-base font-semibold text-gray-900">{it.product_name ?? it.slug}</p>
                                                <p className="mt-1 text-sm text-gray-600">
                                                    {isGift ? 'Gift' : 'For myself'} · {formatMoney(denom)} x {qty}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-lg font-semibold text-gray-900">{formatMoney(total)}</p>
                                                <p className="text-xs text-gray-500">Total</p>
                                            </div>
                                        </div>

                                        <div className="mt-4 flex flex-wrap items-center gap-2">
                                            <Link
                                                href={paths.product(it.slug)}
                                                className="inline-flex rounded-full border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                            >
                                                View product
                                            </Link>
                                            <Link
                                                href={buildEditHref(it)}
                                                className="inline-flex rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                                            >
                                                Edit cart
                                            </Link>
                                            <button
                                                type="button"
                                                className="ml-auto inline-flex rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                                                onClick={() => router.post(paths.cartRemove, { cart_item_id: it.id }, { preserveScroll: true })}
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <div className="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between text-sm text-gray-700">
                                <span>Items</span>
                                <span className="font-semibold text-gray-900">{summary.qty}</span>
                            </div>
                            <div className="mt-2 flex items-center justify-between text-sm text-gray-700">
                                <span>Cart total</span>
                                <span className="font-semibold text-gray-900">{formatMoney(summary.total)}</span>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </StorefrontLayout>
    );
}
