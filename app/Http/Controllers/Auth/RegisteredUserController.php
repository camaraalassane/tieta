<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
// Spatie est bien importé ici
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
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|exists:roles,name', 
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'prenom' => $request->prenom, 
            'email' => $request->email,
            'password' => Hash::make($request->password), 
        ]);

        // Assigner le rôle via Spatie
        $user->assignRole($request->role);

        event(new Registered($user));

        Auth::login($user);

        // Correction pour Laravel 12 : Redirection vers la route nommée
        return redirect()->intended(route('dashboard', absolute: false));
    }
}