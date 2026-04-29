import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type User = {
    name: string;
    email: string | null;
    mobile: string | null;
    billing_address?: string | null;
    billing_address_two?: string | null;
    billing_city?: string | null;
    billing_state?: string | null;
    billing_zip?: string | null;
    billing_country?: string | null;
};

export default function Profile() {
    const page = usePage<{ auth: { user: User | null } }>();
    const u = page.props.auth?.user;
    const returnTo = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('return_to') || '' : '';
    const { data, setData, post, processing, errors } = useForm({
        name: u?.name ?? '',
        email: u?.email ?? '',
        mobile: u?.mobile ?? '',
        billing_address: u?.billing_address ?? '',
        billing_address_two: u?.billing_address_two ?? '',
        billing_city: u?.billing_city ?? '',
        billing_state: u?.billing_state ?? '',
        billing_zip: u?.billing_zip ?? '',
        billing_country: u?.billing_country ?? 'IN',
        return_to: returnTo,
    });

    if (!u) {
        return null;
    }

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/update-profile');
    };

    return (
        <StorefrontLayout>
            <Head title="Profile" />
            <div className="mx-auto max-w-lg px-4 py-10">
                <nav className="mb-6 flex flex-wrap gap-4 text-sm text-gray-600">
                    <span className="font-medium text-gray-900">Profile</span>
                    <Link href={paths.myOrders} className="hover:text-brand-600">
                        My orders
                    </Link>
                </nav>
                <h1 className="text-2xl font-bold text-gray-900">Your profile</h1>
                <form onSubmit={submit} className="mt-6 space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <label className="text-sm font-medium text-gray-700">Name</label>
                        <input
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoComplete="email"
                        />
                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">Mobile</label>
                        <input
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.mobile}
                            onChange={(e) => setData('mobile', e.target.value)}
                        />
                        {errors.mobile && <p className="text-sm text-red-600">{errors.mobile}</p>}
                    </div>

                    <div className="border-t border-gray-200 pt-4">
                        <h2 className="text-sm font-semibold text-gray-900">Billing details</h2>
                        <p className="mt-1 text-xs text-gray-500">Used for provider/payment validation during checkout.</p>
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-700">Address line 1</label>
                        <input
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.billing_address}
                            onChange={(e) => setData('billing_address', e.target.value)}
                        />
                        {errors.billing_address && <p className="text-sm text-red-600">{errors.billing_address}</p>}
                    </div>

                    <div>
                        <label className="text-sm font-medium text-gray-700">Address line 2</label>
                        <input
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.billing_address_two}
                            onChange={(e) => setData('billing_address_two', e.target.value)}
                        />
                        {errors.billing_address_two && <p className="text-sm text-red-600">{errors.billing_address_two}</p>}
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-gray-700">City</label>
                            <input
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                                value={data.billing_city}
                                onChange={(e) => setData('billing_city', e.target.value)}
                            />
                            {errors.billing_city && <p className="text-sm text-red-600">{errors.billing_city}</p>}
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700">State</label>
                            <input
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                                value={data.billing_state}
                                onChange={(e) => setData('billing_state', e.target.value)}
                            />
                            {errors.billing_state && <p className="text-sm text-red-600">{errors.billing_state}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="text-sm font-medium text-gray-700">ZIP/Pincode</label>
                            <input
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                                value={data.billing_zip}
                                onChange={(e) => setData('billing_zip', e.target.value)}
                            />
                            {errors.billing_zip && <p className="text-sm text-red-600">{errors.billing_zip}</p>}
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700">Country code</label>
                            <input
                                className="mt-1 w-full rounded-lg border px-3 py-2 uppercase"
                                value={data.billing_country}
                                onChange={(e) => setData('billing_country', e.target.value.toUpperCase())}
                                maxLength={4}
                            />
                            {errors.billing_country && <p className="text-sm text-red-600">{errors.billing_country}</p>}
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        Save                    </button>
                </form>
            </div>
        </StorefrontLayout>
    );
}
