<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use App\Models\parametri_globali;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;
use App\Models\wallet_ricarica_richiesta;
use App\Services\WalletSaldoService;
use App\Support\ImportoEuro;
use Illuminate\Http\Request;

class WalletRicaricaController extends Controller
{
    private function minimoEuro(): int
    {
        $v = parametri_globali::recordAttivo('Ricarica wallet minimo (EUR)')?->valore_assoluto;

        if ($v !== null) {
            return max(1, (int) round((float) $v));
        }

        return max(1, (int) config('wallet.ricarica_min_default', 150));
    }

    public function show(Request $request)
    {
        $min = $this->minimoEuro();
        $saldo = app(WalletSaldoService::class)->saldoUtente((int) $request->user()->id);

        return view('wallet.ricarica', [
            'minEuro' => $min,
            'saldoAttuale' => $saldo,
            'condicoesWallet' => LegalDocumentVersion::ultimaVersaoPublicada(
                LegalDocumentVersion::SLUG_CONDICOES_WALLET
            ),
        ]);
    }

    public function store(Request $request)
    {
        $min = $this->minimoEuro();
        $validated = $request->validate([
            'importo' => ['required', 'integer', 'min:'.$min],
        ], [
            'importo.required' => 'Inserisci un importo.',
            'importo.integer' => 'L\'importo deve essere un numero intero (senza centesimi).',
            'importo.min' => 'L\'importo minimo è '.ImportoEuro::format($min, 0).'.',
        ]);

        $euro = (int) $validated['importo'];

        if (config('wallet.ricarica_accredita_senza_gateway')) {
            $desc = wallet_descrizione::query()->where('codice', 'ricarica')->firstOrFail();
            wallet_movimento::query()->create([
                'user_id' => $request->user()->id,
                'tipo' => 'credito',
                'wallet_descrizione_id' => $desc->id,
                'importo' => $euro,
                'data_movimento' => now(),
                'riferimento' => 'Ricarica (ambiente sviluppo)',
            ]);

            return redirect()
                ->route('wallet.ricarica')
                ->with('ok', 'Ricarica simulata: sono stati accreditati '.ImportoEuro::format($euro, 0).' sul wallet (solo modalità sviluppo).');
        }

        wallet_ricarica_richiesta::query()->create([
            'user_id' => $request->user()->id,
            'importo' => $euro,
            'stato' => 'in_attesa',
        ]);

        return redirect()
            ->route('wallet.ricarica')
            ->with('info', 'Richiesta di ricarica di '.ImportoEuro::format($euro, 0).' registrata in attesa di pagamento. L’importo non è ancora accreditato: quando il gateway sarà attivo confermeremo l’incasso; in ambiente di test il back-office può simulare l’accredito.');
    }
}
