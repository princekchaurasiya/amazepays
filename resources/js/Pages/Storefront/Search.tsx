import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { ProductCard } from '@/Components/Storefront';

type Product = Record<string, unknown>;

export default function SearchPage({ results = [], query = '' }: { results: Product[]; query?: string }) {
    const page = usePage();
    const q = query || new URL(page.url as string, 'http://x').searchParams.get('query') || '';

    return (
        <StorefrontLayout>
            <Head title={q ? `Search: ${q}` : 'Search'} />
            <div className="mx-auto max-w-7xl px-4 py-10">
                <h1 className="mb-2 text-2xl font-bold text-gray-900">Search results</h1>
                <p className="mb-8 text-gray-600">
                    {results.length} result(s) for &quot;{q}&quot;
                </p>
                {results.length === 0 ? (
                    <p className="text-gray-500">Try a different brand or product name.</p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
                        {results.map((p, i) => (
                            <ProductCard key={(p.id as number) ?? i} product={p} />
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
