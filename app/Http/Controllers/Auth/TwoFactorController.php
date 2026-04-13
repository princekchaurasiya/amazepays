<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityEventLog;
use App\Services\SecurityEventService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
        private SecurityEventService $securityEvents,
    ) {}

    public function setup(Request $request)
    {
        $user = $request->user();
        $data = $this->twoFactor->generateSecret($user);

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrCodeUrl' => $data['qr_code_url'],
            'secret' => $data['secret'],
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = $request->user();
        $success = $this->twoFactor->confirm($user, $request->code);

        if (! $success) {
            return back()->withErrors(['code' => 'Invalid verification code. Please try again.']);
        }

        $this->securityEvents->log(SecurityEventLog::EVENT_2FA_CHALLENGE, SecurityEventLog::SEVERITY_INFO, null, [
            'action' => '2fa_enabled',
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Two-factor authentication enabled successfully.');
    }

    public function challenge()
    {
        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->user();
        $code = $request->code;

        // Try TOTP code
        if (strlen($code) === 6 && $this->twoFactor->verify($user, $code)) {
            session(['2fa_verified' => true, '2fa_verified_at' => now()->timestamp]);

            $this->securityEvents->log(SecurityEventLog::EVENT_2FA_CHALLENGE, SecurityEventLog::SEVERITY_INFO, null, [
                'action' => '2fa_verified',
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        // Try recovery code
        if (strlen($code) === 10 && $this->twoFactor->verifyRecoveryCode($user, $code)) {
            session(['2fa_verified' => true, '2fa_verified_at' => now()->timestamp]);

            $this->securityEvents->log(SecurityEventLog::EVENT_2FA_CHALLENGE, SecurityEventLog::SEVERITY_MEDIUM, null, [
                'action' => '2fa_recovery_code_used',
            ]);

            return redirect()->intended(route('admin.dashboard'))
                ->with('warning', 'Recovery code used. Please set up 2FA again.');
        }

        $this->securityEvents->log(SecurityEventLog::EVENT_2FA_FAILED, SecurityEventLog::SEVERITY_HIGH);

        return back()->withErrors(['code' => 'Invalid 2FA code.']);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $this->twoFactor->disable($request->user());

        $this->securityEvents->log('2fa_disabled', SecurityEventLog::SEVERITY_MEDIUM);

        return back()->with('success', 'Two-factor authentication has been disabled. A 72-hour restriction on financial actions is now active.');
    }
}
