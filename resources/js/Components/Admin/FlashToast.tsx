import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle, XCircle, AlertTriangle, X } from 'lucide-react';

type FlashType = 'success' | 'error' | 'warning';

type Toast = {
    id: number;
    type: FlashType;
    message: string;
};

const icons: Record<FlashType, React.ElementType> = {
    success: CheckCircle,
    error: XCircle,
    warning: AlertTriangle,
};

const styles: Record<FlashType, string> = {
    success: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
    error: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
    warning: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
};

let nextId = 0;

export default function FlashToast() {
    const { flash } = usePage<{ flash: Record<string, string | null> }>().props;
    const [toasts, setToasts] = useState<Toast[]>([]);

    useEffect(() => {
        const newToasts: Toast[] = [];

        (['success', 'error', 'warning'] as FlashType[]).forEach(type => {
            if (flash?.[type]) {
                newToasts.push({ id: nextId++, type, message: flash[type]! });
            }
        });

        if (newToasts.length > 0) {
            setToasts(prev => [...prev, ...newToasts]);
        }
    }, [flash]);

    useEffect(() => {
        if (toasts.length === 0) return;

        const timer = setTimeout(() => {
            setToasts(prev => prev.slice(1));
        }, 5000);

        return () => clearTimeout(timer);
    }, [toasts]);

    const dismiss = (id: number) => {
        setToasts(prev => prev.filter(t => t.id !== id));
    };

    if (toasts.length === 0) return null;

    return (
        <div className="fixed top-4 right-4 z-[100] space-y-2 max-w-sm w-full pointer-events-none">
            {toasts.map(toast => {
                const Icon = icons[toast.type];
                return (
                    <div
                        key={toast.id}
                        className={`pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-lg border shadow-lg animate-slide-in-right ${styles[toast.type]}`}
                    >
                        <Icon size={18} className="flex-shrink-0 mt-0.5" />
                        <p className="flex-1 text-sm">{toast.message}</p>
                        <button
                            onClick={() => dismiss(toast.id)}
                            className="flex-shrink-0 opacity-60 hover:opacity-100"
                        >
                            <X size={14} />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
