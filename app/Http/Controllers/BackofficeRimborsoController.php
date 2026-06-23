<?php

namespace App\Http\Controllers;

use App\Models\ordine;
use App\Models\rimborso;
use App\Models\User;
use App\Support\CodiceOrdine;
use App\Services\Rimborso\RimborsoEsecuzionePagamentoService;
use App\Support\FiltriTabella;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BackofficeRimborsoController extends Controller
{
    public function __construct(
        private readonly RimborsoEsecuzionePagamentoService $pagamento,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $userId = max(0, (int) $request->input('user_id', 0));
        if ($userId > 0) {
            return redirect()->route('backoffice.rimborsi.pendentes', ['user_id' => $userId]);
        }

        if ($request->has('paga_oggi')) {
            return redirect()->route('backoffice.rimborsi.pendentes', [
                'paga_oggi' => $request->boolean('paga_oggi') ? 1 : 0,
            ]);
        }

        return view('backoffice.rimborsi.index', [
            'sezione' => 'menu',
            'countPendentes' => rimborso::query()->whereNull('data_reale')->count(),
            'countRimborsati' => rimborso::query()->whereNotNull('data_reale')->count(),
        ]);
    }

    public function pendentes(Request $request): View
    {
        $pagaOggi = $request->boolean('paga_oggi');
        $perPage = FiltriTabella::perPage($request);
        $userId = max(0, (int) $request->input('user_id', 0));
        $selectedUser = $userId > 0 ? User::query()->find($userId) : null;

        $base = rimborso::query()
            ->whereNull('data_reale')
            ->with([
                'spedizione.user',
                'spedizione.ordine',
                'spedizione.corriereRecord',
                'spedizione.spedizioneStato',
                'metodoPagamentoRimborso',
            ])
            ->when($userId > 0, fn ($q) => $q->whereHas('spedizione', fn ($s) => $s->where('user_id', $userId)))
            ->orderBy('data_prevista')
            ->orderBy('id');

        $pendentes = (clone $base)
            ->when($pagaOggi, fn ($q) => $q->whereDate('data_prevista', '<=', now()->toDateString()))
            ->paginate($perPage)
            ->withQueryString();

        return view('backoffice.rimborsi.index', [
            'sezione' => 'pendentes',
            'pagaOggi' => $pagaOggi,
            'pendentes' => $pendentes,
            'perPage' => $perPage,
            'totalPendentes' => (clone $base)->count(),
            'filtroUserId' => $userId,
            'selectedUser' => $selectedUser,
        ]);
    }

    public function rimborsati(Request $request): View
    {
        $perPage = FiltriTabella::perPage($request);
        $userId = max(0, (int) $request->input('user_id', 0));
        $selectedUser = $userId > 0 ? User::query()->find($userId) : null;

        $rimborsati = rimborso::query()
            ->whereNotNull('data_reale')
            ->when($userId > 0, fn ($q) => $q->whereHas('spedizione', fn ($s) => $s->where('user_id', $userId)))
            ->with(['spedizione.user', 'spedizione.ordine', 'metodoPagamentoRimborso'])
            ->orderByDesc('data_reale')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('backoffice.rimborsi.index', [
            'sezione' => 'rimborsati',
            'rimborsati' => $rimborsati,
            'filtroUserId' => $userId,
            'selectedUser' => $selectedUser,
        ]);
    }

    public function perOrdine(Request $request): View
    {
        $raw = trim((string) $request->query('ordine', ''));
        $situazione = (string) $request->query('situazione', 'tutti');
        $perPage = FiltriTabella::perPage($request);
        $erro = null;
        $ordine = null;
        $lista = null;

        if ($raw !== '') {
            $id = CodiceOrdine::idDaRiferimento($raw);

            if ($id === null) {
                $erro = 'ID ordine non valido (solo cifre).';
            } else {
                $ordine = ordine::query()->find($id);
                if (! $ordine) {
                    $erro = 'Ordine non trovato.';
                } else {
                    $q = rimborso::query()
                        ->where('ordine_id', $ordine->id)
                        ->with(['spedizione.user', 'metodoPagamentoRimborso'])
                        ->orderBy('data_richiesta');

                    if ($situazione === 'attesa') {
                        $q->whereNull('data_reale');
                    } elseif ($situazione === 'rimborsato') {
                        $q->whereNotNull('data_reale');
                    }

                    $lista = $q->paginate($perPage)->withQueryString();
                }
            }
        }

        return view('backoffice.rimborsi.index', [
            'sezione' => 'per_ordine',
            'erro' => $erro,
            'ordine' => $ordine,
            'lista' => $lista,
            'filtroOrdine' => $raw,
            'situazione' => $situazione,
        ]);
    }

    public function paga(Request $request, rimborso $rimborso): RedirectResponse
    {
        $rimborso->loadMissing(['spedizione.user', 'spedizione.ordine']);

        try {
            DB::transaction(function () use ($rimborso): void {
                $locked = rimborso::query()->whereKey($rimborso->id)->lockForUpdate()->first();
                if (! $locked || $locked->data_reale !== null) {
                    throw new DomainException('Rimborso già eseguito o non trovato.');
                }

                $spedizione = $locked->spedizione;
                $user = $spedizione?->user;
                if (! $spedizione || ! $user) {
                    throw new DomainException('Dati spedizione o utente mancanti.');
                }

                $this->pagamento->esegui($locked, $spedizione, $user);
            });
        } catch (DomainException $e) {
            return redirect()
                ->back()
                ->with('rimborso_bo_erro', $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('rimborso_bo_ok', 'Rimborso accreditato sul wallet del cliente ('.\App\Support\ImportoEuro::format((float) $rimborso->valore).').');
    }
}
