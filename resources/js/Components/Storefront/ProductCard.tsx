import React, { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { paths } from '@/lib/paths';

type Product = {
    url?: string | null;
    slug?: string | null;
    display_name?: string | null;
    name?: string | null;
    display_image_url?: string | null;
    discount_percentage?: number | string | null;
    out_of_stock?: boolean | number | null;
};

const PALETTE = ['#0B0B8F', '#1F2A7A', '#0F3460', '#2B3A99', '#0B3B5B', '#12306B', '#1B2B5E', '#2D4490'];

export default function ProductCard({ product }: { product: Product }) {
    const slug = product.url ?? product.slug ?? '';
    const href = slug ? paths.product(slug) : '#';
    const img = product.display_image_url ?? null;
    const discount = Number(product.discount_percentage ?? 0);
    const out = Boolean(product.out_of_stock);
    const displayName = product.display_name ?? product.name ?? '';

    const { bgColor, bgDark } = useMemo(() => {
        const idx = Math.abs(displayName.split('').reduce((a, c) => a + c.charCodeAt(0), 0)) % PALETTE.length;
        return { bgColor: PALETTE[idx], bgDark: PALETTE[(idx + 4) % PALETTE.length] };
    }, [displayName]);

    return (
        <Link
            href={href}
            className="group block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-lg hover:ring-product-primary/20"
        >
            <div
                className="relative aspect-[4/3] overflow-hidden bg-white"
                style={img ? undefined : { background: `linear-gradient(160deg, ${bgColor} 0%, ${bgDark} 100%)` }}
            >
                {out && (
                    <span className="absolute left-2 top-2 z-10 rounded bg-black/70 px-2 py-1 text-xs font-semibold uppercase text-white">
                        Out of stock
                    </span>
                )}
                {img ? (
                    <img
                        src={img}
                        alt=""
                        loading="lazy"
                        className="h-full w-full object-contain p-3 transition group-hover:scale-[1.01]"
                    />
                ) : (
                    <span className="flex h-full w-full items-center justify-center text-4xl font-bold text-white/90">
                        {displayName.slice(0, 1).toUpperCase()}
                    </span>
                )}
                <div className="pointer-events-none absolute bottom-2 right-2 h-8 w-8 overflow-hidden rounded-full bg-white/90 p-1 shadow">
                    <img src="/images/logo.png" alt="" className="h-full w-full object-contain" />
                </div>
            </div>
            <div className="p-3">
                <p className="line-clamp-2 text-sm font-semibold text-gray-900">{displayName}</p>
                {discount > 0 && (
                    <p className="mt-1 text-xs font-medium text-emerald-600">
                        {String(discount).replace(/\.?0+$/, '')}% OFF
                    </p>
                )}
            </div>
        </Link>
    );
}
