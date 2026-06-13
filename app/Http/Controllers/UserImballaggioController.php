<?php

namespace App\Http\Controllers;

use App\Models\tipo_spedizone;
use App\Models\UserImballaggio;
use App\Services\UserImballaggiDefault;
use Illuminate\Http\Request;

class UserImballaggioController extends Controller
{
    public function index(Request $request, UserImballaggiDefault $defaults)
    {
        $user = $request->user();
        $defaults->ensureDefaults($user);

        $imballaggi = $user->imballaggi()
            ->with('tipoSpedizione')
            ->orderByDesc('is_preferito')
            ->orderBy('nome')
            ->get();

        $tipiByNome = tipo_spedizone::query()
            ->get()
            ->keyBy(static fn (tipo_spedizone $t) => mb_strtolower((string) $t->tipo_spedizione));

        $categorieNav = [];
        foreach (
            [
                ['slug' => 'pacco', 'label' => 'Pacco', 'db' => 'Pacco'],
                ['slug' => 'pallet', 'label' => 'Pallet', 'db' => 'Pallet'],
                ['slug' => 'documenti', 'label' => 'Documenti', 'db' => 'Documento'],
            ] as $def
        ) {
            $tipo = $tipiByNome->get(mb_strtolower($def['db']));
            if ($tipo) {
                $categorieNav[] = [
                    'slug' => $def['slug'],
                    'label' => $def['label'],
                    'tipo_id' => (int) $tipo->id,
                ];
            }
        }

        $imballaggiJson = $imballaggi->map(static function (UserImballaggio $row) {
            return [
                'id' => $row->id,
                'nome' => $row->nome,
                'id_tipo_spediziones' => (int) $row->id_tipo_spediziones,
                'tipo_label' => $row->tipoSpedizione->tipo_spedizione ?? '—',
                'altezza' => (float) $row->altezza,
                'larghezza' => (float) $row->larghezza,
                'spessore' => (float) $row->spessore,
                'peso' => (float) $row->peso,
                'is_preferito' => (bool) $row->is_preferito,
            ];
        })->values()->all();

        $tipi = tipo_spedizone::query()->orderBy('tipo_spedizione')->get(['id', 'tipo_spedizione']);

        return view('imballaggi.index', [
            'imballaggi' => $imballaggi,
            'imballaggiJson' => $imballaggiJson,
            'tipi' => $tipi,
            'categorieNav' => $categorieNav,
        ]);
    }

    public function create(Request $request)
    {
        return redirect()->route('imballaggi.index', ['nuovo' => 1]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'id_tipo_spediziones' => ['required', 'integer', 'exists:tipo_spediziones,id'],
            'altezza' => ['required', 'numeric', 'min:0.01'],
            'larghezza' => ['required', 'numeric', 'min:0.01'],
            'spessore' => ['required', 'numeric', 'min:0.01'],
            'peso' => ['required', 'numeric', 'min:0.01'],
        ]);

        $request->user()->imballaggi()->create($validated);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Imballaggio creato.']);
        }

        return redirect()->route('imballaggi.index')->with('ok', 'Imballaggio creato.');
    }

    public function edit(Request $request, UserImballaggio $imballaggio)
    {
        $this->authorizeImballaggio($request, $imballaggio);

        return redirect()->route('imballaggi.index', ['modifica' => $imballaggio->id]);
    }

    public function update(Request $request, UserImballaggio $imballaggio)
    {
        $this->authorizeImballaggio($request, $imballaggio);

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'id_tipo_spediziones' => ['required', 'integer', 'exists:tipo_spediziones,id'],
            'altezza' => ['required', 'numeric', 'min:0.01'],
            'larghezza' => ['required', 'numeric', 'min:0.01'],
            'spessore' => ['required', 'numeric', 'min:0.01'],
            'peso' => ['required', 'numeric', 'min:0.01'],
        ]);

        $tipoPrima = (int) $imballaggio->id_tipo_spediziones;
        $imballaggio->update($validated);
        if ($tipoPrima !== (int) $validated['id_tipo_spediziones']) {
            $imballaggio->update(['is_preferito' => false]);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Imballaggio aggiornato.']);
        }

        return redirect()->route('imballaggi.index')->with('ok', 'Imballaggio aggiornato.');
    }

    public function destroy(Request $request, UserImballaggio $imballaggio)
    {
        $this->authorizeImballaggio($request, $imballaggio);
        $imballaggio->delete();

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Imballaggio eliminato.']);
        }

        return redirect()->route('imballaggi.index')->with('ok', 'Imballaggio eliminato.');
    }

    public function setPreferito(Request $request, UserImballaggio $imballaggio)
    {
        $this->authorizeImballaggio($request, $imballaggio);

        $user = $request->user();
        $tipoId = (int) $imballaggio->id_tipo_spediziones;

        if ($imballaggio->is_preferito) {
            $imballaggio->update(['is_preferito' => false]);
        } else {
            $user->imballaggi()->where('id_tipo_spediziones', $tipoId)->update(['is_preferito' => false]);
            $imballaggio->update(['is_preferito' => true]);
        }

        $fresh = $imballaggio->fresh();

        if ($request->ajax()) {
            return response()->json([
                'ok' => true,
                'is_preferito' => (bool) $fresh->is_preferito,
                'imballaggio_id' => (int) $fresh->id,
                'id_tipo_spediziones' => (int) $fresh->id_tipo_spediziones,
            ]);
        }

        return redirect()->route('imballaggi.index')->with(
            'ok',
            $fresh->is_preferito ? 'Imballaggio impostato come preferito per questa categoria.' : 'Preferito rimosso.'
        );
    }

    private function authorizeImballaggio(Request $request, UserImballaggio $imballaggio): void
    {
        abort_if((int) $imballaggio->user_id !== (int) $request->user()->id, 403);
    }
}
