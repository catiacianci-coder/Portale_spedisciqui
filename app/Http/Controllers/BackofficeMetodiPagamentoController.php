<?php

namespace App\Http\Controllers;

use App\Support\BackofficeMetodiPagamentoConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackofficeMetodiPagamentoController extends Controller
{
    public function index(Request $request): View
    {
        $contesto = (string) $request->query('contesto', BackofficeMetodiPagamentoConfig::CONTESTO_ORDINI);
        if (! BackofficeMetodiPagamentoConfig::isContestoValido($contesto)) {
            $contesto = BackofficeMetodiPagamentoConfig::CONTESTO_ORDINI;
        }

        $metodi = BackofficeMetodiPagamentoConfig::metodiPerContesto($contesto);
        $conteggi = [];
        foreach (BackofficeMetodiPagamentoConfig::contesti() as $row) {
            $conteggi[$row['id']] = BackofficeMetodiPagamentoConfig::metodiPerContesto($row['id'])->count();
        }

        return view('backoffice.metodi-pagamento.index', [
            'contesto' => $contesto,
            'contesti' => BackofficeMetodiPagamentoConfig::contesti(),
            'conteggi' => $conteggi,
            'metodi' => $metodi,
        ]);
    }

    public function update(Request $request, string $contesto, int $id): RedirectResponse
    {
        if (! BackofficeMetodiPagamentoConfig::isContestoValido($contesto)) {
            abort(404);
        }

        $metodo = BackofficeMetodiPagamentoConfig::findMetodo($contesto, $id);

        $validated = $request->validate([
            'metodo_pagamento' => ['required', 'string', 'max:120'],
            'commissioni' => ['required', 'numeric', 'min:-100', 'max:100'],
            'varie' => ['nullable', 'string', 'max:500'],
        ], [
            'metodo_pagamento.required' => 'Inserisci il nome del metodo.',
            'metodo_pagamento.max' => 'Il nome non può superare 120 caratteri.',
            'commissioni.required' => 'Inserisci la commissione.',
            'commissioni.numeric' => 'La commissione deve essere un numero.',
        ]);

        $varie = trim((string) ($validated['varie'] ?? ''));

        $metodo->forceFill([
            'metodo_pagamento' => trim($validated['metodo_pagamento']),
            'commissioni' => round((float) $validated['commissioni'], 4),
            'varie' => $varie !== '' ? $varie : null,
        ])->save();

        return redirect()
            ->route('backoffice.metodi_pagamento.index', ['contesto' => $contesto])
            ->with('ok', 'Metodo «'.$metodo->metodo_pagamento.'» aggiornato.');
    }

    public function toggleAbilitato(Request $request, string $contesto, int $id): RedirectResponse
    {
        if (! BackofficeMetodiPagamentoConfig::isContestoValido($contesto)) {
            abort(404);
        }

        $metodo = BackofficeMetodiPagamentoConfig::findMetodo($contesto, $id);
        $metodo->forceFill([
            'abilitato' => ! (bool) $metodo->abilitato,
        ])->save();

        $stato = $metodo->abilitato ? 'abilitato' : 'disabilitato';

        return redirect()
            ->route('backoffice.metodi_pagamento.index', ['contesto' => $contesto])
            ->with('ok', 'Metodo «'.$metodo->metodo_pagamento.'» '.$stato.'.');
    }
}
