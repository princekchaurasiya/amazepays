import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function ChangePassword() {
    const { data, setData, post, processing, errors } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/change-password-update');
    };

    return (
        <StorefrontLayout>
            <Head title="Change password" />
            <div className="mx-auto max-w-lg px-4 py-10">
                <nav className="mb-6 flex gap-4 text-sm text-gray-600">
                    <Link href={paths.profile} className="hover:text-brand-600">
                        Profile
                    </Link>
                    <Link href={paths.myOrders} className="hover:text-brand-600">
                        My orders
                    </Link>
                    <span className="font-medium text-gray-900">Change password</span>
                </nav>
                <h1 className="text-2xl font-bold text-gray-900">Change password</h1>
                <form onSubmit={submit} className="mt-6 space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <label className="text-sm font-medium text-gray-700">Current password</label>
                        <input
                            type="password"
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                        />
                        {errors.current_password && <p className="text-sm text-red-600">{errors.current_password}</p>}
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">New password</label>
                        <input
                            type="password"
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">Confirm</label>
                        <input
                            type="password"
                            className="mt-1 w-full rounded-lg border px-3 py-2"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        Update password
                    </button>
                </form>
            </div>
        </StorefrontLayout>
    );
}
