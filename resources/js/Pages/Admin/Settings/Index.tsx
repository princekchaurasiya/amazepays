import React, { FormEvent, useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs, ConfirmDialog } from '@/Components/Admin';
import {
    Settings,
    Save,
    Plus,
    Pencil,
    Trash2,
    ChevronUp,
    ChevronDown,
    LayoutGrid,
    X,
    Shield,
    Images,
} from 'lucide-react';

type Field = {
    key: string;
    label: string;
    type: string;
    help: string | null;
    value: string | number | boolean;
};

type Group = {
    id: string;
    title: string;
    fields: Field[];
};

type SectionRow = {
    id: number;
    section_name: string;
    section_type: string;
    title: string | null;
    content: string | null;
    status: boolean;
    sort_order: number;
    config: Record<string, unknown>;
};

type SectionTypeOption = { value: string; label: string };

type Props = {
    groups: Group[];
    sections?: SectionRow[];
    homepageSectionTypes?: SectionTypeOption[];
};

function sectionPayload(row: SectionRow, overrides: Partial<SectionRow> = {}) {
    const merged = { ...row, ...overrides };
    const base: Record<string, unknown> = {
        section_name: merged.section_name,
        section_type: merged.section_type,
        title: merged.title || null,
        content: merged.content || null,
        status: merged.status,
        sort_order: merged.sort_order,
    };
    if (merged.section_type === 'hot_deals') {
        const n = Number(merged.config?.priority_product_count ?? 10);
        base.config = { priority_product_count: Number.isFinite(n) && n > 0 ? n : 10 };
    }
    return base;
}

