<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
// SUPPRIMÉ : use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            // MODIFIÉ : Utilisation de route() avec le paramètre de succès
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // MODIFIÉ ici aussi
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}