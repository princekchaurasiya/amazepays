<?php

namespace App\Http\Controllers;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordUpdateController extends Controller
{
    public function updatePassword(Request $request)
    {



        $request->validate([
            'current_password' => 'required',
            'password' => 'required',
        ]);






        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()
                ->back()
                ->with('error', 'Current password is incorrect.');
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return redirect()
            ->route('change-password')
            ->with('success', 'Password changed successfully.');
    }
}
