/**
 * URL-safe slug from a human-readable label (matches typical Laravel `^[a-z0-9]+(?:-[a-z0-9]+)*$` rules).
 */
export function slugFromLabel(input: string): string {
    const s = input
        .trim()
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '');

    return s;
}
