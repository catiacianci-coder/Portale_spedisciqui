<?php

namespace App\Support;

use App\Models\metodo_pagamento_rimborso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class RimborsoTrasferimentoWalletFilter
{
    public function __construct(
        public int $perPage = 10,
        public string $cliente = '',
        public int $userId = 0,
        public string $ordine = '',
        public string $etichetta = '',
        public string $periodo = '',
        public string $dataDe = '',
        public string $dataA = '',
        public string $stato = 'in_attesa',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $periodo = (string) $request->input('periodo', '');
        if (! in_array($periodo, ['', '7', '15', '30', 'custom'], true)) {
            $periodo = '';
        }

        $stato = (string) $request->input('stato', 'in_attesa');
        if (! in_array($stato, ['in_attesa', 'senza_richiesta', 'completati', 'tutti'], true)) {
            $stato = 'in_attesa';
        }

        return new self(
            perPage: FiltriTabella::perPage($request),
            cliente: trim((string) $request->input('cliente', '')),
            userId: max(0, (int) $request->input('user_id', 0)),
            ordine: trim((string) $request->input('ordine', '')),
            etichetta: trim((string) $request->input('etichetta', '')),
            periodo: $periodo,
            dataDe: trim((string) $request->input('data_de', '')),
            dataA: trim((string) $request->input('data_a', '')),
            stato: $stato,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cliente' => $this->cliente,
            'user_id' => $this->userId > 0 ? (string) $this->userId : '',
            'ordine' => $this->ordine,
            'etichetta' => $this->etichetta,
            'periodo' => $this->periodo,
            'data_de' => $this->dataDe,
            'data_a' => $this->dataA,
            'stato' => $this->stato,
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->cliente !== ''
            || $this->userId > 0
            || $this->ordine !== ''
            || $this->etichetta !== ''
            || $this->periodo !== '';
    }

    public function customPeriodoSemDatas(): bool
    {
        return $this->periodo === 'custom'
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataDe)
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataA);
    }

    public function applyToQuery(Builder $query): void
    {
        $walletId = metodo_pagamento_rimborso::idMetodoWalletAttivo();
        if ($walletId !== null) {
            $query->where('id_metodo_pagamento_rimborsi', $walletId);
        }

        $query->whereNotNull('data_reale');

        match ($this->stato) {
            'in_attesa' => $query->whereNull('data_trasferimento_esterno'),
            'senza_richiesta' => $query->whereNull('data_richiesta_trasferimento_esterno'),
            'completati' => $query->whereNotNull('data_trasferimento_esterno'),
            default => null,
        };

        if ($this->userId > 0) {
            $query->whereHas('spedizione', fn (Builder $q) => $q->where('user_id', $this->userId));
        } elseif ($this->cliente !== '') {
            $like = '%'.addcslashes(mb_strtolower($this->cliente), '%_\\').'%';
            $query->whereHas('spedizione.user', function (Builder $uq) use ($like): void {
                $uq->whereRaw('LOWER(email) LIKE ?', [$like])
                    ->orWhereHas('anagrafica', function (Builder $aq) use ($like): void {
                        $aq->whereRaw('LOWER(COALESCE(nome, \'\')) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(cognome, \'\')) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(denominazione_ragione_sociale, \'\')) LIKE ?', [$like]);
                    });
            });
        }

        if ($this->ordine !== '') {
            $ordineId = CodiceOrdine::idDaRiferimento($this->ordine);
            if ($ordineId !== null) {
                $query->where('ordine_id', $ordineId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($this->etichetta !== '') {
            $like = '%'.addcslashes($this->etichetta, '%_\\').'%';
            $query->where('codice_interno', 'like', $like);
        }

        $range = $this->dateRange();
        if ($range !== null) {
            [$de, $ate] = $range;
            $col = match ($this->stato) {
                'completati' => 'data_trasferimento_esterno',
                'senza_richiesta' => 'data_richiesta_trasferimento_esterno',
                default => 'data_reale',
            };
            $query->whereBetween($col, [$de, $ate]);
        }
    }

    public function resolveSelectedUser(): ?User
    {
        if ($this->userId <= 0) {
            return null;
        }

        return User::query()->find($this->userId);
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
