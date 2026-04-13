import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type User = {
    name: string;
    email: string | null;
    mobile: string | null;
};

export default function Profile() {
    const page = usePage<{ auth: { user: User | null } }>();
    const u = page.props.auth?.user;
    if (!u) {
        return null;
    }

    const { data, setData, post, processing, errors } = useForm({
        name: u.name ?? '',
        email: u.email ?? '',
        mobile: u.mobile ?? '',
    });

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
                    <Link href={paths.changePassword} className="hover:text-brand-600">
                        Change password
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
