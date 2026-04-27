/** Hex #RRGGBB → rgba for soft category tile backgrounds on the storefront. */
export function categoryAccentBackground(hex: string | null | undefined, alpha = 0.18): string | undefined {
    if (!hex || typeof hex !== 'string') {
        return undefined;
    }
    const normalized = hex.trim();
    if (!/^#[0-9A-Fa-f]{6}$/.test(normalized)) {
        return undefined;
    }
    const r = parseInt(normalized.slice(1, 3), 16);
    const g = parseInt(normalized.slice(3, 5), 16);
    const b = parseInt(normalized.slice(5, 7), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/**
 * Deterministic accent color for a category name.
 * Used when the backend doesn't persist `accent_color` (Phase-3 schema).
 */
export function categoryAccentColor(name: string | null | undefined): string {
    const palette = [
        '#0ea5e9', // sky
        '#06b6d4', // cyan
        '#14b8a6', // teal
        '#22c55e', // green
        '#84cc16', // lime
        '#f59e0b', // amber
        '#f97316', // orange
        '#ef4444', // red
        '#ec4899', // pink
        '#a855f7', // purple
        '#6366f1', // indigo
        '#3b82f6', // blue
        '#0f766e', // deep teal
        '#ca8a04', // gold
        '#475569', // slate
        '#78716b', // stone
    ];

    const s = (name ?? '').trim().toLowerCase();
    let hash = 0;
    for (let i = 0; i < s.length; i++) {
        hash = (hash * 31 + s.charCodeAt(i)) >>> 0;
    }
    return palette[hash % palette.length];
}
