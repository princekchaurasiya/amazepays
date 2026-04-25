<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use App\Mail\NewLeadInquiryMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactUsController extends Controller
{
    public function saveContact(Request $request)
    {
        try {
            $messages = [
                'name.required' => 'Please enter your name.',
                'name.regex' => 'The name can only contain letters and spaces.',
                'name.max' => 'The name should not exceed 50 characters.',
                'email.required' => 'We need your email address.',
                'email.email' => 'Please provide a valid email address.',
                'email.max' => 'The email should not exceed 100 characters.',
                'message.required' => 'Please enter a message.',
                'message.string' => 'The message should be a valid string.',
                'message.min' => 'The message should be at least 10 characters long.',
                'message.max' => 'The message should not exceed 1000 characters.',
                'contact_number.required' => 'Please enter your contact number.',
                'contact_number.regex' => 'Please provide a valid contact number.',
            ];

            $validated = $request->validate([
                'name' => ['required', 'string', 'regex:/^[\pL\s]+$/u', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'message' => ['required', 'string', 'min:10', 'max:1000'],
                'contact_number' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            ], $messages);

            ContactUs::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'message' => $validated['message'],
                'contact_number' => $validated['contact_number'],
            ]);

            $leadDetails = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'message' => $validated['message'],
                'contact_number' => $validated['contact_number'],
            ];

            $recipientEmail = env('CONTACT_US_ADMIN_EMAIL');
            $itAdminEmail = env('CONTACT_US_IT_ADMIN_EMAIL');

            $mailable = (new NewLeadInquiryMail($leadDetails))
                ->from(config('companyDefaultValues.sendMailFrom'), config('companyDefaultValues.company_name'));

            $mailer = Mail::to($recipientEmail);
            if (! empty($itAdminEmail)) {
                $mailer->cc($itAdminEmail);
            }
            $mailer->send($mailable);

            return redirect()->back()->with('success', 'Contact information saved successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('An error occurred while submitting the contact form. Please try again.')->withInput();
        }
    }
}
