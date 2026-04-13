import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { BrandCard, HeroCarousel, ProductCard } from '@/Components/Storefront';
import CategoryGlyph from '@/Components/Storefront/CategoryGlyph';
import { getCategoryIconDef } from '@/lib/categoryIcons';
import type { Slide } from '@/Components/Storefront/HeroCarousel';
import { paths } from '@/lib/paths';

type HomeSettings = {
    section_banner_status?: boolean;
    section_brand_status?: boolean;
    section_hot_deal_status?: boolean;
    section_category_status?: boolean;
    section_other_deal_status?: boolean;
    section_brand_title?: string;
    section_hot_deal_title?: string;
    section_category_title?: string;
    section_other_deal_title?: string;
};

type Cat = { id: number; name: string; slug: string; thumbnail?: string | null };
type Brand = { id: number; name: string; slug: string; logo?: string | null };
type Product = Record<string, unknown>;

type KgenProduct = Record<string, unknown>;

function thumbUrl(t: string | null | undefined) {
    if (!t || t === 'null') return null;
    if (t.startsWith('http')) return t;
    return `/storage/${t.replace(/^\/+/, '')}`;
}

export default function Home({
    slides = [],
    homeSettings,
    categories = [],
    brands = [],
    priorityProducts = [],
    noPriorityProducts = [],
    kgenProducts = [],
    brandMaxDiscounts = {},
    kgenSectionTitle = 'KGen Technology',
    showKgenSection = false,
}: {
    slides?: Slide[];
    homeSettings?: HomeSettings;
    categories?: Cat[];
    brands?: Brand[];
    priorityProducts?: Product[];
    noPriorityProducts?: Product[];
    kgenProducts?: KgenProduct[];
    brandMaxDiscounts?: Record<string, number | string>;
    kgenSectionTitle?: string;
    showKgenSection?: boolean;
}) {
    const hs = homeSettings ?? {};
    const emptySetup = (!categories?.length && !priorityProducts?.length && !noPriorityProducts?.length);

    return (
        <StorefrontLayout>
            <Head title="Exclusive Gift Cards & Vouchers" />
            {hs.section_banner_status && slides.length > 0 && <HeroCarousel slides={slides} />}

            {emptySetup && (
                <div className="mx-auto max-w-3xl px-4 py-8">
                    <div className="rounded-xl border border-sky-200 bg-sky-50 p-6 text-sky-900">
                        <h2 className="text-lg font-semibold">Setup required</h2>
                        <p className="mt-2 text-sm">Add categories, brands, and products in the admin panel.</p>
                        <Link href={paths.panel} className="mt-4 inline-block text-sm font-medium text-brand-600 hover:underline">
                            Open admin panel
                        </Link>
                    </div>
                </div>
            )}

            <div className="mx-auto max-w-7xl px-4 py-10">
                {hs.section_brand_status && brands.length > 0 && (
                    <section id="storefront-section-brands" className="mb-12 scroll-mt-28">
                        <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                            {hs.section_brand_title ?? 'Popular brands'}
                        </p>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            {brands.map((b) => (
                                <BrandCard
                                    key={b.id}
                                    brand={b}
                                    discount={brandMaxDiscounts[String(b.id)] ?? brandMaxDiscounts[b.id as unknown as string]}
                                />
                            ))}
                        </div>
                    </section>
                )}

                {hs.section_hot_deal_status && priorityProducts.length > 0 && (
                    <section id="storefront-section-hot" className="mb-12 scroll-mt-28">
                        <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                            {hs.section_hot_deal_title ?? 'Hot deals'}
                        </p>
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
                            {priorityProducts.map((p, i) => (
                                <ProductCard key={(p.id as number) ?? i} product={p as Product} />
                            ))}
                        </div>
                    </section>
                )}

                {hs.section_category_status && categories.length > 0 && (
                    <section id="storefront-section-categories" className="mb-12 scroll-mt-28">
                        <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                            {hs.section_category_title ?? 'Categories'}
                        </p>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            {categories.map((c) => {
                                const t = thumbUrl(c.thumbnail);
                                const showGlyph = !t && getCategoryIconDef(c.name);
                                return (
                                    <Link
                                        key={c.id}
                                        href={paths.category(c.slug)}
                                        className="flex flex-col items-center rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100 transition hover:ring-brand-500/25"
                                    >
                                        <span className="mb-2 flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-gray-50 ring-1 ring-gray-100">
                                            {t ? (
                                                <img src={t} alt="" className="h-full w-full object-cover" />
                                            ) : showGlyph ? (
                                                <CategoryGlyph name={c.name} className="h-7 w-7 text-brand-600" />
                                            ) : (
                                                <span className="text-lg font-bold text-brand-600">{c.name.slice(0, 1).toUpperCase()}</span>
                                            )}
                                        </span>
                                        <span className="line-clamp-2 text-sm font-medium text-gray-900">{c.name}</span>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                )}

                {hs.section_other_deal_status && noPriorityProducts.length > 0 && (
                    <section id="storefront-section-deals" className="mb-12 scroll-mt-28">
                        <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                            {hs.section_other_deal_title ?? 'Other deals'}
                        </p>
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
                            {noPriorityProducts.map((p, i) => (
                                <ProductCard key={(p.id as number) ?? i} product={p as Product} />
                            ))}
                        </div>
                    </section>
                )}

                {showKgenSection && kgenProducts.length > 0 && (
                    <section className="mb-12">
                        <h2 className="mb-6 text-center text-xl font-bold text-gray-900">{kgenSectionTitle}</h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {kgenProducts.map((kp, idx) => {
                                const name = String(kp.productDisplayName ?? kp.productName ?? 'Product');
                                const att = (kp.attachments as string[] | undefined)?.[0];
                                const img = att ?? 'https://via.placeholder.com/300x200';
                                const disc = Number(kp.discount_percentage ?? 0);
                                return (
                                    <div key={idx} className="relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                                        {disc > 0 && (
                                            <span className="absolute left-2 top-2 z-10 rounded bg-red-600 px-2 py-0.5 text-xs text-white">
                                                {disc}% Off
                                            </span>
                                        )}
                                        <img src={img} alt="" className="h-44 w-full object-cover" />
                                        <div className="p-3">
                                            <p className="font-semibold text-gray-900">{name}</p>
                                            <Link
                                                href={paths.kgenPlaceOrder}
                                                className="mt-2 inline-block text-sm font-medium text-brand-600 hover:underline"
                                            >
                                                View options
                                            </Link>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                )}
            </div>
        </StorefrontLayout>
    );
}
