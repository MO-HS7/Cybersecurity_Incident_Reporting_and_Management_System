<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class AuthenticatedSessionController
 *
 * This controller manages user authentication sessions,
 * including login, session creation, and logout.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     *
     * @return Response Inertia view containing the login form.
     *
     * @group Authentication
     * @unauthenticated
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Authenticate and start a new user session.
     *
     * @param  LoginRequest  $request The validated login request containing credentials.
     * @return RedirectResponse Redirects to the intended page or the home page.
     *
     * @group Authentication
     * @unauthenticated
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Log the user out and destroy the session.
     *
     * @param  Request  $request The current HTTP request instance.
     * @return RedirectResponse Redirects to the homepage after logout.
     *
     * @group Authentication
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
