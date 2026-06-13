<?php

namespace App\Http\Controllers;

use App\Models\parametri_globali;
use App\Services\Cliente\ClienteNotificazioniRiepilogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClienteNotificazioneController extends Controller
{
    public function dispensarAvvisoPiattaforma(Request $request): RedirectResponse
    {
        $testo = parametri_globali::homepageAvvisoTesto();
        if ($testo !== null) {
            $request->session()->put('cliente_avviso_piattaforma_hash', md5($testo));
        }

        ClienteNotificazioniRiepilogoService::pulisciCacheUtente((int) $request->user()->id);

        return back();
    }
}
