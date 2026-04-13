import React from 'react';
import { getCategoryIconDef } from '@/lib/categoryIcons';

type Props = {
    name: string;
    /** Tailwind classes for the SVG (stroke uses currentColor). */
    className?: string;
};

export default function CategoryGlyph({ name, className = 'h-6 w-6 text-brand-600' }: Props) {
    const def = getCategoryIconDef(name);
    if (!def) {
        return null;
    }
    return (
        <svg
            className={def.className ?? className}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden
        >
            {def.paths.map((d, i) => (
                <path key={i} strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={d} />
            ))}
        </svg>
    );
}
