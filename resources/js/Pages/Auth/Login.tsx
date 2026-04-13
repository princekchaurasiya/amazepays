import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import AuthLayout from '@/Layouts/AuthLayout';
import { paths } from '@/lib/paths';

function csrf(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

type Step = 'phone' | 'otp' | 'profile';

export default function Login() {
    const [step, setStep] = useState<Step>('phone');
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [referral, setReferral] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const sendOtp = async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post('/auth/send-otp', { destination: phone.trim() }, { headers: { 'X-CSRF-TOKEN': csrf() } });
            if (data.status === 'error') setError(data.message ?? 'Failed');
            else setStep('otp');
        } catch (e: unknown) {
            setError('Failed to send OTP');
        } finally {
            setLoading(false);
        }
    };

    const verifyOtp = async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post(
                '/auth/verify-otp',
                { phone: phone.trim(), otp: otp.join('') },
                { headers: { 'X-CSRF-TOKEN': csrf() } },
            );
            if (data.status !== 'success') {
                setError(data.message ?? 'Invalid OTP');
                setLoading(false);
                return;
            }
            if (data.action === 'logged_in') {
                window.location.href = data.redirect_url ?? paths.home;
                return;
            }
            if (data.action === 'needs_profile') setStep('profile');
        } catch {
            setError('Verification failed');
        } finally {
            setLoading(false);
        }
    };

    const complete = async () => {
        setLoading(true);
        setError(null);
        try {
            const { data } = await axios.post(
                '/auth/complete-registration',
                { phone: phone.trim(), name: name.trim(), email: email.trim() || undefined, referral_code: referral.trim() || undefined },
                { headers: { 'X-CSRF-TOKEN': csrf() } },
            );
            if (data.status === 'success') window.location.href = data.redirect_url ?? paths.home;
            else setError('Could not complete registration');
        } catch {
            setError('Registration failed');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthLayout>
            <Head title="Log in" />
            <div className="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                <h1 className="text-center text-xl font-bold text-gray-900">Log in / Sign up</h1>
                {error && <p className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
                {step === 'phone' && (
                    <div className="mt-6 space-y-4">
                        <input
                            type="tel"
                            className="w-full rounded-lg border border-gray-300 px-3 py-2"
                            placeholder="10-digit mobile"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                        />
                        <button
                            type="button"
                            disabled={loading}
                            onClick={sendOtp}
                            className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            Continue
                        </button>
                    </div>
                )}
                {step === 'otp' && (
                    <div className="mt-6 space-y-4">
                        <div className="flex justify-center gap-2">
                            {otp.map((d, i) => (
                                <input
                                    key={i}
                                    maxLength={1}
                                    className="h-10 w-10 rounded border text-center"
                                    value={d}
                                    onChange={(e) => {
                                        const v = e.target.value.replace(/\D/g, '').slice(-1);
                                        const n = [...otp];
                                        n[i] = v;
                                        setOtp(n);
                                    }}
                                />
                            ))}
                        </div>
                        <button type="button" disabled={loading} onClick={verifyOtp} className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white">
                            Verify
                        </button>
                        <button type="button" className="w-full text-sm text-brand-600" onClick={() => setStep('phone')}>
                            Back
                        </button>
                    </div>
                )}
                {step === 'profile' && (
                    <div className="mt-6 space-y-4">
                        <input className="w-full rounded-lg border px-3 py-2" placeholder="Full name" value={name} onChange={(e) => setName(e.target.value)} />
                        <input className="w-full rounded-lg border px-3 py-2" placeholder="Email (optional)" value={email} onChange={(e) => setEmail(e.target.value)} />
                        <button type="button" disabled={loading} onClick={complete} className="w-full rounded-full bg-gray-900 py-2.5 text-sm font-semibold text-white">
                            Complete
                        </button>
                    </div>
                )}
                <button type="button" onClick={() => router.visit(paths.home)} className="mt-6 w-full text-center text-sm text-gray-600 hover:text-brand-600">
                    Back to home
                </button>
            </div>
        </AuthLayout>
    );
}
