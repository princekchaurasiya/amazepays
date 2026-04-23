import { useCallback, useEffect, useMemo, useState } from 'react';

export function useBulkRowSelection(pageRowIds: number[]) {
    const pageKey = useMemo(() => pageRowIds.slice().sort((a, b) => a - b).join(','), [pageRowIds]);

    const [selected, setSelected] = useState<Set<number>>(() => new Set());

    useEffect(() => {
        setSelected(new Set());
    }, [pageKey]);

    const toggleOne = useCallback((id: number) => {
        setSelected(prev => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const toggleAllOnPage = useCallback(() => {
        setSelected(prev => {
            if (pageRowIds.length === 0) {
                return new Set();
            }
            const allSelected = pageRowIds.every(id => prev.has(id));
            if (allSelected) {
                return new Set();
            }
            return new Set(pageRowIds);
        });
    }, [pageRowIds]);

    const clear = useCallback(() => setSelected(new Set()), []);

    const isSelected = useCallback((id: number) => selected.has(id), [selected]);

    const selectedIds = useMemo(() => [...selected], [selected]);

    const selectedOnPageCount = useMemo(
        () => pageRowIds.filter(id => selected.has(id)).length,
        [pageRowIds, selected],
    );

    const allOnPageSelected =
        pageRowIds.length > 0 && pageRowIds.every(id => selected.has(id));
    const someOnPageSelected = selectedOnPageCount > 0 && !allOnPageSelected;

    return {
        selectedIds,
        selectedCount: selected.size,
        toggleOne,
        toggleAllOnPage,
        clear,
        isSelected,
        allOnPageSelected,
        someOnPageSelected,
    };
}
