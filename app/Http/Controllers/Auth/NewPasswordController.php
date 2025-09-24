<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class NewPasswordController
 *
 * This controller handles displaying the reset password form
 * and processing the request to update a user's password.
 */
class NewPasswordController extends Controller
{
    /**
     * Display the password reset form.
     *
     * @param  Request  $request The HTTP request containing the reset token and email.
     * @return Response Inertia view for resetting the password.
     *
     * @group Authentication
     * @unauthenticated
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming password reset request.
     *
     * @param  Request  $request The HTTP request containing email, token, and new password fields.
     * @return RedirectResponse Redirects to login page with status on success.
     *
     * @throws ValidationException If the password reset fails or validation is not met.
     *
     * @group Authentication
     * @unauthenticated
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
