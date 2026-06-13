<?php

namespace App\Http\Controllers;

use App\Models\spedizione;
use App\Support\EtichettaSpedizioneAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SpedizioneEtichettaController extends Controller
{
    public function showCliente(Request $request, spedizione $spedizione): BinaryFileResponse
    {
        $this->authorize('view', $spedizione);

        return $this->download($spedizione);
    }

    public function showBackoffice(Request $request, spedizione $spedizione): BinaryFileResponse
    {
        return $this->download($spedizione);
    }

    private function download(spedizione $spedizione): BinaryFileResponse
    {
        $full = EtichettaSpedizioneAccess::percorsoAssoluto($spedizione);

        if ($full === null) {
            if (EtichettaSpedizioneAccess::etichettaCancellata($spedizione)) {
                abort(404, 'Lettera di vettura cancellata.');
            }

            abort(404, 'Etichetta non disponibile.');
        }

        $nome = basename($full);

        return response()->file($full, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nome.'"',
        ]);
    }
}
