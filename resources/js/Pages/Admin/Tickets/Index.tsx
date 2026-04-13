import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { MessageSquare, Plus, Search } from 'lucide-react';

type TicketRow = {
    id: number;
    ticket_number: string;
    subject: string;
    category: string;
    priority: string;
    status: string;
    created_at: string | null;
    user?: { id: number; name: string; mobile?: string | null };
    order?: { id: number; order_number: string | null };
    assigned_to?: { id: number; name: string } | null;
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
    tickets: Paginated<TicketRow>;
    filters: { search?: string; status?: string; priority?: string; category?: string };
};

const statusColors: Record<string, string> = {
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-amber-100 text-amber-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-700',
};

const priorityColors: Record<string, string> = {
    low: 'text-gray-600',
    medium: 'text-blue-600',
    high: 'text-orange-600',
    urgent: 'text-red-600 font-semibold',
};

export default function Index({ tickets, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [priority, setPriority] = useState(filters.priority ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/panel/tickets',
            {
                search: search || undefined,
                status: status || undefined,
                priority: priority || undefined,
                category: category || undefined,
            },
            { preserveState: true },
        );
    };

    const q = () =>
        `search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&priority=${encodeURIComponent(priority)}&category=${encodeURIComponent(category)}`;

    return (
        <AdminLayout>
            <Head title="Support tickets" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Tickets' }]} />
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <MessageSquare className="text-indigo-600" size={28} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Support tickets</h1>
                    </div>
                    <Link
                        href="/panel/tickets/create"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium"
                    >
                        <Plus size={18} />
                        New ticket
                    </Link>
                </div>
                <form onSubmit={apply} className="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                    <div className="flex-1 min-w-[160px]">
                        <label className="block text-xs text-gray-500 mb-1">Search</label>
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                                placeholder="Ticket #, name, mobile, email"
                            />
                        </div>
                    </div>
                    <div className="w-36">
                        <label className="block text-xs text-gray-500 mb-1">Status</label>
                        <select
                            value={status}
                            onChange={e => setStatus(e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                        >
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div className="w-36">
                        <label className="block text-xs text-gray-500 mb-1">Priority</label>
                        <select
                            value={priority}
                            onChange={e => setPriority(e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                        >
                            <option value="">All</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div className="w-44">
                        <label className="block text-xs text-gray-500 mb-1">Category</label>
                        <select
                            value={category}
                            onChange={e => setCategory(e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                        >
                            <option value="">All</option>
                            <option value="order_issue">Order issue</option>
                            <option value="refund">Refund</option>
                            <option value="voucher_not_received">Voucher not received</option>
                            <option value="payment_issue">Payment issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">
                        Filter
                    </button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">Ticket</th>
                                <th className="px-5 py-3">Subject</th>
                                <th className="px-5 py-3">User</th>
                                <th className="px-5 py-3">Order</th>
                                <th className="px-5 py-3">Priority</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3">Assigned</th>
                                <th className="px-5 py-3 w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tickets.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-5 py-12 text-center text-gray-500">
                                        No tickets.
                                    </td>
                                </tr>
                            ) : (
                                tickets.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td className="px-5 py-3">
                                            <Link href={`/panel/tickets/${row.id}`} className="text-indigo-600 font-mono text-xs">
                                                {row.ticket_number}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-3 font-medium max-w-xs truncate">{row.subject}</td>
                                        <td className="px-5 py-3 text-xs">
                                            {row.user ? row.user.name : '—'}
                                            {row.user?.mobile && (
                                                <span className="block text-gray-500">{row.user.mobile}</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-xs">
                                            {row.order ? (
                                                <Link href={`/panel/orders/${row.order.id}`} className="text-indigo-600">
                                                    {row.order.order_number || row.order.id}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className={`px-5 py-3 text-xs capitalize ${priorityColors[row.priority] || ''}`}>
                                            {row.priority}
                                        </td>
                                        <td className="px-5 py-3">
                                            <span
                                                className={`inline-block px-2 py-0.5 rounded text-xs capitalize ${statusColors[row.status] || ''}`}
                                            >
                                                {row.status.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="px-5 py-3 text-xs">{row.assigned_to?.name || '—'}</td>
                                        <td className="px-5 py-3 text-right">
                                            <ActionButtons
                                                viewHref={`/panel/tickets/${row.id}`}
                                                editHref={`/panel/tickets/${row.id}`}
                                            />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {tickets.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm text-gray-500">
                            <span>
                                {tickets.from}–{tickets.to} of {tickets.total}
                            </span>
                            <div className="flex gap-2">
                                {tickets.current_page > 1 && (
                                    <Link
                                        href={`/panel/tickets?page=${tickets.current_page - 1}&${q()}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {tickets.current_page < tickets.last_page && (
                                    <Link
                                        href={`/panel/tickets?page=${tickets.current_page + 1}&${q()}`}
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
