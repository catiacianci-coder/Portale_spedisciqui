<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterFlowController extends Controller
{
    public function show(Request $request): View
    {
        if ($request->filled('return')) {
            $raw = $request->query('return');
            if (is_string($raw) && strlen($raw) <= 512) {
                $path = '/'.ltrim($raw, '/');
                if (! str_starts_with($path, '//') && preg_match('#^/[a-zA-Z0-9/_\-]*$#', $path)) {
                    session(['url_provenienza' => url($path)]);
                }
            }
        }

        return view('registrazione');
    }

    public function completeAnagrafica(Request $request): View
    {
        $user = $request->user();
        session([
            'registering_user_id' => $user->id,
            'temp_user_data' => ['tipo_utente' => $user->tipo_utente],
        ]);

        return view('registrazione', ['current_step' => 2]);
    }
}
