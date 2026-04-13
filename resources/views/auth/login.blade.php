@extends('layouts.auth')

@section('title', 'Sign in — ' . config('app.name'))

@section('content')
<div class="auth-page">
    <div class="auth-card-shell">
        <div class="text-center mb-2">
            <a href="{{ route('home') }}" class="auth-brand block">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name', 'AmazePays') }}" class="auth-brand-logo" width="160" height="40" />
            </a>
        </div>

        <div
            class="auth-card"
            data-unified-auth-root
            data-send-url="{{ route('auth.send-otp') }}"
            data-verify-url="{{ route('auth.verify-otp') }}"
            data-complete-url="{{ route('auth.complete-registration') }}"
        >
            <div class="auth-card-body pb-4">
                <div class="u-global-error auth-alert-error auth-sr-only mb-4" role="alert" aria-live="polite"></div>

                {{-- Step 1: Phone --}}
                <div class="u-step-phone auth-form-stack">
                    <div class="text-center mb-2">
                        <h1 class="auth-card-title text-center">Log in</h1>
                        <p class="auth-card-sub text-center">Continue with phone number</p>
                    </div>
                    <div>
                        <label for="u-phone" class="label">Phone no. <span class="text-red-500">*</span></label>
                        <input type="text" id="u-phone" class="u-phone-input input mt-1.5" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile" />
                    </div>
                    <button type="button" class="u-send-otp u-btn-pill">Continue</button>
                </div>

                {{-- Step 2: OTP --}}
                <div class="u-step-otp u-hidden">
                    <div class="mb-4 flex items-center gap-2">
                        <button type="button" class="u-back-phone rounded-full p-1 text-gray-600 hover:bg-gray-100" aria-label="Back">&larr;</button>
                        <div class="flex-1 text-center">
                            <h2 class="text-lg font-semibold text-brand-950">Enter OTP</h2>
                            <p class="text-sm text-gray-500">Sent to <span class="u-phone-display font-medium text-gray-800"></span></p>
                        </div>
                        <span class="w-8"></span>
                    </div>
                    <div class="u-otp-row mb-4">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" maxlength="1" inputmode="numeric" autocomplete="one-time-code" class="u-otp-digit u-otp-box" />
                        @endfor
                    </div>
                    <p class="mb-2 text-center text-sm text-gray-500">
                        <span class="u-resend-text"></span>
                        <a href="#" class="u-resend-link text-accent-600 hover:underline">Resend OTP</a>
                    </p>
                    <button type="button" class="u-verify-otp u-btn-pill" disabled>Continue</button>
                </div>

                {{-- Step 3: New user profile --}}
                <div class="u-step-profile u-hidden auth-form-stack">
                    <h2 class="text-lg font-semibold text-brand-950 text-center">Complete your profile</h2>
                    <p class="text-sm text-gray-500 text-center">Almost done — add a few details.</p>
                    <div>
                        <label for="u-name" class="label">Full name <span class="text-red-500">*</span></label>
                        <input type="text" id="u-name" class="u-name-input input mt-1.5" autocomplete="name" placeholder="Your name" />
                    </div>
                    <div>
                        <label for="u-email" class="label">Email <span class="text-gray-400">(optional)</span></label>
                        <input type="email" id="u-email" class="u-email-input input mt-1.5" autocomplete="email" placeholder="you@example.com" />
                    </div>
                    <div>
                        <label for="u-ref" class="label">Referral code <span class="text-gray-400">(optional)</span></label>
                        <input type="text" id="u-ref" class="u-referral-input input mt-1.5" maxlength="64" placeholder="Referral code" />
                    </div>
                    <button type="button" class="u-complete-profile u-btn-pill">Complete</button>
                </div>

                <p class="auth-legal">
                    This site is protected by reCAPTCHA and the
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google Privacy Policy</a> and
                    <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms of Service</a> apply.
                </p>
            </div>
        </div>
    </div>
</div>

@include('auth.partials.unified-auth-script')
@endsection
