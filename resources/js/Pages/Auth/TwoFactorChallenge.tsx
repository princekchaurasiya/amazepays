import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Shield } from 'lucide-react';

export default function TwoFactorChallenge() {
    const form = useForm({ code: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/panel/2fa/verify');
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-slate-50 px-4 py-12">
            <Head title="Two-factor challenge" />
            <div className="w-full max-w-[420px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-5">
                    <div className="flex items-center gap-2">
                        <Shield className="text-indigo-600" size={26} aria-hidden />
                        <h1 className="text-xl font-semibold text-indigo-950">Verify your identity</h1>
                    </div>
                    <p className="mt-2 text-sm text-slate-500">
                        Enter the 6-digit code from your authenticator app, or a 10-character recovery code.
                    </p>
                </div>
                <div className="px-6 py-6">
                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <label htmlFor="two-fa-code" className="block text-sm font-semibold text-slate-700">
                                Code
                            </label>
                            <input
                                id="two-fa-code"
                                value={form.data.code}
                                onChange={e => form.setData('code', e.target.value)}
                                className="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-mono text-lg text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                autoComplete="one-time-code"
                                autoFocus
                            />
                            {form.errors.code && <p className="mt-1 text-xs text-red-600">{form.errors.code}</p>}
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-lg bg-indigo-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Continue
                        </button>
                    </form>
                    <p className="mt-6 text-center text-sm text-slate-500">
                        <Link href="/" className="font-medium text-indigo-600 hover:text-indigo-800">
                            Back to site
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
