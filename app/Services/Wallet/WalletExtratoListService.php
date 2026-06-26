<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\wallet_movimento;
use App\Support\WalletMovimentoRiferimentoPresenter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class WalletExtratoListService
{
    public function paginateForUser(User $user, WalletExtratoFilters $filters, Request $request): LengthAwarePaginator
    {
        $linhas = $this->buildLinhas(collect([$user]), $filters);

        return $this->paginateLinhas($linhas, $filters, $request);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function paginateForUsers(Collection $users, WalletExtratoFilters $filters, Request $request): LengthAwarePaginator
    {
        $linhas = $this->buildLinhas($users, $filters);

        return $this->paginateLinhas($linhas, $filters, $request);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, WalletExtratoLinha>
     */
    private function buildLinhas(Collection $users, WalletExtratoFilters $filters): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $usersById = $users->keyBy('id');

        $movQ = wallet_movimento::query()
            ->with([
                'descrizione',
                'ordine',
                'ricaricaRichiesta.metodoPagamentoWalletRicarica',
                'user.anagrafica',
            ])
            ->whereIn('user_id', $userIds);

        $this->applyDateScope($movQ, $filters);

        if ($filters->walletDescrizioneId > 0) {
            $movQ->where('wallet_descrizione_id', $filters->walletDescrizioneId);
        }

        return $movQ
            ->orderByDesc('data_movimento')
            ->orderByDesc('id')
            ->get()
            ->map(fn (wallet_movimento $movimento) => $this->linhaFromMovimento(
                $movimento,
                $usersById->get($movimento->user_id),
            ))
            ->values();
    }

    private function linhaFromMovimento(wallet_movimento $m, ?User $usuario): WalletExtratoLinha
    {
        $nota = trim((string) ($m->nota_interna ?? ''));

        return new WalletExtratoLinha(
            movimentoId: (int) $m->id,
            sortAt: $m->data_movimento ?? $m->created_at ?? now(),
            dettaglio: (string) ($m->descrizione?->descrizione ?? '—'),
            ordineLdv: WalletMovimentoRiferimentoPresenter::ordineLdv($m),
            valor: (float) $m->importo,
            isCredito: $m->tipo === 'credito',
            usuario: $usuario ?? $m->user,
            notaInterna: $nota !== '' ? $nota : null,
        );
    }

    /**
     * @param  Collection<int, WalletExtratoLinha>  $linhas
     */
    private function paginateLinhas(Collection $linhas, WalletExtratoFilters $filters, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $total = $linhas->count();
        $items = $linhas->forPage($page, $filters->perPage)->values();

        return new Paginator(
            $items,
            $total,
            $filters->perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    private function applyDateScope($query, WalletExtratoFilters $filters): void
    {
        $range = $this->dateRange($filters);
        if ($range === null) {
            return;
        }
        [$de, $ate] = $range;
        $query->where('data_movimento', '>=', $de);
        $query->where('data_movimento', '<=', $ate);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function dateRange(WalletExtratoFilters $filters): ?array
    {
        return match ($filters->periodo) {
            'oggi' => [now()->startOfDay(), now()->endOfDay()],
            '7' => [now()->copy()->subDays(7)->startOfDay(), now()->endOfDay()],
            '30' => [now()->copy()->subDays(30)->startOfDay(), now()->endOfDay()],
            'custom' => $this->customDateRange($filters->dataDe, $filters->dataAte),
            default => null,
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function customDateRange(string $de, string $ate): ?array
    {
        $start = null;
        $end = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $de)) {
            $start = Carbon::parse($de)->startOfDay();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) {
            $end = Carbon::parse($ate)->endOfDay();
        }
        if ($start === null && $end === null) {
            return null;
        }
        $start ??= Carbon::create(2000, 1, 1)->startOfDay();
        $end ??= now()->endOfDay();

        return [$start, $end];
    }
}
