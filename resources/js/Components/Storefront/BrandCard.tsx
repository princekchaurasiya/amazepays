import React, { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { paths } from '@/lib/paths';

type Brand = {
    slug: string;
    name: string;
    logo?: string | null;
};

const PALETTE = ['#1a1a2e', '#16213e', '#0f3460', '#533483', '#2b2d42', '#3d5a80', '#264653', '#2d6a4f'];

function logoUrl(path: string | null | undefined) {
    if (!path || path === 'null') return null;
    if (path.startsWith('http')) return path;
    return `/storage/${path.replace(/^\/+/, '')}`;
}

export default function BrandCard({ brand, discount }: { brand: Brand; discount?: number | string | null }) {
    const href = paths.brand(brand.slug);
    const logo = logoUrl(brand.logo);
    const discountVal = discount != null ? Number(discount) : 0;

    const { bgColor, bgDark } = useMemo(() => {
        const idx = Math.abs(brand.name.split('').reduce((a, c) => a + c.charCodeAt(0), 0)) % PALETTE.length;
        return { bgColor: PALETTE[idx], bgDark: PALETTE[(idx + 4) % PALETTE.length] };
    }, [brand.name]);

    return (
        <Link
            href={href}
            className="group block overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:ring-brand-500/30"
        >
            <div
                className="relative aspect-[4/3] overflow-hidden"
                style={{ background: `linear-gradient(160deg, ${bgColor} 0%, ${bgDark} 100%)` }}
            >
                {logo ? (
                    <img src={logo} alt="" className="h-full w-full object-contain p-4 transition group-hover:scale-[1.02]" />
                ) : (
                    <span className="flex h-full w-full items-center justify-center text-4xl font-bold text-white/90">
                        {brand.name.slice(0, 1).toUpperCase()}
                    </span>
                )}
                <div className="pointer-events-none absolute bottom-2 right-2 h-8 w-8 overflow-hidden rounded-full bg-white/90 p-1 shadow">
                    <img src="/images/logo.png" alt="" className="h-full w-full object-contain" />
                </div>
            </div>
            <div className="p-3">
                <p className="line-clamp-2 text-sm font-semibold text-gray-900">{brand.name}</p>
                {discountVal > 0 && (
                    <p className="mt-1 text-xs font-medium text-emerald-600">
                        {String(discountVal).replace(/\.?0+$/, '')}% OFF
                    </p>
                )}
            </div>
        </Link>
    );
}
