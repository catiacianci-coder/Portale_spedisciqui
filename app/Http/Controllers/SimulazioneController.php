<?php

namespace App\Http\Controllers;

use App\Models\comune;
use App\Models\corriere;
use App\Models\origine_destino;
use Illuminate\Http\Request;

class SimulazioneController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'id_comune_origine' => ['nullable', 'integer'],
            'id_comune_destino' => ['nullable', 'integer'],
        ]);

        $origineId = $validated['id_comune_origine'] ?? null;
        $destinoId = $validated['id_comune_destino'] ?? null;

        $tratte = collect();
        $corrieri = collect();
        $origineComune = null;
        $destinoComune = null;
        $ricercaEseguita = ! is_null($origineId) && ! is_null($destinoId);

        if ($ricercaEseguita) {
            $tratte = origine_destino::with(['origine', 'destino', 'corriere'])
                ->where('id_comune_origine', $origineId)
                ->where('id_comune_destino', $destinoId)
                ->get();

            $corrieri = $tratte
                ->pluck('corriere')
                ->filter()
                ->unique('id')
                ->values();

            $comuniPerId = comune::query()->whereIn('id', array_filter([(int) $origineId, (int) $destinoId]))->get()->keyBy('id');
            $origineComune = $comuniPerId->get((int) $origineId)?->comune;
            $destinoComune = $comuniPerId->get((int) $destinoId)?->comune;
        }

        return view('simulazione', compact(
            'tratte',
            'corrieri',
            'origineId',
            'destinoId',
            'origineComune',
            'destinoComune',
            'ricercaEseguita'
        ));
    }

    public function showByCorriere($id_corriere)
    {
        // 1. Cerchiamo il corriere per assicurarci che esista
        $corriere = corriere::findOrFail($id_corriere);

        // 2. Recuperiamo tutte le tratte (coppie) di quel corriere
        // Carichiamo anche i dati dei comuni (origine e destino)
        $tratte = origine_destino::with(['origine', 'destino'])
            ->where('id_corriere', $id_corriere)
            ->get();

        // 3. Passiamo tutto alla pagina simulazione
        $corrieri = collect([$corriere]);
        $origineId = null;
        $destinoId = null;
        $origineComune = null;
        $destinoComune = null;
        $ricercaEseguita = true;

        return view('simulazione', compact(
            'corriere',
            'tratte',
            'corrieri',
            'origineId',
            'destinoId',
            'origineComune',
            'destinoComune',
            'ricercaEseguita'
        ));
    }
}
