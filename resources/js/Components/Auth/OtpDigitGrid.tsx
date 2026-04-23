import React, { forwardRef, useCallback, useImperativeHandle, useRef, type Dispatch, type SetStateAction } from 'react';

export type OtpDigitGridHandle = {
    focus: (index: number) => void;
};

export type OtpDigitGridProps = {
    length?: number;
    value: string[];
    onChange: Dispatch<SetStateAction<string[]>>;
    disabled?: boolean;
    className?: string;
    inputClassName?: string;
};

function normalizeCells(value: string[], length: number): string[] {
    const out = value.slice(0, length);
    while (out.length < length) out.push('');
    return out;
}

export const OtpDigitGrid = forwardRef<OtpDigitGridHandle, OtpDigitGridProps>(function OtpDigitGrid(
    { length = 6, value, onChange, disabled = false, className, inputClassName },
    ref,
) {
    const cells = normalizeCells(value, length);
    const otpRefs = useRef<Array<HTMLInputElement | null>>([]);

    const focusOtp = useCallback(
        (index: number) => {
            const i = Math.max(0, Math.min(length - 1, index));
            const byRef = otpRefs.current[i];
            const byQuery =
                (document.querySelector(`input[data-otp-index="${i}"]`) as HTMLInputElement | null) ?? null;
            const target = byRef ?? byQuery;
            if (target) {
                target.focus();
                // Do not call select(): selecting the previous digit on backspace makes the first
                // Backspace feel like it only “highlights” the digit; a second press is needed to clear.
                const len = target.value.length;
                if (len > 0) {
                    try {
                        target.setSelectionRange(len, len);
                    } catch {
                        /* setSelectionRange can throw on some input types in edge cases */
                    }
                }
            }
        },
        [length],
    );

    useImperativeHandle(
        ref,
        () => ({
            focus: (index: number) => focusOtp(index),
        }),
        [focusOtp],
    );

    const setOtpDigit = useCallback(
        (index: number, rawValue: string) => {
            const v = rawValue.replace(/\D/g, '').slice(-1);
            const next = normalizeCells(value, length);
            next[index] = v;
            onChange(next);
        },
        [length, onChange, value],
    );

    return (
        <div className={className ?? 'flex justify-center gap-2'}>
            {cells.map((d, i) => (
                <input
                    key={i}
                    ref={(el) => {
                        otpRefs.current[i] = el;
                    }}
                    type="tel"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    autoComplete={i === 0 ? 'one-time-code' : 'off'}
                    data-otp-index={i}
                    maxLength={1}
                    disabled={disabled}
                    className={inputClassName ?? 'h-10 w-10 rounded border text-center'}
                    value={d}
                    onChange={(e) => setOtpDigit(i, e.target.value)}
                    onKeyUp={(e) => {
                        const target = e.currentTarget;
                        if (/^[0-9]$/.test(e.key) && target.value && i < length - 1) {
                            setTimeout(() => focusOtp(i + 1), 0);
                        }
                    }}
                    onKeyDown={(e) => {
                        if (e.key === 'Backspace') {
                            e.preventDefault();
                            onChange((prev) => {
                                const n = normalizeCells(prev, length);
                                if (n[i]) {
                                    n[i] = '';
                                    return n;
                                }
                                if (i > 0) {
                                    n[i - 1] = '';
                                    requestAnimationFrame(() => focusOtp(i - 1));
                                }
                                return n;
                            });
                            return;
                        }
                        if (e.key === 'ArrowLeft' && i > 0) {
                            e.preventDefault();
                            setTimeout(() => focusOtp(i - 1), 0);
                            return;
                        }
                        if (e.key === 'ArrowRight' && i < length - 1) {
                            e.preventDefault();
                            setTimeout(() => focusOtp(i + 1), 0);
                        }
                    }}
                    onPaste={(e) => {
                        const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, length);
                        if (!pasted) return;
                        e.preventDefault();
                        onChange(() => {
                            const n = normalizeCells([], length);
                            for (let idx = 0; idx < length; idx++) {
                                n[idx] = pasted[idx] ?? '';
                            }
                            return n;
                        });
                        const nextIndex = Math.min(pasted.length, length - 1);
                        setTimeout(() => focusOtp(nextIndex), 0);
                    }}
                />
            ))}
        </div>
    );
});
