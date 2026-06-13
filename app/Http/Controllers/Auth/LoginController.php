<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Support\CarrelloUtente;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    private function anagraficaIncompleta(User $user): bool
    {
        $a = $user->anagrafica;

        if (! $a) {
            return true;
        }

        $indirizzo = trim((string) ($a->indirizzo ?? ''));

        return $indirizzo === '';
    }

    public function showLoginForm() 
    { 
        // Salviamo la pagina di provenienza solo se non è già presente
        if (!session()->has('url.intended')) {
            session(['url.intended' => url()->previous()]);
        }
        
        return view('auth.login'); 
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Prima del login la sessione ospite può contenere preventivo e carrello.
        // regenerate() dopo Auth::attempt può far perdere quei dati: backup e ripristino.
        $preventivoBackup = $request->session()->get('preventivo');
        $carrelloBackup = $request->session()->get('carrello');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            if ($preventivoBackup !== null) {
                $request->session()->put('preventivo', $preventivoBackup);
            }
            if (is_array($carrelloBackup) && ! empty($carrelloBackup['items'])) {
                $request->session()->put('carrello', $carrelloBackup);
                CarrelloUtente::salvaDaSessione($request);
            }

            $user = Auth::user();

            if ($this->anagraficaIncompleta($user)) {
                session([
                    'registering_user_id' => $user->id,
                    'temp_user_data' => ['tipo_utente' => $user->tipo_utente],
                ]);

                return redirect()
                    ->route('register.complete')
                    ->with('info_anagrafica', 'Completa l’anagrafica (codice fiscale e indirizzo) per usare il portale.');
            }

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'Credenziali errate.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}