{{-- Unified OTP login / sign up --}}
<div id="auth-modal" class="u-modal-overlay u-hidden" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title" hidden>
    <div class="u-modal-panel">
        <button type="button" class="u-modal-close" id="auth-modal-close" aria-label="Close">&times;</button>
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="auth-brand-logo mx-auto" width="140" height="36" />
        </div>
        <div
            data-unified-auth-root
            data-send-url="{{ route('auth.send-otp') }}"
            data-verify-url="{{ route('auth.verify-otp') }}"
            data-complete-url="{{ route('auth.complete-registration') }}"
        >
            <div class="u-global-error auth-alert-error auth-sr-only mb-4" role="alert"></div>

            <div class="u-step-phone auth-form-stack">
                <h2 id="auth-modal-title" class="text-lg font-semibold text-brand-950 text-center">Log in / Sign up</h2>
                <p class="text-sm text-gray-500 text-center">We’ll send an OTP to verify your number</p>
                <div>
                    <label for="modal-u-phone" class="label">Phone no. <span class="text-red-500">*</span></label>
                    <input type="text" id="modal-u-phone" class="u-phone-input input mt-1.5" inputmode="numeric" autocomplete="tel" placeholder="10-digit mobile" />
                </div>
                <button type="button" class="u-send-otp u-btn-pill">Continue</button>
            </div>

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

            <div class="u-step-profile u-hidden auth-form-stack">
                <h2 class="text-lg font-semibold text-brand-950 text-center">Complete your profile</h2>
                <div>
                    <label for="modal-u-name" class="label">Full name <span class="text-red-500">*</span></label>
                    <input type="text" id="modal-u-name" class="u-name-input input mt-1.5" autocomplete="name" placeholder="Your name" />
                </div>
                <div>
                    <label for="modal-u-email" class="label">Email <span class="text-gray-400">(optional)</span></label>
                    <input type="email" id="modal-u-email" class="u-email-input input mt-1.5" autocomplete="email" placeholder="you@example.com" />
                </div>
                <div>
                    <label for="modal-u-ref" class="label">Referral code <span class="text-gray-400">(optional)</span></label>
                    <input type="text" id="modal-u-ref" class="u-referral-input input mt-1.5" maxlength="64" placeholder="Referral code" />
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

<script>
(function () {
    var modal = document.getElementById('auth-modal');
    var closeBtn = document.getElementById('auth-modal-close');
    if (!modal) return;

    function openAuthModal() {
        modal.classList.remove('u-hidden');
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeAuthModal() {
        modal.classList.add('u-hidden');
        modal.setAttribute('hidden', 'hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-auth-modal]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            openAuthModal();
        });
    });
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-target="#Modallogin"], [data-target="#ModalregisterD"]');
        if (t) {
            e.preventDefault();
            openAuthModal();
        }
    });
    window.openAuthModal = openAuthModal;
    window.closeAuthModal = closeAuthModal;
    if (closeBtn) closeBtn.addEventListener('click', closeAuthModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeAuthModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('u-hidden')) closeAuthModal();
    });
})();
</script>
@include('auth.partials.unified-auth-script')
