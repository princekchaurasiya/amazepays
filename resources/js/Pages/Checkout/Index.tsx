import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';

type Product = Record<string, unknown>;
type Order = {
    id?: number;
    denomination?: number;
    quantity?: number;
    grand_payable_amount?: number;
    discounted_amount_value?: number;
    amount_payable_after_discount?: number;
};

export default function CheckoutIndex({
    product,
    order,
    slug,
}: {
    product: Product;
    order: Order;
    slug: string;
}) {
    const name = String(product.name ?? 'Gift card');
    const img = product.display_image_url as string | undefined;
    const orderId = order.id;

    const billing = useForm({
        billing_name: '',
        billing_email: '',
        billing_tel: '',
        billing_zip: '',
        billing_address: '',
        billing_address_two: '',
        billing_city: '',
        billing_state: '',
        billing_country: 'IN',
        billing_gst_number: '',
    });

    return (
        <CheckoutLayout>
            <Head title="Checkout" />
            <h1 className="text-2xl font-bold text-gray-900">Checkout</h1>
            <div className="mt-8 grid gap-8 lg:grid-cols-2">
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-gray-900">Billing details</h2>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        {(['billing_name', 'billing_email', 'billing_tel', 'billing_zip'] as const).map((f) => (
                            <label key={f} className="block text-sm">
                                <span className="text-gray-600">{f.replace('billing_', '')}</span>
                                <input
                                    className="mt-1 w-full rounded-lg border px-3 py-2"
                                    value={billing.data[f]}
                                    onChange={(e) => billing.setData(f, e.target.value)}
                                />
                            </label>
                        ))}
                        {(['billing_address', 'billing_address_two', 'billing_city', 'billing_state'] as const).map((f) => (
                            <label key={f} className="block text-sm sm:col-span-2">
                                <span className="text-gray-600">{f.replace('billing_', '')}</span>
                                <input
                                    className="mt-1 w-full rounded-lg border px-3 py-2"
                                    value={billing.data[f]}
                                    onChange={(e) => billing.setData(f, e.target.value)}
                                />
                            </label>
                        ))}
                        <label className="block text-sm sm:col-span-2">
                            <span className="text-gray-600">GST (optional)</span>
                            <input
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                                value={billing.data.billing_gst_number}
                                onChange={(e) => billing.setData('billing_gst_number', e.target.value)}
                            />
                        </label>
                    </div>
                </div>
                <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-gray-900">Order summary</h2>
                    <div className="mt-4 flex gap-4">
                        {img ? <img src={img} alt="" className="h-24 w-24 rounded-lg object-cover" /> : null}
                        <div>
                            <p className="font-semibold text-gray-900">{name}</p>
                            <p className="text-sm text-gray-600">Denomination: ₹{order.denomination ?? '—'}</p>
                            <p className="text-sm text-gray-600">Qty: {order.quantity ?? '—'}</p>
                            <p className="mt-2 text-lg font-bold text-gray-900">
                                Payable: ₹{Number(order.amount_payable_after_discount ?? order.grand_payable_amount ?? 0).toLocaleString('en-IN')}
                            </p>
                        </div>
                    </div>
                    {orderId ? (
                        <form action="/payment/upi" method="post" className="mt-6">
                            <input type="hidden" name="_token" value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content} />
                            <input type="hidden" name="order_id" value={orderId} />
                            <button type="submit" className="w-full rounded-full bg-emerald-600 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                                Pay with UPI
                            </button>
                        </form>
                    ) : (
                        <p className="mt-4 text-sm text-amber-700">Complete product selection first.</p>
                    )}
                    <Link href={paths.product(slug)} className="mt-4 inline-block text-sm text-brand-600 hover:underline">
                        Edit cart
                    </Link>
                </div>
            </div>
        </CheckoutLayout>
    );
}
