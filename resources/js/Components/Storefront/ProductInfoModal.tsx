import React, { useEffect, useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, Gavel, Info, X, type LucideIcon } from 'lucide-react';

export type ProductInfoSection = {
    id: string;
    label: string;
    contentHtml: string;
};

type Props = {
    open: boolean;
    title: string;
    sections: ProductInfoSection[];
    activeSectionId: string;
    onChangeSection: (id: string) => void;
    onClose: () => void;
    /** Shown next to "About brand" when present (e.g. product art). */
    sideImageUrl?: string | null;
};

const SECTION_ICONS: Record<string, LucideIcon> = {
    'about-brand': Info,
    'important-instructions': ClipboardList,
    'how-to-use': BookOpen,
    'terms-and-conditions': Gavel,
};

function NavIcon({ sectionId }: { sectionId: string }) {
    const Icon = SECTION_ICONS[sectionId] ?? Info;
    return <Icon className="h-3.5 w-3.5 shrink-0 md:h-4 md:w-4" aria-hidden="true" />;
}

export default function ProductInfoModal({
    open,
    title,
    sections,
    activeSectionId,
    onChangeSection,
    onClose,
    sideImageUrl,
}: Props) {
    const page = usePage<{ i18n?: { storefront?: { product?: Record<string, string> } } }>();
    const text = page.props.i18n?.storefront?.product ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;

    useEffect(() => {
        if (!open) return;

        const onEsc = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose();
        };

        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        window.addEventListener('keydown', onEsc);
        return () => {
            document.body.style.overflow = prev;
            window.removeEventListener('keydown', onEsc);
        };
    }, [open, onClose]);

    const active = useMemo(
        () => sections.find((section) => section.id === activeSectionId) ?? sections[0],
        [sections, activeSectionId],
    );

    const showAboutWithImage = Boolean(sideImageUrl && active?.id === 'about-brand');

    const imageAlt = t('modal_image_alt', 'Brand image');

    if (!open) return null;

    const proseClass =
        'prose prose-sm max-w-none text-gray-600 prose-headings:text-product-primary prose-a:text-product-primary';

    return (
        <div className="fixed inset-0 z-[120] bg-black/45" role="presentation" onClick={onClose}>
            <div className="absolute inset-0 flex items-end justify-center p-0 md:items-center md:p-6">
                <div
                    className="flex h-[88vh] w-full max-h-[min(88vh,920px)] flex-col rounded-t-3xl bg-white shadow-2xl ring-1 ring-gray-200/60 md:max-w-3xl md:rounded-3xl"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="product-info-modal-title"
                    onClick={(event) => event.stopPropagation()}
                >
                    <div className="flex shrink-0 items-start justify-between border-b border-gray-200/90 px-5 py-4 md:px-8 md:py-5">
                        <h2
                            id="product-info-modal-title"
                            className="pr-4 text-2xl font-bold leading-tight text-product-primary md:text-3xl md:leading-tight"
                        >
                            {title}
                        </h2>
                        <button
                            type="button"
                            onClick={onClose}
                            className="shrink-0 rounded-lg p-1 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                            aria-label={t('modal_close', 'Close')}
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5 md:px-8 md:py-6">
                        {showAboutWithImage ? (
                            <div className="grid gap-6 md:grid-cols-2 md:items-start md:gap-8">
                                <div className="w-full">
                                    <img
                                        src={sideImageUrl!}
                                        alt={imageAlt}
                                        className="aspect-square w-full max-h-80 rounded-2xl object-cover object-center shadow-sm"
                                    />
                                </div>
                                <div>
                                    <div className={proseClass} dangerouslySetInnerHTML={{ __html: active?.contentHtml ?? '' }} />
                                </div>
                            </div>
                        ) : (
                            <div>
                                <div className={proseClass} dangerouslySetInnerHTML={{ __html: active?.contentHtml ?? '' }} />
                            </div>
                        )}
                    </div>

                    <div className="shrink-0 border-t border-gray-200/90 bg-white/95 px-5 py-3 backdrop-blur-sm md:px-8 md:py-4">
                        <p className="mb-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {t('modal_nav_title', 'See also')}
                        </p>
                        <div className="flex flex-wrap gap-2 md:gap-2.5">
                            {sections.map((section) => {
                                const isActive = section.id === active?.id;
                                return (
                                    <button
                                        key={section.id}
                                        type="button"
                                        onClick={() => onChangeSection(section.id)}
                                        className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-2 text-left text-xs font-medium transition md:px-3.5 md:py-2 md:text-sm ${
                                            isActive
                                                ? 'border-product-primary bg-blue-50 text-product-primary shadow-sm'
                                                : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900'
                                        }`}
                                    >
                                        <NavIcon sectionId={section.id} />
                                        {section.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
