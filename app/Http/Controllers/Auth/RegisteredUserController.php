<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|exists:roles,name',
        ]);

        // ⭐ Créer l'utilisateur avec email_verified_at automatiquement renseigné
        $user = User::create([
            'name'              => $request->name,
            'prenom'            => $request->prenom,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'email_verified_at' => now(), // ⭐ Email automatiquement vérifié
        ]);

        // Assigner le rôle via Spatie
        $user->assignRole($request->role);

        // ⭐ SUPPRIMÉ : event(new Registered($user)); → N'envoie plus d'email

        Auth::login($user);

        // ⭐ Redirection DIRECTE vers le dashboard (plus de page de vérification)
        return redirect()->intended(route('dashboard', absolute: false));
    }
}
