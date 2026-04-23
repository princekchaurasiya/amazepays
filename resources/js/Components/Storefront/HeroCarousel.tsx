import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { paths } from '@/lib/paths';

export type Slide = {
    id: number;
    desktop_image: string | null;
    image_mobile: string | null;
    slug: string | null;
    product_id: number | null;
    category_id: number | null;
    brand_id: number | null;
    is_linked: boolean;
    img_alt_tag: string | null;
    cta_link?: string | null;
    custom_url?: string | null;
    link_type?: string | null;
};

function rawLink(s: Slide): string {
    return String(s.custom_url || s.cta_link || '').trim();
}

/** Absolute http(s) URL for new-tab navigation */
function slideExternalUrl(s: Slide): string | null {
    const u = rawLink(s);
    if (/^https?:\/\//i.test(u)) {
        return u;
    }
    return null;
}

/** Same-origin path for Inertia <Link> */
function slideInternalPath(s: Slide): string | null {
    const u = rawLink(s);
    if (u.startsWith('/')) {
        return u;
    }
    if (!s.slug) {
        return null;
    }
    if (s.product_id) {
        return paths.product(s.slug);
    }
    if (s.category_id) {
        return paths.category(s.slug);
    }
    if (s.brand_id) {
        return paths.brand(s.slug);
    }
    return null;
}

export default function HeroCarousel({ slides }: { slides: Slide[] }) {
    const desktopSlides = slides.filter((s) => s.desktop_image);
    const mobileSlides = slides.filter((s) => s.image_mobile);
    const [deskIdx, setDeskIdx] = useState(0);
    const [mobIdx, setMobIdx] = useState(0);

    if (desktopSlides.length === 0 && mobileSlides.length === 0) {
        return null;
    }

    const renderOne = (s: Slide, isMobile: boolean) => {
        const src = isMobile ? s.image_mobile : s.desktop_image;
        if (!src) {
            return null;
        }
        const img = <img src={src} alt={s.img_alt_tag ?? ''} className="h-auto w-full object-cover" />;
        if (!s.is_linked) {
            return img;
        }
        const external = slideExternalUrl(s);
        if (external) {
            return (
                <a href={external} target="_blank" rel="noopener noreferrer" className="block">
                    {img}
                </a>
            );
        }
        const internal = slideInternalPath(s);
        if (internal) {
            return (
                <Link href={internal} className="block">
                    {img}
                </Link>
            );
        }
        return img;
    };

    return (
        <div className="w-full overflow-hidden bg-gray-100">
            {desktopSlides.length > 0 && (
                <div className="relative hidden md:block">
                    <div className="relative aspect-[21/8] w-full max-h-[420px] overflow-hidden bg-gray-200">
                        {renderOne(desktopSlides[deskIdx], false)}
                    </div>
                    {desktopSlides.length > 1 && (
                        <>
                            <button
                                type="button"
                                aria-label="Previous slide"
                                className="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow hover:bg-white"
                                onClick={() =>
                                    setDeskIdx((i) => (i - 1 + desktopSlides.length) % desktopSlides.length)
                                }
                            >
                                <ChevronLeft className="h-6 w-6" />
                            </button>
                            <button
                                type="button"
                                aria-label="Next slide"
                                className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-2 shadow hover:bg-white"
                                onClick={() => setDeskIdx((i) => (i + 1) % desktopSlides.length)}
                            >
                                <ChevronRight className="h-6 w-6" />
                            </button>
                        </>
                    )}
                </div>
            )}
            {mobileSlides.length > 0 && (
                <div className="relative md:hidden">
                    <div className="relative w-full overflow-hidden bg-gray-200">
                        {renderOne(mobileSlides[mobIdx], true)}
                    </div>
                    {mobileSlides.length > 1 && (
                        <>
                            <button
                                type="button"
                                aria-label="Previous slide"
                                className="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1.5 shadow"
                                onClick={() =>
                                    setMobIdx((i) => (i - 1 + mobileSlides.length) % mobileSlides.length)
                                }
                            >
                                <ChevronLeft className="h-5 w-5" />
                            </button>
                            <button
                                type="button"
                                aria-label="Next slide"
                                className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1.5 shadow"
                                onClick={() => setMobIdx((i) => (i + 1) % mobileSlides.length)}
                            >
                                <ChevronRight className="h-5 w-5" />
                            </button>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
