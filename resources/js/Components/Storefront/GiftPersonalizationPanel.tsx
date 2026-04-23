import React from 'react';
import SquareImageThumbnail from '@/Components/SquareImageThumbnail';

export type GiftTheme = {
    id: number;
    name: string;
    slug: string;
    thumbnail_url?: string | null;
    image_url?: string | null;
    gallery_images?: string[];
};

export type GiftPersonalizationState = {
    gift_theme_id: string;
    gift_theme_image_url: string;
    gift_message_title: string;
    receiver_msg: string;
    sender_first_name: string;
    receiver_name: string;
    receiver_mobile: string;
    receiver_email: string;
    gift_delivery_option: 'send_now' | 'send_later';
    gift_delivery_at: string;
};

type Props = {
    themes: GiftTheme[];
    data: GiftPersonalizationState;
    onChange: (next: GiftPersonalizationState) => void;
    errors?: Partial<Record<keyof GiftPersonalizationState, string>>;
    text?: Record<string, string>;
};

function updateField(
    data: GiftPersonalizationState,
    onChange: (next: GiftPersonalizationState) => void,
    key: keyof GiftPersonalizationState,
    value: string,
) {
    onChange({ ...data, [key]: value });
}

export default function GiftPersonalizationPanel({ themes, data, onChange, errors = {}, text = {} }: Props) {
    const t = (key: string, fallback: string) => text[key] || fallback;
    const selectedTheme = themes.find((theme) => String(theme.id) === data.gift_theme_id) ?? null;
    const selectedThemeGallery = selectedTheme
        ? (selectedTheme.gallery_images && selectedTheme.gallery_images.length > 0
            ? selectedTheme.gallery_images
            : [selectedTheme.thumbnail_url, selectedTheme.image_url].filter((value): value is string => typeof value === 'string' && value.length > 0))
        : [];

    return (
        <div className="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3 md:p-4">
            <p className="text-sm font-semibold text-gray-900">{t('title', 'Personalize your gift card')}</p>
            <p className="mt-1 text-xs text-gray-600">{t('required_note', 'Fields marked with * are required for gift checkout.')}</p>

            <div className="mt-3">
                <p className="text-xs font-semibold uppercase tracking-wide text-gray-600">{t('select_theme', 'Select your theme')}</p>
                <div className="mt-2 flex gap-2 overflow-x-auto pb-1">
                    {themes.map((theme) => {
                        const selected = data.gift_theme_id === String(theme.id);
                        return (
                            <button
                                key={`chip-${theme.id}`}
                                type="button"
                                onClick={() => {
                                    const themeGallery = theme.gallery_images && theme.gallery_images.length > 0
                                        ? theme.gallery_images
                                        : [theme.thumbnail_url, theme.image_url].filter((value): value is string => typeof value === 'string' && value.length > 0);
                                    onChange({
                                        ...data,
                                        gift_theme_id: String(theme.id),
                                        gift_theme_image_url: themeGallery[0] ?? '',
                                    });
                                }}
                                className={`shrink-0 rounded-full border px-3 py-1.5 text-sm ${
                                    selected ? 'border-emerald-800 bg-emerald-50 text-emerald-900' : 'border-gray-300 text-gray-700 hover:border-gray-500'
                                }`}
                            >
                                {theme.name}
                            </button>
                        );
                    })}
                </div>
                <div className="mt-3">
                    <p className="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                        {t('theme_gallery', 'Theme gallery')}
                    </p>
                    {selectedTheme ? (
                        selectedThemeGallery.length > 0 ? (
                            <div className="flex gap-2 overflow-x-auto pb-1">
                                {selectedThemeGallery.map((url, idx) => {
                                    const selected = data.gift_theme_image_url === url || (!data.gift_theme_image_url && idx === 0);
                                    return (
                                        <SquareImageThumbnail
                                            key={`${selectedTheme.id}-${idx}-${url}`}
                                            src={url}
                                            alt={`${selectedTheme.name} ${idx + 1}`}
                                            selected={selected}
                                            size="md"
                                            onClick={() => updateField(data, onChange, 'gift_theme_image_url', url)}
                                        />
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="flex h-16 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white text-xs text-gray-500">
                                {t('theme_fallback', 'Theme')}
                            </div>
                        )
                    ) : (
                        <div className="flex h-16 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-white text-xs text-gray-500">
                            {t('theme_select_hint', 'Select a theme to view images')}
                        </div>
                    )}
                </div>
                {errors.gift_theme_id ? <p className="mt-2 text-xs text-red-600">{errors.gift_theme_id}</p> : null}
            </div>

            <div className="mt-3 space-y-2.5">
                <label className="block text-xs font-medium text-gray-700">
                    {t('message_title', 'Message title*')}
                    <input
                        value={data.gift_message_title}
                        onChange={(e) => updateField(data, onChange, 'gift_message_title', e.target.value)}
                        className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm ${errors.gift_message_title ? 'border-red-500' : 'border-gray-300'}`}
                        placeholder={t('message_title_placeholder', 'Enter a title for the gift card')}
                    />
                    {errors.gift_message_title ? <p className="mt-1 text-xs text-red-600">{errors.gift_message_title}</p> : null}
                </label>
                <label className="block text-xs font-medium text-gray-700">
                    {t('your_message', 'Your message*')}
                    <textarea
                        value={data.receiver_msg}
                        onChange={(e) => updateField(data, onChange, 'receiver_msg', e.target.value)}
                        maxLength={500}
                        className={`mt-1 min-h-20 w-full rounded-lg border px-3 py-2 text-sm ${errors.receiver_msg ? 'border-red-500' : 'border-gray-300'}`}
                        placeholder={t('your_message_placeholder', 'Write your personalized message')}
                    />
                    {errors.receiver_msg ? <p className="mt-1 text-xs text-red-600">{errors.receiver_msg}</p> : null}
                </label>
                <label className="block text-xs font-medium text-gray-700">
                    {t('from_label', "From (that's you)*")}
                    <input
                        value={data.sender_first_name}
                        onChange={(e) => updateField(data, onChange, 'sender_first_name', e.target.value)}
                        className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm ${errors.sender_first_name ? 'border-red-500' : 'border-gray-300'}`}
                        placeholder={t('from_placeholder', "Let them know who it's from")}
                    />
                    {errors.sender_first_name ? <p className="mt-1 text-xs text-red-600">{errors.sender_first_name}</p> : null}
                </label>
            </div>

            <div className="mt-3 rounded-lg border border-gray-200 bg-white p-3">
                <p className="text-xs font-semibold uppercase tracking-wide text-gray-600">{t('recipient_block_title', 'Who is this for?')}</p>
                <div className="mt-2 grid gap-3">
                    <label className="block text-xs font-medium text-gray-700">
                        {t('name_label', 'Name*')}
                        <input
                            value={data.receiver_name}
                            onChange={(e) => updateField(data, onChange, 'receiver_name', e.target.value)}
                            className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm ${errors.receiver_name ? 'border-red-500' : 'border-gray-300'}`}
                            placeholder={t('name_placeholder', 'Enter recipient name')}
                        />
                        {errors.receiver_name ? <p className="mt-1 text-xs text-red-600">{errors.receiver_name}</p> : null}
                    </label>
                    <label className="block text-xs font-medium text-gray-700">
                        {t('phone_label', 'Phone number*')}
                        <input
                            value={data.receiver_mobile}
                            onChange={(e) => updateField(data, onChange, 'receiver_mobile', e.target.value)}
                            className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm ${errors.receiver_mobile ? 'border-red-500' : 'border-gray-300'}`}
                            placeholder={t('phone_placeholder', 'Enter recipient phone number')}
                        />
                        {errors.receiver_mobile ? <p className="mt-1 text-xs text-red-600">{errors.receiver_mobile}</p> : null}
                    </label>
                    <label className="block text-xs font-medium text-gray-700">
                        {t('email_label', 'Email*')}
                        <input
                            value={data.receiver_email}
                            onChange={(e) => updateField(data, onChange, 'receiver_email', e.target.value)}
                            className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm ${errors.receiver_email ? 'border-red-500' : 'border-gray-300'}`}
                            placeholder={t('email_placeholder', 'Enter recipient email')}
                        />
                        {errors.receiver_email ? <p className="mt-1 text-xs text-red-600">{errors.receiver_email}</p> : null}
                    </label>
                </div>
            </div>

            <div className="mt-3 rounded-lg border border-gray-200 bg-white p-3">
                <div className="flex items-center justify-between">
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-600">{t('delivery_title', 'Delivery Options')}</p>
                    <p className="text-xs font-medium text-gray-500">{t('required_note_short', '* Mandatory Fields')}</p>
                </div>
                <p className="mt-2 text-sm font-semibold text-gray-900">{t('delivery_select_label', 'Select your delivery option')}</p>
                <p className="mt-1 text-xs text-gray-600">{t('delivery_hint', 'Pick the perfect moment to send your gift!')}</p>
                <div className="mt-3 space-y-2">
                    <button
                        type="button"
                        onClick={() => updateField(data, onChange, 'gift_delivery_option', 'send_now')}
                        className={`w-full rounded-lg border p-3 text-left ${
                            data.gift_delivery_option === 'send_now' ? 'border-emerald-700 bg-emerald-50' : 'border-gray-300 hover:border-gray-400'
                        }`}
                    >
                        <p className="text-sm font-semibold text-gray-900">{t('delivery_send_now', 'Send Now')}</p>
                        <p className="mt-1 text-xs text-gray-600">{t('delivery_send_now_hint', 'Your gift will be delivered immediately after successful checkout.')}</p>
                    </button>
                    <button
                        type="button"
                        onClick={() => updateField(data, onChange, 'gift_delivery_option', 'send_later')}
                        className={`w-full rounded-lg border p-3 text-left ${
                            data.gift_delivery_option === 'send_later' ? 'border-emerald-700 bg-emerald-50' : 'border-gray-300 hover:border-gray-400'
                        }`}
                    >
                        <p className="text-sm font-semibold text-gray-900">{t('delivery_send_later', 'Send Later')}</p>
                        <p className="mt-1 text-xs text-gray-600">{t('delivery_send_later_hint', 'Pick a date and time for delivery.')}</p>
                    </button>
                </div>
                {errors.gift_delivery_option ? <p className="mt-2 text-xs text-red-600">{errors.gift_delivery_option}</p> : null}
                {data.gift_delivery_option === 'send_later' ? (
                    <label className="mt-3 block text-xs font-medium text-gray-700">
                        {t('delivery_datetime_label', 'Delivery date and time*')}
                        <input
                            type="datetime-local"
                            value={data.gift_delivery_at}
                            onChange={(e) => updateField(data, onChange, 'gift_delivery_at', e.target.value)}
                            className={`mt-1 w-full rounded-lg border px-3 py-2 text-sm ${errors.gift_delivery_at ? 'border-red-500' : 'border-gray-300'}`}
                        />
                        {errors.gift_delivery_at ? <p className="mt-1 text-xs text-red-600">{errors.gift_delivery_at}</p> : null}
                    </label>
                ) : null}
            </div>
        </div>
    );
}
