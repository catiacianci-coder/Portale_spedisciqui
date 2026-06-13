<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class WalletRicaricaListaFilter
{
    public function __construct(
        public int $perPage = 10,
        public string $numeroOrdine = '',
        public string $periodo = '',
        public string $dataDe = '',
        public string $dataA = '',
        public string $importo = '',
        public string $stato = 'tutte',
        public string $cliente = '',
        public int $userId = 0,
        public int $metodoPagamentoId = 0,
        public bool $backoffice = false,
    ) {}

    public static function fromRequest(Request $request, bool $backoffice = false): self
    {
        $periodo = (string) $request->input('periodo', '');
        if (! in_array($periodo, ['', '7', '15', '30', 'custom'], true)) {
            $periodo = '';
        }

        $stato = (string) $request->input('stato', 'tutte');
        if (! in_array($stato, ['tutte', 'pagato', 'non_pagato', 'annullata'], true)) {
            $stato = 'tutte';
        }

        return new self(
            perPage: FiltriTabella::perPage($request),
            numeroOrdine: trim((string) $request->input('numero_ordine', '')),
            periodo: $periodo,
            dataDe: trim((string) $request->input('data_de', '')),
            dataA: trim((string) $request->input('data_a', '')),
            importo: trim((string) $request->input('importo', '')),
            stato: $stato,
            cliente: $backoffice ? trim((string) $request->input('cliente', '')) : '',
            userId: $backoffice ? max(0, (int) $request->input('user_id', 0)) : 0,
            metodoPagamentoId: $backoffice ? max(0, (int) $request->input('metodo_pagamento_id', 0)) : 0,
            backoffice: $backoffice,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'numero_ordine' => $this->numeroOrdine,
            'periodo' => $this->periodo,
            'data_de' => $this->dataDe,
            'data_a' => $this->dataA,
            'importo' => $this->importo,
            'stato' => $this->stato,
            'cliente' => $this->cliente,
            'user_id' => $this->userId > 0 ? (string) $this->userId : '',
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->numeroOrdine !== ''
            || $this->periodo !== ''
            || $this->importo !== ''
            || $this->stato !== 'tutte'
            || $this->cliente !== ''
            || $this->userId > 0
            || $this->metodoPagamentoId > 0;
    }

    public function customPeriodoSemDatas(): bool
    {
        return $this->periodo === 'custom'
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataDe)
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataA);
    }

    public function applyToQuery(Builder $query): void
    {
        if (! $this->backoffice) {
            $query->where('stato', '!=', 'annullata');
        }

        if ($this->backoffice) {
            if ($this->userId > 0) {
                $query->where('user_id', $this->userId);
            } elseif ($this->cliente !== '') {
                $like = '%'.addcslashes(mb_strtolower($this->cliente), '%_\\').'%';
                $query->whereHas('user', function (Builder $uq) use ($like): void {
                    $uq->whereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhereHas('anagrafica', function (Builder $aq) use ($like): void {
                            $aq->whereRaw('LOWER(COALESCE(nome, \'\')) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(COALESCE(cognome, \'\')) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(COALESCE(denominazione_ragione_sociale, \'\')) LIKE ?', [$like]);
                        });
                });
            }
        }

        if ($this->numeroOrdine !== '') {
            FiltriTabella::filtraNumeroOrdineWallet($query, $this->numeroOrdine);
        }

        $importoRaw = str_replace(',', '.', $this->importo);
        if ($this->importo !== '' && is_numeric($importoRaw)) {
            $query->where('importo', round((float) $importoRaw, 2));
        }

        if ($this->metodoPagamentoId > 0) {
            $query->where('id_metodo_pagamento_wallet_ricariches', $this->metodoPagamentoId);
        }

        match ($this->stato) {
            'pagato' => $query->where('stato', 'accreditata'),
            'non_pagato' => $query->where('stato', 'in_attesa'),
            'annullata' => $query->where('stato', 'annullata'),
            default => null,
        };

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
