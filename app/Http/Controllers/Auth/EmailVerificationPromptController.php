<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class EmailVerificationPromptController
 *
 * This controller displays a prompt asking users to verify their email
 * if they have not already done so. If the user is verified, they are redirected
 * to the intended home page.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Handle the incoming request and display the email verification prompt.
     *
     * @param  Request  $request The HTTP request instance containing the authenticated user.
     * @return RedirectResponse|Response Redirects to the home page if verified,
     *                                   otherwise returns an Inertia view to prompt verification.
     *
     * @group Authentication
     * @authenticated
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(RouteServiceProvider::HOME)
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
