import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { X } from 'lucide-react';

type PriceMode = 'absolute' | 'relative_percent' | 'relative_fixed';

type Props = {
    open: boolean;
    onClose: () => void;
    selectedIds: number[];
    catalogScope: string;
    canPublish: boolean;
    canUpdate: boolean;
    onSuccess: () => void;
};

export default function BulkEditProductsModal({
    open,
    onClose,
    selectedIds,
    catalogScope,
    canPublish,
    canUpdate,
    onSuccess,
}: Props) {
    const [applyVisibility, setApplyVisibility] = useState(false);
    const [showProduct, setShowProduct] = useState(true);
    const [applyPricing, setApplyPricing] = useState(false);
    const [priceMode, setPriceMode] = useState<PriceMode>('absolute');
    const [sellingPrice, setSellingPrice] = useState('');
    const [mrp, setMrp] = useState('');
    const [discountPercentage, setDiscountPercentage] = useState('');
    const [priceRelativePercent, setPriceRelativePercent] = useState('');
    const [priceRelativeAmount, setPriceRelativeAmount] = useState('');
    const [applyCustomDescription, setApplyCustomDescription] = useState(false);
    const [customDescription, setCustomDescription] = useState('');
    const [applyHowToRedeem, setApplyHowToRedeem] = useState(false);
    const [howToRedeem, setHowToRedeem] = useState('');
    const [applyTerms, setApplyTerms] = useState(false);
    const [termsAndConditions, setTermsAndConditions] = useState('');

    useEffect(() => {
        if (!open) {
            return;
        }
        setApplyVisibility(false);
        setShowProduct(true);
        setApplyPricing(false);
        setPriceMode('absolute');
        setSellingPrice('');
        setMrp('');
        setDiscountPercentage('');
        setPriceRelativePercent('');
        setPriceRelativeAmount('');
        setApplyCustomDescription(false);
        setCustomDescription('');
        setApplyHowToRedeem(false);
        setHowToRedeem('');
        setApplyTerms(false);
        setTermsAndConditions('');
    }, [open]);

    if (!open) {
        return null;
    }

    const submit = () => {
        const payload: Record<string, unknown> = {
            ids: selectedIds,
            catalog_scope: catalogScope,
        };

        if (applyVisibility && canPublish) {
            payload.apply_visibility = true;
            payload.show_product = showProduct;
        }

        if (applyPricing && canUpdate) {
            payload.price_mode = priceMode;
            if (priceMode === 'absolute') {
                if (sellingPrice !== '') {
                    payload.selling_price = parseFloat(sellingPrice);
                }
                if (mrp !== '') {
                    payload.mrp = parseFloat(mrp);
                }
                if (discountPercentage !== '') {
                    payload.discount_percentage = parseFloat(discountPercentage);
                }
            } else if (priceMode === 'relative_percent') {
                payload.price_relative_percent = parseFloat(priceRelativePercent);
            } else if (priceMode === 'relative_fixed') {
                payload.price_relative_amount = parseFloat(priceRelativeAmount);
            }
        }

        if (canUpdate) {
            if (applyCustomDescription) {
                payload.apply_custom_description = true;
                payload.custom_description = customDescription;
            }
            if (applyHowToRedeem) {
                payload.apply_how_to_redeem = true;
                payload.how_to_redeem = howToRedeem;
            }
            if (applyTerms) {
                payload.apply_terms_and_conditions = true;
                payload.terms_and_conditions = termsAndConditions;
            }
        }

        router.patch('/panel/products/bulk-update', payload as any, {
            preserveScroll: true,
            onSuccess: () => {
                onSuccess();
                onClose();
            },
        });
    };

    const hasAnyAction =
        (applyVisibility && canPublish) ||
        (applyPricing && canUpdate) ||
        (canUpdate && (applyCustomDescription || applyHowToRedeem || applyTerms));

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-700">
                <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Bulk edit ({selectedIds.length} selected)
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                        aria-label="Close"
                    >
                        <X size={20} />
                    </button>
                </div>

                <div className="p-4 space-y-6 text-sm">
                    {canPublish ? (
                        <section className="space-y-2">
                            <label className="flex items-center gap-2 font-medium text-gray-900 dark:text-white">
                                <input
                                    type="checkbox"
                                    className="rounded border-gray-300 text-indigo-600"
                                    checked={applyVisibility}
                                    onChange={e => setApplyVisibility(e.target.checked)}
                                />
                                Visibility
                            </label>
                            {applyVisibility ? (
                                <div className="ml-6 flex gap-4">
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name="bulk_show_product"
                                            checked={showProduct}
                                            onChange={() => setShowProduct(true)}
                                        />
                                        Visible
                                    </label>
                                    <label className="flex items-center gap-2">
                                        <input
                                            type="radio"
                                            name="bulk_show_product"
                                            checked={!showProduct}
                                            onChange={() => setShowProduct(false)}
                                        />
                                        Hidden
                                    </label>
                                </div>
                            ) : null}
                        </section>
                    ) : null}

                    {canUpdate ? (
                        <>
                            <section className="space-y-2">
                                <label className="flex items-center gap-2 font-medium text-gray-900 dark:text-white">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600"
                                        checked={applyPricing}
                                        onChange={e => setApplyPricing(e.target.checked)}
                                    />
                                    Pricing
                                </label>
                                {applyPricing ? (
                                    <div className="ml-6 space-y-3 border-l-2 border-indigo-200 dark:border-indigo-800 pl-3">
                                        <div className="flex flex-col gap-2">
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="radio"
                                                    name="bulk_price_mode"
                                                    checked={priceMode === 'absolute'}
                                                    onChange={() => setPriceMode('absolute')}
                                                />
                                                Absolute (set fields below; at least one required)
                                            </label>
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="radio"
                                                    name="bulk_price_mode"
                                                    checked={priceMode === 'relative_percent'}
                                                    onChange={() => setPriceMode('relative_percent')}
                                                />
                                                Relative: percent change on selling price
                                            </label>
                                            <label className="flex items-center gap-2">
                                                <input
                                                    type="radio"
                                                    name="bulk_price_mode"
                                                    checked={priceMode === 'relative_fixed'}
                                                    onChange={() => setPriceMode('relative_fixed')}
                                                />
                                                Relative: fixed amount (add or subtract)
                                            </label>
                                        </div>
                                        {priceMode === 'absolute' ? (
                                            <div className="grid grid-cols-1 gap-2">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="Selling price"
                                                    value={sellingPrice}
                                                    onChange={e => setSellingPrice(e.target.value)}
                                                    className="border rounded-lg px-3 py-2 dark:bg-gray-900 dark:border-gray-600"
                                                />
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="MRP (optional)"
                                                    value={mrp}
                                                    onChange={e => setMrp(e.target.value)}
                                                    className="border rounded-lg px-3 py-2 dark:bg-gray-900 dark:border-gray-600"
                                                />
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    placeholder="Discount % (optional)"
                                                    value={discountPercentage}
                                                    onChange={e => setDiscountPercentage(e.target.value)}
                                                    className="border rounded-lg px-3 py-2 dark:bg-gray-900 dark:border-gray-600"
                                                />
                                            </div>
                                        ) : null}
                                        {priceMode === 'relative_percent' ? (
                                            <input
                                                type="number"
                                                step="0.01"
                                                placeholder="Percent (e.g. 10 or -5)"
                                                value={priceRelativePercent}
                                                onChange={e => setPriceRelativePercent(e.target.value)}
                                                className="border rounded-lg px-3 py-2 w-full dark:bg-gray-900 dark:border-gray-600"
                                            />
                                        ) : null}
                                        {priceMode === 'relative_fixed' ? (
                                            <input
                                                type="number"
                                                step="0.01"
                                                placeholder="Amount to add (negative to subtract)"
                                                value={priceRelativeAmount}
                                                onChange={e => setPriceRelativeAmount(e.target.value)}
                                                className="border rounded-lg px-3 py-2 w-full dark:bg-gray-900 dark:border-gray-600"
                                            />
                                        ) : null}
                                    </div>
                                ) : null}
                            </section>

                            <section className="space-y-3">
                                <p className="font-medium text-gray-900 dark:text-white">Content (same text for all selected)</p>
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600"
                                        checked={applyCustomDescription}
                                        onChange={e => setApplyCustomDescription(e.target.checked)}
                                    />
                                    Custom description
                                </label>
                                {applyCustomDescription ? (
                                    <textarea
                                        value={customDescription}
                                        onChange={e => setCustomDescription(e.target.value)}
                                        rows={3}
                                        className="w-full border rounded-lg px-3 py-2 dark:bg-gray-900 dark:border-gray-600"
                                    />
                                ) : null}
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600"
                                        checked={applyHowToRedeem}
                                        onChange={e => setApplyHowToRedeem(e.target.checked)}
                                    />
                                    How to redeem
                                </label>
                                {applyHowToRedeem ? (
                                    <textarea
                                        value={howToRedeem}
                                        onChange={e => setHowToRedeem(e.target.value)}
                                        rows={3}
                                        className="w-full border rounded-lg px-3 py-2 dark:bg-gray-900 dark:border-gray-600"
                                    />
                                ) : null}
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        className="rounded border-gray-300 text-indigo-600"
                                        checked={applyTerms}
                                        onChange={e => setApplyTerms(e.target.checked)}
                                    />
                                    Terms and conditions
                                </label>
                                {applyTerms ? (
                                    <textarea
                                        value={termsAndConditions}
                                        onChange={e => setTermsAndConditions(e.target.value)}
                                        rows={3}
                                        className="w-full border rounded-lg px-3 py-2 dark:bg-gray-900 dark:border-gray-600"
                                    />
                                ) : null}
                            </section>
                        </>
                    ) : null}
                </div>

                <div className="flex justify-end gap-2 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        disabled={!hasAnyAction}
                        onClick={submit}
                        className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Apply
                    </button>
                </div>
            </div>
        </div>
    );
}
