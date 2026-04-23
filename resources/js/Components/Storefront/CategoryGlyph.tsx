import React from 'react';
import { getCategoryIconDef } from '@/lib/categoryIcons';

type Props = {
    name: string;
    /** Tailwind classes for the SVG (stroke uses currentColor). */
    className?: string;
    /** Optional #RRGGBB from category seed — tints icon on the storefront. */
    accentColor?: string | null;
};

export default function CategoryGlyph({ name, className = 'h-6 w-6 text-brand-600', accentColor }: Props) {
    const def = getCategoryIconDef(name);
    if (!def) {
        return null;
    }
    const strokeStyle =
        accentColor && /^#[0-9A-Fa-f]{6}$/.test(accentColor) ? { color: accentColor } : undefined;
    return (
        <svg
            className={def.className ?? className}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden
            style={strokeStyle}
        >
            {def.paths.map((d, i) => (
                <path key={i} strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={d} />
            ))}
        </svg>
    );
}
