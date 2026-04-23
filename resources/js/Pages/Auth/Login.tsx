import React, { useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AuthLayout from '@/Layouts/AuthLayout';
import { paths } from '@/lib/paths';
import { OtpDigitGrid, type OtpDigitGridHandle } from '@/Components/Auth';
import { usePhoneOtpAuth } from '@/hooks/usePhoneOtpAuth';

export default function Login() {
    const otpInputRef = useRef<OtpDigitGridHandle>(null);
    const page = usePage<{ i18n?: { auth_ui?: Record<string, string> } }>();
    const text = page.props.i18n?.auth_ui ?? {};
    const t = (key: string, fallback: string) => text[key] || fallback;

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
        error,
        loading,
        sendOtp,
        verifyOtp,
        completeRegistration,
    } = usePhoneOtpAuth({
        otpInputRef,
        onLoggedIn: ({ redirectUrl }) => {
            window.location.href = redirectUrl ?? paths.home;
        },
        onRegistrationSuccess: ({ redirectUrl }) => {
            window.location.href = redirectUrl ?? paths.home;
        },
    });

    return (
        <AuthLayout>
            <Head title={t('login_title', 'Log in')} />
            <div className="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                <h1 className="text-center text-xl font-bold text-gray-900">{t('login_signup', 'Log in / Sign up')}</h1>
                {error && <p className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
                {step === 'phone' && (
                    <form
                        className="mt-6 space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!loading) void sendOtp();
                        }}
                    >
                        <input
                            type="tel"
                            className="w-full rounded-lg border border-gray-300 px-3 py-2"
                            placeholder={t('mobile_placeholder', '10-digit mobile')}
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                            autoComplete="tel"
                        />
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            {t('continue', 'Continue')}
                        </button>
                    </form>
                )}
                {step === 'otp' && (
                    <form
                        className="mt-6 space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!loading) void verifyOtp();
                        }}
                    >
                        <OtpDigitGrid ref={otpInputRef} value={otp} onChange={setOtp} disabled={loading} />
                        <button type="submit" disabled={loading} className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white">
                            {t('verify', 'Verify')}
                        </button>
                        <button type="button" className="w-full text-sm text-brand-600" onClick={() => setStep('phone')}>
                            {t('back', 'Back')}
                        </button>
                    </form>
                )}
                {step === 'profile' && (
                    <form
                        className="mt-6 space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!loading) void completeRegistration();
                        }}
                    >
                        <input className="w-full rounded-lg border px-3 py-2" placeholder={t('full_name', 'Full name')} value={name} onChange={(e) => setName(e.target.value)} autoComplete="name" />
                        <input
                            className="w-full rounded-lg border px-3 py-2"
                            placeholder={t('email_optional', 'Email (optional)')}
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            autoComplete="email"
                        />
                        <button type="submit" disabled={loading} className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white">
                            {t('complete', 'Complete')}
                        </button>
                    </form>
                )}
                <button type="button" onClick={() => router.visit(paths.home)} className="mt-6 w-full text-center text-sm text-gray-600 hover:text-brand-600">
                    {t('back_to_home', 'Back to home')}
                </button>
            </div>
        </AuthLayout>
    );
}
