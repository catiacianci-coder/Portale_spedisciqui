<?php

namespace App\Http\Controllers;

use App\Models\rimborso;
use App\Services\Cliente\ClienteNotificazioniRiepilogoService;
use App\Support\FiltriTabella;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MieiRimborsoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $uid = (int) $request->user()->id;
        $situazione = (string) $request->input('situazione', 'tutti');
        if (! in_array($situazione, ['tutti', 'attesa', 'rimborsato'], true)) {
            $situazione = 'tutti';
        }

        $perPage = FiltriTabella::perPage($request);

        $destacarId = (int) $request->input('destacar', 0);
        if ($destacarId > 0 && $request->has('destacar')) {
            $owned = rimborso::query()
                ->whereKey($destacarId)
                ->whereHas('spedizione', fn ($q) => $q->where('user_id', $uid))
                ->whereNotNull('data_reale')
                ->first();

            if ($owned === null) {
                return redirect()->route('miei-rimborsi.index', $request->except('destacar'));
            }

            $owned->forceFill(['credito_avviso_letto_in' => now()])->save();
            ClienteNotificazioniRiepilogoService::pulisciCacheUtente($uid);

            $qForPage = $this->buildListaQuery($request, 'rimborsato', $uid);
            $page = $this->resolvePageForRimborsoId($qForPage, $destacarId, $perPage);
            $params = array_merge($request->except('destacar'), [
                'situazione' => 'rimborsato',
                'page' => $page,
                'per_page' => $perPage,
            ]);

            return redirect()
                ->route('miei-rimborsi.index', $params)
                ->withFragment('rimborso-'.$destacarId);
        }

        $query = $this->buildListaQuery($request, $situazione, $uid);

        return view('miei-rimborsi.index', [
            'rimborsi' => $query->paginate($perPage)->withQueryString(),
            'situazione' => $situazione,
            'codice' => trim((string) $request->input('codice', '')),
        ]);
    }

    private function buildListaQuery(Request $request, string $situazione, int $userId): Builder
    {
        $query = rimborso::query()
            ->whereHas('spedizione', fn ($q) => $q->where('user_id', $userId))
            ->with(['spedizione.ordine', 'metodoPagamentoRimborso'])
            ->orderByDesc('data_richiesta')
            ->orderByDesc('id');

        if ($situazione === 'attesa') {
            $query->whereNull('data_reale');
        } elseif ($situazione === 'rimborsato') {
            $query->whereNotNull('data_reale');
        }

        $codice = trim((string) $request->input('codice', ''));
        if ($codice !== '') {
            $query->where('codice_interno', 'like', '%'.$codice.'%');
        }

        return $query;
    }

    private function resolvePageForRimborsoId(Builder $query, int $rimborsoId, int $perPage): int
    {
        $orderedIds = (clone $query)
            ->orderByDesc('data_richiesta')
            ->orderByDesc('id')
            ->pluck('id');

        $idx = $orderedIds->search(fn ($id) => (int) $id === $rimborsoId);
        if ($idx === false) {
            return 1;
        }

        return (int) floor((int) $idx / $perPage) + 1;
    }
}
