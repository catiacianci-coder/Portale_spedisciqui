<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Models\Ticket;
use App\Models\TicketStato;
use App\Models\TicketTipoProblema;
use App\Support\CodiceOrdine;
use App\Support\FiltriTabella;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssistenzaSolicitacaoController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = FiltriTabella::perPage($request);

        $tickets = Ticket::query()
            ->where('user_id', auth()->id())
            ->with('stato')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $tiposProblema = TicketTipoProblema::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tipoEntregaId = TicketTipoProblema::query()
            ->where('codigo', TicketTipoProblema::CODIGO_ENTREGA)
            ->value('id');
        $tipoEtiquetaNaoGeradaId = TicketTipoProblema::query()
            ->where('codigo', TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA)
            ->value('id');

        return view('assistenza.index', [
            'tickets' => $tickets,
            'perPage' => $perPage,
            'tiposProblema' => $tiposProblema,
            'tipoEntregaId' => $tipoEntregaId,
            'tipoEtiquetaNaoGeradaId' => $tipoEtiquetaNaoGeradaId,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('assistenza.index', [], 302);
    }

    public function spedizioniPorPedido(Request $request): JsonResponse
    {
        $modo = (string) $request->query('modo', '');
        if (! in_array($modo, ['entrega', 'etiqueta_nao_gerada'], true)) {
            return response()->json(['error' => 'Modalità non valida.'], 422);
        }

        $codice = trim((string) $request->query('ordine_codice', ''));
        if ($codice === '') {
            return response()->json(['error' => 'Indica il numero ordine.'], 422);
        }

        $ordine = $this->resolveOrdinePagoDoCliente($codice);
        if ($ordine === null) {
            return response()->json(['error' => 'Ordine non trovato o non pagato.'], 404);
        }

        $q = spedizione::query()
            ->where('user_id', auth()->id())
            ->pagasNoPedido($ordine->id);

        if ($modo === 'etiqueta_nao_gerada') {
            $q->semRastreio();
        }

        $spedizioni = $q->orderBy('id')->get()->map(fn (spedizione $s) => $this->serializeSpedizione($s));

        return response()->json([
            'ordine' => ['id' => $ordine->id],
            'spedizioni' => $spedizioni,
        ]);
    }

    public function spedizionePorCodigo(Request $request): JsonResponse
    {
        $codigo = strtoupper(trim(preg_replace('/\s+/', '', (string) $request->query('codigo', ''))));
        if ($codigo === '') {
            return response()->json(['error' => 'Indica il codice spedizione.'], 422);
        }

        $spedizione = spedizione::query()
            ->where('user_id', auth()->id())
            ->where('spedizione_stato_id', stato_spedizione::PAGATA)
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
            ->where(function (Builder $w) use ($codigo): void {
                $w->where('codice_interno', $codigo)
                    ->orWhere('tracking', $codigo);
            })
            ->semRastreio()
            ->with('ordine')
            ->first();

        if ($spedizione === null) {
            return response()->json(['error' => 'Spedizione non trovata, non pagata o con tracking già presente.'], 404);
        }

        return response()->json([
            'spedizione' => $this->serializeSpedizione($spedizione),
            'ordine' => [
                'id' => $spedizione->ordine_id,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $base = $request->validate([
            'ticket_tipo_problema_id' => ['required', 'integer', Rule::exists('ticket_tipo_problemas', 'id')],
            'oggetto' => 'required|string|max:500',
            'body' => 'required|string|max:65000',
        ]);

        $tipo = TicketTipoProblema::query()->findOrFail((int) $base['ticket_tipo_problema_id']);

        $novoId = TicketStato::idForCodigo(TicketStato::CODIGO_NOVO);
        if ($novoId === null) {
            abort(500, 'Stato «Nuovo» non configurato.');
        }

        $payload = [
            'user_id' => $request->user()->id,
            'ticket_stato_id' => $novoId,
            'ticket_tipo_problema_id' => $tipo->id,
            'oggetto' => $base['oggetto'],
            'ordine_id' => null,
            'spedizione_id' => null,
            'campo_1' => null,
            'campo_2' => null,
            'campo_3' => null,
            'campo_4' => null,
        ];

        if ($tipo->codigo === TicketTipoProblema::CODIGO_ENTREGA) {
            $extra = $request->validate([
                'ordine_codice_entrega' => 'required|string|max:64',
                'spedizione_id_entrega' => ['required', 'integer'],
            ]);
            $ordine = $this->resolveOrdinePagoDoCliente($extra['ordine_codice_entrega']);
            if ($ordine === null) {
                throw ValidationException::withMessages(['ordine_codice_entrega' => 'Ordine non trovato o non pagato.']);
            }
            $spedizione = spedizione::query()
                ->where('user_id', auth()->id())
                ->where('id', (int) $extra['spedizione_id_entrega'])
                ->pagasNoPedido($ordine->id)
                ->first();
            if ($spedizione === null) {
                throw ValidationException::withMessages(['spedizione_id_entrega' => 'Etichetta non valida per questo ordine.']);
            }
            $payload['ordine_id'] = $ordine->id;
            $payload['spedizione_id'] = $spedizione->id;
            $payload['campo_1'] = (string) $ordine->id;
            $payload['campo_2'] = (string) $spedizione->id;
            $payload['campo_3'] = (string) $spedizione->corriere;
        } elseif ($tipo->codigo === TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA) {
            $extra = $request->validate([
                'etiqueta_ng_modo' => ['required', Rule::in(['pedido', 'codigo'])],
                'ordine_codice_ng' => 'nullable|string|max:64',
                'codigo_remessa_ng' => 'nullable|string|max:64',
                'spedizione_ids_ng' => 'nullable|array',
                'spedizione_ids_ng.*' => 'integer',
            ]);
            if ($extra['etiqueta_ng_modo'] === 'pedido') {
                $ordineCod = trim((string) ($extra['ordine_codice_ng'] ?? ''));
                if ($ordineCod === '') {
                    throw ValidationException::withMessages(['ordine_codice_ng' => 'Indica l\'ordine.']);
                }
                $ordine = $this->resolveOrdinePagoDoCliente($ordineCod);
                if ($ordine === null) {
                    throw ValidationException::withMessages(['ordine_codice_ng' => 'Ordine non trovato o non pagato.']);
                }
                $ids = array_values(array_unique(array_map('intval', $extra['spedizione_ids_ng'] ?? [])));
                if ($ids === []) {
                    throw ValidationException::withMessages(['spedizione_ids_ng' => 'Seleziona almeno un\'etichetta.']);
                }
                $count = spedizione::query()
                    ->where('user_id', auth()->id())
                    ->whereIn('id', $ids)
                    ->pagasNoPedido($ordine->id)
                    ->semRastreio()
                    ->count();
                if ($count !== count($ids)) {
                    throw ValidationException::withMessages(['spedizione_ids_ng' => 'Una o più etichette non sono valide (devono essere pagate e senza tracking).']);
                }
                $payload['ordine_id'] = $ordine->id;
                $payload['campo_1'] = implode(',', $ids);
            } else {
                $cod = strtoupper(trim(preg_replace('/\s+/', '', (string) ($extra['codigo_remessa_ng'] ?? ''))));
                if ($cod === '') {
                    throw ValidationException::withMessages(['codigo_remessa_ng' => 'Indica il codice spedizione.']);
                }
                $spedizione = spedizione::query()
                    ->where('user_id', auth()->id())
                    ->where('spedizione_stato_id', stato_spedizione::PAGATA)
                    ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO))
                    ->where(function (Builder $w) use ($cod): void {
                        $w->where('codice_interno', $cod)->orWhere('tracking', $cod);
                    })
                    ->semRastreio()
                    ->first();
                if ($spedizione === null) {
                    throw ValidationException::withMessages(['codigo_remessa_ng' => 'Spedizione non trovata o con tracking già presente.']);
                }
                $payload['ordine_id'] = $spedizione->ordine_id;
                $payload['spedizione_id'] = $spedizione->id;
                $payload['campo_1'] = (string) $spedizione->id;
            }
        }

        $ticket = DB::transaction(function () use ($payload, $request) {
            $t = Ticket::create($payload);
            $t->messaggi()->create([
                'user_id' => $request->user()->id,
                'is_staff' => false,
                'body' => $request->input('body'),
            ]);

            return $t;
        });

        return redirect()
            ->route('assistenza.ticket.show', $ticket)
            ->with('status', 'Richiesta registrata. Il nostro team la prenderà in carico.');
    }

    private function resolveOrdinePagoDoCliente(string $codice): ?ordine
    {
        $codice = trim($codice);
        if ($codice === '') {
            return null;
        }

        $id = CodiceOrdine::idDaRiferimento($codice);
        if ($id === null) {
            return null;
        }

        return ordine::query()
            ->where('user_id', auth()->id())
            ->conStatoCodice(ordine::STATO_PAGATO)
            ->whereKey($id)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSpedizione(spedizione $s): array
    {
        $rastreio = $s->codigoRastreio() ?? '';

        return [
            'id' => $s->id,
            'codice_interno' => $s->codice_interno,
            'carrier' => $s->corriere,
            'service_description' => $s->service_description,
            'codigo_rastreio' => $rastreio !== '' ? $rastreio : null,
            'tem_rastreio' => $rastreio !== '',
            'destinatario' => $s->ragione_sociale_d
                ? trim((string) $s->ragione_sociale_d)
                : trim(implode(' ', array_filter([(string) $s->nome_d, (string) $s->sobrenome_d]))),
        ];
    }
}