export default function Index({ groups, sections = [], homepageSectionTypes = [] }: Props) {
    const page = usePage<{ auth?: { user?: { permissions?: string[] } } }>();
    const canEditSections = page.props.auth?.user?.permissions?.includes('settings.update') ?? false;
    const canManageRoles = page.props.auth?.user?.permissions?.includes('settings.roles.manage') ?? false;

    const initial: Record<string, string | number | boolean> = {};
    groups.forEach(g => {
        g.fields.forEach(f => {
            initial[f.key] = f.value;
        });
    });

    const form = useForm<{ settings: Record<string, string | number | boolean> }>({ settings: initial });

    const [sectionModalOpen, setSectionModalOpen] = useState(false);
    const [editingSection, setEditingSection] = useState<SectionRow | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<SectionRow | null>(null);
    const [sectionSaving, setSectionSaving] = useState(false);
    const [sectionErrors, setSectionErrors] = useState<Record<string, string>>({});

    const sectionForm = useForm({
        section_name: '',
        section_type: 'custom_html',
        title: '',
        content: '',
        status: true,
        sort_order: 0,
        priority_product_count: 10,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put('/panel/settings', { preserveScroll: true });
    };

    const setField = (key: string, v: string | number | boolean) => {
        form.setData('settings', { ...form.data.settings, [key]: v });
    };

    const openAddSection = () => {
        setEditingSection(null);
        sectionForm.clearErrors();
        setSectionErrors({});
        sectionForm.setData({
            section_name: '',
            section_type: 'custom_html',
            title: '',
            content: '',
            status: true,
            sort_order: sections.length ? Math.max(...sections.map(s => s.sort_order)) + 1 : 1,
            priority_product_count: 10,
        });
        setSectionModalOpen(true);
    };

    const openEditSection = (row: SectionRow) => {
        setEditingSection(row);
        sectionForm.clearErrors();
        setSectionErrors({});
        sectionForm.setData({
            section_name: row.section_name,
            section_type: row.section_type,
            title: row.title ?? '',
            content: row.content ?? '',
            status: row.status,
            sort_order: row.sort_order,
            priority_product_count: Number(row.config?.priority_product_count ?? 10),
        });
        setSectionModalOpen(true);
    };

    const closeSectionModal = () => {
        setSectionModalOpen(false);
        setEditingSection(null);
        sectionForm.reset();
        sectionForm.clearErrors();
        setSectionErrors({});
    };

    const submitSection = (e: FormEvent) => {
        e.preventDefault();
        sectionForm.clearErrors();
        setSectionErrors({});
        const d = sectionForm.data;
        const payload: Record<string, unknown> = {
            section_name: d.section_name.trim(),
            section_type: d.section_type,
            title: d.title.trim() || null,
            content: d.content.trim() || null,
            status: d.status,
            sort_order: d.sort_order,
        };
        if (d.section_type === 'hot_deals') {
            payload.config = { priority_product_count: d.priority_product_count };
        }

        setSectionSaving(true);
        const opts = {
            preserveScroll: true,
            onSuccess: () => closeSectionModal(),
            onError: (errs: Record<string, string>) => setSectionErrors(errs),
            onFinish: () => setSectionSaving(false),
        };

        if (editingSection) {
            router.put(`/panel/settings/sections/${editingSection.id}`, payload, opts);
        } else {
            router.post('/panel/settings/sections', payload, opts);
        }
    };

    const toggleSectionStatus = (row: SectionRow) => {
        router.put(`/panel/settings/sections/${row.id}`, sectionPayload(row, { status: !row.status }), {
            preserveScroll: true,
        });
    };

    const moveSection = (index: number, dir: -1 | 1) => {
        const j = index + dir;
        if (j < 0 || j >= sections.length) return;
        const ordered = [...sections];
        [ordered[index], ordered[j]] = [ordered[j], ordered[index]];
        router.post(
            '/panel/settings/sections/reorder',
            { ids: ordered.map(s => s.id) },
            { preserveScroll: true },
        );
    };

    const confirmDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/panel/settings/sections/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    const labelCls = 'block text-xs text-gray-500 mb-1 dark:text-gray-400';
    const inputCls =
        'w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600 bg-white dark:text-white text-gray-900';

    const sortedSections = [...sections].sort((a, b) => a.sort_order - b.sort_order || a.id - b.id);

    return (
        <AdminLayout>
            <Head title="Settings" />
            <div className="space-y-6 max-w-5xl">
                <Breadcrumbs items={[{ label: 'Settings' }]} />
                <div className="flex items-center gap-2">
                    <Settings className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Application settings</h1>
                </div>

                {canManageRoles && (
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div className="flex items-start gap-3">
                            <Shield className="text-indigo-600 shrink-0 mt-0.5" size={22} />
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Access control
                                </h2>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Map Spatie permissions to roles (storefront vs B2B catalog, orders, and more).
                                </p>
                            </div>
                        </div>
                        <Link
                            href="/panel/settings/roles"
                            className="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shrink-0"
                        >
                            Roles & permissions
                        </Link>
                    </div>
                )}

                {/* Homepage sections — storefront order & visibility */}
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4 border border-gray-100 dark:border-gray-700">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b dark:border-gray-700 pb-3">
                        <div className="flex items-start gap-2">
                            <LayoutGrid className="text-indigo-600 shrink-0 mt-0.5" size={22} />
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Homepage sections</h2>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Turn blocks on or off and set their order on the storefront home page. The{' '}
                                    <strong className="font-medium">banner</strong> row only shows or hides the hero
                                    area — upload <strong className="font-medium">desktop and mobile images</strong> under{' '}
                                    <strong className="font-medium">Hero carousel</strong> below. Use{' '}
                                    <strong className="font-medium">Custom HTML</strong> for extra content blocks.
                                </p>
                            </div>
                        </div>
                        {canEditSections && (
                            <div className="flex flex-wrap items-center gap-2 shrink-0 justify-end">
                                <Link
                                    href="/panel/settings/hero-slides"
                                    className="inline-flex items-center justify-center gap-2 px-4 py-2 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 bg-indigo-50/80 dark:bg-indigo-950/40 rounded-lg text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
                                >
                                    <Images size={18} />
                                    Hero carousel
                                </Link>
                                <button
                                    type="button"
                                    onClick={openAddSection}
                                    className="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
                                >
                                    <Plus size={18} />
                                    Add section
                                </button>
                            </div>
                        )}
                    </div>

                    {sortedSections.length === 0 ? (
                        <div className="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-gray-600 dark:text-gray-400">
                            <p className="font-medium text-gray-800 dark:text-gray-200">No homepage sections yet</p>
                            <p className="text-sm mt-2">
                                Run <code className="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">php artisan migrate</code>{' '}
                                to create default sections, or click <strong>Add section</strong> if you have permission.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto -mx-2">
                            <table className="w-full text-sm min-w-[640px]">
                                <thead>
                                    <tr className="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                        <th className="px-3 py-2 font-medium w-24">Order</th>
                                        <th className="px-3 py-2 font-medium">Name</th>
                                        <th className="px-3 py-2 font-medium">Type</th>
                                        <th className="px-3 py-2 font-medium">Title</th>
                                        <th className="px-3 py-2 font-medium w-24">Active</th>
                                        <th className="px-3 py-2 font-medium text-right min-w-[11rem]">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sortedSections.map((row, index) => (
                                        <tr
                                            key={row.id}
                                            className="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                                        >
                                            <td className="px-3 py-2 align-middle">
                                                <div className="flex items-center gap-1">
                                                    <span className="text-gray-600 dark:text-gray-300 tabular-nums w-6">
                                                        {row.sort_order}
                                                    </span>
                                                    {canEditSections && (
                                                        <div className="flex flex-col gap-0.5">
                                                            <button
                                                                type="button"
                                                                disabled={index === 0}
                                                                onClick={() => moveSection(index, -1)}
                                                                className="p-0.5 rounded text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-30"
                                                                title="Move up"
                                                            >
                                                                <ChevronUp size={14} />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                disabled={index === sortedSections.length - 1}
                                                                onClick={() => moveSection(index, 1)}
                                                                className="p-0.5 rounded text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-30"
                                                                title="Move down"
                                                            >
                                                                <ChevronDown size={14} />
                                                            </button>
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 font-mono text-xs text-gray-800 dark:text-gray-200">
                                                {row.section_name}
                                            </td>
                                            <td className="px-3 py-2">
                                                <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                    {row.section_type}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-[200px] truncate">
                                                {row.title || '—'}
                                                {row.section_type === 'hot_deals' && (
                                                    <span className="block text-xs text-gray-500 mt-0.5">
                                                        Limit: {String(row.config?.priority_product_count ?? 10)} products
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2">
                                                {canEditSections ? (
                                                    <button
                                                        type="button"
                                                        role="switch"
                                                        aria-checked={row.status}
                                                        onClick={() => toggleSectionStatus(row)}
                                                        className={`relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 ${
                                                            row.status
                                                                ? 'bg-indigo-600'
                                                                : 'bg-gray-200 dark:bg-gray-600'
                                                        }`}
                                                    >
                                                        <span
                                                            className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transition-transform ${
                                                                row.status ? 'translate-x-5' : 'translate-x-0'
                                                            }`}
                                                        />
                                                    </button>
                                                ) : (
                                                    <span className="text-xs">{row.status ? 'On' : 'Off'}</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-right">
                                                {canEditSections ? (
                                                    <div className="inline-flex flex-wrap items-center gap-1 justify-end">
                                                        {row.section_type === 'banner' && (
                                                            <Link
                                                                href="/panel/settings/hero-slides"
                                                                className="mr-1 px-2 py-1 rounded-md text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                                                                title="Desktop & mobile hero images and links"
                                                            >
                                                                Carousel
                                                            </Link>
                                                        )}
                                                        <button
                                                            type="button"
                                                            onClick={() => openEditSection(row)}
                                                            className="p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-300"
                                                            title="Edit"
                                                        >
                                                            <Pencil size={16} />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(row)}
                                                            className="p-2 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                                            title="Delete"
                                                        >
                                                            <Trash2 size={16} />
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-gray-400">View only</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                <form onSubmit={submit} className="space-y-8 max-w-3xl">
                    {groups.map(group => (
                        <div
                            key={group.id}
                            className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4 border border-gray-100 dark:border-gray-700"
                        >
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">
                                {group.title}
                            </h2>
                            {group.fields.map(field => (
                                <div key={field.key}>
                                    {field.type === 'toggle' ? (
                                        <label className="flex items-center gap-3 cursor-pointer select-none">
                                            <button
                                                type="button"
                                                role="switch"
                                                aria-checked={!!form.data.settings[field.key]}
                                                onClick={() => setField(field.key, form.data.settings[field.key] ? false : true)}
                                                className={`relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 ${
                                                    form.data.settings[field.key]
                                                        ? 'bg-indigo-600'
                                                        : 'bg-gray-200 dark:bg-gray-600'
                                                }`}
                                            >
                                                <span
                                                    className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform ${
                                                        form.data.settings[field.key] ? 'translate-x-5' : 'translate-x-0'
                                                    }`}
                                                />
                                            </button>
                                            <span className="text-sm text-gray-700 dark:text-gray-300">{field.label}</span>
                                        </label>
                                    ) : (
                                        <label className={labelCls}>{field.label}</label>
                                    )}
                                    {field.type === 'textarea' && (
                                        <textarea
                                            value={String(form.data.settings[field.key] ?? '')}
                                            onChange={e => setField(field.key, e.target.value)}
                                            rows={5}
                                            className={inputCls}
                                        />
                                    )}
                                    {field.type !== 'textarea' && field.type !== 'toggle' && (
                                        <input
                                            type={field.type === 'number' ? 'number' : field.type}
                                            step={field.type === 'number' ? 'any' : undefined}
                                            value={String(form.data.settings[field.key] ?? '')}
                                            onChange={e =>
                                                setField(
                                                    field.key,
                                                    field.type === 'number'
                                                        ? e.target.value === ''
                                                            ? ''
                                                            : Number(e.target.value)
                                                        : e.target.value,
                                                )
                                            }
                                            className={inputCls}
                                        />
                                    )}
                                    {field.help && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{field.help}</p>
                                    )}
                                    {(form.errors as Record<string, string>)[`settings.${field.key}`] && (
                                        <p className="text-red-500 text-xs mt-1">
                                            {(form.errors as Record<string, string>)[`settings.${field.key}`]}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    ))}
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <Save size={18} />
                        Save settings
                    </button>
                </form>
            </div>

            <ConfirmDialog
                open={deleteTarget !== null}
                onClose={() => setDeleteTarget(null)}
                onConfirm={confirmDelete}
                title="Remove this section?"
                message={
                    deleteTarget
                        ? `Delete “${deleteTarget.section_name}” from the homepage layout? You can add it again later.`
                        : ''
                }
                confirmLabel="Delete"
                variant="danger"
            />

            {sectionModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <button
                        type="button"
                        className="fixed inset-0 bg-black/50 backdrop-blur-sm"
                        aria-label="Close"
                        onClick={closeSectionModal}
                    />
                    <div className="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 border border-gray-100 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
                        <button
                            type="button"
                            onClick={closeSectionModal}
                            className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <X size={20} />
                        </button>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white pr-8 mb-4">
                            {editingSection ? 'Edit homepage section' : 'Add homepage section'}
                        </h3>
                        <form onSubmit={submitSection} className="space-y-4">
                            <div>
                                <label className={labelCls}>Internal name (slug)</label>
                                <input
                                    type="text"
                                    value={sectionForm.data.section_name}
                                    onChange={e => sectionForm.setData('section_name', e.target.value.toLowerCase())}
                                    className={inputCls}
                                    pattern="[a-z0-9_-]+"
                                    required
                                    disabled={!!editingSection}
                                    title="Lowercase letters, numbers, hyphens, underscores"
                                />
                                {(sectionErrors.section_name || sectionForm.errors.section_name) && (
                                    <p className="text-red-500 text-xs mt-1">
                                        {sectionErrors.section_name || sectionForm.errors.section_name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className={labelCls}>Section type</label>
                                <select
                                    value={sectionForm.data.section_type}
                                    onChange={e => sectionForm.setData('section_type', e.target.value)}
                                    className={inputCls}
                                    disabled={!!editingSection}
                                >
                                    {homepageSectionTypes.map(opt => (
                                        <option key={opt.value} value={opt.value}>
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                                {(sectionErrors.section_type || sectionForm.errors.section_type) && (
                                    <p className="text-red-500 text-xs mt-1">
                                        {sectionErrors.section_type || sectionForm.errors.section_type}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className={labelCls}>Heading title (optional)</label>
                                <input
                                    type="text"
                                    value={sectionForm.data.title}
                                    onChange={e => sectionForm.setData('title', e.target.value)}
                                    className={inputCls}
                                    placeholder="Shown above the section on the storefront"
                                />
                            </div>
                            {sectionForm.data.section_type === 'hot_deals' && (
                                <div>
                                    <label className={labelCls}>Priority products to show</label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={500}
                                        value={sectionForm.data.priority_product_count}
                                        onChange={e =>
                                            sectionForm.setData('priority_product_count', Number(e.target.value) || 10)
                                        }
                                        className={inputCls}
                                    />
                                </div>
                            )}
                            {sectionForm.data.section_type === 'custom_html' && (
                                <div>
                                    <label className={labelCls}>HTML content</label>
                                    <textarea
                                        value={sectionForm.data.content}
                                        onChange={e => sectionForm.setData('content', e.target.value)}
                                        rows={6}
                                        className={inputCls}
                                        placeholder="<div>...</div>"
                                    />
                                </div>
                            )}
                            <div>
                                <label className={labelCls}>Sort order</label>
                                <input
                                    type="number"
                                    value={sectionForm.data.sort_order}
                                    onChange={e =>
                                        sectionForm.setData('sort_order', Number(e.target.value) || 0)
                                    }
                                    className={inputCls}
                                />
                            </div>
                            <label className="flex items-center gap-3 cursor-pointer select-none">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={sectionForm.data.status}
                                    onClick={() => sectionForm.setData('status', !sectionForm.data.status)}
                                    className={`relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 ${
                                        sectionForm.data.status ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600'
                                    }`}
                                >
                                    <span
                                        className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transition-transform ${
                                            sectionForm.data.status ? 'translate-x-5' : 'translate-x-0'
                                        }`}
                                    />
                                </button>
                                <span className="text-sm text-gray-700 dark:text-gray-300">Visible on storefront</span>
                            </label>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={closeSectionModal}
                                    className="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={sectionSaving}
                                    className="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {sectionSaving ? 'Saving…' : editingSection ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
