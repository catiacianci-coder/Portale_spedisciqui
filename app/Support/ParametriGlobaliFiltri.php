<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ParametriGlobaliFiltri
{
    public function __construct(
        public string $denominazione = '',
        public string $inizioDa = '',
        public string $inizioA = '',
        public bool $fineNull = false,
        public string $fineDa = '',
        public string $fineA = '',
    ) {}

    public static function daRequest(Request $request): self
    {
        return new self(
            denominazione: trim((string) $request->query('denominazione', '')),
            inizioDa: trim((string) $request->query('inizio_da', '')),
            inizioA: trim((string) $request->query('inizio_a', '')),
            fineNull: $request->boolean('fine_null'),
            fineDa: trim((string) $request->query('fine_da', '')),
            fineA: trim((string) $request->query('fine_a', '')),
        );
    }

    public function haFiltri(): bool
    {
        return $this->denominazione !== ''
            || $this->inizioDa !== ''
            || $this->inizioA !== ''
            || $this->fineNull
            || $this->fineDa !== ''
            || $this->fineA !== '';
    }

    /** @return array<string, string> */
    public function queryParams(): array
    {
        $params = ['vista' => 'parametri'];

        if ($this->denominazione !== '') {
            $params['denominazione'] = $this->denominazione;
        }
        if ($this->inizioDa !== '') {
            $params['inizio_da'] = $this->inizioDa;
        }
        if ($this->inizioA !== '') {
            $params['inizio_a'] = $this->inizioA;
        }
        if ($this->fineNull) {
            $params['fine_null'] = '1';
        }
        if ($this->fineDa !== '') {
            $params['fine_da'] = $this->fineDa;
        }
        if ($this->fineA !== '') {
            $params['fine_a'] = $this->fineA;
        }

        return $params;
    }

    public function applica(Builder $query): Builder
    {
        if ($this->denominazione !== '') {
            $query->where('denominazione', $this->denominazione);
        }

        if ($this->inizioDa !== '') {
            $query->whereDate('inizio_validita', '>=', $this->inizioDa);
        }

        if ($this->inizioA !== '') {
            $query->whereDate('inizio_validita', '<=', $this->inizioA);
        }

        if ($this->fineNull) {
            $query->whereNull('fine_validita');
        } else {
            if ($this->fineDa !== '') {
                $query->whereDate('fine_validita', '>=', $this->fineDa);
            }
            if ($this->fineA !== '') {
                $query->whereDate('fine_validita', '<=', $this->fineA);
            }
        }

        return $query;
    }
}
