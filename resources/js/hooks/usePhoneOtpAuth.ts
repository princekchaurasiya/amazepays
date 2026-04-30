import axios from 'axios';
import { useCallback, useState, type RefObject } from 'react';
import { csrfToken } from '@/lib/csrf';
import type { OtpDigitGridHandle } from '@/Components/Auth/OtpDigitGrid';

export type PhoneOtpStep = 'phone' | 'otp' | 'profile';

const OTP_LENGTH = 6;

export function emptyOtpDigits(length: number = OTP_LENGTH): string[] {
    return Array.from({ length }, () => '');
}

type LoggedInCtx = { redirectUrl?: string | null };

export type UsePhoneOtpAuthOptions = {
    otpInputRef?: RefObject<OtpDigitGridHandle | null>;
    onLoggedIn: (ctx: LoggedInCtx) => void;
    onRegistrationSuccess: (ctx: LoggedInCtx) => void;
};

export function usePhoneOtpAuth({ otpInputRef, onLoggedIn, onRegistrationSuccess }: UsePhoneOtpAuthOptions) {
    const [step, setStep] = useState<PhoneOtpStep>('phone');
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState<string[]>(emptyOtpDigits);
    const [name, setName] = useState('');
    const [referral, setReferral] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const otpCode = otp.join('');

    const resetFlow = useCallback(() => {
        setStep('phone');
        setPhone('');
        setOtp(emptyOtpDigits());
        setName('');
        setReferral('');
        setError(null);
    }, []);

    const sendOtp = useCallback(async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post(
                '/auth/send-otp',
                { destination: phone.trim() },
                { headers: { 'X-CSRF-TOKEN': csrfToken() } },
            );
            if (!data?.success) {
                setError(data?.error?.message ?? 'Failed to send OTP');
            } else {
                setStep('otp');
                setTimeout(() => otpInputRef?.current?.focus(0), 0);
            }
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { error?: { message?: string } })?.error?.message : null;
            setError(msg ?? 'Failed to send OTP');
        } finally {
            setLoading(false);
        }
    }, [phone, otpInputRef]);

    const verifyOtp = useCallback(async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post(
                '/auth/verify-otp',
                { phone: phone.trim(), otp: otpCode },
                { headers: { 'X-CSRF-TOKEN': csrfToken() } },
            );
            if (!data?.success) {
                setError(data?.error?.message ?? 'Invalid OTP');
                setLoading(false);
                return;
            }
            const payload = data.data as { action?: string; redirect_url?: string | null };
            if (payload.action === 'logged_in') {
                onLoggedIn({ redirectUrl: payload.redirect_url });
                return;
            }
            if (payload.action === 'needs_profile') {
                setStep('profile');
            }
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { error?: { message?: string } })?.error?.message : null;
            setError(msg ?? 'Verification failed');
        } finally {
            setLoading(false);
        }
    }, [phone, otpCode, onLoggedIn]);

    const completeRegistration = useCallback(async () => {
        setError(null);
        setLoading(true);
        try {
            const { data } = await axios.post(
                '/auth/complete-registration',
                {
                    phone: phone.trim(),
                    name: name.trim(),
                    referral_code: referral.trim() || undefined,
                },
                { headers: { 'X-CSRF-TOKEN': csrfToken() } },
            );
            if (!data?.success) {
                const errs = (data as { error?: { details?: Record<string, string[]> } })?.error?.details;
                setError(errs ? Object.values(errs).flat().join(' ') : data?.error?.message ?? 'Failed');
                setLoading(false);
                return;
            }
            const payload = data.data as { redirect_url?: string | null };
            onRegistrationSuccess({ redirectUrl: payload.redirect_url });
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { error?: { message?: string } })?.error?.message : null;
            setError(msg ?? 'Registration failed');
        } finally {
            setLoading(false);
        }
    }, [phone, name, referral, onRegistrationSuccess]);

    return {
        step,
        setStep,
        phone,
        setPhone,
        otp,
        setOtp,
        otpCode,
        name,
        setName,
        referral,
        setReferral,
        error,
        setError,
        loading,
        sendOtp,
        verifyOtp,
        completeRegistration,
        resetFlow,
    };
}
