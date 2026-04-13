import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Eye, Pencil, Trash2, ToggleLeft, ToggleRight } from 'lucide-react';
import ConfirmDialog from './ConfirmDialog';

export type ActionButtonsProps = {
    /** Detail / read-first link */
    viewHref?: string;
    /** Edit link */
    editHref?: string;
    /** Called after user confirms delete in the built-in dialog */
    onDelete?: () => void;
    deleteConfirmTitle?: string;
    deleteConfirmMessage?: string;
    /** e.g. toggle active/inactive */
    onToggle?: () => void;
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
    onDelete,
    deleteConfirmTitle = 'Delete this item?',
    deleteConfirmMessage = 'This cannot be undone.',
    onToggle,
    toggleLabel,
    toggleTitle,
    className = '',
}: ActionButtonsProps) {
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const showView = Boolean(viewHref);
    const showEdit = Boolean(editHref);

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

    if (!viewHref && !editHref && !onToggle && !onDelete) {
        return <span className="text-gray-400 text-xs">—</span>;
    }

    return (
        <>
            <ConfirmDialog
                open={deleteOpen}
                onClose={() => setDeleteOpen(false)}
                onConfirm={handleDeleteConfirm}
                title={deleteConfirmTitle}
                message={deleteConfirmMessage}
                confirmLabel="Delete"
                variant="danger"
                loading={deleting}
            />
            <div className={`inline-flex flex-wrap items-center gap-0.5 ${className}`}>
                {showView && (
                    <Link href={viewHref!} className={btnClass} title="View">
                        <Eye size={16} aria-hidden />
                        <span className="sr-only">View</span>
                    </Link>
                )}
                {showEdit && (
                    <Link href={editHref!} className={btnClass} title="Edit">
                        <Pencil size={16} aria-hidden />
                        <span className="sr-only">Edit</span>
                    </Link>
                )}
                {onToggle && (
                    <button
                        type="button"
                        onClick={onToggle}
                        className={btnClass}
                        title={toggleTitle ?? toggleLabel ?? 'Toggle'}
                    >
                        {toggleLabel?.toLowerCase().includes('deactiv') ? (
                            <ToggleLeft size={16} aria-hidden />
                        ) : (
                            <ToggleRight size={16} aria-hidden />
                        )}
                        <span className="sr-only">{toggleLabel ?? 'Toggle'}</span>
                    </button>
                )}
                {onDelete && (
                    <button
                        type="button"
                        onClick={() => setDeleteOpen(true)}
                        className={`${btnClass} hover:text-red-600 dark:hover:text-red-400`}
                        title="Delete"
                    >
                        <Trash2 size={16} aria-hidden />
                        <span className="sr-only">Delete</span>
                    </button>
                )}
            </div>
        </>
    );
}
