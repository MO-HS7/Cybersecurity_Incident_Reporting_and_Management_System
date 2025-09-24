<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class PasswordResetLinkController
 *
 * This controller handles requests for sending password reset links
 * to users via email when they forget their password.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Show the "forgot password" view where users can request a reset link.
     *
     * @return Response Inertia view for requesting a password reset link.
     *
     * @group Authentication
     * @unauthenticated
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Process the password reset link request and send an email to the user.
     *
     * @param  Request  $request The HTTP request containing the user's email.
     * @return RedirectResponse Redirects back with a status message on success.
     *
     * @throws ValidationException If the email is invalid or sending fails.
     *
     * @group Authentication
     * @unauthenticated
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
