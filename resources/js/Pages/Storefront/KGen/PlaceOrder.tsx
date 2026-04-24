import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function KGenPlaceOrder({
    variantId = '',
    mrp = '',
}: {
    variantId?: string;
    mrp?: string;
}) {
    const { data, setData, post, processing, errors } = useForm({
        variantId: variantId || '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/kgen-place-order');
    };

    return (
        <StorefrontLayout>
            <Head title="Place KGen order" />
            <div className="mx-auto max-w-lg px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">Place order</h1>
                {mrp ? <p className="mt-2 text-sm text-gray-600">Indicative MRP: &#8377;{mrp}</p> : null}
                <form onSubmit={submit} className="mt-6 space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <label className="text-sm font-medium text-gray-700">Variant ID</label>
                        <input
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.variantId}
                            onChange={(e) => setData('variantId', e.target.value)}
                            required
                        />
                        {errors.variantId ? <p className="mt-1 text-sm text-red-600">{errors.variantId}</p> : null}
                    </div>
                    {(errors as Record<string, unknown>).error ? (
                        <p className="text-sm text-red-600">{String((errors as Record<string, unknown>).error)}</p>
                    ) : null}
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        Submit
                    </button>
                </form>
                <p className="mt-4 text-sm text-gray-600">
                    <a href={paths.kgenProducts} className="text-brand-600 hover:underline">
                        Back to catalog
                    </a>
                </p>
            </div>
        </StorefrontLayout>
    );
}
