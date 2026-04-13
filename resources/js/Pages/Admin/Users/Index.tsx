import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { Users, Search } from 'lucide-react';

type UserRow = {
    id: number;
    name: string;
    email: string;
    mobile: string | null;
    created_at: string | null;
    roles?: { name: string }[];
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    users: Paginated<UserRow>;
    filters: { search?: string; role?: string };
};

export default function Index({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [role, setRole] = useState(filters.role ?? '');

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/users', { search: search || undefined, role: role || undefined }, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title="Users" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Users' }]} />
                <div className="flex items-center gap-2">
                    <Users className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
                </div>
                <form onSubmit={applyFilters} className="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-xs text-gray-500 mb-1">Search</label>
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                placeholder="Name, email, mobile"
                                className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700"
                            />
                        </div>
                    </div>
                    <div className="w-48">
                        <label className="block text-xs text-gray-500 mb-1">Role</label>
                        <input
                            value={role}
                            onChange={e => setRole(e.target.value)}
                            placeholder="Spatie role name"
                            className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                        />
                    </div>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                        Filter
                    </button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">Name</th>
                                <th className="px-5 py-3">Email</th>
                                <th className="px-5 py-3">Mobile</th>
                                <th className="px-5 py-3">Roles</th>
                                <th className="px-5 py-3 w-24" />
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-5 py-12 text-center text-gray-500">No users found.</td>
                                </tr>
                            ) : (
                                users.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td className="px-5 py-3 font-medium">{row.name}</td>
                                        <td className="px-5 py-3">{row.email}</td>
                                        <td className="px-5 py-3">{row.mobile || '—'}</td>
                                        <td className="px-5 py-3 text-xs">
                                            {(row.roles || []).map(r => r.name).join(', ') || '—'}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <ActionButtons viewHref={`/panel/users/${row.id}`} />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {users.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm text-gray-500">
                            <span>{users.from}–{users.to} of {users.total}</span>
                            <div className="flex gap-2">
                                {users.current_page > 1 && (
                                    <Link
                                        href={`/panel/users?page=${users.current_page - 1}&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {users.current_page < users.last_page && (
                                    <Link
                                        href={`/panel/users?page=${users.current_page + 1}&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
