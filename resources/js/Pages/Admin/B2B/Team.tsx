import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type Member = {
    id: number;
    name: string | null;
    email: string | null;
    mobile: string | null;
    role: string | null;
    is_primary: boolean;
};

type Props = {
    tenant: { id: number; name: string } | null;
    members: Member[];
};

export default function Team({ tenant, members }: Props) {
    return (
        <AdminLayout>
            <Head title="Team" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Team</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Users linked to your B2B tenant and their roles on the account.
                    </p>
                </div>
                {!tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        No tenant linked to this user.
                    </div>
                ) : members.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-500 dark:border-gray-600 dark:bg-gray-800/50 dark:text-gray-400">
                        No team members found for {tenant.name}.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-100 bg-gray-50 text-left text-gray-500 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                    <th className="px-5 py-3 font-medium">Name</th>
                                    <th className="px-5 py-3 font-medium">Email</th>
                                    <th className="px-5 py-3 font-medium">Mobile</th>
                                    <th className="px-5 py-3 font-medium">Role</th>
                                    <th className="px-5 py-3 font-medium">Primary</th>
                                </tr>
                            </thead>
                            <tbody>
                                {members.map((m) => (
                                    <tr key={m.id} className="border-t border-gray-100 dark:border-gray-700">
                                        <td className="px-5 py-3 font-medium text-gray-900 dark:text-white">{m.name ?? '—'}</td>
                                        <td className="px-5 py-3">{m.email ?? '—'}</td>
                                        <td className="px-5 py-3">{m.mobile ?? '—'}</td>
                                        <td className="px-5 py-3 capitalize">{m.role ?? '—'}</td>
                                        <td className="px-5 py-3">{m.is_primary ? 'Yes' : '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
