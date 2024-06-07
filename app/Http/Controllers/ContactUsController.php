<?php

namespace App\Http\Controllers;
use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function saveContact(Request $request)
{
    // Validate the form data
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',

    ]);

    // Save the data to the "contacts" table
    ContactUs::create([
        'name' => $request->input('name'),
        'email' => $request->input('email'),
        'message' => $request->input('message'),
    ]);

    return redirect()->back()->with('success', 'Contact information saved successfully!');
}
}
