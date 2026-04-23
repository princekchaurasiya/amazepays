import React, { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { paths } from '@/lib/paths';
import GiftOptionSelector, { type GiftOption, type GiftOptionPolicy } from './GiftOptionSelector';
import GiftPersonalizationPanel, { type GiftPersonalizationState, type GiftTheme } from './GiftPersonalizationPanel';

type PriceData = {
    type: 'SLAB' | 'RANGE';
    denominations: number[];
    min: number | null;
    max: number | null;
};

type Props = {
    slug: string;
    loggedIn: boolean;
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

function normalizeGiftOptionPolicy(v: unknown): GiftOptionPolicy {
    const policy = String(v ?? '').toLowerCase();
    if (policy === 'self_only' || policy === 'gift_only') return policy;
    return 'both';
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
    loggedIn,
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
    const isActionDisabled = isInvalidRange || !loggedIn;

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

    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-4 md:p-5 shadow-sm">
            <div className="mb-3">
                <p className="text-2xl font-semibold text-gray-900">{digitalCardTitle}</p>
                <p className="mt-1 text-sm text-gray-500">{digitalCardSubtitle}</p>
            </div>
            {priceData.type === 'RANGE' ? (
                <div>
                    <label className="text-sm font-semibold text-gray-900">{t('enter_amount', 'Enter amount')}</label>
                    <input
                        type="number"
                        min={canUseRange ? priceData.min ?? undefined : undefined}
                        max={canUseRange ? priceData.max ?? undefined : undefined}
                        value={rangeInput}
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
                                setError(tv('amount_min', 'The amount must be greater than or equal to :min.', { min: formatMoney(minAmount, currencySymbol) }));
                                setErrorField('denomination');
                                return;
                            }
                            if (canUseRange && parsed > maxAmount) {
                                setError(tv('amount_max', 'The amount must be less than or equal to :max.', { max: formatMoney(maxAmount, currencySymbol) }));
                                setErrorField('denomination');
                                return;
                            }
                            setError(null);
                            setErrorField(null);
                        }}
                        className={`mt-2 w-full rounded-lg border px-3 py-2 text-sm [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none ${
                            errorField === 'denomination' ? 'border-red-500 focus:border-red-500' : 'border-gray-300'
                        }`}
                        placeholder={t('enter_denomination', 'Enter denomination')}
                    />
                    <div className="mt-2">
                        {errorField === 'denomination' && error ? (
                            <p className="text-xs text-red-600">{error}</p>
                        ) : canUseRange ? (
                            <p className="text-xs text-gray-600">
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
                <div>
                    <p className="text-sm font-semibold text-gray-900">{t('select_amount', 'Select amount')}</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                        {priceData.denominations.map((deno) => (
                            <button
                                key={deno}
                                type="button"
                                onClick={() => {
                                    setSelectedDenomination(deno);
                                    setError(null);
                                    setErrorField(null);
                                }}
                                className={`rounded-full border px-3 py-1.5 text-sm ${
                                    selectedDenomination === deno
                                        ? 'border-gray-900 bg-gray-900 text-white'
                                        : 'border-gray-300 text-gray-700 hover:border-gray-500'
                                }`}
                            >
                                {formatMoney(deno, currencySymbol)}
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
                    setGiftSendOption(next);
                    onGiftSendOptionChange?.(next);
                    onGiftCustomizeModeChange?.(next === 'send_as_gift' ? giftCustomizeMode : false);
                    if (next !== 'send_as_gift') {
                        setGiftErrors({});
                    }
                    setError(null);
                    setErrorField(null);
                    setActionMessage(null);
                }}
            />

            {giftSendOption === 'send_as_gift' && giftCustomizeMode ? (
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
            ) : null}

            <div className="mt-4 w-full min-w-0 rounded-2xl border border-gray-200 bg-white px-3 py-3 md:px-4">
                <div className="flex w-full min-w-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p className="min-w-0 text-3xl font-semibold tracking-tight text-gray-900 lg:text-[38px]">{formatMoney(total, currencySymbol)}</p>
                    <div className="flex w-full min-w-0 flex-wrap items-center gap-2 md:w-auto md:justify-end md:gap-3">
                        <div className="inline-flex shrink-0 items-center gap-2 rounded-full border border-gray-300 px-2 py-1">
                    <button
                        type="button"
                        className="h-7 w-7 rounded-full text-lg leading-none text-gray-700 hover:bg-gray-100"
                        onClick={() => {
                            setQuantity((q) => Math.max(1, q - 1));
                            setError(null);
                            setErrorField(null);
                            setActionMessage(null);
                        }}
                    >
                        -
                    </button>
                    <span className="min-w-6 text-center text-sm font-semibold">{quantity}</span>
                    <button
                        type="button"
                        className="h-7 w-7 rounded-full text-lg leading-none text-gray-700 hover:bg-gray-100"
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
                        {loggedIn ? (
                            <div className="flex w-full min-w-0 flex-wrap items-center gap-2 md:w-auto">
                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!validate()) return;
                                        setActionMessage(null);
                                        router.post(paths.cartAdd(slug), payload, {
                                            preserveScroll: true,
                                            preserveState: true,
                                            onSuccess: () => setActionMessage(t('added_to_cart', 'Added to cart.')),
                                            onError: () => setActionMessage(t('add_to_cart_failed', 'Could not add to cart. Please check required fields.')),
                                        });
                                    }}
                                    disabled={isActionDisabled}
                                    className="inline-flex w-full justify-center whitespace-nowrap rounded-full border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:border-gray-400 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:px-4"
                                >
                                    {t('add_to_cart', 'Add to Cart')}
                                </button>
                                {giftSendOption === 'send_as_gift' && !giftCustomizeMode ? (
                                    <button
                                        type="button"
                                        onClick={() => onGiftCustomizeModeChange?.(true)}
                                        className="inline-flex w-full justify-center whitespace-nowrap rounded-full bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800 sm:w-auto sm:px-4"
                                    >
                                        {t('customize_gift_card', 'Customize Gift Card')}
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (!validate()) return;
                                            setActionMessage(null);
                                            router.post(paths.checkout(slug), payload, {
                                                preserveScroll: true,
                                                preserveState: true,
                                            });
                                        }}
                                        disabled={isActionDisabled}
                                        className="inline-flex w-full justify-center whitespace-nowrap rounded-full bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:px-4"
                                    >
                                        {t('checkout', 'Checkout')}
                                    </button>
                                )}
                            </div>
                        ) : (
                            <p className="min-w-0 text-sm text-gray-600">{t('login_to_purchase', 'Log in to purchase this gift card.')}</p>
                        )}
                    </div>
                </div>
            </div>
            {errorField === 'quantity' && error ? <p className="mt-2 text-xs text-red-600">{error}</p> : null}
            {actionMessage ? <p className="mt-2 text-xs text-emerald-700">{actionMessage}</p> : null}
        </div>
    );
}
