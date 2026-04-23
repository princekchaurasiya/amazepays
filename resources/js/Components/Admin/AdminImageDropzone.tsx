import React, { useCallback, useId, useRef, useState } from 'react';
import { Upload, X } from 'lucide-react';
import { AdminUploadDropzoneSurface } from './AdminUploadDropzoneSurface';

export type AdminImageDropzoneProps = {
    /** Optional heading above the dropzone (e.g. “Image gallery”). */
    title?: string;
    /** Line inside the dashed area (default: image types + 5MB). */
    primaryText?: string;
    /** Muted helper below the dashed area. */
    hintText?: string;
    accept?: string;
    disabled?: boolean;
    busy?: boolean;
    busyLabel?: string;
    error?: string;
    className?: string;
    /** Client-side max size per file (bytes). */
    maxFileBytes?: number;
    compact?: boolean;
} & (
    | {
          multiple: true;
          files: File[];
          onFilesChange: (files: File[]) => void;
      }
    | {
          multiple?: false;
          /** Controlled single-file selection (forms). */
          file?: File | null;
          onFileChange?: (f: File | null) => void;
          /** Fire immediately when a file is chosen (e.g. Inertia upload); ignores file display state. */
          onFileSelected?: (file: File) => void;
      }
);

const DEFAULT_PRIMARY = 'Upload image (PNG, JPG, WebP — max 5MB)';

function oversizeMessage(maxBytes: number): string {
    const mb = maxBytes / (1024 * 1024);
    if (mb >= 1) {
        return `Each file must be at most ${mb % 1 === 0 ? mb : mb.toFixed(1)} MB.`;
    }
    return `Each file must be at most ${Math.round(maxBytes / 1024)} KB.`;
}

export default function AdminImageDropzone(props: AdminImageDropzoneProps) {
    const {
        title,
        primaryText = DEFAULT_PRIMARY,
        hintText,
        accept = 'image/*',
        disabled = false,
        busy = false,
        busyLabel = 'Uploading…',
        error: externalError,
        className = '',
        maxFileBytes,
        compact = false,
    } = props;

    const multiple = 'multiple' in props && props.multiple === true;
    const files = multiple ? props.files : undefined;
    const onFilesChange = multiple ? props.onFilesChange : undefined;
    const file = !multiple ? props.file ?? null : undefined;
    const onFileChange = !multiple ? props.onFileChange : undefined;
    const onFileSelected = !multiple ? props.onFileSelected : undefined;

    const inputRef = useRef<HTMLInputElement>(null);
    const reactId = useId();
    const inputId = `admin-image-upload-${reactId}`;
    const [isDragging, setIsDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    const error = externalError || localError || undefined;
    const inactive = disabled || busy;

    const openPicker = useCallback(() => {
        if (!inactive) {
            inputRef.current?.click();
        }
    }, [inactive]);

    const clearLocalError = () => setLocalError(null);

    const validateSize = (list: File[]): string | null => {
        if (!maxFileBytes) {
            return null;
        }
        for (const f of list) {
            if (f.size > maxFileBytes) {
                return oversizeMessage(maxFileBytes);
            }
        }
        return null;
    };

    const applySingle = (list: File[]) => {
        const first = list[0];
        if (!first) {
            return;
        }
        const msg = validateSize([first]);
        if (msg) {
            setLocalError(msg);
            return;
        }
        clearLocalError();
        if (onFileSelected) {
            onFileSelected(first);
        } else if (onFileChange) {
            onFileChange(first);
        }
    };

    const applyMultiple = (list: File[]) => {
        const msg = validateSize(list);
        if (msg) {
            setLocalError(msg);
            return;
        }
        clearLocalError();
        onFilesChange?.(list);
    };

    const handleFileList = (list: File[]) => {
        if (inactive || list.length === 0) {
            return;
        }
        if (multiple) {
            applyMultiple(list);
        } else {
            applySingle(list);
        }
    };

    const onInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const picked = e.target.files;
        if (!picked?.length) {
            return;
        }
        const arr = Array.from(picked);
        // Always snapshot selected files before clearing the native input.
        // Some browsers can invalidate the FileList if value is reset first.
        if (inputRef.current) {
            inputRef.current.value = '';
        }
        handleFileList(arr);
    };

    const onDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (!inactive) {
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
        if (inactive) {
            return;
        }
        const dt = e.dataTransfer.files;
        if (!dt?.length) {
            return;
        }
        const arr = Array.from(dt);
        handleFileList(arr);
    };

    const onKeyDown = (e: React.KeyboardEvent) => {
        if (inactive) {
            return;
        }
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openPicker();
        }
    };

    const clearSingle = (e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        clearLocalError();
        onFileChange?.(null);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    const ariaLabel = busy
        ? busyLabel
        : multiple && files && files.length
            ? `${files.length} file(s) selected. Click or drop to replace.`
            : !multiple && file
                ? `Selected: ${file.name}. Click to replace.`
                : 'Upload image or drag and drop';

    const showClear =
        !multiple && !onFileSelected && file && onFileChange && !inactive;

    const innerLine = busy
        ? busyLabel
        : !multiple && file && !onFileSelected
            ? file.name
            : primaryText;

    return (
        <div className={`space-y-1.5 ${className}`}>
            {title ? (
                <h3 className="text-sm font-semibold text-gray-900 dark:text-white">{title}</h3>
            ) : null}

            <input
                ref={inputRef}
                id={inputId}
                type="file"
                accept={accept}
                multiple={multiple}
                disabled={inactive}
                className="sr-only"
                onChange={onInputChange}
                aria-invalid={!!error}
                aria-describedby={error ? `${inputId}-error` : undefined}
            />

            <div className="relative">
                <AdminUploadDropzoneSurface
                    disabled={inactive}
                    isDragging={isDragging}
                    error={!!externalError || !!localError}
                    compact={compact}
                    onClick={openPicker}
                    onKeyDown={onKeyDown}
                    onDragOver={onDragOver}
                    onDragLeave={onDragLeave}
                    onDrop={onDrop}
                    ariaLabel={ariaLabel}
                    className="min-w-0 !justify-start gap-3"
                >
                    <Upload
                        size={20}
                        className="shrink-0 text-gray-400 dark:text-gray-500"
                        strokeWidth={2}
                        aria-hidden
                    />
                    <span
                        className="min-w-0 flex-1 truncate text-sm text-gray-600 dark:text-gray-400"
                        title={innerLine}
                    >
                        {innerLine}
                    </span>
                    {showClear ? (
                        <button
                            type="button"
                            onClick={clearSingle}
                            className="ml-auto shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-200/80 hover:text-red-600 dark:hover:bg-gray-700 dark:hover:text-red-400"
                            aria-label="Remove file"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    ) : null}
                </AdminUploadDropzoneSurface>
            </div>

            {hintText ? (
                <p className="text-center text-xs text-gray-500 dark:text-gray-400">{hintText}</p>
            ) : null}

            {error ? (
                <p id={`${inputId}-error`} className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
