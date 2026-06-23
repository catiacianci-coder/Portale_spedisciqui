<?php

namespace App\Http\Controllers;

use App\Models\corriere;
use App\Models\ricarico;
use App\Support\CorriereBackofficeConfig;
use Illuminate\Http\Request;

class BackofficeCorrieriController extends Controller
{
    public function index()
    {
        $corrieri = corriere::query()
            ->orderBy('nome_corriere')
            ->orderBy('nome_visualizzato')
            ->orderBy('id')
            ->get();

        $ricarichi = ricarico::query()->orderBy('id')->get(['id', 'percentuale']);

        $vista = (string) request()->query('vista', 'corrieri');
        if (! in_array($vista, ['corrieri', 'campi'], true)) {
            $vista = 'corrieri';
        }

        $openCorriereId = (int) request()->query('corriere', 0);
        $openCampo = (string) request()->query('campo', '');
        if ($vista === 'campi' && $openCampo === '') {
            $openCampo = (string) array_key_first(CorriereBackofficeConfig::campi());
        }

        return view('backoffice.corrieri.index', [
            'corrieri' => $corrieri,
            'campi' => CorriereBackofficeConfig::campi(),
            'tipoOdOptions' => CorriereBackofficeConfig::tipoOdOptions(),
            'ricarichi' => $ricarichi,
            'vista' => $vista,
            'openCorriereId' => $openCorriereId,
            'openCampo' => $openCampo,
        ]);
    }

    public function edit(corriere $corriere)
    {
        return redirect()->route('backoffice.corrieri.index', [
            'vista' => 'corrieri',
            'corriere' => $corriere->id,
        ]);
    }

    public function toggleCarosello(corriere $corriere)
    {
        if ((int) $corriere->ord_carosello > 0) {
            $corriere->update(['ord_carosello' => 0]);
            $msg = 'Corriere rimosso dal carosello home.';
        } else {
            $max = (int) corriere::query()->max('ord_carosello');
            $corriere->update(['ord_carosello' => $max + 1]);
            $msg = 'Corriere aggiunto al carosello home.';
        }

        return redirect()
            ->route('backoffice.corrieri.index', $this->indexQuery())
            ->with('ok', $msg);
    }

    public function toggleAttivo(corriere $corriere)
    {
        $nuovo = ! (bool) $corriere->attivo;
        $corriere->update(['attivo' => $nuovo]);

        $msg = $nuovo
            ? 'Corriere abilitato.'
            : 'Corriere disabilitato.';

        return redirect()
            ->route('backoffice.corrieri.index', $this->indexQuery())
            ->with('ok', $msg);
    }

    public function updateCorriere(Request $request, corriere $corriere)
    {
        $rules = [];
        foreach (CorriereBackofficeConfig::campi() as $key => $meta) {
            $rules[$key] = $meta['rules'];
        }

        $validated = $request->validate($rules);
        $payload = [];

        foreach (CorriereBackofficeConfig::campi() as $key => $meta) {
            if ($meta['type'] === 'boolean') {
                $payload[$key] = $request->boolean($key);
            } else {
                $payload[$key] = CorriereBackofficeConfig::normalize($key, $validated[$key] ?? null);
            }
        }

        $corriere->update($payload);

        return redirect()
            ->route('backoffice.corrieri.index', $this->indexQuery([
                'corriere' => $corriere->id,
            ]))
            ->with('ok', 'Corriere aggiornato.');
    }

    public function updateCampo(Request $request, string $campo)
    {
        if (! CorriereBackofficeConfig::hasCampo($campo)) {
            abort(404);
        }

        $meta = CorriereBackofficeConfig::campi()[$campo];
        $corrieri = corriere::query()->orderBy('id')->pluck('id');

        $rules = [
            'values' => ['required', 'array'],
        ];

        foreach ($corrieri as $id) {
            $rules['values.'.$id] = $meta['rules'];
        }

        $validated = $request->validate($rules);

        foreach ($corrieri as $id) {
            $raw = $validated['values'][$id] ?? null;
            $meta = CorriereBackofficeConfig::campi()[$campo];

            if ($meta['type'] === 'boolean') {
                $value = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            } else {
                $value = CorriereBackofficeConfig::normalize($campo, $raw);
            }

            corriere::query()->whereKey($id)->update([$campo => $value]);
        }

        return redirect()
            ->route('backoffice.corrieri.index', $this->indexQuery([
                'vista' => 'campi',
                'campo' => $campo,
            ]))
            ->with('ok', 'Campo «'.$campo.'» aggiornato per tutti i corrieri.');
    }

    /** @deprecated Usare updateCorriere dalla pagina index */
    public function update(Request $request, corriere $corriere)
    {
        return $this->updateCorriere($request, $corriere);
    }

    /** @return array<string, mixed> */
    private function indexQuery(array $extra = []): array
    {
        return array_merge([
            'vista' => request()->query('vista', 'corrieri'),
        ], $extra);
    }
}
