import React, { FormEvent, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type Msg = {
    id: number;
    message: string;
    is_admin_reply: boolean;
    created_at: string | null;
    user?: { id: number; name: string };
};

type TicketT = {
    id: number;
    ticket_number: string;
    subject: string;
    description: string;
    category: string;
    priority: string;
    status: string;
    resolution_note: string | null;
    resolved_at: string | null;
    user?: { id: number; name: string; email?: string; mobile?: string | null };
    order?: { id: number; order_number: string | null };
    assigned_to?: { id: number; name: string } | null;
    messages: Msg[];
};

type Staff = { id: number; name: string; email: string };

type Props = {
    ticket: TicketT;
    staff: Staff[];
};

export default function Show({ ticket, staff }: Props) {
    const replyForm = useForm({ message: '' });
    const resolveForm = useForm({ resolution_note: '' });
    const metaForm = useForm({
        status: ticket.status,
        priority: ticket.priority,
        assigned_to: ticket.assigned_to?.id ?? ('' as number | ''),
    });

    const [editingMeta, setEditingMeta] = useState(false);

    const submitReply = (e: FormEvent) => {
        e.preventDefault();
        replyForm.post(`/panel/tickets/${ticket.id}/reply`, {
            preserveScroll: true,
            onSuccess: () => replyForm.reset('message'),
        });
    };

    const submitResolve = (e: FormEvent) => {
        e.preventDefault();
        resolveForm.post(`/panel/tickets/${ticket.id}/resolve`, { preserveScroll: true });
    };

    const submitMeta = (e: FormEvent) => {
        e.preventDefault();
        metaForm.transform(d => ({
            status: d.status,
            priority: d.priority,
            assigned_to: d.assigned_to === '' ? null : Number(d.assigned_to),
        }));
        metaForm.put(`/panel/tickets/${ticket.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditingMeta(false),
        });
    };

    return (
        <AdminLayout>
            <Head title={ticket.ticket_number} />
            <div className="space-y-6 max-w-3xl">
                <Breadcrumbs
                    items={[
                        { label: 'Tickets', href: '/panel/tickets' },
                        { label: ticket.ticket_number },
                    ]}
                />
                <div className="flex items-center gap-4">
                    <Link href="/panel/tickets" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white font-mono text-lg">{ticket.ticket_number}</h1>
                        <p className="text-gray-600 dark:text-gray-300">{ticket.subject}</p>
                    </div>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3 text-sm">
                    <div className="flex flex-wrap gap-2 justify-between items-start">
                        <div className="space-y-1">
                            <p>
                                <span className="text-gray-500">Customer:</span>{' '}
                                <span className="font-medium">{ticket.user?.name}</span>
                                {ticket.user?.mobile && <span className="text-gray-500 ml-2">{ticket.user.mobile}</span>}
                            </p>
                            {ticket.order && (
                                <p>
                                    <span className="text-gray-500">Order:</span>{' '}
                                    <Link href={`/panel/orders/${ticket.order.id}`} className="text-indigo-600">
                                        {ticket.order.order_number || ticket.order.id}
                                    </Link>
                                </p>
                            )}
                            <p className="text-gray-500 capitalize">
                                Category: {ticket.category.replace(/_/g, ' ')} · Priority: {ticket.priority} · Status:{' '}
                                {ticket.status.replace(/_/g, ' ')}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setEditingMeta(!editingMeta)}
                            className="text-sm text-indigo-600"
                        >
                            {editingMeta ? 'Cancel' : 'Edit status / assign'}
                        </button>
                    </div>
                    {editingMeta && (
                        <form onSubmit={submitMeta} className="flex flex-wrap gap-3 items-end border-t dark:border-gray-700 pt-4">
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Status</label>
                                <select
                                    value={metaForm.data.status}
                                    onChange={e => metaForm.setData('status', e.target.value)}
                                    className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                                >
                                    <option value="open">Open</option>
                                    <option value="in_progress">In progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Priority</label>
                                <select
                                    value={metaForm.data.priority}
                                    onChange={e => metaForm.setData('priority', e.target.value)}
                                    className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs text-gray-500 mb-1">Assigned to</label>
                                <select
                                    value={metaForm.data.assigned_to === '' ? '' : String(metaForm.data.assigned_to)}
                                    onChange={e =>
                                        metaForm.setData('assigned_to', e.target.value === '' ? '' : Number(e.target.value))
                                    }
                                    className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                                >
                                    <option value="">— Unassigned —</option>
                                    {staff.map(s => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <button type="submit" className="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm">
                                Save
                            </button>
                        </form>
                    )}
                    <div className="border-t dark:border-gray-700 pt-4">
                        <p className="text-xs text-gray-500 mb-1">Initial request</p>
                        <p className="whitespace-pre-wrap">{ticket.description}</p>
                    </div>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 className="font-semibold">Conversation</h2>
                    <div className="space-y-3 max-h-96 overflow-y-auto">
                        {ticket.messages.map(m => (
                            <div
                                key={m.id}
                                className={`rounded-lg p-3 text-sm ${
                                    m.is_admin_reply ? 'bg-indigo-50 dark:bg-indigo-900/20 ml-8' : 'bg-gray-50 dark:bg-gray-700/50 mr-8'
                                }`}
                            >
                                <p className="text-xs text-gray-500 mb-1">
                                    {m.is_admin_reply ? 'Admin' : 'Customer'} · {m.user?.name} ·{' '}
                                    {m.created_at ? new Date(m.created_at).toLocaleString() : ''}
                                </p>
                                <p className="whitespace-pre-wrap">{m.message}</p>
                            </div>
                        ))}
                    </div>
                    <form onSubmit={submitReply} className="space-y-2">
                        <textarea
                            value={replyForm.data.message}
                            onChange={e => replyForm.setData('message', e.target.value)}
                            rows={3}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            placeholder="Reply as admin…"
                        />
                        <button
                            type="submit"
                            disabled={replyForm.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm disabled:opacity-50"
                        >
                            Send reply
                        </button>
                    </form>
                </div>

                {ticket.status !== 'resolved' && ticket.status !== 'closed' && (
                    <form onSubmit={submitResolve} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3">
                        <h2 className="font-semibold">Resolve ticket</h2>
                        <textarea
                            value={resolveForm.data.resolution_note}
                            onChange={e => resolveForm.setData('resolution_note', e.target.value)}
                            rows={3}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            placeholder="Resolution summary…"
                            required
                        />
                        <button
                            type="submit"
                            disabled={resolveForm.processing}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm disabled:opacity-50"
                        >
                            Mark resolved
                        </button>
                    </form>
                )}
                {ticket.resolution_note && (
                    <div className="rounded-lg border border-green-200 bg-green-50 dark:bg-green-900/20 p-4 text-sm">
                        <p className="font-medium text-green-800 dark:text-green-300">Resolution</p>
                        <p className="whitespace-pre-wrap mt-1">{ticket.resolution_note}</p>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
