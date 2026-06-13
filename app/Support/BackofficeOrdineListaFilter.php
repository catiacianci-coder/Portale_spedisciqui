<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class BackofficeOrdineListaFilter
{
    public function __construct(
        public int $perPage = 10,
        public string $numero = '',
        public string $usuario = '',
        public int $userId = 0,
        public string $periodo = '',
        public string $dataDe = '',
        public string $dataA = '',
        public string $pagamento = 'tutti',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $periodo = (string) $request->input('periodo', '');
        if (! in_array($periodo, ['', '7', '15', '30', 'custom'], true)) {
            $periodo = '';
        }

        $pagamentoRaw = (string) $request->input('pagamento', 'tutti');
        $pagamento = match ($pagamentoRaw) {
            'pagato', 'pagati' => 'pagato',
            'non_pagato', 'non_pagati' => 'non_pagato',
            'annullato', 'annullati' => 'annullato',
            default => 'tutti',
        };

        return new self(
            perPage: FiltriTabella::perPage($request),
            numero: trim((string) $request->input('numero', '')),
            usuario: trim((string) $request->input('usuario', '')),
            userId: max(0, (int) $request->input('user_id', 0)),
            periodo: $periodo,
            dataDe: trim((string) $request->input('data_de', '')),
            dataA: trim((string) $request->input('data_a', '')),
            pagamento: $pagamento,
        );
    }

    public function pagamentoUi(): string
    {
        return $this->pagamento;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'numero' => $this->numero,
            'usuario' => $this->usuario,
            'user_id' => $this->userId > 0 ? (string) $this->userId : '',
            'periodo' => $this->periodo,
            'data_de' => $this->dataDe,
            'data_a' => $this->dataA,
            'pagamento' => $this->pagamentoUi(),
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->numero !== ''
            || $this->usuario !== ''
            || $this->userId > 0
            || $this->periodo !== ''
            || $this->pagamento !== 'tutti';
    }

    public function customPeriodoSemDatas(): bool
    {
        return $this->periodo === 'custom'
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataDe)
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataA);
    }

    public function applyStatoPagamento(Builder $query): void
    {
        if ($this->pagamento === 'tutti') {
            return;
        }

        $codice = match ($this->pagamento) {
            'pagato' => \App\Models\ordine::STATO_PAGATO,
            'non_pagato' => \App\Models\ordine::STATO_NON_PAGATO,
            'annullato' => \App\Models\ordine::STATO_ANNULLATO,
            default => null,
        };

        if ($codice !== null) {
            $query->conStatoCodice($codice);
        }
    }

    public function applyToQuery(Builder $query): void
    {
        $this->applyStatoPagamento($query);

        if ($this->userId > 0) {
            $query->where('user_id', $this->userId);
        } elseif ($this->usuario !== '') {
            $like = '%'.addcslashes(mb_strtolower($this->usuario), '%_\\').'%';
            $query->whereHas('user', function (Builder $uq) use ($like): void {
                $uq->whereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }

        if ($this->numero !== '') {
            FiltriTabella::filtraNumeroOrdine($query, $this->numero);
        }

        $range = $this->dateRange();
        if ($range !== null) {
            [$de, $ate] = $range;
            $query->whereBetween('created_at', [$de, $ate]);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function dateRange(): ?array
    {
        return match ($this->periodo) {
            '7' => [now()->copy()->subDays(7)->startOfDay(), now()->endOfDay()],
            '15' => [now()->copy()->subDays(15)->startOfDay(), now()->endOfDay()],
            '30' => [now()->copy()->subDays(30)->startOfDay(), now()->endOfDay()],
            'custom' => $this->customDateRange(),
            default => null,
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function customDateRange(): ?array
    {
        $start = null;
        $end = null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataDe)) {
            $start = Carbon::parse($this->dataDe)->startOfDay();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataA)) {
            $end = Carbon::parse($this->dataA)->endOfDay();
        }

        if ($start === null && $end === null) {
            return null;
        }

        return [$start ?? Carbon::minValue(), $end ?? now()->endOfDay()];
    }
}
