import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type SupportTicketLite = {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
};

type Order = {
    id: number;
    order_number: string | null;
    status: string | null;
    grand_total: string | number | null;
    payment_method: string | null;
    product_name: string | null;
    sku: string | null;
    created_at: string | null;
    user?: { id: number; name: string; email: string };
    product?: { id: number; product_name: string; sku: string | null };
    support_tickets?: SupportTicketLite[];
};

type Props = {
    order: Order;
};

export default function Show({ order }: Props) {
    return (
        <AdminLayout>
            <Head title={`Order ${order.order_number || order.id}`} />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs
                    items={[
                        { label: 'Orders', href: '/panel/orders' },
                        { label: order.order_number || String(order.id) },
                    ]}
                />
                <div className="flex items-center gap-4">
                    <Link href="/panel/orders" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Order {order.order_number || `#${order.id}`}
                    </h1>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span className="text-gray-500">Status</span>
                            <p className="font-medium capitalize">{order.status || '—'}</p>
                        </div>
                        <div>
                            <span className="text-gray-500">Total</span>
                            <p className="font-medium">
                                {order.grand_total != null && order.grand_total !== ''
                                    ? `₹${Number(order.grand_total).toLocaleString('en-IN')}`
                                    : '—'}
                            </p>
                        </div>
                        <div>
                            <span className="text-gray-500">Payment</span>
                            <p className="font-medium">{order.payment_method || '—'}</p>
                        </div>
                        <div>
                            <span className="text-gray-500">Created</span>
                            <p className="font-medium">{order.created_at ? new Date(order.created_at).toLocaleString() : '—'}</p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-gray-500">Customer</span>
                            <p className="font-medium">
                                {order.user ? `${order.user.name} (${order.user.email})` : '—'}
                            </p>
                        </div>
                        <div className="md:col-span-2">
                            <span className="text-gray-500">Product</span>
                            <p className="font-medium">
                                {order.product?.product_name || order.product_name || '—'}{' '}
                                <span className="text-gray-400 font-mono text-xs">({order.product?.sku || order.sku || '—'})</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Support tickets</h2>
                        {order.user && (
                            <Link
                                href={`/panel/tickets/create?user_id=${order.user.id}&order_id=${order.id}`}
                                className="text-sm text-indigo-600 hover:underline"
                            >
                                Create ticket
                            </Link>
                        )}
                    </div>
                    {!order.support_tickets || order.support_tickets.length === 0 ? (
                        <p className="text-sm text-gray-500">No tickets linked to this order.</p>
                    ) : (
                        <ul className="space-y-2 text-sm">
                            {order.support_tickets.map(t => (
                                <li key={t.id} className="flex justify-between border-b dark:border-gray-700 pb-2">
                                    <Link href={`/panel/tickets/${t.id}`} className="text-indigo-600 font-mono text-xs">
                                        {t.ticket_number}
                                    </Link>
                                    <span className="text-gray-500 capitalize">{t.status.replace('_', ' ')}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                <div className="flex flex-wrap gap-3">
                    <button
                        type="button"
                        onClick={() => {
                            if (confirm('Approve this order for processing?')) {
                                router.post(`/panel/orders/${order.id}/approve`);
                            }
                        }}
                        className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700"
                    >
                        Approve
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            const reason = window.prompt('Cancellation reason?', 'Cancelled by admin');
                            if (reason !== null) {
                                router.post(`/panel/orders/${order.id}/cancel`, { reason });
                            }
                        }}
                        className="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            const reason = window.prompt('Refund reason?', 'Refund requested by admin');
                            if (reason !== null) {
                                router.post(`/panel/orders/${order.id}/refund`, { reason });
                            }
                        }}
                        className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700"
                    >
                        Request refund
                    </button>
                </div>
            </div>
        </AdminLayout>
    );
}
