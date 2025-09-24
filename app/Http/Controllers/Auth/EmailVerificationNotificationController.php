<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class EmailVerificationNotificationController
 *
 * This controller is responsible for sending email verification links
 * to users who have not yet verified their email addresses.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification to the authenticated user.
     *
     * @param  Request  $request The current HTTP request instance containing the user.
     * @return RedirectResponse Redirects to the home page if already verified, 
     *                          otherwise back with a status message.
     *
     * @group Authentication
     * @authenticated
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
