<?php

namespace App\Http\Controllers;

use App\Rules\PasswordPortale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfiloPasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('profilo-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', new PasswordPortale()],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'current_password.current_password' => 'La password attuale non è corretta.',
            'password_confirmation.same' => 'Le due password non coincidono.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('profilo.password')
            ->with('password_saved', true);
    }
}
