import React, { useCallback } from 'react';
import { slugFromLabel } from '@/lib/slugFromLabel';

export type AdminNameSlugFieldsProps = {
    name: string;
    slug: string;
    /** Inertia-style merge; only `name` / `slug` keys are sent. */
    setData: (patch: { name?: string; slug?: string }) => void;
    /** Reserved for caller compatibility; slug now updates live from name changes. */
    syncResetKey: string | number;
    nameLabel?: string;
    slugLabel?: string;
    nameError?: string;
    slugError?: string;
    disabled?: boolean;
    nameClassName?: string;
    slugClassName?: string;
};

const defaultInputCls =
    'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900';

/**
 * Name + slug inputs with live slug generation from name.
 */
export default function AdminNameSlugFields({
    name,
    slug,
    setData,
    syncResetKey,
    nameLabel = 'Name',
    slugLabel = 'Slug',
    nameError,
    slugError,
    disabled = false,
    nameClassName = defaultInputCls,
    slugClassName = defaultInputCls,
}: AdminNameSlugFieldsProps) {
    void syncResetKey;

    const onNameChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const value = e.target.value;
            setData({ name: value, slug: slugFromLabel(value) });
        },
        [setData],
    );

    const onSlugChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const value = e.target.value;
            if (value.trim() === '') {
                setData({ slug: slugFromLabel(name) });
                return;
            }
            setData({ slug: value });
        },
        [setData, name],
    );

    return (
        <>
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{nameLabel}</label>
                <input
                    type="text"
                    value={name}
                    onChange={onNameChange}
                    disabled={disabled}
                    className={nameClassName}
                    autoComplete="off"
                />
                {nameError ? <p className="mt-1 text-xs text-red-600">{nameError}</p> : null}
            </div>
            <div>
                <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{slugLabel}</label>
                <input
                    type="text"
                    value={slug}
                    onChange={onSlugChange}
                    disabled={disabled}
                    className={`${slugClassName} font-mono`}
                    autoComplete="off"
                    spellCheck={false}
                />
                {slugError ? <p className="mt-1 text-xs text-red-600">{slugError}</p> : null}
            </div>
        </>
    );
}
