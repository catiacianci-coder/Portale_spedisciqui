<?php

namespace App\Http\Controllers;

use App\Models\parametri_globali;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackofficeHomepageAvvisoController extends Controller
{
    public function edit(): View
    {
        return view('backoffice.homepage-avviso', [
            'testo' => parametri_globali::homepageAvvisoTesto() ?? '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'testo' => ['nullable', 'string', 'max:280'],
        ]);

        parametri_globali::salvaHomepageAvviso((string) ($data['testo'] ?? ''));

        return redirect()
            ->route('backoffice.homepage_avviso.edit')
            ->with('ok', 'Avviso homepage aggiornato.');
    }
}
