import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';

export default function Contact() {
    const page = usePage<{
        company?: { email?: string; contact_no?: string; address?: string };
        flash?: { success?: string | null };
    }>();
    const c = page.props.company;
    const flashOk = page.props.flash?.success;

    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        name: '',
        email: '',
        contact_number: '',
        message: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/save-contact');
    };

    return (
        <StorefrontLayout>
            <Head title="Contact" />
            <div className="mx-auto max-w-5xl px-4 py-10">
                <div className="grid gap-10 lg:grid-cols-2">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Contact us</h1>
                        <p className="mt-4 text-gray-700">
                            Questions about an order, a brand, or corporate gifting? Reach out — we are happy to help.
                        </p>
                        <ul className="mt-6 space-y-2 text-sm text-gray-600">
                            <li>
                                Email:{' '}
                                <a href={`mailto:${c?.email}`} className="text-brand-600 hover:underline">
                                    {c?.email}
                                </a>
                            </li>
                            <li>Phone: {c?.contact_no}</li>
                            <li className="whitespace-pre-line">{c?.address}</li>
                        </ul>
                    </div>
                    <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        {wasSuccessful || flashOk ? (
                            <p className="mb-4 text-emerald-700">{flashOk ?? 'Thanks — we have received your message.'}</p>
                        ) : null}
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label className="text-sm font-medium text-gray-700">Name</label>
                                <input
                                    className="mt-1 w-full rounded-lg border px-3 py-2"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
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
                                    required
                                />
                                {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700">Contact number</label>
                                <input
                                    className="mt-1 w-full rounded-lg border px-3 py-2"
                                    value={data.contact_number}
                                    onChange={(e) => setData('contact_number', e.target.value)}
                                    required
                                />
                                {errors.contact_number && <p className="text-sm text-red-600">{errors.contact_number}</p>}
                            </div>
                            <div>
                                <label className="text-sm font-medium text-gray-700">Message</label>
                                <textarea
                                    className="mt-1 w-full rounded-lg border px-3 py-2"
                                    rows={4}
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    required
                                />
                                {errors.message && <p className="text-sm text-red-600">{errors.message}</p>}
                            </div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
