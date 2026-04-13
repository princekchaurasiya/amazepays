import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type KGenProduct = Record<string, unknown>;

export default function KGenProducts({ products = [], error }: { products: KGenProduct[]; error?: string | null }) {
    return (
        <StorefrontLayout>
            <Head title="KGen products" />
            <div className="mx-auto max-w-6xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">Partner catalog</h1>
                {error ? <p className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{error}</p> : null}
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {products.map((p, i) => {
                        const id = String(p.productID ?? p.productId ?? i);
                        const title = String(p.productDisplayName ?? p.productName ?? 'Product');
                        const variant = (p.variants as Record<string, unknown> | undefined)?.variantID;
                        const variantId = variant != null ? String(variant) : '';
                        const href = variantId
                            ? `${paths.kgenPlaceOrder}?variantId=${encodeURIComponent(variantId)}`
                            : paths.kgenPlaceOrder;
                        return (
                            <div key={id} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                <h2 className="font-semibold text-gray-900">{title}</h2>
                                <p className="mt-1 text-xs text-gray-500">ID: {id}</p>
                                <Link href={href} className="mt-3 inline-block text-sm font-medium text-brand-600 hover:underline">
                                    Place order
                                </Link>
                            </div>
                        );
                    })}
                </div>
                {products.length === 0 && !error ? (
                    <p className="mt-8 text-gray-600">No products available right now.</p>
                ) : null}
            </div>
        </StorefrontLayout>
    );
}
