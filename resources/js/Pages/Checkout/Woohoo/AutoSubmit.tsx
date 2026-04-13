import React, { useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';

export default function WoohooAutoSubmit({ submitUrl }: { submitUrl: string }) {
    const formRef = useRef<HTMLFormElement>(null);

    useEffect(() => {
        const t = window.setTimeout(() => formRef.current?.submit(), 400);
        return () => window.clearTimeout(t);
    }, []);

    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    return (
        <CheckoutLayout>
            <Head title="Redirecting…" />
            <div className="mx-auto max-w-md px-4 py-20 text-center">
                <div className="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-gray-200 border-t-brand-600" />
                <p className="mt-6 text-sm text-gray-600">
                    Please do not refresh or press back. We are creating your order…
                </p>
                <form ref={formRef} method="post" action={submitUrl} className="hidden">
                    <input type="hidden" name="_token" value={token} />
                </form>
            </div>
        </CheckoutLayout>
    );
}
