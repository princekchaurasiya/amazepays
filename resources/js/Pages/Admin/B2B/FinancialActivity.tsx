import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { format } from 'date-fns';
import clsx from 'clsx';

type Row = {
    kind: string;
    type: string;
    label: string;
    reference: string;
    description: string | null;
    amount: number;
    status: string;
    created_at: string | null;
};

type Props = {
    tenant: { id: number; name: string } | null;
    rows: Row[];
    filters: {
        date_from: string | null;
        date_to: string | null;
        include_archived: boolean;
        tab: string;
    };
};

const tabs = [
    { id: 'all', label: 'All' },
    { id: 'payments', label: 'Load requests' },
    { id: 'credits', label: 'Credits' },
    { id: 'debits', label: 'Debits' },
] as const;

export default function FinancialActivity({ tenant, rows, filters }: Props) {
    const apply = (patch: Record<string, string | boolean | undefined>) => {
        const includeArchived =
            'include_archived' in patch ? Boolean(patch.include_archived) : filters.include_archived;

        router.get(
            '/panel/b2b/financial-activity',
            {
                tab: (patch.tab as string | undefined) ?? filters.tab,
                date_from: (patch.date_from as string | undefined) ?? filters.date_from ?? undefined,
                date_to: (patch.date_to as string | undefined) ?? filters.date_to ?? undefined,
                include_archived: includeArchived ? '1' : undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    return (
        <AdminLayout>
            <Head title="Financial activity" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Financial activity</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Wallet movements and wallet load requests for your account.
                    </p>
                    {!filters.include_archived && (
                        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Records older than 6 months are hidden unless you enable &quot;Include archived&quot;.
                        </p>
                    )}
                </div>

                {!tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        No tenant linked to this user.
                    </div>
                ) : (
                    <>
                        <div className="flex flex-wrap gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
                            {tabs.map((t) => (
                                <button
                                    key={t.id}
                                    type="button"
                                    onClick={() => apply({ tab: t.id })}
                                    className={clsx(
                                        'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                                        filters.tab === t.id
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                                    )}
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>

                        <div className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div>
                                <label className="block text-xs text-gray-500">From</label>
                                <input
                                    type="date"
                                    value={filters.date_from ?? ''}
                                    onChange={(e) => apply({ date_from: e.target.value || undefined })}
                                    className="mt-0.5 rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-xs text-gray-500">To</label>
                                <input
                                    type="date"
                                    value={filters.date_to ?? ''}
                                    onChange={(e) => apply({ date_to: e.target.value || undefined })}
                                    className="mt-0.5 rounded-lg border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <label className="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    checked={filters.include_archived}
                                    onChange={(e) => apply({ include_archived: e.target.checked })}
                                    className="rounded border-gray-300"
                                />
                                Include archived (&gt;6 months)
                            </label>
                        </div>

                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-100 bg-gray-50 text-left text-gray-500 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                        <th className="px-4 py-3 font-medium">Type</th>
                                        <th className="px-4 py-3 font-medium">Reference</th>
                                        <th className="px-4 py-3 font-medium">Details</th>
                                        <th className="px-4 py-3 font-medium">Amount</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                        <th className="px-4 py-3 font-medium">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((r, idx) => (
                                        <tr key={`${r.kind}-${r.reference}-${idx}`} className="border-t border-gray-100 dark:border-gray-700">
                                            <td className="px-4 py-3 text-gray-900 dark:text-white">{r.label}</td>
                                            <td className="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                                                {r.reference}
                                            </td>
                                            <td className="max-w-xs truncate px-4 py-3 text-gray-600 dark:text-gray-300">
                                                {r.description || '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.kind === 'wallet' && r.type === 'debit' ? '−' : ''}
                                                ₹{Number(r.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                                            </td>
                                            <td className="px-4 py-3 capitalize">{r.status}</td>
                                            <td className="px-4 py-3 text-gray-500">
                                                {r.created_at
                                                    ? format(new Date(r.created_at), 'dd MMM yyyy, HH:mm')
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {rows.length === 0 && (
                                <p className="p-8 text-center text-gray-500 dark:text-gray-400">No activity in this view.</p>
                            )}
                        </div>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
