<?php

namespace App\Http\Controllers;

use App\Models\corriere;
use App\Models\servizi_aggiuntivi;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\Etichetta\EtichettaRetryService;
use App\Services\Etichetta\SpedizioneEtichettaCorrecaoService;
use App\Support\EtichetteListing;
use App\Support\FiltriTabella;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EtichetteClienteController extends Controller
{
    public function index(Request $request)
    {
        $uid = (int) $request->user()->id;
        $perPage = FiltriTabella::perPage($request);
        $periodo = FiltriTabella::periodoDaRequest($request, 'tutti', ['tutti'], true);
        $ordinamento = FiltriTabella::ordinamentoEtichetteDaRequest($request);

        $codiceEtichetta = trim((string) $request->input('codice_etichetta', ''));
        $numeroOrdine = trim((string) $request->input('numero_ordine', ''));
        $destinatario = trim((string) $request->input('destinatario', ''));
        $statiEtichette = stato_spedizione::query()
            ->where('id', '!=', stato_spedizione::NON_PAGATA)
            ->orderBy('id')
            ->get(['id', 'denominazione_stato']);
        $statiAmmessi = $statiEtichette->pluck('id')->map(fn ($id) => (int) $id)->all();
        $filtroStatusRaw = trim((string) $request->input('status', ''));
        $filtroStatus = ($filtroStatusRaw !== '' && ctype_digit($filtroStatusRaw))
            ? (int) $filtroStatusRaw
            : null;

        $corriereRaw = trim((string) $request->input('corriere_id', ''));
        $filtroCorriere = ($corriereRaw !== '' && ctype_digit($corriereRaw))
            ? (int) $corriereRaw
            : null;

        $servizioRaw = trim((string) $request->input('servizio_aggiuntivo_id', ''));
        $filtroServizioAggiuntivo = ($servizioRaw !== '' && ctype_digit($servizioRaw))
            ? (int) $servizioRaw
            : null;

        if (strlen($destinatario) > 160) {
            $destinatario = substr($destinatario, 0, 160);
        }

        $with = [
            'ordine.user',
            'ordine.metodoPagamentoOrdine',
            'corriereRecord',
            'tipoSpedizione',
            'spedizioneStato',
            'rimborso',
            'serviziAggiuntiviRighe.corriereServizioAggiuntivo:id,testo_servizio',
        ];

        $query = spedizione::query()
            ->where('user_id', $uid)
            ->where('spedizione_stato_id', '!=', stato_spedizione::NON_PAGATA)
            ->whereHas('ordine', fn ($q) => $q->conPagamentoRegistrato())
            ->with($with);

        FiltriTabella::applicaOrdinamentoEtichetteCliente(
            $query,
            $ordinamento['column'],
            $ordinamento['dir'],
        );

        if ($periodo['errors'] === []) {
            FiltriTabella::applicaFiltroDataPagamentoOrdine($query, $periodo['from'], $periodo['to']);
        }

        FiltriTabella::filtraEtichetteCliente($query, $codiceEtichetta, $numeroOrdine, $destinatario);
        FiltriTabella::filtraCorriereSpedizione($query, $filtroCorriere);
        FiltriTabella::filtraStatoSpedizione($query, $filtroStatus, $statiAmmessi);
        FiltriTabella::filtraServizioAggiuntivoEtichetta($query, $filtroServizioAggiuntivo);

        $spedizioni = $query->paginate($perPage)->withQueryString();

        $suggerimentiDestinatario = spedizione::query()
            ->where('user_id', $uid)
            ->orderByDesc('id')
            ->limit(200)
            ->get(['nome_d', 'sobrenome_d', 'ragione_sociale_d'])
            ->map(function ($s): string {
                $nome = trim((string) ($s->ragione_sociale_d ?: trim((string) (($s->nome_d ?? '').' '.($s->sobrenome_d ?? '')))));

                return $nome;
            })
            ->filter(fn (string $n): bool => $n !== '')
            ->unique()
            ->sort()
            ->values()
            ->take(80);

        $corrieriFiltro = corriere::query()
            ->whereIn('id', spedizione::query()
                ->where('user_id', $uid)
                ->where('spedizione_stato_id', '!=', stato_spedizione::NON_PAGATA)
                ->whereNotNull('id_codice_servizio')
                ->distinct()
                ->select('id_codice_servizio'))
            ->orderBy('nome_visualizzato')
            ->orderBy('nome_corriere')
            ->get(['id', 'nome_visualizzato', 'nome_corriere']);

        $serviziAggiuntiviFiltro = servizi_aggiuntivi::query()
            ->whereNotNull('abbrev')
            ->where('abbrev', '!=', '')
            ->orderBy('denominazione_servizio')
            ->get(['id', 'denominazione_servizio', 'abbrev']);

        return view('etichette.index', [
            'spedizioni' => $spedizioni,
            'perPage' => $perPage,
            'filtroPeriod' => $periodo['period'],
            'filtroDataDa' => $periodo['data_da'],
            'filtroDataA' => $periodo['data_a'],
            'filtroCodiceEtichetta' => $codiceEtichetta,
            'filtroNumeroOrdine' => $numeroOrdine,
            'filtroDestinatario' => $destinatario,
            'filtroStatus' => $filtroStatus !== null ? (string) $filtroStatus : '',
            'filtroCorriere' => $filtroCorriere !== null ? (string) $filtroCorriere : '',
            'filtroServizioAggiuntivo' => $filtroServizioAggiuntivo !== null ? (string) $filtroServizioAggiuntivo : '',
            'statiEtichette' => $statiEtichette,
            'corrieriFiltro' => $corrieriFiltro,
            'serviziAggiuntiviFiltro' => $serviziAggiuntiviFiltro,
            'filtroErrors' => $periodo['errors'],
            'sortColumn' => $ordinamento['column'],
            'sortDir' => $ordinamento['dir'],
            'queryParams' => array_merge(
                FiltriTabella::parametriQuery($request),
                [
                    'sort' => $ordinamento['column'],
                    'dir' => $ordinamento['dir'],
                ],
            ),
            'suggerimentiDestinatario' => $suggerimentiDestinatario,
        ]);
    }

    public function dettaglio(Request $request, spedizione $spedizione): View
    {
        $this->authorize('view', $spedizione);

        return view('etichette.partials.dettaglio-remessa-modal', [
            's' => $spedizione,
            'd' => EtichetteListing::dettaglioPayload($spedizione),
        ]);
    }

    public function correcao(Request $request, spedizione $spedizione, SpedizioneEtichettaCorrecaoService $svc): JsonResponse
    {
        if (! config('etichetta.correcao_cliente_abilitata', false)) {
            abort(403);
        }

        $this->authorize('view', $spedizione);

        try {
            return response()->json([
                'ok' => true,
                'data' => $svc->datiPerModal($spedizione, (int) $request->user()->id),
            ]);
        } catch (DomainException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function correcaoSalvar(Request $request, spedizione $spedizione, SpedizioneEtichettaCorrecaoService $svc): JsonResponse
    {
        if (! config('etichetta.correcao_cliente_abilitata', false)) {
            abort(403);
        }

        $this->authorize('view', $spedizione);

        $validated = $request->validate([
            'nome_d' => ['nullable', 'string', 'max:120'],
            'sobrenome_d' => ['nullable', 'string', 'max:120'],
            'indirizzo_d' => ['required', 'string', 'max:255'],
            'numero_d' => ['required', 'string', 'max:32'],
            'frazione_d' => ['nullable', 'string', 'max:120'],
            'tel_d' => ['required', 'string', 'max:64'],
            'note_d' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $nova = $svc->salvaEGeneraNuova($spedizione, (int) $request->user()->id, $validated);

            return response()->json([
                'ok' => true,
                'message' => 'Dati aggiornati. Nuova etichetta generata con codice '.$nova->codice_interno.'.',
                'redirect' => route('etichette.index'),
            ]);
        } catch (DomainException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function retry(Request $request, spedizione $spedizione, EtichettaRetryService $retry): RedirectResponse
    {
        $this->authorize('view', $spedizione);

        try {
            $outcome = $retry->retry($spedizione, (int) $request->user()->id);
            $tipo = $outcome['ok'] ? 'ok' : 'error';

            return redirect()
                ->route('etichette.index')
                ->with($tipo, $outcome['message']);
        } catch (DomainException $e) {
            return redirect()
                ->route('etichette.index')
                ->withErrors(['etichette' => $e->getMessage()]);
        }
    }
}
