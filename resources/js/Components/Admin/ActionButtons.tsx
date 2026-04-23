import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Eye, Pencil, Trash2, ToggleLeft, ToggleRight } from 'lucide-react';
import ConfirmDialog from './ConfirmDialog';

export type ActionButtonsProps = {
    /** Detail / read-first link */
    viewHref?: string;
    /**
     * Navigate to edit URL. Mutually exclusive with {@link onEditClick} — pass only one.
     */
    editHref?: string;
    /**
     * Inline edit handler (button). Mutually exclusive with {@link editHref} — pass only one.
     */
    onEditClick?: () => void;
    /** Called after user confirms delete in the built-in dialog */
    onDelete?: () => void;
    deleteConfirmTitle?: string;
    deleteConfirmMessage?: string;
    /** e.g. toggle active/inactive */
    onToggle?: () => void;
    /**
     * Current “on” state for the row (e.g. is_active). Drives ToggleRight (on) vs ToggleLeft (off).
     * Pass whenever using onToggle; avoids fragile string matching on titles.
     */
    toggleOn?: boolean;
    toggleLabel?: string;
    /** Shown when onToggle is set (browser tooltip) */
    toggleTitle?: string;
    className?: string;
};

/**
 * Compact row of icon actions for admin list tables (View / Edit / Toggle / Delete).
 * If viewHref and editHref are the same URL, only one edit-style control is shown.
 */
export default function ActionButtons({
    viewHref,
    editHref,
    onEditClick,
    onDelete,
    deleteConfirmTitle,
    deleteConfirmMessage,
    onToggle,
    toggleOn,
    toggleLabel,
    toggleTitle,
    className = '',
}: ActionButtonsProps) {
    const page = usePage<{ i18n?: { admin?: { actions?: Record<string, string> } } }>();
    const text = page.props.i18n?.admin?.actions ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const resolvedDeleteTitle = deleteConfirmTitle ?? t('delete_confirm_title', 'Delete this item?');
    const resolvedDeleteMessage = deleteConfirmMessage ?? t('delete_confirm_message', 'This cannot be undone.');

    const showView = Boolean(viewHref);
    const showEditButton = Boolean(onEditClick);
    const showEditLink = Boolean(editHref) && !showEditButton;

    const btnClass =
        'inline-flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400 transition-colors';

    const handleDeleteConfirm = () => {
        if (!onDelete) {
            return;
        }
        setDeleting(true);
        try {
            onDelete();
        } finally {
            setDeleting(false);
            setDeleteOpen(false);
        }
    };

    if (!viewHref && !showEditLink && !showEditButton && !onToggle && !onDelete) {
        return <span className="text-gray-400 text-xs">—</span>;
    }

    const labelLower = (toggleLabel ?? '').toLowerCase();
    /** Safe fallback only when toggleOn omitted: “off” action implies row is on. Never scan toggleTitle (substring false positives). */
    const inferredToggleOn =
        labelLower.includes('disable') || /^deactiv/i.test(toggleLabel ?? '');
    const resolvedToggleOn = toggleOn ?? inferredToggleOn;

    return (
        <>
            <ConfirmDialog
                open={deleteOpen}
                onClose={() => setDeleteOpen(false)}
                onConfirm={handleDeleteConfirm}
                title={resolvedDeleteTitle}
                message={resolvedDeleteMessage}
                confirmLabel={t('delete', 'Delete')}
                variant="danger"
                loading={deleting}
            />
            <div className={`inline-flex flex-wrap items-center gap-0.5 ${className}`}>
                {showView && (
                    <Link href={viewHref!} className={btnClass} title={t('view', 'View')}>
                        <Eye size={16} aria-hidden />
                        <span className="sr-only">{t('view', 'View')}</span>
                    </Link>
                )}
                {showEditButton && (
                    <button
                        type="button"
                        onClick={onEditClick}
                        className={btnClass}
                        title={t('edit', 'Edit')}
                    >
                        <Pencil size={16} aria-hidden />
                        <span className="sr-only">{t('edit', 'Edit')}</span>
                    </button>
                )}
                {showEditLink && (
                    <Link href={editHref!} className={btnClass} title={t('edit', 'Edit')}>
                        <Pencil size={16} aria-hidden />
                        <span className="sr-only">{t('edit', 'Edit')}</span>
                    </Link>
                )}
                {onToggle && (
                    <button
                        type="button"
                        onClick={onToggle}
                        className={btnClass}
                        title={toggleTitle ?? toggleLabel ?? t('toggle', 'Toggle')}
                    >
                        {resolvedToggleOn ? (
                            <ToggleRight size={16} aria-hidden />
                        ) : (
                            <ToggleLeft size={16} aria-hidden />
                        )}
                        <span className="sr-only">{toggleLabel ?? t('toggle', 'Toggle')}</span>
                    </button>
                )}
                {onDelete && (
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        className={`${btnClass} hover:text-red-600 dark:hover:text-red-400`}
                        title={t('delete', 'Delete')}
                    >
                        <Trash2 size={16} aria-hidden />
                        <span className="sr-only">{t('delete', 'Delete')}</span>
                    </button>
                )}
            </div>
        </>
    );
}
