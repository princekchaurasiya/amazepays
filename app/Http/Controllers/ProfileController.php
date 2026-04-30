<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    protected $otpVerificationController;

    public function __construct()
    {
        $this->otpVerificationController = new OtpVerificationController;
    }

    public function updateProfile(Request $request)
    {
        try {
            $allowed = [
                'name',
                'email',
                'mobile',
                'otp',
                'billing_address',
                'billing_address_two',
                'billing_city',
                'billing_state',
                'billing_zip',
                'billing_country',
                'return_to',
            ];
            $unknown = array_values(array_diff($request->keys(), $allowed));
            if ($unknown !== []) {
                return redirect()->back()->withErrors([
                    'unexpected_fields' => 'Unexpected input fields detected: '.implode(', ', $unknown),
                ]);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['nullable', 'email', 'max:255'],
                'mobile' => ['required', 'regex:/^[0-9]{10}$/'],
                'otp' => 'nullable|string|size:6',
                'billing_address' => 'nullable|string|max:255',
                'billing_address_two' => 'nullable|string|max:255',
                'billing_city' => 'nullable|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_zip' => 'nullable|string|max:20',
                'billing_country' => 'nullable|string|max:4',
                'return_to' => 'nullable|string|max:2048',
            ]);

            $user = Auth::user();

            if ($user->mobile !== $validated['mobile']) {
                $otpVerificationResponse = $this->otpVerificationController->profileUpdateVerifyOtp($validated['mobile'], $validated['otp'] ?? '');

                if ($otpVerificationResponse['status'] === 'error') {
                    return redirect()->back()->withErrors(['otp' => $otpVerificationResponse['message']]);
                }
            }

            // Phase-3 users table uses display_name (no `name` column).
            $user->display_name = $validated['name'];
            $user->email = $validated['email'] !== null && trim((string) $validated['email']) !== ''
                ? mb_strtolower(trim((string) $validated['email']))
                : null;
            $user->authIdentities()->updateOrCreate(
                ['type' => 'mobile', 'identifier' => (string) $validated['mobile']],
                [
                    'display_identifier' => (string) $validated['mobile'],
                    'is_primary' => true,
                    'verified_at' => now(),
                ]
            );
            // Phase-3: billing address is stored in `user_addresses` (not users table).
            $addressData = [
                'type' => 'billing',
                'full_name' => (string) ($validated['name'] ?? $user->display_name ?? 'Customer'),
                'phone' => (string) ($validated['mobile'] ?? ''),
                'line1' => (string) ($validated['billing_address'] ?? ''),
                'line2' => (string) ($validated['billing_address_two'] ?? ''),
                'city' => (string) ($validated['billing_city'] ?? ''),
                'state' => (string) ($validated['billing_state'] ?? ''),
                'postal_code' => (string) ($validated['billing_zip'] ?? ''),
                'country' => (string) ($validated['billing_country'] ?? 'IN'),
                'is_default_billing' => true,
            ];

            if ($user->save()) {
                // Ensure we have exactly one default billing address for this user.
                $existingDefault = UserAddress::query()
                    ->where('user_id', $user->id)
                    ->where('is_default_billing', true)
                    ->first();

                UserAddress::query()
                    ->where('user_id', $user->id)
                    ->where('is_default_billing', true)
                    ->update(['is_default_billing' => false]);

                if ($existingDefault) {
                    $existingDefault->fill($addressData);
                    $existingDefault->is_default_billing = true;
                    $existingDefault->save();
                } else {
                    UserAddress::query()->create(array_merge($addressData, ['user_id' => $user->id]));
                }

                $returnTo = (string) ($validated['return_to'] ?? '');
                if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
                    return redirect($returnTo)->with('success', 'Profile updated successfully!');
                }

                return redirect()->back()->with('success', 'Profile updated successfully!');
            }

            return redirect()->back()->withErrors(['general' => 'Failed to update the profile. Please try again later.']);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->withErrors(['general' => 'An unexpected error occurred. Please try again.']);
        }
    }

    public function isMobileNumberInUse(Request $request)
    {
        $isInUse = User::query()->whereMobile((string) $request->destination)
            ->where('id', '!=', auth()->id())
            ->exists();

        return ResponsePayload::ok('response.ok', [
            'available' => ! $isInUse,
        ]);
    }
}
