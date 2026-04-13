import React, { FormEvent, useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type UserLite = { id: number; name: string; email: string | null; mobile: string | null };
type OrderLite = { id: number; order_number: string | null; status: string | null };

type Props = {
    prefillUser: UserLite | null;
    prefillOrders: OrderLite[];
    prefillOrderId: number | null;
};

export default function Create({ prefillUser, prefillOrders, prefillOrderId }: Props) {
    const [lookupQ, setLookupQ] = useState('');
    const [foundUser, setFoundUser] = useState<UserLite | null>(prefillUser);
    const [orders, setOrders] = useState<OrderLite[]>(prefillOrders || []);

    const form = useForm({
        user_id: prefillUser?.id ?? ('' as number | ''),
        order_id: prefillOrderId ?? ('' as number | ''),
        subject: '',
        description: '',
        category: 'other',
        priority: 'medium',
    });

    useEffect(() => {
        if (prefillUser) {
            form.setData('user_id', prefillUser.id);
        }
        if (prefillOrderId) {
            form.setData('order_id', prefillOrderId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const runLookup = async (e?: FormEvent) => {
        e?.preventDefault();
        if (lookupQ.length < 3) return;
        const r = await fetch(`/panel/tickets/lookup-user?q=${encodeURIComponent(lookupQ)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await r.json();
        if (data.user) {
            setFoundUser(data.user);
            setOrders(data.orders || []);
            form.setData('user_id', data.user.id);
            form.setData('order_id', '');
        } else {
            setFoundUser(null);
            setOrders([]);
            form.setData('user_id', '');
            form.setData('order_id', '');
        }
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.transform(d => ({
            user_id: Number(d.user_id),
            order_id: d.order_id === '' ? null : Number(d.order_id),
            subject: d.subject,
            description: d.description,
            category: d.category,
            priority: d.priority,
        })).post('/panel/tickets');
    };

    const inputCls = 'w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600';
    const labelCls = 'block text-xs text-gray-500 mb-1';

    return (
        <AdminLayout>
            <Head title="New ticket" />
            <div className="space-y-6 max-w-2xl">
                <Breadcrumbs items={[{ label: 'Tickets', href: '/panel/tickets' }, { label: 'Create' }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/tickets" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">New support ticket</h1>
                </div>
                <form onSubmit={submit} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <div>
                        <label className={labelCls}>Find user (mobile, email, or name)</label>
                        <div className="flex gap-2">
                            <input
                                value={lookupQ}
                                onChange={e => setLookupQ(e.target.value)}
                                className={inputCls}
                                placeholder="Min 3 characters"
                            />
                            <button type="button" onClick={() => runLookup()} className="px-3 py-2 bg-gray-200 dark:bg-gray-600 rounded-lg text-sm">
                                Find
                            </button>
                        </div>
                    </div>
                    {foundUser && (
                        <div className="rounded-lg border dark:border-gray-600 p-3 text-sm">
                            <p className="font-medium">{foundUser.name}</p>
                            <p className="text-gray-500">{foundUser.email || '—'}</p>
                            <p className="text-gray-500">{foundUser.mobile || '—'}</p>
                        </div>
                    )}
                    {orders.length > 0 && (
                        <div>
                            <label className={labelCls}>Link order (optional)</label>
                            <select
                                value={form.data.order_id === '' ? '' : String(form.data.order_id)}
                                onChange={e => form.setData('order_id', e.target.value === '' ? '' : Number(e.target.value))}
                                className={inputCls}
                            >
                                <option value="">— None —</option>
                                {orders.map(o => (
                                    <option key={o.id} value={o.id}>
                                        {o.order_number || o.id} ({o.status})
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                    <div>
                        <label className={labelCls}>Subject</label>
                        <input
                            value={form.data.subject}
                            onChange={e => form.setData('subject', e.target.value)}
                            className={inputCls}
                            required
                        />
                        {form.errors.subject && <p className="text-red-500 text-xs mt-1">{form.errors.subject}</p>}
                    </div>
                    <div className="grid md:grid-cols-2 gap-4">
                        <div>
                            <label className={labelCls}>Category</label>
                            <select
                                value={form.data.category}
                                onChange={e => form.setData('category', e.target.value)}
                                className={inputCls}
                            >
                                <option value="order_issue">Order issue</option>
                                <option value="refund">Refund</option>
                                <option value="voucher_not_received">Voucher not received</option>
                                <option value="payment_issue">Payment issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Priority</label>
                            <select
                                value={form.data.priority}
                                onChange={e => form.setData('priority', e.target.value)}
                                className={inputCls}
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label className={labelCls}>Description</label>
                        <textarea
                            value={form.data.description}
                            onChange={e => form.setData('description', e.target.value)}
                            rows={5}
                            className={inputCls}
                            required
                        />
                        {form.errors.description && <p className="text-red-500 text-xs mt-1">{form.errors.description}</p>}
                    </div>
                    {form.errors.user_id && <p className="text-red-500 text-xs">{form.errors.user_id}</p>}
                    {form.errors.order_id && <p className="text-red-500 text-xs">{form.errors.order_id}</p>}
                    <button
                        type="submit"
                        disabled={form.processing || !form.data.user_id}
                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                    >
                        Create ticket
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
