<?php

namespace App\Http\Controllers;

use App\Models\parametri_globali;
use App\Support\ParametriApi;
use Illuminate\Http\Request;

class BackofficeParametriGlobaliController extends Controller
{
    public function edit()
    {
        $chiaviImpresa = [
            'nome_impresa' => parametri_globali::DENOM_NOME_IMPRESA,
            'indirizzo_impresa' => parametri_globali::DENOM_INDIRIZZO_IMPRESA,
            'p_iva_impresa' => parametri_globali::DENOM_P_IVA_IMPRESA,
            'sito_impresa' => parametri_globali::DENOM_SITO_IMPRESA,
        ];

        $chiaviPagamenti = [
            'iban_cc_r_b' => parametri_globali::DENOM_IBAN_CC_R_B,
        ];

        $denoms = array_merge(array_values($chiaviImpresa), array_values($chiaviPagamenti), ParametriApi::denominazioni());
        $byDenom = parametri_globali::query()
            ->whereIn('denominazione', $denoms)
            ->pluck('valore_testo', 'denominazione');

        $valoriImpresa = [];
        foreach ($chiaviImpresa as $campo => $denom) {
            $valoriImpresa[$campo] = trim((string) ($byDenom[$denom] ?? ''));
        }

        $valoriPagamenti = [];
        foreach ($chiaviPagamenti as $campo => $denom) {
            $valoriPagamenti[$campo] = trim((string) ($byDenom[$denom] ?? ''));
        }

        $valoriApi = [];
        foreach (ParametriApi::definizioni() as $denom => $meta) {
            $valoriApi[$denom] = trim((string) ($byDenom[$denom] ?? ''));
        }

        return view('backoffice.parametri-globali', [
            'valori' => $valoriImpresa,
            'valoriPagamenti' => $valoriPagamenti,
            'valoriApi' => $valoriApi,
            'apiPerGruppo' => ParametriApi::denominazioniPerGruppo(),
            'apiDefinizioni' => ParametriApi::definizioni(),
        ]);
    }

    public function update(Request $request)
    {
        $rulesImpresa = [
            'nome_impresa' => ['nullable', 'string', 'max:255'],
            'indirizzo_impresa' => ['nullable', 'string', 'max:1000'],
            'p_iva_impresa' => ['nullable', 'string', 'max:40'],
            'sito_impresa' => ['nullable', 'string', 'max:512'],
            'iban_cc_r_b' => ['nullable', 'string', 'max:64'],
        ];

        $rulesApi = [];
        foreach (ParametriApi::denominazioni() as $denom) {
            $rulesApi['api_'.$denom] = ['nullable', 'string', 'max:2000'];
        }

        $validated = $request->validate(array_merge($rulesImpresa, $rulesApi));

        $mapImpresa = [
            'nome_impresa' => parametri_globali::DENOM_NOME_IMPRESA,
            'indirizzo_impresa' => parametri_globali::DENOM_INDIRIZZO_IMPRESA,
            'p_iva_impresa' => parametri_globali::DENOM_P_IVA_IMPRESA,
            'sito_impresa' => parametri_globali::DENOM_SITO_IMPRESA,
        ];

        foreach ($mapImpresa as $field => $denom) {
            $testo = isset($validated[$field]) ? trim((string) $validated[$field]) : '';
            parametri_globali::query()->where('denominazione', $denom)->update([
                'valore_testo' => $testo === '' ? null : $testo,
                'updated_at' => now(),
            ]);
        }

        $iban = isset($validated['iban_cc_r_b']) ? trim((string) $validated['iban_cc_r_b']) : '';
        parametri_globali::query()->updateOrInsert(
            ['denominazione' => parametri_globali::DENOM_IBAN_CC_R_B],
            [
                'valore_testo' => $iban === '' ? null : $iban,
                'varie' => 'Conto corrente (IBAN) per ricevere i bonifici bancari.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        foreach (ParametriApi::definizioni() as $denom => $meta) {
            $field = 'api_'.$denom;
            $testo = isset($validated[$field]) ? trim((string) $validated[$field]) : '';
            parametri_globali::query()->updateOrInsert(
                ['denominazione' => $denom],
                [
                    'valore_testo' => $testo === '' ? null : $testo,
                    'varie' => $meta['label'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        parametri_globali::forgetTestoCache();

        return redirect()
            ->route('backoffice.parametri_globali.edit')
            ->with('ok', 'Parametri globali aggiornati.');
    }
}
