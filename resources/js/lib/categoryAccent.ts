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
