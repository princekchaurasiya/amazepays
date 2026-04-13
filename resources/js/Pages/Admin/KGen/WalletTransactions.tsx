import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function WalletTransactions({
    transactions = [],
    nextCursor = null,
    errorMessage = null,
}: {
    transactions: Record<string, unknown>[];
    nextCursor?: string | null;
    errorMessage?: string | null;
}) {
    return (
        <AdminLayout>
            <Head title="KGen wallet transactions" />
            <div className="space-y-4 p-6">
                <h1 className="text-2xl font-bold text-gray-900">KGen wallet transactions</h1>
                {errorMessage ? (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{errorMessage}</div>
                ) : null}
                <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-3 py-2 text-left font-medium text-gray-700">#</th>
                                <th className="px-3 py-2 text-left font-medium text-gray-700">Payload</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {transactions.length === 0 ? (
                                <tr>
                                    <td colSpan={2} className="px-3 py-6 text-center text-gray-500">
                                        No transactions
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((row, i) => (
                                    <tr key={i}>
                                        <td className="px-3 py-2 text-gray-600">{i + 1}</td>
                                        <td className="px-3 py-2 font-mono text-xs text-gray-800">
                                            <pre className="whitespace-pre-wrap break-all">{JSON.stringify(row, null, 0)}</pre>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                {nextCursor ? (
                    <p className="text-xs text-gray-500">
                        Next cursor: <span className="font-mono">{nextCursor}</span> (use API query to paginate)
                    </p>
                ) : null}
            </div>
        </AdminLayout>
    );
}
