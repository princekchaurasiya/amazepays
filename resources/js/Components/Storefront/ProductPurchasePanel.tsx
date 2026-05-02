import React, { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { paths } from '@/lib/paths';
import { normalizeGiftOptionPolicy, shouldClearGiftCustomizeMode, type GiftOption, type GiftOptionPolicy } from '@/lib/giftOptions';
import GiftOptionSelector from './GiftOptionSelector';
import GiftPersonalizationPanel, { type GiftPersonalizationState, type GiftTheme } from './GiftPersonalizationPanel';
import { Check, Clock, CreditCard, Loader2, ShieldCheck, ShoppingCart, Zap } from 'lucide-react';

type PriceData = {
    type: 'SLAB' | 'RANGE';
    denominations: number[];
    min: number | null;
    max: number | null;
};

type Props = {
    slug: string;
    price: unknown;
    giftThemes: Array<Record<string, unknown>>;
    giftOptionPolicy?: GiftOptionPolicy | string | null;
    currencySymbol?: string;
    uiText?: Record<string, string>;
    onAmountChange?: (amount: number) => void;
    giftCustomizeMode?: boolean;
    onGiftCustomizeModeChange?: (active: boolean) => void;
    onGiftSendOptionChange?: (option: GiftOption) => void;
    onGiftDataChange?: (data: GiftPersonalizationState) => void;
};

function toNum(v: unknown): number | null {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
}

function normalizePriceData(raw: unknown): PriceData {
    const src = raw && typeof raw === 'object' ? (raw as Record<string, unknown>) : {};
    const rawType = String(src.type ?? '').toUpperCase();
    const denominations = Array.isArray(src.denominations)
        ? src.denominations.map((x) => Number(x)).filter((x) => Number.isFinite(x) && x > 0)
        : [];
    const min = toNum(src.min);
    const max = toNum(src.max);

    if (rawType === 'SLAB' && denominations.length > 0) {
        return { type: 'SLAB', denominations, min: null, max: null };
    }

    const safeMin = min ?? (denominations.length > 0 ? Math.min(...denominations) : null);
    const safeMax = max ?? (denominations.length > 0 ? Math.max(...denominations) : null);

    return { type: 'RANGE', denominations: [], min: safeMin, max: safeMax };
}

function formatMoney(n: number, currencySymbol: string): string {
    return `${currencySymbol}${Math.round(n).toLocaleString('en-IN')}`;
}

function normalizeGiftThemes(raw: Array<Record<string, unknown>>): GiftTheme[] {
    return raw
        .map((item) => {
            const id = Number(item.id);
            const name = String(item.name ?? '').trim();
            const slug = String(item.slug ?? '').trim();
            if (!Number.isFinite(id) || id <= 0 || !name || !slug) {
                return null;
            }
            return {
                id,
                name,
                slug,
                thumbnail_url: typeof item.thumbnail_url === 'string' ? item.thumbnail_url : null,
                image_url: typeof item.image_url === 'string' ? item.image_url : null,
                gallery_images: Array.isArray(item.gallery_images)
                    ? item.gallery_images.filter((value): value is string => typeof value === 'string' && value.length > 0)
                    : [],
            };
        })
        .filter((theme): theme is NonNullable<typeof theme> => theme !== null);
}

export default function ProductPurchasePanel({
    slug,
    price,
    giftThemes,
    giftOptionPolicy,
    currencySymbol = '₹',
    uiText = {},
    onAmountChange,
    giftCustomizeMode = false,
    onGiftCustomizeModeChange,
    onGiftSendOptionChange,
    onGiftDataChange,
}: Props) {
    /* UI: SLAB renders as selectable tiles, RANGE as rupee-prefixed input.
       Layout stacks on mobile and aligns two columns inside the totals/actions card on larger screens. */
    type GiftFieldKey = keyof GiftPersonalizationState;
    const priceData = useMemo(() => normalizePriceData(price), [price]);
    const page = usePage<{ i18n?: { storefront?: { product?: Record<string, unknown> } } }>();
    const policy = useMemo(() => normalizeGiftOptionPolicy(giftOptionPolicy), [giftOptionPolicy]);
    const themes = useMemo(() => normalizeGiftThemes(giftThemes), [giftThemes]);
    const storefrontProductText = (page.props.i18n?.storefront?.product ?? {}) as Record<string, unknown>;

    const [quantity, setQuantity] = useState<number>(1);
    const [selectedDenomination, setSelectedDenomination] = useState<number>(
        priceData.type === 'SLAB' ? (priceData.denominations[0] ?? 0) : (priceData.min ?? 0),
    );
    const [rangeInput, setRangeInput] = useState<string>(priceData.min ? String(priceData.min) : '');
    const [error, setError] = useState<string | null>(null);
    const [errorField, setErrorField] = useState<'denomination' | 'quantity' | 'gift' | null>(null);
    const [giftErrors, setGiftErrors] = useState<Partial<Record<GiftFieldKey, string>>>({});
    const [giftSendOption, setGiftSendOption] = useState<GiftOption>(policy === 'gift_only' ? 'send_as_gift' : 'buy_for_self');
    const [giftData, setGiftData] = useState<GiftPersonalizationState>({
        gift_theme_id: '',
        gift_theme_image_url: '',
        gift_message_title: '',
        receiver_msg: '',
        sender_first_name: '',
        receiver_name: '',
        receiver_mobile: '',
        receiver_email: '',
        gift_delivery_option: 'send_now',
        gift_delivery_at: '',
    });
    const [actionMessage, setActionMessage] = useState<string | null>(null);
    const [pendingAction, setPendingAction] = useState<null | 'cart' | 'checkout'>(null);

    const canUseRange = priceData.type === 'RANGE' && priceData.min !== null && priceData.max !== null;
    const minAmount = priceData.min ?? 0;
    const maxAmount = priceData.max ?? 0;
    const t = (key: string, fallback: string) => {
        const fromProp = uiText[key];
        if (typeof fromProp === 'string' && fromProp !== '') return fromProp;
        const fromShared = storefrontProductText[key];
        if (typeof fromShared === 'string' && fromShared !== '') return fromShared;
        return fallback;
    };
    const tv = (key: string, fallback: string, vars: Record<string, string> = {}) => {
        let msg = t(`validation.${key}`, fallback);
        Object.entries(vars).forEach(([k, v]) => {
            msg = msg.replace(`:${k}`, v);
        });
        return msg;
    };
    const digitalCardTitle = t('digital_card_title', 'Digital Card');
    const digitalCardSubtitle = t('digital_card_subtitle', 'Added instantly to your account. Or, sent via email/SMS to the recipient.');

    const denomination = priceData.type === 'SLAB' ? selectedDenomination : Number(rangeInput || 0);
    const total = denomination > 0 ? denomination * quantity : 0;

    useEffect(() => {
        if (!onAmountChange) return;
        // Keep the product hero card in sync with the actual payable total (denomination x quantity).
        onAmountChange(total > 0 ? total : 0);
    }, [total, onAmountChange]);

    useEffect(() => {
        onGiftDataChange?.(giftData);
    }, [giftData, onGiftDataChange]);

    useEffect(() => {
        if (!giftData.gift_theme_id) {
            if (giftData.gift_theme_image_url !== '') {
                setGiftData((prev) => ({ ...prev, gift_theme_image_url: '' }));
            }
            return;
        }

        const activeTheme = themes.find((theme) => String(theme.id) === giftData.gift_theme_id);
        if (!activeTheme) {
            if (giftData.gift_theme_image_url !== '') {
                setGiftData((prev) => ({ ...prev, gift_theme_image_url: '' }));
            }
            return;
        }

        const gallery = activeTheme.gallery_images && activeTheme.gallery_images.length > 0
            ? activeTheme.gallery_images
            : [activeTheme.thumbnail_url, activeTheme.image_url].filter((value): value is string => typeof value === 'string' && value.length > 0);

        if (gallery.length === 0) {
            if (giftData.gift_theme_image_url !== '') {
                setGiftData((prev) => ({ ...prev, gift_theme_image_url: '' }));
            }
            return;
        }

        if (!gallery.includes(giftData.gift_theme_image_url)) {
            setGiftData((prev) => ({ ...prev, gift_theme_image_url: gallery[0] ?? '' }));
        }
    }, [giftData.gift_theme_id, giftData.gift_theme_image_url, themes]);

    useEffect(() => {
        onGiftSendOptionChange?.(giftSendOption);
    }, [giftSendOption, onGiftSendOptionChange]);

    const payload = useMemo(() => {
        const base = {
            denomination: denomination || 0,
            quantity,
            gift_send_option: giftSendOption,
        };

        if (giftSendOption !== 'send_as_gift') {
            return base;
        }

        return {
            ...base,
            gift_theme_id: giftData.gift_theme_id,
            gift_message_title: giftData.gift_message_title,
            receiver_msg: giftData.receiver_msg,
            sender_first_name: giftData.sender_first_name,
            receiver_name: giftData.receiver_name,
            receiver_mobile: giftData.receiver_mobile,
            receiver_email: giftData.receiver_email,
            gift_delivery_option: giftData.gift_delivery_option,
            ...(giftData.gift_delivery_option === 'send_later' ? { gift_delivery_at: giftData.gift_delivery_at } : {}),
        };
    }, [denomination, giftData, giftSendOption, quantity]);

    const isInvalidRange = priceData.type === 'RANGE' && (!!error || rangeInput === '');
    const isActionDisabled = isInvalidRange;

    const validate = (): boolean => {
        if (quantity < 1 || quantity > 10) {
            setError(tv('quantity_range', 'Quantity must be between 1 and 10.'));
            setErrorField('quantity');
            return false;
        }

        if (priceData.type === 'SLAB') {
            if (!priceData.denominations.includes(denomination)) {
                setError(tv('invalid_denomination', 'Please select a valid denomination.'));
                setErrorField('denomination');
                return false;
            }
            setError(null);
            setErrorField(null);
        } else {
            if (!canUseRange) {
                setError(t('range_not_configured', 'Price range not configured.'));
                setErrorField('denomination');
                return false;
            }

            if (!Number.isFinite(denomination) || denomination <= 0) {
                setError(tv('invalid_amount', 'Please enter a valid amount.'));
                setErrorField('denomination');
                return false;
            }

            if (denomination < minAmount) {
                setError(tv('amount_min', 'The amount must be greater than or equal to :min.', { min: formatMoney(minAmount, currencySymbol) }));
                setErrorField('denomination');
                return false;
            }

            if (denomination > maxAmount) {
                setError(tv('amount_max', 'The amount must be less than or equal to :max.', { max: formatMoney(maxAmount, currencySymbol) }));
                setErrorField('denomination');
                return false;
            }
        }

        setError(null);
        setErrorField(null);

        if (giftSendOption === 'send_as_gift') {
            const nextGiftErrors: Partial<Record<GiftFieldKey, string>> = {};

            if (!giftData.gift_theme_id || !themes.some((theme) => String(theme.id) === giftData.gift_theme_id)) {
                nextGiftErrors.gift_theme_id = tv('gift_theme_required', 'Please select a valid gift theme.');
            }
            if (!giftData.gift_message_title.trim()) {
                nextGiftErrors.gift_message_title = tv('gift_title_required', 'Message title is required for gift checkout.');
            }
            if (!giftData.receiver_msg.trim()) {
                nextGiftErrors.receiver_msg = tv('gift_message_required', 'Gift message is required.');
            }
            if (!giftData.sender_first_name.trim()) {
                nextGiftErrors.sender_first_name = tv('sender_required', 'Please enter who the gift is from.');
            }
            if (!giftData.receiver_name.trim()) {
                nextGiftErrors.receiver_name = tv('recipient_name_required', 'Recipient name is required.');
            }
            if (!/^\d{10}$/.test(giftData.receiver_mobile.trim())) {
                nextGiftErrors.receiver_mobile = tv('recipient_mobile_invalid', 'Recipient mobile must be exactly 10 digits.');
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(giftData.receiver_email.trim())) {
                nextGiftErrors.receiver_email = tv('recipient_email_invalid', 'Please enter a valid recipient email.');
            }
            if (!giftData.gift_delivery_option) {
                nextGiftErrors.gift_delivery_option = tv('delivery_option_required', 'Please select a delivery option.');
            }
            if (giftData.gift_delivery_option === 'send_later') {
                const deliveryTs = Date.parse(giftData.gift_delivery_at);
                if (!giftData.gift_delivery_at || Number.isNaN(deliveryTs) || deliveryTs <= Date.now()) {
                    nextGiftErrors.gift_delivery_at = tv('delivery_datetime_invalid', 'Please choose a future date and time for delivery.');
                }
            }

            if (Object.keys(nextGiftErrors).length > 0) {
                setGiftErrors(nextGiftErrors);
                return false;
            }
        }

        setError(null);
        setErrorField(null);
        setGiftErrors({});
        return true;
    };

    const rangeHintId = `range-hint-${slug}`;
    const rangeErrorId = `range-error-${slug}`;

    const isBusy = pendingAction !== null;
    const inputBaseClass =
        'w-full rounded-lg border bg-white py-2.5 pl-8 pr-3 text-sm [appearance:textfield] outline-none transition focus:border-product-primary focus:ring-2 focus:ring-product-primary/30 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none';

    return (
        <div className="w-full max-w-lg rounded-2xl border border-gray-200/80 bg-white p-6 shadow-md">
            <h2 className="text-xl font-semibold text-gray-800">{digitalCardTitle}</h2>
            <p className="mt-1 text-sm text-gray-500">{digitalCardSubtitle}</p>

            {priceData.type === 'RANGE' ? (
                <div className="mb-4 mt-5">
                    <label htmlFor={`range-${slug}`} className="mb-1 block text-sm font-medium text-gray-700">
                        {t('enter_amount', 'Enter amount')}
                    </label>
                    <div className="relative">
                        <span className="pointer-events-none absolute left-3 top-2.5 text-sm text-gray-500">{currencySymbol}</span>
                        <input
                            id={`range-${slug}`}
                            type="number"
                            min={canUseRange ? priceData.min ?? undefined : undefined}
                            max={canUseRange ? priceData.max ?? undefined : undefined}
                            value={rangeInput}
                            inputMode="numeric"
                            aria-invalid={errorField === 'denomination' && Boolean(error)}
                            aria-describedby={errorField === 'denomination' && error ? rangeErrorId : rangeHintId}
                            onChange={(e) => {
                                const next = e.target.value;
                                setRangeInput(next);
                                const parsed = Number(next || 0);
                                if (next === '') {
                                    setError(null);
                                    setErrorField(null);
                                    return;
                                }
                                if (!Number.isFinite(parsed) || parsed <= 0) {
                                    setError(tv('invalid_amount', 'Please enter a valid amount.'));
                                    setErrorField('denomination');
                                    return;
                                }
                                if (canUseRange && parsed < minAmount) {
                                    setError(
                                        tv('amount_min', 'The amount must be greater than or equal to :min.', {
                                            min: formatMoney(minAmount, currencySymbol),
                                        }),
                                    );
                                    setErrorField('denomination');
                                    return;
                                }
                                if (canUseRange && parsed > maxAmount) {
                                    setError(
                                        tv('amount_max', 'The amount must be less than or equal to :max.', {
                                            max: formatMoney(maxAmount, currencySymbol),
                                        }),
                                    );
                                    setErrorField('denomination');
                                    return;
                                }
                                setError(null);
                                setErrorField(null);
                            }}
                            className={`${inputBaseClass} ${
                                errorField === 'denomination' ? 'border-red-500 focus:ring-red-200' : 'border-gray-300'
                            }`}
                            placeholder={t('enter_denomination', 'Enter denomination')}
                        />
                    </div>
                    <div className="mt-1.5">
                        {errorField === 'denomination' && error ? (
                            <p id={rangeErrorId} className="text-xs text-red-600">
                                {error}
                            </p>
                        ) : canUseRange ? (
                            <p id={rangeHintId} className="text-xs text-gray-400">
                                {t('range_hint', 'Enter a value between :min and :max.')
                                    .replace(':min', formatMoney(minAmount, currencySymbol))
                                    .replace(':max', formatMoney(maxAmount, currencySymbol))}
                            </p>
                        ) : (
                            <p className="text-xs text-amber-700">{t('range_not_configured', 'Price range not configured.')}</p>
                        )}
                    </div>
                </div>
            ) : (
                <div className="mb-4 mt-5">
                    <p className="mb-1 text-sm font-medium text-gray-700">{t('select_amount', 'Select amount')}</p>
                    <div className="grid grid-cols-3 gap-2 sm:gap-3">
                        {priceData.denominations.map((deno) => (
                            <button
                                key={deno}
                                type="button"
                                onClick={() => {
                                    setSelectedDenomination(deno);
                                    setError(null);
                                    setErrorField(null);
                                }}
                                aria-pressed={selectedDenomination === deno}
                                className={`relative flex h-11 items-center justify-center rounded-lg border bg-white px-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-product-primary/40 ${
                                    selectedDenomination === deno
                                        ? 'border-product-primary text-product-primary ring-1 ring-product-primary bg-blue-50'
                                        : 'border-gray-300 text-gray-700 hover:border-product-primary/40'
                                }`}
                            >
                                {formatMoney(deno, currencySymbol)}
                                {selectedDenomination === deno ? (
                                    <span className="absolute right-1.5 top-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-product-primary text-white">
                                        <Check className="h-3 w-3" aria-hidden="true" />
                                    </span>
                                ) : null}
                            </button>
                        ))}
                    </div>
                    {errorField === 'denomination' && error ? <p className="mt-2 text-xs text-red-600">{error}</p> : null}
                </div>
            )}

            <GiftOptionSelector
                policy={policy}
                value={giftSendOption}
                onChange={(next) => {
                    const previous = giftSendOption;
                    setGiftSendOption(next);
                    onGiftSendOptionChange?.(next);
                    if (shouldClearGiftCustomizeMode(previous, next)) {
                        onGiftCustomizeModeChange?.(false);
                    }
                    if (next !== 'send_as_gift') {
                        setGiftErrors({});
                    }
                    setError(null);
                    setErrorField(null);
                    setActionMessage(null);
                }}
            />

            {giftSendOption === 'send_as_gift' && giftCustomizeMode ? (
                <div className="mb-5">
                    <GiftPersonalizationPanel
                        themes={themes}
                        data={giftData}
                        text={(storefrontProductText.gift_panel as Record<string, string> | undefined) ?? {}}
                        onChange={(next) => {
                            const changedKeys = (Object.keys(next) as GiftFieldKey[]).filter((key) => next[key] !== giftData[key]);
                            setGiftData(next);
                            onGiftDataChange?.(next);
                            if (changedKeys.length > 0) {
                                setGiftErrors((prev) => {
                                    if (Object.keys(prev).length === 0) return prev;
                                    const copy = { ...prev };
                                    changedKeys.forEach((key) => {
                                        delete copy[key];
                                    });
                                    return copy;
                                });
                            }
                            setActionMessage(null);
                        }}
                        errors={giftErrors}
                    />
                </div>
            ) : null}

            <div className="mb-5 flex w-full min-w-0 items-center justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{t('total_amount', 'Total amount')}</p>
                    <p className="mt-0.5 text-2xl font-bold tabular-nums text-gray-900">{formatMoney(total, currencySymbol)}</p>
                </div>
                <div>
                    <p className="mb-1.5 text-right text-xs font-medium text-gray-500 sm:hidden">{t('quantity', 'Quantity')}</p>
                    <div className="inline-flex items-stretch overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <button
                            type="button"
                            aria-label={t('decrease_quantity', 'Decrease quantity')}
                            className="px-3 py-1.5 text-base leading-none text-gray-600 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-product-primary/40 active:scale-95"
                            onClick={() => {
                                setQuantity((q) => Math.max(1, q - 1));
                                setError(null);
                                setErrorField(null);
                                setActionMessage(null);
                            }}
                        >
                            -
                        </button>
                        <span className="flex min-w-10 items-center justify-center border-x border-gray-200 px-3 text-sm font-semibold tabular-nums">
                            {quantity}
                        </span>
                        <button
                            type="button"
                            aria-label={t('increase_quantity', 'Increase quantity')}
                            className="px-3 py-1.5 text-base leading-none text-gray-600 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-product-primary/40 active:scale-95"
                            onClick={() => {
                                setQuantity((q) => Math.min(10, q + 1));
                                setError(null);
                                setErrorField(null);
                                setActionMessage(null);
                            }}
                        >
                            +
                        </button>
                    </div>
                </div>
            </div>

            <div className="flex w-full min-w-0 flex-col gap-3 sm:flex-row">
                {giftSendOption === 'send_as_gift' && !giftCustomizeMode ? (
                    <>
                        <button
                            type="button"
                            onClick={() => {
                                if (!validate()) return;
                                setActionMessage(null);
                                router.post(paths.cartAdd(slug), payload, {
                                    preserveScroll: true,
                                    preserveState: true,
                                    onStart: () => setPendingAction('cart'),
                                    onFinish: () => setPendingAction(null),
                                    onSuccess: () => setActionMessage(t('added_to_cart', 'Added to cart.')),
                                    onError: () => setActionMessage(t('add_to_cart_failed', 'Could not add to cart. Please check required fields.')),
                                });
                            }}
                            disabled={isActionDisabled || isBusy}
                            className="inline-flex w-full min-w-0 flex-1 items-center justify-center gap-2 rounded-lg border border-product-primary bg-white py-2.5 text-sm font-medium text-product-primary transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-[2.75rem]"
                        >
                            {pendingAction === 'cart' ? (
                                <>
                                    <Loader2 className="h-4 w-4 shrink-0 animate-spin" aria-hidden="true" />
                                    {t('adding_to_cart', 'Adding…')}
                                </>
                            ) : (
                                <>
                                    <ShoppingCart className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    {t('add_to_cart', 'Add to Cart')}
                                </>
                            )}
                        </button>
                        <button
                            type="button"
                            onClick={() => onGiftCustomizeModeChange?.(true)}
                            className="inline-flex w-full min-w-0 flex-[1.15] items-center justify-center gap-2 rounded-lg bg-product-primary py-3 text-base font-semibold text-white shadow-md transition hover:bg-product-primary/90 sm:min-h-[3rem]"
                        >
                            <CreditCard className="h-5 w-5 shrink-0" aria-hidden="true" />
                            {t('customize_gift_card', 'Customize Gift Card')}
                        </button>
                    </>
                ) : (
                    <>
                        <button
                            type="button"
                            onClick={() => {
                                if (!validate()) return;
                                setActionMessage(null);
                                router.post(paths.cartAdd(slug), payload, {
                                    preserveScroll: true,
                                    preserveState: true,
                                    onStart: () => setPendingAction('cart'),
                                    onFinish: () => setPendingAction(null),
                                    onSuccess: () => setActionMessage(t('added_to_cart', 'Added to cart.')),
                                    onError: () => setActionMessage(t('add_to_cart_failed', 'Could not add to cart. Please check required fields.')),
                                });
                            }}
                            disabled={isActionDisabled || isBusy}
                            className="inline-flex w-full min-w-0 flex-1 items-center justify-center gap-2 rounded-lg border border-product-primary bg-white py-2.5 text-sm font-medium text-product-primary transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-[2.75rem]"
                        >
                            {pendingAction === 'cart' ? (
                                <>
                                    <Loader2 className="h-4 w-4 shrink-0 animate-spin" aria-hidden="true" />
                                    {t('adding_to_cart', 'Adding…')}
                                </>
                            ) : (
                                <>
                                    <ShoppingCart className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    {t('add_to_cart', 'Add to Cart')}
                                </>
                            )}
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                if (!validate()) return;
                                setActionMessage(null);
                                router.post(paths.cartAdd(slug), payload, {
                                    preserveScroll: true,
                                    preserveState: true,
                                    onStart: () => setPendingAction('checkout'),
                                    onFinish: () => setPendingAction(null),
                                    onSuccess: () => router.visit(paths.checkout(slug)),
                                    onError: () => setActionMessage(t('checkout_failed_prefix', 'Could not proceed. Please review the form.')),
                                });
                            }}
                            disabled={isActionDisabled || isBusy}
                            className="inline-flex w-full min-w-0 flex-[1.2] items-center justify-center gap-2 rounded-lg bg-product-primary py-3 text-base font-semibold text-white shadow-md transition hover:bg-product-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-product-primary/50 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-[3rem]"
                        >
                            {pendingAction === 'checkout' ? (
                                <>
                                    <Loader2 className="h-5 w-5 shrink-0 animate-spin" aria-hidden="true" />
                                    {t('please_wait', 'Please wait')}…
                                </>
                            ) : (
                                <>
                                    <Zap className="h-5 w-5 shrink-0" aria-hidden="true" />
                                    {t('checkout_now', t('checkout', 'Checkout'))}
                                </>
                            )}
                        </button>
                    </>
                )}
            </div>

            <div className="mt-4 flex w-full items-center justify-between gap-2 text-xs text-gray-400">
                <span className="inline-flex min-w-0 items-center gap-1">
                    <ShieldCheck className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    <span className="truncate">{t('secure_transaction', 'Secure Transaction')}</span>
                </span>
                <span className="inline-flex min-w-0 items-center justify-end gap-1">
                    <Clock className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    <span className="truncate">{t('instant_delivery', 'Instant Delivery')}</span>
                </span>
            </div>
            {errorField === 'quantity' && error ? <p className="mt-2 text-xs text-red-600">{error}</p> : null}
            {actionMessage ? <p className="mt-2 text-xs text-emerald-700">{actionMessage}</p> : null}
        </div>
    );
}
