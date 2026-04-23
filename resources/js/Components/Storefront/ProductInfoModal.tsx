import React, { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { X } from 'lucide-react';

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
};

export default function ProductInfoModal({ open, title, sections, activeSectionId, onChangeSection, onClose }: Props) {
    const page = usePage<{ i18n?: { storefront?: { product?: Record<string, string> } } }>();
    const text = page.props.i18n?.storefront?.product ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;

    useEffect(() => {
        if (!open) return;

        const onEsc = (event: KeyboardEvent) => {
            if (event.key === 'Escape') onClose();
        };

        window.addEventListener('keydown', onEsc);
        return () => window.removeEventListener('keydown', onEsc);
    }, [open, onClose]);

    if (!open) return null;

    const active = sections.find((section) => section.id === activeSectionId) ?? sections[0];

    return (
        <div className="fixed inset-0 z-[120] bg-black/45" onClick={onClose}>
            <div className="absolute inset-0 flex items-end justify-center p-0 md:items-center md:p-6">
                <div
                    className="h-[88vh] w-full rounded-t-3xl bg-white shadow-2xl md:h-[88vh] md:max-w-3xl md:rounded-3xl"
                    onClick={(event) => event.stopPropagation()}
                >
                    <div className="flex items-start justify-between border-b border-gray-200 px-5 py-4 md:px-8 md:py-6">
                        <h2 className="text-2xl font-bold text-gray-900 md:text-[44px] md:leading-tight">{title}</h2>
                        <button type="button" onClick={onClose} className="text-gray-500 hover:text-gray-800" aria-label={t('modal_close', 'Close')}>
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    <div className="h-[calc(100%-176px)] overflow-y-auto px-5 py-4 md:h-[calc(100%-202px)] md:px-8 md:py-6">
                        <div className="prose prose-sm max-w-none text-gray-700" dangerouslySetInnerHTML={{ __html: active?.contentHtml ?? '' }} />
                    </div>

                    <div className="sticky bottom-0 border-t border-gray-200 bg-white/95 px-5 py-3 backdrop-blur md:px-8 md:py-4">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{t('modal_nav_title', 'See also')}</p>
                        <div className="flex flex-wrap gap-2 md:gap-3">
                            {sections.map((section) => (
                                <button
                                    key={section.id}
                                    type="button"
                                    onClick={() => onChangeSection(section.id)}
                                    className={`inline-flex rounded-full border px-3 py-1.5 text-xs md:text-sm ${
                                        section.id === active?.id
                                            ? 'border-emerald-700 bg-emerald-50 font-semibold text-emerald-900'
                                            : 'border-gray-300 text-gray-700 hover:border-gray-500 hover:text-gray-900'
                                    }`}
                                >
                                    {section.label}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
