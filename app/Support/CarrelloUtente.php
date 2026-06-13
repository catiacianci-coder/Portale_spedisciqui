<?php

namespace App\Support;

use Illuminate\Http\Request;

class CarrelloUtente
{
    /** Se la sessione non ha righe ma l’utente ha un carrello salvato, lo ricarica in sessione. */
    public static function idrataSessione(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $cart = $request->session()->get('carrello', ['items' => []]);
        if (! empty($cart['items'])) {
            return;
        }

        $stored = $user->carrello_json;
        if (is_array($stored) && ! empty($stored['items'])) {
            $request->session()->put('carrello', $stored);
        }
    }

    /** Copia il carrello della sessione sul profilo utente (solo utenti autenticati). */
    public static function salvaDaSessione(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $user->carrello_json = $request->session()->get('carrello', ['items' => []]);
        $user->save();
    }
}
