<?php

namespace App\Http\Controllers;

use App\Models\corriere;
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

        return view('backoffice.corrieri.index', [
            'corrieri' => $corrieri,
        ]);
    }

    public function edit(corriere $corriere)
    {
        return view('backoffice.corrieri.edit', [
            'corriere' => $corriere,
        ]);
    }

    public function update(Request $request, corriere $corriere)
    {
        $validated = $request->validate([
            'punto_ritiro' => ['nullable', 'string', 'max:255'],
            'punto_consegna' => ['nullable', 'string', 'max:255'],
            'trackingsn' => ['nullable', 'boolean'],
            'url_tracking' => ['nullable', 'string', 'max:512'],
        ]);

        $puntoRitiro = trim((string) ($validated['punto_ritiro'] ?? ''));
        $puntoConsegna = trim((string) ($validated['punto_consegna'] ?? ''));
        $urlTracking = trim((string) ($validated['url_tracking'] ?? ''));

        $corriere->update([
            'punto_ritiro' => $puntoRitiro !== '' ? $puntoRitiro : null,
            'punto_consegna' => $puntoConsegna !== '' ? $puntoConsegna : null,
            'trackingsn' => $request->boolean('trackingsn'),
            'url_tracking' => $urlTracking !== '' ? $urlTracking : null,
        ]);

        return redirect()
            ->route('backoffice.corrieri.edit', $corriere)
            ->with('ok', 'Etichette punto ritiro/consegna aggiornate.');
    }
}
