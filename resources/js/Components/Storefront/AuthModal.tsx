import React, { useState } from 'react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { X } from 'lucide-react';

type Step = 'phone' | 'otp' | 'profile';

function csrf(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

type Props = {
    open: boolean;
    onClose: () => void;
};

export default function AuthModal({ open, onClose }: Props) {
    const [step, setStep] = useState<Step>('phone');
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [referral, setReferral] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    if (!open) return null;

    const reset = () => {
        setStep('phone');
        setPhone('');
        setOtp(['', '', '', '', '', '']);
        setName('');
        setEmail('');
        setReferral('');
        setError(null);
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    const sendOtp = async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post('/auth/send-otp', { destination: phone.trim() }, { headers: { 'X-CSRF-TOKEN': csrf() } });
            if (data.status === 'error') {
                setError(data.message ?? 'Failed to send OTP');
            } else {
                setStep('otp');
            }
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { message?: string })?.message : null;
            setError(msg ?? 'Failed to send OTP');
        } finally {
            setLoading(false);
        }
    };

    const verifyOtp = async () => {
        setError(null);
        setLoading(true);
        const code = otp.join('');
        try {
            const { data } = await axios.post(
                '/auth/verify-otp',
                { phone: phone.trim(), otp: code },
                { headers: { 'X-CSRF-TOKEN': csrf() } },
            );
            if (data.status !== 'success') {
                setError((data as { message?: string }).message ?? 'Invalid OTP');
                setLoading(false);
                return;
            }
            if (data.action === 'logged_in') {
                handleClose();
                if (data.redirect_url) window.location.href = data.redirect_url;
                else router.reload();
                return;
            }
            if (data.action === 'needs_profile') {
                setStep('profile');
            }
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { message?: string })?.message : null;
            setError(msg ?? 'Verification failed');
        } finally {
            setLoading(false);
        }
    };

    const completeProfile = async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post(
                '/auth/complete-registration',
                {
                    phone: phone.trim(),
                    name: name.trim(),
                    email: email.trim() || undefined,
                    referral_code: referral.trim() || undefined,
                },
                { headers: { 'X-CSRF-TOKEN': csrf() } },
            );
            if (data.status !== 'success') {
                const errs = (data as { errors?: Record<string, string[]> }).errors;
                setError(errs ? Object.values(errs).flat().join(' ') : (data as { message?: string }).message ?? 'Failed');
                setLoading(false);
                return;
            }
            handleClose();
            if (data.redirect_url) window.location.href = data.redirect_url;
            else router.reload();
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { message?: string })?.message : null;
            setError(msg ?? 'Registration failed');
        } finally {
            setLoading(false);
        }
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
                    <div className="space-y-4">
                        <h2 className="text-center text-lg font-semibold text-gray-900">Log in / Sign up</h2>
                        <p className="text-center text-sm text-gray-500">We’ll send an OTP to verify your number</p>
                        <label className="block text-sm font-medium text-gray-700">
                            Phone
                            <input
                                type="tel"
                                inputMode="numeric"
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                placeholder="10-digit mobile"
                                value={phone}
                                onChange={(e) => setPhone(e.target.value)}
                            />
                        </label>
                        <button
                            type="button"
                            disabled={loading}
                            onClick={sendOtp}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {loading ? 'Please wait…' : 'Continue'}
                        </button>
                    </div>
                )}

                {step === 'otp' && (
                    <div className="space-y-4">
                        <h2 className="text-center text-lg font-semibold text-gray-900">Enter OTP</h2>
                        <p className="text-center text-sm text-gray-500">Sent to {phone}</p>
                        <div className="flex justify-center gap-2">
                            {otp.map((d, i) => (
                                <input
                                    key={i}
                                    type="text"
                                    inputMode="numeric"
                                    maxLength={1}
                                    className="h-10 w-10 rounded-lg border border-gray-300 text-center text-lg"
                                    value={d}
                                    onChange={(e) => {
                                        const v = e.target.value.replace(/\D/g, '').slice(-1);
                                        const next = [...otp];
                                        next[i] = v;
                                        setOtp(next);
                                        if (v && i < 5) (e.target.nextElementSibling as HTMLInputElement | null)?.focus();
                                    }}
                                />
                            ))}
                        </div>
                        <button
                            type="button"
                            disabled={loading || otp.join('').length < 4}
                            onClick={verifyOtp}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {loading ? 'Verifying…' : 'Continue'}
                        </button>
                        <button type="button" className="w-full text-sm text-brand-600 hover:underline" onClick={() => setStep('phone')}>
                            Change number
                        </button>
                    </div>
                )}

                {step === 'profile' && (
                    <div className="space-y-4">
                        <h2 className="text-center text-lg font-semibold text-gray-900">Complete your profile</h2>
                        <label className="block text-sm font-medium text-gray-700">
                            Full name
                            <input
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                            />
                        </label>
                        <label className="block text-sm font-medium text-gray-700">
                            Email (optional)
                            <input
                                type="email"
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                            />
                        </label>
                        <label className="block text-sm font-medium text-gray-700">
                            Referral code (optional)
                            <input
                                className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                value={referral}
                                onChange={(e) => setReferral(e.target.value)}
                            />
                        </label>
                        <button
                            type="button"
                            disabled={loading || name.trim().length < 2}
                            onClick={completeProfile}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {loading ? 'Saving…' : 'Complete'}
                        </button>
                    </div>
                )}

                <p className="mt-4 text-center text-xs text-gray-500">
                    This site is protected by reCAPTCHA and Google&apos;s policies apply.
                </p>
            </div>
        </div>
    );
}
