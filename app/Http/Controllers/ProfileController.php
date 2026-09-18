<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form pre-filled with the authenticated user's data.
     */
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone_number')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Your profile has been updated successfully.');
    }
}
