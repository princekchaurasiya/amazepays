import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { BrandCard, ProductCard } from '@/Components/Storefront';
import { paths } from '@/lib/paths';

type Brand = { id: number; name: string; slug: string; logo?: string | null };
type Product = Record<string, unknown>;
type Cat = { id: number; name: string; slug: string };

export default function BusinessPage({
    categories = [],
    brands = [],
    brandMaxDiscounts = {},
    featuredProducts = [],
    savingsDisplay = '',
}: {
    categories?: Cat[];
    brands?: Brand[];
    brandMaxDiscounts?: Record<string, number | string>;
    featuredProducts?: Product[];
    savingsDisplay?: string;
}) {
    return (
        <StorefrontLayout>
            <Head title="For businesses" />
            <div className="bg-gradient-to-b from-slate-900 to-slate-800 px-4 py-16 text-white">
                <div className="mx-auto max-w-4xl text-center">
                    <h1 className="text-3xl font-bold md:text-4xl">Gift cards for your team and customers</h1>
                    <p className="mt-4 text-lg text-slate-200">Bulk pricing, APIs, and a dedicated B2B portal.</p>
                    {savingsDisplay ? <p className="mt-6 text-sm text-emerald-300">Platform savings: {savingsDisplay}</p> : null}
                    <div className="mt-8 flex flex-wrap justify-center gap-4">
                        <Link
                            href={`${paths.panel}/b2b/place-order`}
                            className="rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100"
                        >
                            Open B2B portal
                        </Link>
                        <Link href={paths.contact} className="rounded-full border border-white/40 px-6 py-3 text-sm font-semibold hover:bg-white/10">
                            Contact sales
                        </Link>
                    </div>
                </div>
            </div>
            <div className="mx-auto max-w-7xl px-4 py-12">
                {brands.length > 0 && (
                    <section className="mb-12">
                        <h2 className="mb-4 text-center text-xs font-semibold uppercase tracking-widest text-gray-500">Popular for business</h2>
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
                            {brands.slice(0, 12).map((b) => (
                                <BrandCard
                                    key={b.id}
                                    brand={b}
                                    discount={brandMaxDiscounts[String(b.id)] ?? brandMaxDiscounts[b.id as unknown as string]}
                                />
                            ))}
                        </div>
                    </section>
                )}
                {featuredProducts.length > 0 && (
                    <section>
                        <h2 className="mb-4 text-center text-xs font-semibold uppercase tracking-widest text-gray-500">Featured</h2>
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                            {featuredProducts.map((p, i) => (
                                <ProductCard key={(p.id as number) ?? i} product={p} />
                            ))}
                        </div>
                    </section>
                )}
                {categories.length > 0 && (
                    <section className="mt-12">
                        <h2 className="mb-4 text-center text-xs font-semibold uppercase tracking-widest text-gray-500">Categories</h2>
                        <div className="flex flex-wrap justify-center gap-2">
                            {categories.map((c) => (
                                <Link
                                    key={c.id}
                                    href={paths.category(c.slug)}
                                    className="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-200"
                                >
                                    {c.name}
                                </Link>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </StorefrontLayout>
    );
}
