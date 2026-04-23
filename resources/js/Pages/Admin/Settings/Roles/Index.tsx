import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { Shield, Pencil } from 'lucide-react';

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
};

type Props = {
    roles: RoleRow[];
};

export default function Index({ roles }: Props) {
    return (
        <AdminLayout>
            <Head title="Roles & permissions" />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs
                    items={[
                        { label: 'Settings', href: '/panel/settings' },
                        { label: 'Roles & permissions' },
                    ]}
                />
                <div className="flex items-center gap-2">
                    <Shield className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Roles & permissions</h1>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    Assign Spatie permissions to each role. Users receive permissions through their roles.
                </p>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3 font-medium">Role</th>
                                <th className="px-5 py-3 font-medium">Permissions</th>
                                <th className="px-5 py-3 font-medium w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {roles.length === 0 ? (
                                <tr>
                                    <td colSpan={3} className="px-5 py-8 text-center text-gray-500">
                                        No roles found.
                                    </td>
                                </tr>
                            ) : (
                                roles.map((r) => (
                                    <tr
                                        key={r.id}
                                        className="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                                    >
                                        <td className="px-5 py-3 font-mono text-sm text-gray-900 dark:text-white">
                                            {r.name}
                                        </td>
                                        <td className="px-5 py-3 text-gray-600 dark:text-gray-300 tabular-nums">
                                            {r.permissions_count}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <Link
                                                href={`/panel/settings/roles/${r.id}/edit`}
                                                className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                            >
                                                <Pencil size={14} />
                                                Edit
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
