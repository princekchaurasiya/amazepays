import React, { FormEvent, useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { Shield, Save } from 'lucide-react';

type PermRow = { name: string; label: string; assigned: boolean };
type Group = { title: string; permissions: PermRow[] };

type Props = {
    role: { id: number; name: string };
    groupedPermissions: Group[];
};

export default function Edit({ role, groupedPermissions }: Props) {
    const initialNames = useMemo(
        () =>
            groupedPermissions.flatMap((g) => g.permissions.filter((p) => p.assigned).map((p) => p.name)),
        [groupedPermissions],
    );

    const form = useForm<{ permissions: string[] }>({ permissions: initialNames });
    const [expanded, setExpanded] = useState<Record<string, boolean>>({});

    const toggle = (name: string) => {
        const set = new Set(form.data.permissions);
        if (set.has(name)) set.delete(name);
        else set.add(name);
        form.setData('permissions', [...set]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/panel/settings/roles/${role.id}`, { preserveScroll: true });
    };

    const labelCls = 'text-sm font-medium text-gray-800 dark:text-gray-200 cursor-pointer select-none';

    return (
        <AdminLayout>
            <Head title={`Role: ${role.name}`} />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs
                    items={[
                        { label: 'Settings', href: '/panel/settings' },
                        { label: 'Roles & permissions', href: '/panel/settings/roles' },
                        { label: role.name },
                    ]}
                />
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <Shield className="text-indigo-600" size={28} />
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white capitalize">
                                {role.name.replace(/-/g, ' ')}
                            </h1>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                                Toggle permissions and save. Changes apply on next request (cache cleared server-side).
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/panel/settings/roles"
                        className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                        ← All roles
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {groupedPermissions.map((group) => {
                        const open = expanded[group.title] ?? true;
                        return (
                            <div
                                key={group.title}
                                className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                            >
                                <button
                                    type="button"
                                    onClick={() =>
                                        setExpanded((s) => ({ ...s, [group.title]: !open }))
                                    }
                                    className="w-full flex items-center justify-between px-5 py-3 text-left bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700"
                                >
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {group.title}
                                    </span>
                                    <span className="text-xs text-gray-500">{open ? 'Hide' : 'Show'}</span>
                                </button>
                                {open && (
                                    <ul className="divide-y divide-gray-100 dark:divide-gray-700 max-h-[28rem] overflow-y-auto">
                                        {group.permissions.map((p) => (
                                            <li key={p.name} className="px-5 py-2 flex items-start gap-3">
                                                <input
                                                    type="checkbox"
                                                    id={`perm-${p.name}`}
                                                    checked={form.data.permissions.includes(p.name)}
                                                    onChange={() => toggle(p.name)}
                                                    className="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <label htmlFor={`perm-${p.name}`} className={`${labelCls} flex-1`}>
                                                    <span className="block text-sm text-gray-900 dark:text-gray-100">
                                                        {p.label}
                                                    </span>
                                                </label>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        );
                    })}

                    {form.errors.permissions && (
                        <p className="text-red-600 text-sm">{form.errors.permissions}</p>
                    )}

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <Save size={18} />
                        Save permissions
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
