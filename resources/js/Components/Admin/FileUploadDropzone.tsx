import React, { useCallback, useId, useRef, useState } from 'react';
import { Upload, X } from 'lucide-react';
import { AdminUploadDropzoneSurface } from './AdminUploadDropzoneSurface';

export type FileUploadDropzoneProps = {
    /** Selected file (controlled). */
    value: File | null;
    onChange: (file: File | null) => void;
    /** `accept` string for the underlying input, e.g. images or PDFs. */
    accept?: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string;
    className?: string;
    /** Dropzone min height */
    compact?: boolean;
};

function formatBytes(n: number): string {
    if (n < 1024) {
        return `${n} B`;
    }
    if (n < 1024 * 1024) {
        return `${(n / 1024).toFixed(1)} KB`;
    }
    return `${(n / (1024 * 1024)).toFixed(1)} MB`;
}

/**
 * Accessible file picker with the same dashed dropzone shell as admin image uploads.
 * Use for payment proofs, PDFs, and mixed document types.
 */
export default function FileUploadDropzone({
    value,
    onChange,
    accept,
    label = 'Upload file',
    description,
    disabled = false,
    required = false,
    error,
    className = '',
    compact = false,
}: FileUploadDropzoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const reactId = useId();
    const inputId = `file-upload-${reactId}`;
    const [isDragging, setIsDragging] = useState(false);

    const openPicker = useCallback(() => {
        if (!disabled) {
            inputRef.current?.click();
        }
    }, [disabled]);

    const onInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const f = e.target.files?.[0] ?? null;
        onChange(f);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    const clear = (e: React.MouseEvent<HTMLButtonElement>) => {
        e.preventDefault();
        e.stopPropagation();
        onChange(null);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    const onDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (!disabled) {
            setIsDragging(true);
        }
    };

    const onDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
    };

    const onDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
        if (disabled) {
            return;
        }
        const f = e.dataTransfer.files?.[0];
        if (f) {
            onChange(f);
        }
    };

    const onKeyDown = (e: React.KeyboardEvent) => {
        if (disabled) {
            return;
        }
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openPicker();
        }
    };

    const ariaLabel = value ? `Selected file: ${value.name}. Click to replace.` : 'Choose file or drag and drop';

    return (
        <div className={`space-y-1.5 ${className}`}>
            {label ? (
                <label htmlFor={inputId} className="block text-xs font-medium text-gray-600 dark:text-gray-300">
                    {label}
                    {required ? <span className="text-red-500 ml-0.5">*</span> : null}
                </label>
            ) : null}
            {description ? (
                <p className="text-xs text-gray-500 dark:text-gray-400">{description}</p>
            ) : null}

            <input
                ref={inputRef}
                id={inputId}
                type="file"
                accept={accept}
                disabled={disabled}
                className="sr-only"
                onChange={onInputChange}
                aria-invalid={!!error}
                aria-describedby={error ? `${inputId}-error` : undefined}
                aria-required={required}
            />

            <AdminUploadDropzoneSurface
                disabled={disabled}
                isDragging={isDragging}
                error={!!error}
                compact={compact}
                onClick={openPicker}
                onKeyDown={onKeyDown}
                onDragOver={onDragOver}
                onDragLeave={onDragLeave}
                onDrop={onDrop}
                ariaLabel={ariaLabel}
                className="!justify-start"
            >
                <div className="flex w-full min-w-0 items-center gap-3">
                    <Upload
                        className="h-5 w-5 shrink-0 text-gray-400 dark:text-gray-500"
                        strokeWidth={2}
                        aria-hidden
                    />
                    <div className="min-w-0 flex-1 space-y-0.5 text-left">
                        {value ? (
                            <>
                                <p
                                    className="truncate text-sm font-medium text-gray-900 dark:text-white"
                                    title={value.name}
                                >
                                    {value.name}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400">{formatBytes(value.size)}</p>
                            </>
                        ) : (
                            <>
                                <p className="text-sm font-medium text-gray-800 dark:text-gray-100">
                                    <span className="text-indigo-600 dark:text-indigo-400 group-hover:underline">
                                        Choose a file
                                    </span>
                                    <span className="font-normal text-gray-500 dark:text-gray-400"> or drag and drop</span>
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {accept ? `Accepted: ${accept.replace(/,/g, ', ')}` : 'PDF, PNG, JPG up to 2 MB'}
                                </p>
                            </>
                        )}
                    </div>
                    {value && !disabled ? (
                        <button
                            type="button"
                            onClick={e => clear(e)}
                            className="shrink-0 rounded-lg p-2 text-gray-400 hover:bg-gray-200/80 hover:text-red-600 dark:hover:bg-gray-700 dark:hover:text-red-400"
                            aria-label="Remove file"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    ) : null}
                </div>
            </AdminUploadDropzoneSurface>

            {error ? (
                <p id={`${inputId}-error`} className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
