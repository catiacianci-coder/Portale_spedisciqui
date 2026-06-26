<?php

namespace App\Http\Controllers;

use App\Models\Anagrafica;
use App\Services\Anagrafica\AnagraficaRevisioneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfiloAnagraficaController extends Controller
{
    public function __construct(
        private readonly AnagraficaRevisioneService $anagraficaRevisione,
    ) {}

    public function edit(Request $request)
    {
        $a = $request->user()->anagrafica;
        $idComuneCorrente = null;
        if ($a) {
            $idComuneCorrente = $this->anagraficaRevisione->risolviIdComuneDaAnagrafica($a);
        }

        return view('profilo-anagrafica', [
            'anagrafica' => $a,
            'idComuneCorrente' => $idComuneCorrente,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $latest = Anagrafica::query()->where('user_id', $user->id)->attiva()->first();
        if (! $latest) {
            return redirect()
                ->route('register.complete')
                ->withErrors(['profilo' => 'Completa prima l’anagrafica dalla registrazione.']);
        }

        $tipo = (string) ($user->tipo_utente ?? 'privato');
        $validated = $request->validate($this->anagraficaRevisione->validationRules($tipo));

        $nuova = $this->anagraficaRevisione->creaRevisioneSeModificato($user, $latest, $validated);
        if ($nuova === null) {
            return redirect()
                ->route('profilo.anagrafica')
                ->with('anagrafica_unchanged', true);
        }

        return redirect()
            ->route('profilo.anagrafica')
            ->with('ok', 'Anagrafica aggiornata');
    }
}
