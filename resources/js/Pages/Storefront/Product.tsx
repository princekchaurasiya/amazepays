import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { ProductInfoModal, type ProductInfoSection, ProductPurchasePanel, type GiftPersonalizationState } from '@/Components/Storefront';
import { normalizeGiftOptionPolicy } from '@/lib/giftOptions';
import { Gift } from 'lucide-react';

export default function ProductPage({
    productDetails,
    formattedTncData,
    descriptionData,
    formatteddecodedHowToUse,
    giftThemes,
    uiText,
}: {
    productDetails: Record<string, unknown>;
    formattedTncData?: string | null;
    descriptionData?: string | null;
    formatteddecodedHowToUse?: string | null;
    giftThemes?: Array<Record<string, unknown>>;
    uiText?: Record<string, string>;
}) {
    const page = usePage<{ auth?: { user?: unknown }; i18n?: { storefront?: { product?: Record<string, string> } } }>();
    const loggedIn = Boolean(page.props.auth?.user);
    const storefrontProductCopy = page.props.i18n?.storefront?.product;
    const t = useCallback(
        (key: string, fallback: string): string => {
            const v = storefrontProductCopy?.[key];
            return typeof v === 'string' && v !== '' ? v : fallback;
        },
        [storefrontProductCopy],
    );
    const slug = String(productDetails.slug ?? productDetails.url ?? '');
    const name = String(productDetails.display_name ?? productDetails.name ?? 'Gift card');
    const fallbackImg = productDetails.display_image_url as string | undefined;
    const rawImages = productDetails.images as unknown;
    const rawCardTheme = useMemo(
        () => (productDetails.card_theme as Record<string, unknown> | undefined) ?? {},
        [productDetails.card_theme],
    );
    const defaultCardValue = Number(productDetails.default_card_value ?? 0);

    let highQualityImg: string | undefined;
    if (typeof rawImages === 'string' && rawImages.trim() !== '') {
        try {
            const parsed = JSON.parse(rawImages) as Record<string, unknown> | string[];
            if (Array.isArray(parsed)) {
                highQualityImg = typeof parsed[0] === 'string' ? parsed[0] : undefined;
            } else if (parsed && typeof parsed === 'object') {
                highQualityImg =
                    (typeof parsed.large === 'string' && parsed.large) ||
                    (typeof parsed.original === 'string' && parsed.original) ||
                    (typeof parsed.small === 'string' && parsed.small) ||
                    undefined;
            }
        } catch {
            highQualityImg = undefined;
        }
    } else if (rawImages && typeof rawImages === 'object') {
        const parsed = rawImages as Record<string, unknown>;
        highQualityImg =
            (typeof parsed.large === 'string' && parsed.large) ||
            (typeof parsed.original === 'string' && parsed.original) ||
            (typeof parsed.small === 'string' && parsed.small) ||
            undefined;
    }

    const img = highQualityImg || fallbackImg;
    const [imageFailed, setImageFailed] = useState(false);
    const [cardTotalValue, setCardTotalValue] = useState<number>(
        Number.isFinite(defaultCardValue) && defaultCardValue > 0 ? defaultCardValue : 0,
    );
    const [giftCustomizeMode, setGiftCustomizeMode] = useState(false);
    const [giftSendOption, setGiftSendOption] = useState<'send_as_gift' | 'buy_for_self'>(() =>
        normalizeGiftOptionPolicy(productDetails.gift_option_policy) === 'gift_only' ? 'send_as_gift' : 'buy_for_self',
    );
    const [giftPreviewData, setGiftPreviewData] = useState<GiftPersonalizationState>({
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
    const [infoModalOpen, setInfoModalOpen] = useState(false);
    const [activeInfoSectionId, setActiveInfoSectionId] = useState('about-brand');

    const previousLoggedInRef = useRef<boolean>(loggedIn);
    useEffect(() => {
        if (previousLoggedInRef.current === true && loggedIn === false) {
            setGiftCustomizeMode(false);
        }
        previousLoggedInRef.current = loggedIn;
    }, [loggedIn]);

    const cardTheme = useMemo(
        () => ({
            logoUrl: typeof rawCardTheme.logo_url === 'string' ? rawCardTheme.logo_url : null,
            bgColor: typeof rawCardTheme.bg_color === 'string' ? rawCardTheme.bg_color : '#f3f4f6',
            textColor: typeof rawCardTheme.text_color === 'string' ? rawCardTheme.text_color : '#111827',
            accentColor: typeof rawCardTheme.accent_color === 'string' ? rawCardTheme.accent_color : '#0f766e',
        }),
        [rawCardTheme],
    );

    const instructionHtml = String(
        productDetails.important_instructions ??
            productDetails.importantInstructions ??
            productDetails.instructions ??
            productDetails.important_instruction ??
            '',
    ).trim();
    const brandSummaryHtml = String(
        productDetails.brand_description ??
            productDetails.brandDescription ??
            productDetails.about_brand ??
            productDetails.aboutBrand ??
            descriptionData ??
            '',
    ).trim();
    const validityText = String(
        productDetails.validity_text ??
            productDetails.validity ??
            productDetails.valid_upto ??
            productDetails.expiry_info ??
            productDetails.expiry ??
            '',
    ).trim();
    const hasAboutBrandCard = brandSummaryHtml.length > 0;
    const hasValidityCard = validityText.length > 0;

    const infoSections = useMemo<ProductInfoSection[]>(
        () =>
            [
                { id: 'about-brand', label: t('section_about_brand', 'About Brand'), contentHtml: brandSummaryHtml },
                { id: 'important-instructions', label: t('section_important_instructions', 'Important Instructions'), contentHtml: instructionHtml },
                { id: 'how-to-use', label: t('section_how_to_use', 'How To Use'), contentHtml: formatteddecodedHowToUse ?? '' },
                { id: 'terms-and-conditions', label: t('section_terms_and_conditions', 'Terms & Conditions'), contentHtml: formattedTncData ?? '' },
            ].filter((section) => section.contentHtml.trim().length > 0),
        [brandSummaryHtml, formattedTncData, formatteddecodedHowToUse, instructionHtml, t],
    );

    const activeGiftTheme = useMemo(() => {
        const selectedId = Number(giftPreviewData.gift_theme_id || 0);
        if (!Array.isArray(giftThemes) || !Number.isFinite(selectedId) || selectedId <= 0) return null;
        return giftThemes.find((theme) => Number(theme.id) === selectedId) ?? null;
    }, [giftPreviewData.gift_theme_id, giftThemes]);
    const activeGiftThemeImage = useMemo(() => {
        const selected = giftPreviewData.gift_theme_image_url?.trim();
        if (selected) {
            return selected;
        }
        const gallery = Array.isArray(activeGiftTheme?.gallery_images)
            ? activeGiftTheme.gallery_images.find((value): value is string => typeof value === 'string' && value.trim().length > 0)
            : null;
        if (gallery) {
            return gallery;
        }
        if (typeof activeGiftTheme?.image_url === 'string' && activeGiftTheme.image_url.trim().length > 0) {
            return activeGiftTheme.image_url;
        }
        return null;
    }, [activeGiftTheme, giftPreviewData.gift_theme_image_url]);

    const openInfoSection = (sectionId: string) => {
        setActiveInfoSectionId(sectionId);
        setInfoModalOpen(true);
    };

    return (
        <StorefrontLayout>
            <Head title={name} />
            {/* Responsive: single column <lg, two columns at lg+, with a stable left “brand card” stack. */}
            <div className="mx-auto w-full max-w-6xl px-4 py-6 md:py-8">
                <div className="grid w-full min-w-0 items-start gap-8 md:grid-cols-2 xl:grid-cols-12">
                    <div
                        className={`w-full min-w-0 ${
                            giftCustomizeMode ? 'xl:col-span-5 xl:sticky xl:top-24 self-start' : 'xl:col-span-6'
                        }`}
                    >
                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div className="bg-product-navy px-5 py-5 text-white">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                                        <Gift className="h-6 w-6 text-white" aria-hidden="true" />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-xs font-semibold uppercase tracking-widest text-white/70">
                                            {t('digital_card_title', 'Digital Card')}
                                        </p>
                                        <h2 className="mt-1 break-words text-lg font-semibold leading-tight">{name}</h2>
                                    </div>
                                </div>
                            </div>

                            <div className="p-5">
                                <div className="rounded-xl border border-gray-200 bg-product-canvas p-4">
                                    <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm" style={{ backgroundColor: cardTheme.bgColor }}>
                                        {giftCustomizeMode && giftSendOption === 'send_as_gift' ? (
                                            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                                {activeGiftThemeImage ? (
                                                    <div className="aspect-[16/10] overflow-hidden bg-gray-100">
                                                        <img
                                                            src={activeGiftThemeImage}
                                                            alt={activeGiftTheme?.name ? `${String(activeGiftTheme.name)} theme` : 'Gift theme'}
                                                            className="h-full w-full object-cover"
                                                        />
                                                    </div>
                                                ) : (
                                                    <div className="flex aspect-[16/10] items-center justify-center bg-gray-100 text-sm font-medium text-gray-500">
                                                        {t('theme_fallback', 'Theme preview')}
                                                    </div>
                                                )}
                                                <div className="space-y-2 border-t border-gray-200 bg-white px-4 py-3 text-center">
                                                    <p className="text-xs font-semibold text-gray-600">
                                                        {t('gift_preview_recipient', 'Dear')}{' '}
                                                        {giftPreviewData.receiver_name || t('gift_preview_recipient_placeholder', '[Recipient]')}
                                                    </p>
                                                    <p className="text-lg font-bold text-gray-900">
                                                        {giftPreviewData.gift_message_title || t('gift_preview_title', 'Your message title goes here')}
                                                    </p>
                                                    <p className="line-clamp-3 text-sm text-gray-700">
                                                        {giftPreviewData.receiver_msg || t('gift_preview_message', 'Your personalized message will be shown here.')}
                                                    </p>
                                                    <p className="text-xs font-semibold text-gray-700">
                                                        {giftPreviewData.sender_first_name
                                                            ? `${t('gift_preview_from', 'From')}: ${giftPreviewData.sender_first_name}`
                                                            : t('gift_preview_from_placeholder', 'From [Your Name]')}
                                                    </p>
                                                </div>
                                            </div>
                                        ) : !imageFailed && img ? (
                                            <img
                                                src={img}
                                                alt={name}
                                                loading="eager"
                                                decoding="async"
                                                onError={() => setImageFailed(true)}
                                                className="aspect-[16/10] w-full rounded-xl object-contain bg-white/70 p-2"
                                            />
                                        ) : (
                                            <div className="flex aspect-[16/10] items-center justify-center rounded-xl bg-white/70 p-3">
                                                {cardTheme.logoUrl ? (
                                                    <img
                                                        src={cardTheme.logoUrl}
                                                        alt={`${name} logo`}
                                                        onError={() => setImageFailed(true)}
                                                        className="max-h-20 w-auto object-contain"
                                                    />
                                                ) : (
                                                    <div className="text-4xl font-bold" style={{ color: cardTheme.textColor }}>
                                                        {name.slice(0, 1)}
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        <div className="mt-4 text-center">
                                            <p className="text-[11px] font-semibold uppercase tracking-widest" style={{ color: cardTheme.textColor }}>
                                                {t('card_total_value', 'Card Total Value')}
                                            </p>
                                            <p className="mt-1 text-4xl font-extrabold leading-none" style={{ color: cardTheme.textColor }}>
                                                ₹{Math.round(cardTotalValue).toLocaleString('en-IN')}
                                            </p>
                                            <p className="mt-2 text-sm font-semibold" style={{ color: cardTheme.accentColor }}>
                                                {Number(productDetails.discount_percentage ?? 0) > 0
                                                    ? t('get_for_off', 'Get for :percent% off').replace(':percent', String(Number(productDetails.discount_percentage)))
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-4 space-y-3">
                                    {hasAboutBrandCard ? (
                                        <button
                                            type="button"
                                            onClick={() => openInfoSection('about-brand')}
                                            className="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-900 hover:border-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-product-primary focus-visible:ring-offset-2"
                                        >
                                            {t('about_the_brand', 'About the Brand')}
                                        </button>
                                    ) : null}

                                    {hasValidityCard ? (
                                        <div className="rounded-lg border border-gray-200 bg-product-canvas px-4 py-3 text-sm text-gray-700">
                                            <span className="font-semibold text-gray-900">{t('validity', 'Validity')}:</span> {validityText}
                                        </div>
                                    ) : null}
                                </div>

                                {infoSections.length > 0 ? (
                                    <div className="mt-4 flex flex-wrap items-center gap-4 border-t border-gray-200 pt-4">
                                        {infoSections.map((section) => (
                                            <button
                                                key={section.id}
                                                type="button"
                                                onClick={() => openInfoSection(section.id)}
                                                className={`text-xs font-medium ${
                                                    section.id === activeInfoSectionId && infoModalOpen
                                                        ? 'text-gray-900 underline decoration-gray-400 underline-offset-4'
                                                        : 'text-gray-700 hover:text-gray-900'
                                                }`}
                                            >
                                                {section.label}
                                            </button>
                                        ))}
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    </div>
                    <div className={`w-full min-w-0 ${giftCustomizeMode ? 'xl:col-span-7' : 'xl:col-span-6'}`}>
                        {productDetails.discount_percentage != null && Number(productDetails.discount_percentage) > 0 && (
                            <p className="mt-2 text-emerald-600">{String(productDetails.discount_percentage)}% off</p>
                        )}
                        {slug ? (
                            <div className="mt-2 flex w-full justify-end md:mt-0">
                                <ProductPurchasePanel
                                    slug={slug}
                                    price={productDetails.price}
                                    giftOptionPolicy={productDetails.gift_option_policy as string | undefined}
                                    giftThemes={giftThemes ?? []}
                                    uiText={uiText ?? {}}
                                    onAmountChange={(amount) => setCardTotalValue((prev) => (amount > 0 ? amount : prev))}
                                    giftCustomizeMode={giftCustomizeMode}
                                    onGiftCustomizeModeChange={setGiftCustomizeMode}
                                    onGiftSendOptionChange={setGiftSendOption}
                                    onGiftDataChange={setGiftPreviewData}
                                />
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>
            <ProductInfoModal
                open={infoModalOpen}
                title={infoSections.find((section) => section.id === activeInfoSectionId)?.label ?? t('product_information', 'Product information')}
                sections={infoSections}
                activeSectionId={activeInfoSectionId}
                onChangeSection={setActiveInfoSectionId}
                onClose={() => setInfoModalOpen(false)}
                sideImageUrl={!imageFailed && img ? img : null}
            />
        </StorefrontLayout>
    );
}
