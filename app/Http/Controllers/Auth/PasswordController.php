<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Class PasswordController
 *
 * This controller allows authenticated users to update
 * their account password from their profile or settings.
 */
class PasswordController extends Controller
{
    /**
     * Update the authenticated user's password.
     *
     * @param  Request  $request The HTTP request containing the current and new password fields.
     * @return RedirectResponse Redirects back with a success message if updated.
     *
     * @group Authentication
     * @authenticated
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
