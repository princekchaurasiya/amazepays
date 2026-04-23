import React, { FormEvent, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import FileUploadDropzone from '@/Components/Admin/FileUploadDropzone';
import { Wallet as WalletIcon } from 'lucide-react';

type LoadReq = {
    id: number;
    amount: number;
    payment_mode: string;
    reference_no: string | null;
    status: string;
    created_at: string | null;
};

type Props = {
    tenant: {
        id: number;
        name: string;
        credit_limit: number | string | null;
        current_balance: number | string | null;
    } | null;
    wallet: { balance: number };
    load_requests: LoadReq[];
};

const PAYMENT_MODES = [
    { value: 'neft', label: 'NEFT' },
    { value: 'imps', label: 'IMPS' },
    { value: 'rtgs', label: 'RTGS' },
    { value: 'cash', label: 'Cash' },
    { value: 'cheque', label: 'Cheque' },
];

export default function Wallet({ tenant, wallet, load_requests }: Props) {
    const balance = Number(wallet?.balance ?? 0);
    const { auth } = usePage().props as { auth?: { user?: { permissions?: string[] } } };
    const canRequestLoad = !!auth?.user?.permissions?.includes('b2b.wallet.request_load');

    const [amount, setAmount] = useState('');
    const [paymentMode, setPaymentMode] = useState('neft');
    const [referenceNo, setReferenceNo] = useState('');
    const [proof, setProof] = useState<File | null>(null);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (!proof) {
            return;
        }
        const fd = new FormData();
        fd.append('amount', amount);
        fd.append('payment_mode', paymentMode);
        if (referenceNo) {
            fd.append('reference_no', referenceNo);
        }
        fd.append('proof', proof);
        router.post('/panel/b2b/wallet/load-request', fd, {
            forceFormData: true,
            onSuccess: () => {
                setAmount('');
                setReferenceNo('');
                setProof(null);
            },
        });
    };

    return (
        <AdminLayout>
            <Head title="Wallet" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Wallet</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Your prepaid balance and tenant credit information.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/30">
                                <WalletIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Your wallet balance</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    ₹{balance.toLocaleString('en-IN')}
                                </p>
                            </div>
                        </div>
                    </div>
                    {tenant && (
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Tenant</p>
                            <p className="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{tenant.name}</p>
                            {tenant.credit_limit != null && (
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Credit limit: ₹{Number(tenant.credit_limit).toLocaleString('en-IN')}
                                </p>
                            )}
                            {tenant.current_balance != null && (
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    Tenant balance: ₹{Number(tenant.current_balance).toLocaleString('en-IN')}
                                </p>
                            )}
                        </div>
                    )}
                </div>

                {canRequestLoad ? (
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-4">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Request wallet load</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Submit transfer details and proof. Your balance updates after finance approves the request.
                        </p>
                        <form onSubmit={submit} className="grid gap-3 sm:grid-cols-2 max-w-2xl">
                            <input
                                type="number"
                                required
                                value={amount}
                                onChange={e => setAmount(e.target.value)}
                                placeholder="Amount (min 100)"
                                className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 sm:col-span-2"
                            />
                            <select
                                value={paymentMode}
                                onChange={e => setPaymentMode(e.target.value)}
                                className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            >
                                {PAYMENT_MODES.map(m => (
                                    <option key={m.value} value={m.value}>{m.label}</option>
                                ))}
                            </select>
                            <input
                                value={referenceNo}
                                onChange={e => setReferenceNo(e.target.value)}
                                placeholder="UTR / reference (optional)"
                                className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            />
                            <div className="sm:col-span-2">
                                <FileUploadDropzone
                                    label="Payment proof"
                                    description="Receipt or transfer screenshot — PDF, PNG or JPEG, max 2 MB."
                                    value={proof}
                                    onChange={setProof}
                                    accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                    required
                                    compact
                                />
                            </div>
                            <button
                                type="submit"
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm sm:col-span-2 w-fit"
                            >
                                Submit request
                            </button>
                        </form>
                    </div>
                ) : null}

                <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <h2 className="px-5 py-3 font-semibold border-b dark:border-gray-700 text-gray-900 dark:text-white">
                        Recent load requests
                    </h2>
                    {load_requests.length === 0 ? (
                        <p className="px-5 py-8 text-sm text-gray-500 text-center">No load requests yet.</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                    <th className="px-5 py-3">Date</th>
                                    <th className="px-5 py-3">Amount</th>
                                    <th className="px-5 py-3">Mode</th>
                                    <th className="px-5 py-3">Reference</th>
                                    <th className="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {load_requests.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                            {row.created_at ? new Date(row.created_at).toLocaleString() : '—'}
                                        </td>
                                        <td className="px-5 py-3">{'\u20B9'}{row.amount.toLocaleString('en-IN')}</td>
                                        <td className="px-5 py-3">{row.payment_mode}</td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.reference_no || '—'}</td>
                                        <td className="px-5 py-3">{row.status}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
