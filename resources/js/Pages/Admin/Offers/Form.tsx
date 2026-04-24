import React, { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type OfferT = {
    id: number;
    name: string;
    code: string | null;
    type: string;
    discount_value: string | number | null;
    discount_percentage: string | number | null;
    min_order_value: string | number | null;
    max_discount: string | number | null;
    usage_limit: number | null;
    per_user_limit: number | null;
    is_active: boolean;
    is_public: boolean;
    start_date: string | null;
    end_date: string | null;
    tenant_id: number | null;
    description: string | null;
    applicable_product_ids?: number[] | null;
    applicable_brand_ids?: number[] | null;
    applicable_category_ids?: number[] | null;
};

type LookupRow = { id: number; name: string; sku?: string | null };

type Props = {
    offer: OfferT | null;
    tenants: Record<string, string>;
    types: string[];
    products: LookupRow[];
    brands: LookupRow[];
    categories: LookupRow[];
};

function toInputDate(v: string | null): string {
    if (!v) {
        return '';
    }
    try {
        return v.slice(0, 16).replace(' ', 'T');
    } catch {
        return '';
    }
}

function MultiPicker({
    label,
    hint,
    items,
    selected,
    onChange,
    showSku,
}: {
    label: string;
    hint: string;
    items: LookupRow[];
    selected: number[];
    onChange: (ids: number[]) => void;
    showSku?: boolean;
}) {
    const [q, setQ] = useState('');
    const filtered = useMemo(() => {
        const s = q.trim().toLowerCase();
        if (!s) return items;
        return items.filter(
            i =>
                i.name.toLowerCase().includes(s) ||
                (showSku && (i.sku || '').toLowerCase().includes(s)) ||
                String(i.id).includes(s),
        );
    }, [items, q, showSku]);

    const toggle = (id: number) => {
        if (selected.includes(id)) {
            onChange(selected.filter(x => x !== id));
        } else {
            onChange([...selected, id]);
        }
    };

    return (
        <div className="border dark:border-gray-600 rounded-lg p-4 space-y-2">
            <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">{label}</p>
                <p className="text-xs text-gray-500">{hint}</p>
            </div>
            <input
                value={q}
                onChange={e => setQ(e.target.value)}
                placeholder="Search…"
                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            />
            <div className="max-h-48 overflow-y-auto space-y-1 text-sm border dark:border-gray-600 rounded-md p-2">
                {filtered.length === 0 ? (
                    <p className="text-gray-500 text-xs">No matches.</p>
                ) : (
                    filtered.map(row => (
                        <label key={row.id} className="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-1">
                            <input
                                type="checkbox"
                                checked={selected.includes(row.id)}
                                onChange={() => toggle(row.id)}
                            />
                            <span className="flex-1 truncate">
                                {row.name}
                                {showSku && row.sku && (
                                    <span className="text-gray-400 font-mono text-xs ml-1">({row.sku})</span>
                                )}
                            </span>
                        </label>
                    ))
                )}
            </div>
            <p className="text-xs text-gray-500">{selected.length} selected</p>
        </div>
    );
}

export default function Form({ offer, tenants, types, products, brands, categories }: Props) {
    const tenantOptions = Object.entries(tenants).map(([id, name]) => ({ id: Number(id), name }));

    const form = useForm({
        name: offer?.name ?? '',
        code: offer?.code ?? '',
        type: offer?.type ?? types[0] ?? 'flat_discount',
        discount_value: offer?.discount_value != null ? String(offer.discount_value) : '',
        discount_percentage: offer?.discount_percentage != null ? String(offer.discount_percentage) : '',
        min_order_value: offer?.min_order_value != null ? String(offer.min_order_value) : '0',
        max_discount: offer?.max_discount != null ? String(offer.max_discount) : '',
        usage_limit: offer?.usage_limit != null ? String(offer.usage_limit) : '',
        per_user_limit: offer?.per_user_limit != null ? String(offer.per_user_limit) : '1',
        is_active: offer?.is_active ?? true,
        is_public: offer?.is_public ?? true,
        start_date: toInputDate(offer?.start_date ?? null),
        end_date: toInputDate(offer?.end_date ?? null),
        tenant_id: offer?.tenant_id != null ? String(offer.tenant_id) : '',
        description: offer?.description ?? '',
        applicable_product_ids: (offer?.applicable_product_ids ?? []) as number[],
        applicable_brand_ids: (offer?.applicable_brand_ids ?? []) as number[],
        applicable_category_ids: (offer?.applicable_category_ids ?? []) as number[],
    });

    const typeHint = useMemo(() => {
        switch (form.data.type) {
            case 'product_specific':
                return 'Select at least one product, or combine with brands/categories for narrower targeting.';
            case 'brand_specific':
                return 'Select brands this offer applies to.';
            case 'category_specific':
                return 'Select categories this offer applies to.';
            default:
                return 'Optional: limit this offer to specific products, brands, or categories.';
        }
    }, [form.data.type]);

    const buildPayload = (data: typeof form.data) => ({
        name: data.name,
        code: data.code || null,
        type: data.type,
        discount_value: data.discount_value === '' ? null : Number(data.discount_value),
        discount_percentage: data.discount_percentage === '' ? null : Number(data.discount_percentage),
        min_order_value: data.min_order_value === '' ? 0 : Number(data.min_order_value),
        max_discount: data.max_discount === '' ? null : Number(data.max_discount),
        usage_limit: data.usage_limit === '' ? null : Number(data.usage_limit),
        per_user_limit: data.per_user_limit === '' ? 1 : Number(data.per_user_limit),
        is_active: data.is_active,
        is_public: data.is_public,
        start_date: data.start_date || null,
        end_date: data.end_date || null,
        tenant_id: data.tenant_id === '' ? null : Number(data.tenant_id),
        description: data.description || null,
        applicable_product_ids: data.applicable_product_ids.length ? data.applicable_product_ids : null,
        applicable_brand_ids: data.applicable_brand_ids.length ? data.applicable_brand_ids : null,
        applicable_category_ids: data.applicable_category_ids.length ? data.applicable_category_ids : null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (offer) {
            form.transform(buildPayload);
            form.put(`/panel/offers/${offer.id}`);
        } else {
            form.transform(buildPayload);
            form.post('/panel/offers');
        }
    };

    const labelCls = 'block text-xs text-gray-500 mb-1';
    const inputCls = 'w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600';

    const isProduct = form.data.type === 'product_specific';
    const isBrand = form.data.type === 'brand_specific';
    const isCategory = form.data.type === 'category_specific';
    const isGeneral = !isProduct && !isBrand && !isCategory;
    const showProducts = isProduct || isGeneral;
    const showBrands = isBrand || isGeneral;
    const showCategories = isCategory || isGeneral;

    return (
        <AdminLayout>
            <Head title={offer ? `Edit ${offer.name}` : 'New offer'} />
            <div className="space-y-6 max-w-3xl">
                <Breadcrumbs
                    items={[
                        { label: 'Offers', href: '/panel/offers' },
                        { label: offer ? offer.name : 'Create' },
                    ]}
                />
                <div className="flex items-center gap-4">
                    <Link href="/panel/offers" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{offer ? 'Edit offer' : 'New offer'}</h1>
                </div>
                <form onSubmit={submit} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <div className="grid md:grid-cols-2 gap-4">
                        <div>
                            <label className={labelCls}>Name</label>
                            <input value={form.data.name} onChange={e => form.setData('name', e.target.value)} className={inputCls} required />
                            {form.errors.name && <p className="text-red-500 text-xs mt-1">{form.errors.name}</p>}
                        </div>
                        <div>
                            <label className={labelCls}>Code</label>
                            <input value={form.data.code} onChange={e => form.setData('code', e.target.value)} className={inputCls} />
                        </div>
                        <div>
                            <label className={labelCls}>Type</label>
                            <select value={form.data.type} onChange={e => form.setData('type', e.target.value)} className={inputCls}>
                                {types.map(t => (
                                    <option key={t} value={t}>
                                        {t}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Tenant</label>
                            <select
                                value={form.data.tenant_id}
                                onChange={e => form.setData('tenant_id', e.target.value)}
                                className={inputCls}
                            >
                                <option value="">— None —</option>
                                {tenantOptions.map(t => (
                                    <option key={t.id} value={String(t.id)}>
                                        {t.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Discount value</label>
                            <input
                                value={form.data.discount_value}
                                onChange={e => form.setData('discount_value', e.target.value)}
                                className={inputCls}
                                type="number"
                                step="0.01"
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Discount %</label>
                            <input
                                value={form.data.discount_percentage}
                                onChange={e => form.setData('discount_percentage', e.target.value)}
                                className={inputCls}
                                type="number"
                                step="0.01"
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Min order value</label>
                            <input
                                value={form.data.min_order_value}
                                onChange={e => form.setData('min_order_value', e.target.value)}
                                className={inputCls}
                                type="number"
                                step="0.01"
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Max discount</label>
                            <input
                                value={form.data.max_discount}
                                onChange={e => form.setData('max_discount', e.target.value)}
                                className={inputCls}
                                type="number"
                                step="0.01"
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Usage limit</label>
                            <input value={form.data.usage_limit} onChange={e => form.setData('usage_limit', e.target.value)} className={inputCls} type="number" />
                        </div>
                        <div>
                            <label className={labelCls}>Per user limit</label>
                            <input
                                value={form.data.per_user_limit}
                                onChange={e => form.setData('per_user_limit', e.target.value)}
                                className={inputCls}
                                type="number"
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Start</label>
                            <input
                                type="datetime-local"
                                value={form.data.start_date}
                                onChange={e => form.setData('start_date', e.target.value)}
                                className={inputCls}
                            />
                        </div>
                        <div>
                            <label className={labelCls}>End</label>
                            <input
                                type="datetime-local"
                                value={form.data.end_date}
                                onChange={e => form.setData('end_date', e.target.value)}
                                className={inputCls}
                            />
                        </div>
                    </div>
                    <div className="flex gap-6">
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />
                            Active
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.is_public} onChange={e => form.setData('is_public', e.target.checked)} />
                            Public
                        </label>
                    </div>
                    <div>
                        <label className={labelCls}>Description</label>
                        <textarea
                            value={form.data.description}
                            onChange={e => form.setData('description', e.target.value)}
                            rows={3}
                            className={inputCls}
                        />
                    </div>

                    <div className="space-y-3 border-t dark:border-gray-700 pt-4">
                        <p className="text-sm font-medium text-gray-900 dark:text-white">Apply to catalog</p>
                        <p className="text-xs text-gray-500">{typeHint}</p>
                        {showProducts && (
                            <MultiPicker
                                label="Products"
                                hint="Visible storefront products (max 500 loaded)."
                                items={products}
                                selected={form.data.applicable_product_ids}
                                onChange={ids => form.setData('applicable_product_ids', ids)}
                                showSku
                            />
                        )}
                        {showBrands && (
                            <MultiPicker
                                label="Brands"
                                hint="Storefront brands."
                                items={brands}
                                selected={form.data.applicable_brand_ids}
                                onChange={ids => form.setData('applicable_brand_ids', ids)}
                            />
                        )}
                        {showCategories && (
                            <MultiPicker
                                label="Categories"
                                hint="Navigation categories."
                                items={categories}
                                selected={form.data.applicable_category_ids}
                                onChange={ids => form.setData('applicable_category_ids', ids)}
                            />
                        )}
                    </div>

                    <button type="submit" disabled={form.processing} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        {offer ? 'Update offer' : 'Create offer'}
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
