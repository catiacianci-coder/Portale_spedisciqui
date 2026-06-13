<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\PasswordPortale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Inserisci l’indirizzo email.',
            'email.email' => 'Inserisci un indirizzo email valido.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        $messaggioInvio = 'Abbiamo inviato il link sulla tua email per reimpostare la password.';

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withErrors(['email' => 'Hai già richiesto un link di recente. Attendi qualche minuto prima di riprovare.'])
                ->withInput($request->only('email'));
        }

        /* Stesso messaggio anche se l’email non è registrata: evita click ripetuti e non espone se l’account esiste. */
        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return back()->with('status', $messaggioInvio);
        }

        return back()->with('status', $messaggioInvio);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', new PasswordPortale()],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'password_confirmation.same' => 'Le due password non coincidono.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Password aggiornata correttamente. Ora puoi accedere.');
        }

        $message = match ($status) {
            Password::INVALID_USER => 'Non siamo riusciti a trovare l’account indicato.',
            Password::INVALID_TOKEN => 'Il link non è più valido o è scaduto. Richiedi di nuovo il recupero password.',
            default => 'Operazione non riuscita. Riprova.',
        };

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
