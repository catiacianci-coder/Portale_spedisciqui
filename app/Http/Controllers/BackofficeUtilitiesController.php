<?php

namespace App\Http\Controllers;

use App\Models\metodo_pagamento;
use App\Models\parametri_globali;
use App\Models\ricarico;
use App\Support\ParametriGlobaliBackoffice;
use App\Support\ParametriGlobaliFiltri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BackofficeUtilitiesController extends Controller
{
    public function index(): View
    {
        $vista = (string) request()->query('vista', 'parametri');
        if (! in_array($vista, ['parametri', 'ricarichi'], true)) {
            $vista = 'parametri';
        }

        $filtriParametri = ParametriGlobaliFiltri::daRequest(request());
        $parametriTotali = parametri_globali::query()->count();

        $parametriQuery = parametri_globali::query()
            ->with('metodoPagamento:id,metodo_pagamento');
        $filtriParametri->applica($parametriQuery);
        $parametri = $parametriQuery
            ->orderBy('denominazione')
            ->orderByDesc('inizio_validita')
            ->orderBy('id')
            ->get();

        $denominazioni = parametri_globali::query()
            ->select('denominazione')
            ->distinct()
            ->orderBy('denominazione')
            ->pluck('denominazione');

        $ricarichi = ricarico::query()
            ->orderBy('id')
            ->get();

        $metodiPagamento = metodo_pagamento::query()
            ->orderBy('metodo_pagamento')
            ->get(['id', 'metodo_pagamento']);

        return view('backoffice.utilities.index', [
            'vista' => $vista,
            'parametri' => $parametri,
            'parametriTotali' => $parametriTotali,
            'filtriParametri' => $filtriParametri,
            'denominazioni' => $denominazioni,
            'ricarichi' => $ricarichi,
            'metodiPagamento' => $metodiPagamento,
            'colonneParametri' => ParametriGlobaliBackoffice::colonneTabella(),
        ]);
    }

    public function storeParametro(Request $request): RedirectResponse
    {
        $validated = $request->validate(ParametriGlobaliBackoffice::regoleStore());
        $payload = ParametriGlobaliBackoffice::payloadDaValidati($validated);

        parametri_globali::query()->create($payload);
        parametri_globali::forgetTestoCache();

        return redirect()
            ->back(fallback: route('backoffice.utilities.index', ['vista' => 'parametri']))
            ->with('ok', 'Nuovo parametro globale creato.');
    }

    public function updateParametro(Request $request, parametri_globali $parametriGlobali): RedirectResponse
    {
        $validated = $request->validate(ParametriGlobaliBackoffice::regoleUpdate());

        $payload = ParametriGlobaliBackoffice::payloadDaValidati($validated);

        if ($payload['inizio_validita'] === null) {
            $payload['inizio_validita'] = '2026-04-01';
        }

        $parametriGlobali->update($payload);
        parametri_globali::forgetTestoCache();

        return redirect()
            ->back(fallback: route('backoffice.utilities.index', ['vista' => 'parametri']))
            ->with('ok', 'Parametro globale aggiornato.');
    }

    public function duplicaParametro(Request $request, parametri_globali $parametriGlobali): RedirectResponse
    {
        $validated = $request->validate(ParametriGlobaliBackoffice::regoleDuplica());
        $payload = ParametriGlobaliBackoffice::payloadDaValidati($validated);

        $nuovoInizio = Carbon::parse((string) $payload['inizio_validita'])->startOfDay();

        DB::transaction(function () use ($parametriGlobali, $payload, $nuovoInizio): void {
            if ($parametriGlobali->inizio_validita !== null) {
                $parametriGlobali->update([
                    'fine_validita' => $nuovoInizio->copy()->subDay()->toDateString(),
                ]);
            }

            parametri_globali::query()->create($payload);
        });

        parametri_globali::forgetTestoCache();

        return redirect()
            ->back(fallback: route('backoffice.utilities.index', ['vista' => 'parametri']))
            ->with('ok', 'Parametro duplicato: il record originale è chiuso al giorno prima della nuova validità.');
    }

    public function updateRicarico(Request $request, ricarico $ricarico): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'percentuale' => ['required', 'numeric', 'min:0', 'max:999.99'],
        ]);

        $ricarico->update([
            'nome' => self::nullableTrim($validated['nome'] ?? null),
            'percentuale' => round((float) $validated['percentuale'], 2),
        ]);

        return redirect()
            ->route('backoffice.utilities.index', ['vista' => 'ricarichi'])
            ->with('ok', 'Ricarico aggiornato.');
    }

    private static function nullableTrim(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));

        return $s === '' ? null : $s;
    }
}
