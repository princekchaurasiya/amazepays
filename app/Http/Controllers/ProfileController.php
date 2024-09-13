<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        Log::info('Profile Update Request received for user: ' . Auth::id(), $request->all());

        try {
            // Custom validation messages
            $messages = [
                'name.required' => 'Please enter your name.',
                'name.string' => 'The name should be a valid string.',
                'name.max' => 'The name should not exceed 255 characters.',
                'name.regex' => 'The name should not contain numbers or special characters.',
                'email.required' => 'We need your email address.',
                'email.email' => 'Please provide a valid email address.',
                'email.unique' => 'This email is already registered. Please use a different email address.',
                'phone.required' => 'A phone number is required.',
                'phone.regex' => 'The phone number must be 10 digits and should not contain spaces or special characters.',
                'phone.min' => 'The phone number must be exactly 10 digits.',
                'phone.max' => 'The phone number must be exactly 10 digits.',
            ];

            // Validation rules
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'], // Name must be letters and spaces only
                'email' => 'required|email|unique:users,email,' . Auth::id(),
                'phone' => ['required', 'regex:/^[0-9]{10}$/'], // Phone number must be 10 digits
            ], $messages);

            // Log successful validation
            Log::info('Profile update validation passed for user: ' . Auth::id());

            // Update the user's profile
            $user = Auth::user();
            $user->name = $validatedData['name'];
            $user->email = $validatedData['email'];
            $user->mobile = $validatedData['phone'];

            if ($user->save()) {
                // Log successful save
                Log::info('Profile updated successfully for user: ' . Auth::id());
                return redirect()->back()->with('success', 'Profile updated successfully!');
            } else {
                // Log failed save
                Log::error('Failed to save profile update for user: ' . Auth::id());
                return redirect()->back()->withErrors('An error occurred while saving your profile. Please try again.');
            }
        } catch (ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed for user: ' . Auth::id() . ' with errors: ', $e->errors());

            // Redirect back with validation errors
            return redirect()->back()->withErrors($e->errors());
        } catch (\Exception $e) {
            // Log general exception errors
            Log::error('Profile update failed for user: ' . Auth::id() . ' with error: ' . $e->getMessage());
            return redirect()->back()->withErrors('An unexpected error occurred. Please try again.');
        }
    }
}
