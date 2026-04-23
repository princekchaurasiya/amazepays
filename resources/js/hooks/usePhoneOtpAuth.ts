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
    const [email, setEmail] = useState('');
    const [referral, setReferral] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const otpCode = otp.join('');

    const resetFlow = useCallback(() => {
        setStep('phone');
        setPhone('');
        setOtp(emptyOtpDigits());
        setName('');
        setEmail('');
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
            if (data.status === 'error') {
                setError(data.message ?? 'Failed to send OTP');
            } else {
                setStep('otp');
                setTimeout(() => otpInputRef?.current?.focus(0), 0);
            }
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { message?: string })?.message : null;
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
            if (data.status !== 'success') {
                setError((data as { message?: string }).message ?? 'Invalid OTP');
                setLoading(false);
                return;
            }
            if (data.action === 'logged_in') {
                onLoggedIn({ redirectUrl: data.redirect_url });
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
                    email: email.trim() || undefined,
                    referral_code: referral.trim() || undefined,
                },
                { headers: { 'X-CSRF-TOKEN': csrfToken() } },
            );
            if (data.status !== 'success') {
                const errs = (data as { errors?: Record<string, string[]> }).errors;
                setError(errs ? Object.values(errs).flat().join(' ') : (data as { message?: string }).message ?? 'Failed');
                setLoading(false);
                return;
            }
            onRegistrationSuccess({ redirectUrl: data.redirect_url });
        } catch (e: unknown) {
            const msg = axios.isAxiosError(e) ? (e.response?.data as { message?: string })?.message : null;
            setError(msg ?? 'Registration failed');
        } finally {
            setLoading(false);
        }
    }, [phone, name, email, referral, onRegistrationSuccess]);

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
        email,
        setEmail,
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
