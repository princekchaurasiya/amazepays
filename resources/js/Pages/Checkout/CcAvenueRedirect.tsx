import React, { useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';

/**
 * Auto-POST to CCAvenue hosted checkout (encRequest + access_code).
 */
export default function CcAvenueRedirect({
    action,
    encRequest,
    accessCode,
}: {
    action: string;
    encRequest: string;
    accessCode: string;
}) {
    const formRef = useRef<HTMLFormElement>(null);

    useEffect(() => {
        const t = window.setTimeout(() => formRef.current?.submit(), 300);
        return () => window.clearTimeout(t);
    }, []);

    return (
        <CheckoutLayout>
            <Head title="Redirecting to payment…" />
            <div className="mx-auto max-w-md px-4 py-20 text-center">
                <div className="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-gray-200 border-t-brand-600" />
                <p className="mt-6 text-sm text-gray-600">Redirecting to secure payment. Please wait…</p>
                <form ref={formRef} method="post" action={action} className="hidden">
                    <input type="hidden" name="encRequest" value={encRequest} />
                    <input type="hidden" name="access_code" value={accessCode} />
                </form>
            </div>
        </CheckoutLayout>
    );
}
