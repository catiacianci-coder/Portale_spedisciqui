<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Models\parametri_globali;
use App\Models\spedizione;
use App\Support\CodiceOrdine;
use App\Services\Rimborso\RimborsoElegibilidadeService;
use App\Services\Rimborso\RimborsoSolicitacaoService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RimborsoEtichettaController extends Controller
{
    public function __construct(
        private readonly RimborsoElegibilidadeService $elegibilidade,
        private readonly RimborsoSolicitacaoService $solicitacao,
    ) {}

    public function index(Request $request): View
    {
        return view('rimborso-etichette.index', [
            'modo' => old('modo', $request->input('modo', 'ordine')),
            'valor' => old('valor', $request->input('valor', '')),
            'ordine' => null,
            'spedizioni' => collect(),
            'erro_busca' => null,
            'info_busca' => null,
            'dias_elegibilidade' => (int) config('rimborso.dias_elegibilidade_etiqueta', 30),
            'giorni_ldv_si' => parametri_globali::giorniLdvSi(),
            'giorni_ldv_no' => parametri_globali::giorniLdvNo(),
        ]);
    }

    public function buscar(Request $request): View
    {
        $data = $request->validate([
            'modo' => ['required', 'in:ordine,etichetta'],
            'valor' => ['required', 'string', 'max:128'],
        ]);

        $uid = (int) $request->user()->id;
        $modo = $data['modo'];
        $valor = trim($data['valor']);
        $ordine = null;
        $spedizioni = collect();
        $erro = null;
        $info = null;

        if ($modo === 'ordine') {
            $ordine = $this->resolveOrdinePagatoCliente($valor, $uid);
            if ($ordine === null) {
                $erro = 'Ordine non trovato o non pagato.';
            } else {
                $spedizioni = spedizione::query()
                    ->where('user_id', $uid)
                    ->where('ordine_id', $ordine->id)
                    ->with(['rimborso', 'spedizioneStato', 'corriereRecord'])
                    ->orderBy('id')
                    ->get();

                if ($spedizioni->isEmpty()) {
                    $erro = 'Questo ordine non ha spedizioni.';
                } elseif ($spedizioni->every(fn (spedizione $s) => ! $this->elegibilidade->isElegivel($s, false))) {
                    $giorni = (int) config('rimborso.dias_elegibilidade_etiqueta', 30);
                    if ($spedizioni->every(fn (spedizione $s) => $s->rimborso !== null)) {
                        $info = 'Per tutte le spedizioni risulta già una richiesta di rimborso.';
                    } else {
                        $info = 'Nessuna spedizione è al momento eleggibile (verifica il periodo di '.$giorni.' giorni o lo stato).';
                    }
                }
            }
        } else {
            $codice = strtoupper(trim(preg_replace('/\s+/', '', $valor)));
            $sped = spedizione::query()
                ->where('user_id', $uid)
                ->where('codice_interno', $codice)
                ->with(['ordine', 'rimborso', 'spedizioneStato', 'corriereRecord'])
                ->first();

            if (! $sped || ! $sped->ordine?->haStato(ordine::STATO_PAGATO)) {
                $erro = 'Spedizione non trovata o ordine non pagato.';
            } else {
                $ordine = $sped->ordine;
                $spedizioni = collect([$sped]);
                if (! $this->elegibilidade->isElegivel($sped, false)) {
                    $info = $sped->rimborso
                        ? 'Rimborso già richiesto per questa spedizione.'
                        : 'Questa spedizione non è eleggibile per una nuova richiesta di rimborso.';
                }
            }
        }

        return view('rimborso-etichette.index', [
            'modo' => $modo,
            'valor' => $valor,
            'ordine' => $ordine,
            'spedizioni' => $spedizioni,
            'erro_busca' => $erro,
            'info_busca' => $info,
            'dias_elegibilidade' => (int) config('rimborso.dias_elegibilidade_etiqueta', 30),
            'giorni_ldv_si' => parametri_globali::giorniLdvSi(),
            'giorni_ldv_no' => parametri_globali::giorniLdvNo(),
        ]);
    }

    public function solicitar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'spedizione_id' => ['required', 'integer'],
        ]);

        $spedizione = spedizione::query()
            ->where('user_id', $request->user()->id)
            ->with(['ordine', 'rimborso', 'corriereRecord'])
            ->findOrFail($data['spedizione_id']);

        try {
            $out = $this->solicitacao->solicitar($spedizione, $request->user());
        } catch (DomainException $e) {
            return redirect()
                ->route('rimborso-etichette.index')
                ->with('rimborso_erro', $e->getMessage());
        }

        $redirect = redirect()->route('rimborso-etichette.index')
            ->with('rimborso_ok', true)
            ->with('rimborso_credito_imediato', $out['credito_immediato']);

        if (! $out['credito_immediato']) {
            $redirect->with('rimborso_giorni', parametri_globali::giorniLdvSi());
        }

        return $redirect;
    }

    private function resolveOrdinePagatoCliente(string $valor, int $userId): ?ordine
    {
        $id = CodiceOrdine::idDaRiferimento($valor);
        if ($id === null) {
            return null;
        }

        return ordine::query()
            ->where('user_id', $userId)
            ->whereKey($id)
            ->conStatoCodice(ordine::STATO_PAGATO)
            ->first();
    }
}
