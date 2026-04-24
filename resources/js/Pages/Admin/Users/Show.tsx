import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type UserT = {
    id: number;
    name: string;
    email: string;
    mobile: string | null;
    account_locked?: boolean;
    account_locked_reason?: string | null;
    roles?: { name: string }[];
    wallet?: { id: number; balance: string | number };
};

type OrderLite = {
    id: number;
    order_number: string | null;
    status: string | null;
    grand_total: string | number | null;
    created_at: string | null;
};

type Props = {
    user: UserT;
    orders: OrderLite[];
    assignableRoles: string[];
    canAssignRoles: boolean;
};

export default function Show({ user, orders, assignableRoles, canAssignRoles }: Props) {
    const profileForm = useForm({
        name: user.name,
        email: user.email,
        mobile: user.mobile ?? '',
    });

    const rolesForm = useForm({
        roles: (user.roles || []).map((r) => r.name),
    });

    const submitProfile = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.put(`/panel/users/${user.id}`);
    };

    const submitRoles = (e: React.FormEvent) => {
        e.preventDefault();
        rolesForm.post(`/panel/users/${user.id}/roles`);
    };

    return (
        <AdminLayout>
            <Head title={`User ${user.name}`} />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs items={[{ label: 'Users', href: '/panel/users' }, { label: user.name }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/users" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{user.name}</h1>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <form onSubmit={submitProfile} className="space-y-4 max-w-xl">
                        <div>
                            <label className="block text-xs text-gray-500 mb-1">Name</label>
                            <input
                                value={profileForm.data.name}
                                onChange={e => profileForm.setData('name', e.target.value)}
                                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            />
                            {profileForm.errors.name && <p className="text-red-500 text-xs mt-1">{profileForm.errors.name}</p>}
                        </div>
                        <div>
                            <label className="block text-xs text-gray-500 mb-1">Email</label>
                            <input
                                type="email"
                                value={profileForm.data.email}
                                onChange={e => profileForm.setData('email', e.target.value)}
                                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            />
                            {profileForm.errors.email && <p className="text-red-500 text-xs mt-1">{profileForm.errors.email}</p>}
                        </div>
                        <div>
                            <label className="block text-xs text-gray-500 mb-1">Mobile</label>
                            <input
                                value={profileForm.data.mobile}
                                onChange={e => profileForm.setData('mobile', e.target.value)}
                                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            />
                        </div>
                        <button
                            type="submit"
                            disabled={profileForm.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                        >
                            Save changes
                        </button>
                    </form>
                    <div className="mt-6 pt-6 border-t dark:border-gray-700">
                        <p className="text-sm text-gray-500 mb-2">Roles</p>
                        {canAssignRoles && assignableRoles.length > 0 ? (
                            <form onSubmit={submitRoles} className="space-y-3 max-w-xl">
                                <div className="flex flex-col gap-2">
                                    {assignableRoles.map((name) => (
                                        <label key={name} className="flex items-center gap-2 text-sm cursor-pointer">
                                            <input
                                                type="checkbox"
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                checked={rolesForm.data.roles.includes(name)}
                                                onChange={() => {
                                                    const next = new Set(rolesForm.data.roles);
                                                    if (next.has(name)) next.delete(name);
                                                    else next.add(name);
                                                    rolesForm.setData('roles', [...next]);
                                                }}
                                            />
                                            <span className="font-mono text-gray-800 dark:text-gray-200">{name}</span>
                                        </label>
                                    ))}
                                </div>
                                {rolesForm.errors.roles && (
                                    <p className="text-red-500 text-xs">{rolesForm.errors.roles}</p>
                                )}
                                <button
                                    type="submit"
                                    disabled={rolesForm.processing}
                                    className="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                                >
                                    Save roles
                                </button>
                            </form>
                        ) : (
                            <p className="font-medium">
                                {(user.roles || []).map((r) => r.name).join(', ') || '—'}
                            </p>
                        )}
                        {user.wallet && (
                            <p className="mt-2 text-sm">
                                Wallet balance: <span className="font-medium">₹{Number(user.wallet.balance).toLocaleString('en-IN')}</span>{' '}
                                <Link href={`/panel/wallets/${user.wallet.id}`} className="text-indigo-600 text-sm ml-2">View wallet</Link>
                            </p>
                        )}
                        <div className="mt-4 flex flex-wrap gap-2">
                            {user.account_locked ? (
                                <button
                                    type="button"
                                    onClick={() => router.post(`/panel/users/${user.id}/unblock`)}
                                    className="px-3 py-1.5 bg-green-600 text-white rounded text-sm"
                                >
                                    Unblock
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => {
                                        const reason = window.prompt('Block reason (required)');
                                        if (reason) {
                                            router.post(`/panel/users/${user.id}/block`, { reason });
                                        }
                                    }}
                                    className="px-3 py-1.5 bg-red-600 text-white rounded text-sm"
                                >
                                    Block user
                                </button>
                            )}
                        </div>
                    </div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h2 className="text-lg font-semibold mb-4">Recent orders</h2>
                    {orders.length === 0 ? (
                        <p className="text-gray-500 text-sm">No orders.</p>
                    ) : (
                        <ul className="space-y-2 text-sm">
                            {orders.map(o => (
                                <li key={o.id} className="flex justify-between border-b dark:border-gray-700 pb-2">
                                    <Link href={`/panel/orders/${o.id}`} className="text-indigo-600">
                                        {o.order_number || o.id}
                                    </Link>
                                    <span className="text-gray-500">{o.status}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
