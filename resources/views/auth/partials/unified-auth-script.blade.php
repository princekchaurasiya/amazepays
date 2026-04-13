{{-- Vanilla JS for unified phone → OTP → profile flow; mount on [data-unified-auth-root] --}}
<script>
(function () {
    function getCsrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function normalizeMobile(raw) {
        var d = (raw || '').replace(/\D/g, '');
        if (d.length > 10) {
            if (d.slice(0, 2) === '91' && d.length >= 12) d = d.slice(-10);
            else d = d.slice(-10);
        }
        return d;
    }

    function showStep(root, step) {
        var p = root.querySelector('.u-step-phone');
        var o = root.querySelector('.u-step-otp');
        var pr = root.querySelector('.u-step-profile');
        if (p) p.classList.toggle('u-hidden', step !== 'phone');
        if (o) o.classList.toggle('u-hidden', step !== 'otp');
        if (pr) pr.classList.toggle('u-hidden', step !== 'profile');
    }

    function collectOtp(root) {
        var inputs = root.querySelectorAll('.u-otp-digit');
        var s = '';
        inputs.forEach(function (inp) { s += (inp.value || '').replace(/\D/g, ''); });
        return s;
    }

    function setOtpEnabled(root, enabled) {
        var btn = root.querySelector('.u-verify-otp');
        if (btn) {
            btn.disabled = !enabled;
        }
    }

    function startResendTimer(root, seconds) {
        var el = root.querySelector('.u-resend-text');
        var link = root.querySelector('.u-resend-link');
        if (!el) return;
        var left = seconds;
        if (link) link.classList.add('pointer-events-none', 'text-gray-400');
        function tick() {
            el.textContent = 'Resend in ' + left + 's';
            if (left <= 0) {
                el.textContent = '';
                if (link) {
                    link.classList.remove('pointer-events-none', 'text-gray-400');
                    link.classList.add('text-accent-600');
                }
                return;
            }
            left--;
            setTimeout(tick, 1000);
        }
        tick();
    }

    function bindOtpInputs(root) {
        var inputs = root.querySelectorAll('.u-otp-digit');
        inputs.forEach(function (inp, i) {
            inp.addEventListener('input', function () {
                inp.value = (inp.value || '').replace(/\D/g, '').slice(0, 1);
                if (inp.value && i < inputs.length - 1) inputs[i + 1].focus();
                setOtpEnabled(root, collectOtp(root).length === 6);
            });
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !inp.value && i > 0) inputs[i - 1].focus();
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var vBtn = root.querySelector('.u-verify-otp');
                    if (vBtn && !vBtn.disabled) vBtn.click();
                }
            });
        });
        root.addEventListener('paste', function (e) {
            if (!e.target.classList.contains('u-otp-digit')) return;
            var t = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (t.length === 6) {
                e.preventDefault();
                inputs.forEach(function (inp, j) { inp.value = t[j] || ''; });
                inputs[inputs.length - 1].focus();
                setOtpEnabled(root, true);
            }
        });
    }

    function showErr(root, msg) {
        var el = root.querySelector('.u-global-error');
        if (!el) return;
        el.textContent = msg || '';
        el.classList.toggle('auth-sr-only', !msg);
    }

    function initRoot(root) {
        var sendUrl = root.getAttribute('data-send-url');
        var verifyUrl = root.getAttribute('data-verify-url');
        var completeUrl = root.getAttribute('data-complete-url');

        var phoneInput = root.querySelector('.u-phone-input');
        var btnSend = root.querySelector('.u-send-otp');
        var btnBack = root.querySelector('.u-back-phone');
        var btnVerify = root.querySelector('.u-verify-otp');
        var btnComplete = root.querySelector('.u-complete-profile');
        var resendLink = root.querySelector('.u-resend-link');
        var phoneDisplay = root.querySelector('.u-phone-display');
        var nameInput = root.querySelector('.u-name-input');
        var emailInput = root.querySelector('.u-email-input');
        var refInput = root.querySelector('.u-referral-input');
        var pendingPhone = '';

        bindOtpInputs(root);
        showStep(root, 'phone');
        setOtpEnabled(root, false);

        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body
            }).then(function (r) {
                var ct = r.headers.get('content-type') || '';
                return r.json().then(function (data) {
                    return { ok: r.ok, status: r.status, data: data };
                }).catch(function () {
                    return { ok: false, status: r.status, data: {} };
                });
            });
        }

        if (btnSend) {
            btnSend.addEventListener('click', function () {
                showErr(root, '');
                var raw = phoneInput ? phoneInput.value : '';
                var n = normalizeMobile(raw);
                if (!/^[6-9]\d{9}$/.test(n)) {
                    showErr(root, 'Please enter a valid 10-digit Indian mobile number.');
                    return;
                }
                btnSend.disabled = true;
                var b = new URLSearchParams();
                b.append('_token', getCsrf());
                b.append('destination', n);
                b.append('otp_type', 'unified');
                post(sendUrl, b.toString()).then(function (res) {
                    if (res.data.status === 'success') {
                        pendingPhone = n;
                        if (phoneInput) phoneInput.value = n;
                        if (phoneDisplay) phoneDisplay.textContent = '+91 ' + n;
                        root.querySelectorAll('.u-otp-digit').forEach(function (x) { x.value = ''; });
                        setOtpEnabled(root, false);
                        showStep(root, 'otp');
                        startResendTimer(root, 60);
                    } else {
                        showErr(root, res.data.message || 'Could not send OTP.');
                    }
                }).catch(function () {
                    showErr(root, 'Network error. Please try again.');
                }).finally(function () {
                    btnSend.disabled = false;
                });
            });
            if (phoneInput) {
                phoneInput.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    if (btnSend && !btnSend.disabled) btnSend.click();
                });
            }
        }

        if (btnBack) {
            btnBack.addEventListener('click', function () {
                showErr(root, '');
                showStep(root, 'phone');
            });
        }

        if (resendLink) {
            resendLink.addEventListener('click', function (e) {
                if (resendLink.classList.contains('pointer-events-none')) return;
                e.preventDefault();
                if (!pendingPhone) return;
                var b = new URLSearchParams();
                b.append('_token', getCsrf());
                b.append('destination', pendingPhone);
                b.append('otp_type', 'unified');
                post(sendUrl, b.toString()).then(function (res) {
                    if (res.data.status === 'success') {
                        startResendTimer(root, 60);
                    } else {
                        showErr(root, res.data.message || 'Could not resend OTP.');
                    }
                });
            });
        }

        if (btnVerify) {
            btnVerify.addEventListener('click', function () {
                showErr(root, '');
                var otp = collectOtp(root);
                if (otp.length !== 6) {
                    showErr(root, 'Please enter the complete OTP.');
                    return;
                }
                btnVerify.disabled = true;
                var b = new URLSearchParams();
                b.append('_token', getCsrf());
                b.append('phone', pendingPhone);
                b.append('otp', otp);
                post(verifyUrl, b.toString()).then(function (res) {
                    if (res.data.status === 'success' && res.data.action === 'logged_in') {
                        window.location.href = res.data.redirect_url || '{{ route('home') }}';
                        return;
                    }
                    if (res.data.status === 'success' && res.data.action === 'needs_profile') {
                        showStep(root, 'profile');
                        return;
                    }
                    if (res.data.contact_info) {
                        var msg = res.data.message || 'Your account has been restricted.';
                        showErr(root, msg);
                    } else {
                        showErr(root, res.data.message || 'Invalid OTP, please try again.');
                    }
                }).catch(function () {
                    showErr(root, 'Something went wrong. Please try again.');
                }).finally(function () {
                    btnVerify.disabled = false;
                });
            });
        }

        if (btnComplete) {
            btnComplete.addEventListener('click', function () {
                showErr(root, '');
                var name = nameInput ? nameInput.value.trim() : '';
                if (!name) {
                    showErr(root, 'Please enter your name.');
                    return;
                }
                btnComplete.disabled = true;
                var b = new URLSearchParams();
                b.append('_token', getCsrf());
                b.append('phone', pendingPhone);
                b.append('name', name);
                if (emailInput && emailInput.value.trim()) b.append('email', emailInput.value.trim());
                if (refInput && refInput.value.trim()) b.append('referral_code', refInput.value.trim());
                post(completeUrl, b.toString()).then(function (res) {
                    if (res.data.status === 'success') {
                        window.location.href = res.data.redirect_url || '{{ route('home') }}';
                        return;
                    }
                    if (res.data.errors) {
                        var first = Object.values(res.data.errors)[0];
                        showErr(root, Array.isArray(first) ? first[0] : String(first));
                    } else {
                        showErr(root, res.data.message || 'Could not complete registration.');
                    }
                }).catch(function () {
                    showErr(root, 'Something went wrong. Please try again.');
                }).finally(function () {
                    btnComplete.disabled = false;
                });
            });
            [nameInput, emailInput, refInput].forEach(function (inp) {
                if (!inp) return;
                inp.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    if (btnComplete && !btnComplete.disabled) btnComplete.click();
                });
            });
        }
    }

    document.querySelectorAll('[data-unified-auth-root]').forEach(initRoot);
})();
</script>
