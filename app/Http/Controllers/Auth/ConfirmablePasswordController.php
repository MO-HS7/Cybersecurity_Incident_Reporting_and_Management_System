<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class ConfirmablePasswordController
 *
 * This controller handles confirming the user's password before
 * performing sensitive actions (e.g., account deletion, updating email).
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Display the confirm password view.
     *
     * @return Response Inertia view containing the confirm password form.
     *
     * @group Authentication
     * @authenticated
     */
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    /**
     * Confirm the user's password before granting access to sensitive actions.
     *
     * @param  Request  $request The HTTP request containing the user's password input.
     * @return RedirectResponse Redirects to the intended route after successful confirmation.
     *
     * @throws ValidationException If the provided password does not match the current user.
     *
     * @group Authentication
     * @authenticated
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
