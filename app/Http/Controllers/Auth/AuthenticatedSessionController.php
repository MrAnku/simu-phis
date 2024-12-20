<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
    
            $request->session()->regenerate();

            log_action('Company logged in');
    
            return redirect()->intended(route('dashboard', absolute: false));
        } catch (ValidationException $e) {
            // Check if the exception is related to MFA
            if ($e->validator->errors()->has('mfa')) {
                return redirect()->route('mfa.enter');
            }
    
            // If it's a different validation exception, rethrow it
            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        log_action('Company logged out');
        
        Auth::guard('company')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();


        return redirect('/login');
    }
}
