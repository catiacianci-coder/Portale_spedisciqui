<?php

namespace App\Support;

use App\Models\ordine;
use App\Models\servizi_aggiuntivi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

final class FiltriTabella
{
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORT_DIR_ASC = 'asc';

    public const SORT_DIR_DESC = 'desc';

    public static function perPage(Request $request, int $default = 10): int
    {
        $n = (int) $request->input('per_page', $default);

        return in_array($n, self::PER_PAGE_OPTIONS, true) ? $n : $default;
    }

    /**
     * @param  list<string>  $extraAllowed  es. tutti, oggi, mese_scorso
     * @param  bool  $customIncompletoSenzaErrore  Se true, periodo custom senza date non genera errore né filtro data
     * @return array{
     *     period: string,
     *     data_da: string,
     *     data_a: string,
     *     errors: list<string>,
     *     from: ?Carbon,
     *     to: ?Carbon
     * }
     */
    public static function periodoDaRequest(
        Request $request,
        string $defaultPeriod = '30',
        array $extraAllowed = [],
        bool $customIncompletoSenzaErrore = false,
    ): array {
        $period = (string) $request->input('period', $defaultPeriod);
        $dataDa = (string) $request->input('data_da', '');
        $dataA = (string) $request->input('data_a', '');

        $allowed = array_merge(['7', '15', '30', 'custom'], $extraAllowed);
        if (! in_array($period, $allowed, true)) {
            $period = $defaultPeriod;
        }

        $errors = [];
        $from = null;
        $to = null;

        if ($period === 'custom') {
            if ($dataDa === '' || $dataA === '') {
                if (! $customIncompletoSenzaErrore) {
                    $errors[] = 'Per il periodo personalizzato servono entrambe le date (da/a).';
                }
            } else {
                try {
                    $d1 = Carbon::createFromFormat('Y-m-d', $dataDa)->startOfDay();
                    $d2 = Carbon::createFromFormat('Y-m-d', $dataA)->endOfDay();
                    if ($d1->gt($d2)) {
                        $errors[] = 'La data «da» non può essere successiva alla data «a».';
                    } else {
                        $from = $d1;
                        $to = $d2;
                    }
                } catch (\Throwable) {
                    $errors[] = 'Date non valide nel periodo personalizzato.';
                }
            }
        } elseif ($period !== 'tutti') {
            [$from, $to] = self::intervalloDaPeriodo($period, $dataDa, $dataA);
        }

        return [
            'period' => $period,
            'data_da' => $dataDa,
            'data_a' => $dataA,
            'errors' => $errors,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function intervalloDaPeriodo(string $period, string $dataDa = '', string $dataA = ''): array
    {
        $now = now();

        return match ($period) {
            'oggi' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
            '15' => [$now->copy()->subDays(15)->startOfDay(), $now->copy()->endOfDay()],
            '30' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            'mese_scorso' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'custom' => [
                Carbon::createFromFormat('Y-m-d', $dataDa)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $dataA)->endOfDay(),
            ],
            default => [null, null],
        };
    }

    public static function applicaFiltroCreatedAt(Builder|Relation $query, ?Carbon $from, ?Carbon $to, string $column = 'created_at'): void
    {
        if ($from !== null && $to !== null) {
            $query->whereBetween($column, [$from, $to]);
        }
    }

    public static function applicaFiltroDataPagamentoOrdine(Builder|Relation $query, ?Carbon $from, ?Carbon $to): void
    {
        if ($from === null || $to === null) {
            return;
        }

        $query->whereHas('ordine', fn (Builder $q) => $q->whereBetween('data_pagamento', [$from, $to]));
    }

    /**
     * @param  array<int, string>  $escludi
     * @return array<string, mixed>
     */
    public static function parametriQuery(Request $request, array $escludi = ['page']): array
    {
        return collect($request->query())
            ->except($escludi)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();
    }

    /**
     * Parametri filtro per redirect dopo POST (query string + campi hidden nel body).
     *
     * @param  list<string>  $escludi
     * @return array<string, mixed>
     */
    public static function parametriRedirect(Request $request, array $escludi = ['page', '_token']): array
    {
        return collect($request->query())
            ->merge($request->except($escludi))
            ->except(array_merge($escludi, ['page']))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();
    }

    /**
     * Filtra per id ordine (accetta anche input legacy O{id}).
     *
     * @param  string|null  $codiceColumn  Solo se la tabella ha una colonna codice persistita (legacy).
     */
    public static function filtraNumeroOrdine(Builder|Relation $query, string $numero, ?string $codiceColumn = null): void
    {
        $needle = trim($numero);
        if ($needle === '') {
            return;
        }

        $id = CodiceOrdine::idDaRiferimento($needle);

        $query->where(function ($w) use ($needle, $id, $codiceColumn): void {
            if ($id !== null) {
                $w->where('id', $id);

                return;
            }

            if (ctype_digit($needle)) {
                $w->where('id', (int) $needle);

                return;
            }

            if ($codiceColumn !== null) {
                $w->where($codiceColumn, 'like', '%'.$needle.'%');

                return;
            }

            $w->whereRaw('0 = 1');
        });
    }

    public static function filtraNumeroOrdineWallet(Builder|Relation $query, string $numero): void
    {
        $needle = trim($numero);
        if ($needle === '') {
            return;
        }

        $id = preg_replace('/^ORW-/i', '', $needle);
        $query->where(function ($w) use ($needle, $id): void {
            $w->where('numero_ordine_wallet', 'like', '%'.$needle.'%');
            if ($id !== '' && ctype_digit($id)) {
                $w->orWhere('id', (int) $id);
            }
        });
    }

    public static function filtraSpedizioniCliente(
        Builder|Relation $query,
        string $codice,
        string $tracking,
        string $numeroOrdine,
    ): void {
        $codice = trim($codice);
        if ($codice !== '') {
            $query->where(function ($w) use ($codice): void {
                $w->where('codice_interno', 'like', '%'.$codice.'%');
                if (ctype_digit($codice)) {
                    $w->orWhere('id', (int) $codice);
                }
            });
        }

        $tracking = trim($tracking);
        if ($tracking !== '') {
            $query->where('tracking', 'like', '%'.$tracking.'%');
        }

        $numeroOrdine = trim($numeroOrdine);
        if ($numeroOrdine !== '') {
            $query->whereHas('ordine', function (Builder $q) use ($numeroOrdine): void {
                self::filtraNumeroOrdine($q, $numeroOrdine);
            });
        }
    }

    public static function filtraStatoSpedizione(Builder|Relation $query, ?int $statoId, array $statiAmmessi): void
    {
        if ($statoId === null || $statoId <= 0) {
            return;
        }

        if ($statiAmmessi !== [] && ! in_array($statoId, $statiAmmessi, true)) {
            return;
        }

        $query->where('spedizione_stato_id', $statoId);
    }

    public static function filtraEtichetteCliente(
        Builder|Relation $query,
        string $codiceEtichetta,
        string $numeroOrdine,
        string $destinatario,
    ): void {
        $codiceEtichetta = trim($codiceEtichetta);
        if ($codiceEtichetta !== '') {
            $query->where(function ($w) use ($codiceEtichetta): void {
                $w->where('codice_interno', 'like', '%'.$codiceEtichetta.'%')
                    ->orWhere('tracking', 'like', '%'.$codiceEtichetta.'%');
                if (ctype_digit($codiceEtichetta)) {
                    $w->orWhere('id', (int) $codiceEtichetta);
                }
            });
        }

        $numeroOrdine = trim($numeroOrdine);
        if ($numeroOrdine !== '') {
            $query->whereHas('ordine', function (Builder $q) use ($numeroOrdine): void {
                self::filtraNumeroOrdine($q, $numeroOrdine);
            });
        }

        $destinatario = trim($destinatario);
        if ($destinatario !== '') {
            $needle = '%'.$destinatario.'%';
            $query->where(function ($w) use ($needle): void {
                $w->where('nome_d', 'like', $needle)
                    ->orWhere('sobrenome_d', 'like', $needle)
                    ->orWhere('ragione_sociale_d', 'like', $needle)
                    ->orWhereRaw("CONCAT(COALESCE(nome_d,''), ' ', COALESCE(sobrenome_d,'')) LIKE ?", [$needle]);
            });
        }
    }

    public static function filtraCorriereSpedizione(Builder|Relation $query, ?int $corriereId): void
    {
        if ($corriereId === null || $corriereId <= 0) {
            return;
        }

        $query->where('id_codice_servizio', $corriereId);
    }

    public static function filtraServizioAggiuntivoEtichetta(Builder|Relation $query, ?int $servizioId): void
    {
        if ($servizioId === null || $servizioId <= 0) {
            return;
        }

        $servizio = servizi_aggiuntivi::query()->find($servizioId);
        if ($servizio === null) {
            return;
        }

        $den = trim((string) $servizio->denominazione_servizio);
        if ($den === '') {
            return;
        }

        $needle = mb_strtolower($den);

        $query->whereHas('serviziAggiuntiviRighe', function (Builder $q) use ($needle): void {
            $q->where(function (Builder $w) use ($needle): void {
                $w->whereRaw('LOWER(COALESCE(testo_servizio, \'\')) = ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(testo_servizio, \'\')) LIKE ?', [$needle.' %'])
                    ->orWhereRaw('LOWER(COALESCE(testo_servizio, \'\')) LIKE ?', [$needle.'%'])
                    ->orWhereHas('corriereServizioAggiuntivo', function (Builder $c) use ($needle): void {
                        $c->where(function (Builder $cw) use ($needle): void {
                            $cw->whereRaw('LOWER(COALESCE(testo_servizio, \'\')) = ?', [$needle])
                                ->orWhereRaw('LOWER(COALESCE(testo_servizio, \'\')) LIKE ?', [$needle.' %'])
                                ->orWhereRaw('LOWER(COALESCE(testo_servizio, \'\')) LIKE ?', [$needle.'%']);
                        });
                    });
            });
        });
    }

    /**
     * @return array{column: string, dir: 'asc'|'desc'}
     */
    public static function ordinamentoEtichetteDaRequest(Request $request): array
    {
        $allowed = ['ordine'];
        $column = (string) $request->input('sort', 'ordine');
        if (! in_array($column, $allowed, true)) {
            $column = 'ordine';
        }

        $dir = strtolower((string) $request->input('dir', self::SORT_DIR_DESC));
        if (! in_array($dir, [self::SORT_DIR_ASC, self::SORT_DIR_DESC], true)) {
            $dir = self::SORT_DIR_DESC;
        }

        return ['column' => $column, 'dir' => $dir];
    }

    public static function applicaOrdinamentoEtichetteCliente(Builder|Relation $query, string $column, string $dir): void
    {
        if ($column === 'ordine') {
            $query->orderBy(
                ordine::select('id')
                    ->whereColumn('ordinis.id', 'spedizionis.ordine_id')
                    ->limit(1),
                $dir
            );

            if ($dir === self::SORT_DIR_ASC) {
                $query->orderBy('spedizionis.id', self::SORT_DIR_ASC);
            } else {
                $query->orderByDesc('spedizionis.id');
            }

            return;
        }

        $query->orderByDesc('spedizionis.id');
    }

    /**
     * @param  array<string, mixed>  $queryParams
     * @return array<string, mixed>
     */
    public static function parametriOrdinamentoToggle(
        array $queryParams,
        string $column,
        string $currentColumn,
        string $currentDir,
    ): array {
        $nextDir = ($currentColumn === $column && $currentDir === self::SORT_DIR_DESC)
            ? self::SORT_DIR_ASC
            : self::SORT_DIR_DESC;

        return array_merge($queryParams, [
            'sort' => $column,
            'dir' => $nextDir,
        ]);
    }
}
