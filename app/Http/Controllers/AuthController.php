<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Show instant registration form.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Lightning-fast registration: name + phone (or email), auto-login, free module access.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                'required_without:email',
                Rule::unique('users', 'phone_number'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'required_without:phone_number',
                Rule::unique('users', 'email'),
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'] ?? null,
            'email' => $validated['email'] ?? null,
            'password' => Hash::make(Str::random(32)),
            'role' => User::ROLE_USER,
            'subscription_status' => User::SUBSCRIPTION_FREE,
        ]);

        $user->grantFreeModuleAccess();

        Auth::login($user, true);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Registration successful.',
                'redirect' => route('dashboard'),
                'user' => $user->only(['id', 'name', 'phone_number', 'email', 'subscription_status']),
            ], 201);
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
