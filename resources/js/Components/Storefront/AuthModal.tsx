import React, { useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { OtpDigitGrid, type OtpDigitGridHandle } from '@/Components/Auth';
import { usePhoneOtpAuth } from '@/hooks/usePhoneOtpAuth';

type Props = {
    open: boolean;
    onClose: () => void;
};

export default function AuthModal({ open, onClose }: Props) {
    const otpInputRef = useRef<OtpDigitGridHandle>(null);
    const page = usePage<{ i18n?: { auth_ui?: Record<string, string> } }>();
    const text = page.props.i18n?.auth_ui ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;

    const { otpCode, resetFlow, ...auth } = usePhoneOtpAuth({
        otpInputRef,
        onLoggedIn: ({ redirectUrl }) => {
            resetFlow();
            onClose();
            if (redirectUrl) window.location.href = redirectUrl;
            else router.reload();
        },
        onRegistrationSuccess: ({ redirectUrl }) => {
            resetFlow();
            onClose();
            if (redirectUrl) window.location.href = redirectUrl;
            else router.reload();
        },
    });

    const {
        step,
        setStep,
        phone,
        setPhone,
        otp,
        setOtp,
        name,
        setName,
        email,
        setEmail,
        referral,
        setReferral,
        error,
        loading,
        sendOtp,
        verifyOtp,
        completeRegistration,
    } = auth;

    if (!open) return null;

    const handleClose = () => {
        resetFlow();
        onClose();
    };

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal>
            <div className="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <button type="button" className="absolute right-4 top-4 text-gray-500 hover:text-gray-800" onClick={handleClose} aria-label="Close">
                    <X className="h-5 w-5" />
                </button>
                <img src="/images/logo.png" alt="" className="mx-auto mb-4 h-9 w-auto" />
                {error && (
                    <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
                        {error}
                    </div>
                )}

                {step === 'phone' && (
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!loading) void sendOtp();
                        }}
                    >
                        <h2 className="text-center text-lg font-semibold text-gray-900">{t('login_signup', 'Log in / Sign up')}</h2>
                        <p className="text-center text-sm text-gray-500">{t('otp_subtitle', "We'll send an OTP to verify your number")}</p>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('phone', 'Phone')}
                            <input
                                type="tel"
                                inputMode="numeric"
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                placeholder={t('mobile_placeholder', '10-digit mobile')}
                                value={phone}
                                onChange={(e) => setPhone(e.target.value)}
                                autoComplete="tel"
                            />
                        </label>
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {loading ? t('please_wait', 'Please wait...') : t('continue', 'Continue')}
                        </button>
                    </form>
                )}

                {step === 'otp' && (
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!loading && otpCode.length >= 4) void verifyOtp();
                        }}
                    >
                        <h2 className="text-center text-lg font-semibold text-gray-900">{t('enter_otp', 'Enter OTP')}</h2>
                        <p className="text-center text-sm text-gray-500">{t('sent_to', 'Sent to :phone').replace(':phone', phone)}</p>
                        <OtpDigitGrid
                            ref={otpInputRef}
                            value={otp}
                            onChange={setOtp}
                            disabled={loading}
                            inputClassName="h-10 w-10 rounded-lg border border-gray-300 text-center text-lg"
                        />
                        <button
                            type="submit"
                            disabled={loading || otpCode.length < 4}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {loading ? t('verifying', 'Verifying...') : t('continue', 'Continue')}
                        </button>
                        <button type="button" className="w-full text-sm text-brand-600 hover:underline" onClick={() => setStep('phone')}>
                            {t('change_number', 'Change number')}
                        </button>
                    </form>
                )}

                {step === 'profile' && (
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!loading && name.trim().length >= 2) void completeRegistration();
                        }}
                    >
                        <h2 className="text-center text-lg font-semibold text-gray-900">{t('complete_profile', 'Complete your profile')}</h2>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('full_name', 'Full name')}
                            <input
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                autoComplete="name"
                            />
                        </label>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('email_optional', 'Email (optional)')}
                            <input
                                type="email"
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                autoComplete="email"
                            />
                        </label>
                        <label className="block text-sm font-medium text-gray-700">
                            {t('referral_optional', 'Referral code (optional)')}
                            <input
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={referral}
                                onChange={(e) => setReferral(e.target.value)}
                                autoComplete="off"
                            />
                        </label>
                        <button
                            type="submit"
                            disabled={loading || name.trim().length < 2}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {loading ? t('saving', 'Saving...') : t('complete', 'Complete')}
                        </button>
                    </form>
                )}

                <p className="mt-4 text-center text-xs text-gray-500">
                    {t('recaptcha_note', "This site is protected by reCAPTCHA and Google's policies apply.")}
                </p>
            </div>
        </div>
    );
}
