import React from 'react';
import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { ProductCard } from '@/Components/Storefront';
import { paths } from '@/lib/paths';

type Brand = { id: number; name: string; slug: string };
type Product = Record<string, unknown>;

export default function BrandPage({ brand, products = [] }: { brand: Brand; products: Product[] }) {
    return (
        <StorefrontLayout>
            <Head title={brand.name} />
            <div className="mx-auto max-w-7xl px-4 py-10">
                <h1 className="mb-2 text-2xl font-bold text-gray-900">{brand.name}</h1>
                <p className="mb-8 text-sm text-gray-600">
                    <a href={paths.home} className="text-brand-600 hover:underline">
                        Home
                    </a>{' '}
                    / {brand.name}
                </p>
                {products.length === 0 ? (
                    <p className="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-600">
                        No products for this brand yet.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
                        {products.map((p, i) => (
                            <ProductCard key={(p.id as number) ?? i} product={p} />
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
