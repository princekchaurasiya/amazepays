<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactUsController extends Controller
{
    public function saveContact(Request $request)
    {
        // Log incoming request data
        Log::info('Contact Us Request received', $request->all());

        try {
            // Define custom validation messages
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
                'message.max' => 'The message should not exceed 70 characters.',
            ];

            // Validate the form data with regex constraints
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'regex:/^[\pL\s]+$/u', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'message' => ['required', 'string', 'min:10', 'max:1000'],
            ], $messages);

            // Log validated data
            Log::info('Contact Us validation passed', $validatedData);

            // Save the data to the "contacts" table
            ContactUs::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'message' => $validatedData['message'],
            ]);

            Log::info('Contact information saved successfully');

           // Prepare email data
           $leadDetails = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'message' => $validatedData['message'],
        ];

        // Define the recipient email
        $recipientEmail = 'it@amazepays.in';

        // Send the email using Blade template
        Mail::send('email.new_lead_inquiry', [
            'leadDetails' => $leadDetails  // Pass the lead details to the view
        ], function ($message) use ($recipientEmail) {
            $message->from(config("companyDefaultValues.sendMailFrom"), config("companyDefaultValues.company_name"))
                ->to($recipientEmail)
                ->subject("New Lead Inquiry for Amazepay");
        });

        Log::info('Lead inquiry email sent to admin.');


            return redirect()->back()->with('success', 'Contact information saved successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::error('Contact Us form validation failed: ' . $e->getMessage());

            // Return validation errors to the user
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Log general exceptions
            Log::error('Contact Us form submission failed: ' . $e->getMessage());

            return redirect()->back()->withErrors('An error occurred while submitting the contact form. Please try again.')->withInput();
        }
    }
}
