import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { BrandCard, HeroCarousel, ProductCard } from '@/Components/Storefront';
import CategoryGlyph from '@/Components/Storefront/CategoryGlyph';
import { getCategoryIconDef } from '@/lib/categoryIcons';
import { categoryAccentBackground, categoryAccentColor } from '@/lib/categoryAccent';
import type { Slide } from '@/Components/Storefront/HeroCarousel';
import { paths } from '@/lib/paths';

type HomeSettings = {
    section_banner_status?: boolean;
    section_brand_status?: boolean;
    section_category_status?: boolean;
    section_other_deal_status?: boolean;
};

type Cat = { id: number; name: string; slug: string; thumbnail?: string | null; accent_color?: string | null };
type Brand = { id: number; name: string; slug: string; logo?: string | null };
type Product = Record<string, unknown>;
type HomepageSectionItem = {
    sort_order?: number;
    product_id?: number | null;
    brand_id?: number | null;
    category_id?: number | null;
} & Record<string, unknown>;
type HomepageSection = { type?: string; title?: string | null; items?: HomepageSectionItem[] } & Record<string, unknown>;

function thumbUrl(t: string | null | undefined) {
    if (!t || t === 'null') return null;
    if (t.startsWith('http')) return t;
    return `/storage/${t.replace(/^\/+/, '')}`;
}

export default function Home({
    slides = [],
    homeSettings,
    sections = [],
    sectionProductsById = {},
    sectionBrandsById = {},
    sectionCategoriesById = {},
    brandMaxDiscounts = {},
}: {
    slides?: Slide[];
    homeSettings?: HomeSettings;
    categories?: Cat[];
    brands?: Brand[];
    sections?: HomepageSection[];
    sectionProductsById?: Record<string, Product>;
    sectionBrandsById?: Record<string, Brand>;
    sectionCategoriesById?: Record<string, Cat>;
    brandMaxDiscounts?: Record<string, number | string>;
}) {
    const hs = homeSettings ?? {};
    const emptySetup = !slides?.length && !sections?.length;

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
                {Array.isArray(sections) &&
                    sections.map((s, idx) => {
                        const items = s.items ?? [];
                        if (items.length === 0) return null;

                        // Identify section content type
                        const hasBrands = items.some((it) => Number(it?.brand_id || 0) > 0);
                        const hasCategories = items.some((it) => Number(it?.category_id || 0) > 0);
                        const hasProducts = items.some((it) => Number(it?.product_id || 0) > 0);

                        if (hasBrands) {
                            const brandItems = items
                                .filter((it) => Number(it?.brand_id || 0) > 0)
                                .slice()
                                .sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0));

                            const brandRows = brandItems
                                .map((it) => sectionBrandsById[String(it.brand_id)] as Brand | undefined)
                                .filter(Boolean) as Brand[];

                            if (brandRows.length === 0 || !s?.title) return null;

                            return (
                                <section key={`${s.type ?? 'brand-section'}-${idx}`} className="mb-12 scroll-mt-28">
                                    <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                                        {s.title}
                                    </p>
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                                        {brandRows.map((b) => (
                                            <BrandCard
                                                key={b.id}
                                                brand={b}
                                                discount={brandMaxDiscounts[String(b.id)] ?? brandMaxDiscounts[b.id as unknown as string]}
                                            />
                                        ))}
                                    </div>
                                </section>
                            );
                        }

                        if (hasCategories) {
                            const catItems = items
                                .filter((it) => Number(it?.category_id || 0) > 0)
                                .slice()
                                .sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0));

                            const categoryRows = catItems
                                .map((it) => sectionCategoriesById[String(it.category_id)] as Cat | undefined)
                                .filter(Boolean) as Cat[];

                            if (categoryRows.length === 0 || !s?.title) return null;

                            return (
                                <section key={`${s.type ?? 'category-section'}-${idx}`} className="mb-12 scroll-mt-28">
                                    <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                                        {s.title}
                                    </p>
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                                        {categoryRows.map((c) => {
                                            const t = thumbUrl(c.thumbnail);
                                            const showGlyph = !t && getCategoryIconDef(c.name);
                                            const accent =
                                                typeof c.accent_color === 'string' && /^#[0-9A-Fa-f]{6}$/.test(c.accent_color.trim())
                                                    ? c.accent_color.trim()
                                                    : categoryAccentColor(c.name);
                                            const tintBg = categoryAccentBackground(accent, 0.22);
                                            return (
                                                <Link
                                                    key={c.id}
                                                    href={paths.category(c.slug)}
                                                    className="flex flex-col items-center rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100 transition hover:ring-brand-500/25"
                                                >
                                                    <span
                                                        className={`mb-2 flex h-14 w-14 items-center justify-center overflow-hidden rounded-full ring-1 ${tintBg ? 'ring-black/5' : 'bg-gray-50 ring-gray-100'}`}
                                                        style={tintBg ? { backgroundColor: tintBg } : undefined}
                                                    >
                                                        {t ? (
                                                            <img src={t} alt="" className="h-full w-full object-cover" />
                                                        ) : showGlyph ? (
                                                            <CategoryGlyph
                                                                name={c.name}
                                                                className={`h-7 w-7 ${accent ? '' : 'text-brand-600'}`}
                                                                accentColor={accent}
                                                            />
                                                        ) : (
                                                            <span
                                                                className={`text-lg font-bold ${accent ? '' : 'text-brand-600'}`}
                                                                style={accent ? { color: accent } : undefined}
                                                            >
                                                                {c.name.slice(0, 1).toUpperCase()}
                                                            </span>
                                                        )}
                                                    </span>
                                                    <span className="line-clamp-2 text-sm font-medium text-gray-900">{c.name}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </section>
                            );
                        }

                        if (hasProducts) {
                            const prodItems = items
                                .filter((it) => Number(it?.product_id || 0) > 0)
                                .slice()
                                .sort((a, b) => Number(a?.sort_order || 0) - Number(b?.sort_order || 0));

                            const products = prodItems
                                .map((it) => sectionProductsById[String(it.product_id)] as Product | undefined)
                                .filter(Boolean) as Product[];

                            if (products.length === 0 || !s?.title) return null;

                            return (
                                <section key={`${s.type ?? 'section'}-${idx}`} className="mb-12 scroll-mt-28">
                                    <p className="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                                        {s.title}
                                    </p>
                                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
                                        {products.map((p, i) => (
                                            <ProductCard key={(p.id as number) ?? i} product={p as Product} />
                                        ))}
                                    </div>
                                </section>
                            );
                        }

                        return null;
                    })}
            </div>
        </StorefrontLayout>
    );
}
