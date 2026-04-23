import React, { useEffect, useMemo, useState } from 'react';
import { Download, ExternalLink, FileText, X } from 'lucide-react';

export type FilePreviewModalProps = {
    open: boolean;
    onClose: () => void;
    /** Authenticated URL that returns the file (same-origin cookies apply). */
    fileUrl: string;
    title?: string;
    subtitle?: string;
    /** Stored path or filename, e.g. `wallet_proofs/abc.pdf`, used to pick image vs PDF viewer. */
    fileHint?: string | null;
};

type PreviewKind = 'image' | 'pdf' | 'unknown';

function detectKind(hint: string | null | undefined): PreviewKind {
    const h = (hint || '').toLowerCase();
    if (h.endsWith('.pdf')) {
        return 'pdf';
    }
    if (/\.(jpe?g|png|gif|webp|bmp|svg)$/.test(h)) {
        return 'image';
    }
    return 'unknown';
}

/**
 * Modal to preview images and PDFs without leaving the page. Reuse anywhere you have an authenticated file URL.
 */
export default function FilePreviewModal({
    open,
    onClose,
    fileUrl,
    title = 'File preview',
    subtitle,
    fileHint,
}: FilePreviewModalProps) {
    const kind = useMemo(() => detectKind(fileHint), [fileHint]);
    const [imageFailed, setImageFailed] = useState(false);

    useEffect(() => {
        if (open) {
            setImageFailed(false);
        }
    }, [open, fileUrl]);

    useEffect(() => {
        if (!open) {
            return;
        }
        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [open, onClose]);

    if (!open) {
        return null;
    }

    const showImage = kind === 'image' && !imageFailed;
    const showPdfFrame = kind === 'pdf' || kind === 'unknown' || imageFailed;

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6">
            <button
                type="button"
                className="fixed inset-0 bg-black/50 backdrop-blur-sm"
                aria-label="Close preview"
                onClick={onClose}
            />

            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="file-preview-title"
                className="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-800"
            >
                <div className="flex shrink-0 items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div className="min-w-0">
                        <h2 id="file-preview-title" className="text-lg font-semibold text-gray-900 dark:text-white">
                            {title}
                        </h2>
                        {subtitle ? (
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{subtitle}</p>
                        ) : null}
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        <a
                            href={fileUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            <ExternalLink className="h-4 w-4" />
                            New tab
                        </a>
                        <a
                            href={fileUrl}
                            download
                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            <Download className="h-4 w-4" />
                            Download
                        </a>
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            aria-label="Close"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div className="min-h-0 flex-1 overflow-auto bg-gray-100 dark:bg-gray-900/50 p-4">
                    {showImage ? (
                        <div className="flex min-h-[50vh] items-center justify-center">
                            <img
                                src={fileUrl}
                                alt=""
                                className="max-h-[75vh] w-auto max-w-full rounded-lg object-contain shadow-md"
                                onError={() => setImageFailed(true)}
                            />
                        </div>
                    ) : null}

                    {showPdfFrame ? (
                        <div className="flex min-h-[60vh] flex-col gap-3">
                            {imageFailed && kind === 'image' ? (
                                <p className="text-center text-sm text-amber-700 dark:text-amber-400">
                                    Could not display as image. Try PDF view or open in a new tab.
                                </p>
                            ) : null}
                            <div className="relative min-h-[65vh] flex-1 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800">
                                <iframe
                                    title={title}
                                    src={fileUrl}
                                    className="h-full min-h-[65vh] w-full"
                                />
                            </div>
                            <p className="flex items-center justify-center gap-2 text-center text-xs text-gray-500 dark:text-gray-400">
                                <FileText className="h-4 w-4 shrink-0" />
                                If the preview is blank, use <strong className="font-medium">New tab</strong> — some browsers block embedded PDFs.
                            </p>
                        </div>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
