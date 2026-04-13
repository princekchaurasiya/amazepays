import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';

export default function VerifyEmail() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        code: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/verify-email');
    };

    return (
        <AuthLayout>
            <Head title="Verify email" />
            <div className="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                <h1 className="text-xl font-bold text-gray-900">Verify your email</h1>
                <p className="mt-2 text-sm text-gray-600">Enter your email and the 6-digit code we sent.</p>
                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Code</label>
                        <input
                            className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            maxLength={6}
                            required
                        />
                        {errors.code && <p className="mt-1 text-sm text-red-600">{errors.code}</p>}
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        Verify
                    </button>
                </form>
            </div>
        </AuthLayout>
    );
}
