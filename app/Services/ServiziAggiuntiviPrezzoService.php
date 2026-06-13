<?php

namespace App\Services;

use App\Models\corrieri_servizi_aggiuntivi;
use Illuminate\Support\Collection;

/**
 * Listino servizi aggiuntivi corriere (tabella corrieri_servizi_aggiuntivis / tabella_s_a_c.csv).
 * percentuale_cor e ricarico_k91 sono frazioni decimali (es. 0,16 = 16%).
 */
final class ServiziAggiuntiviPrezzoService
{
    public static function gruppoRichiedeValoreMerce(Collection $righe): bool
    {
        if ($righe->count() > 1) {
            return true;
        }
        $r = $righe->first();
        if (! $r instanceof corrieri_servizi_aggiuntivi) {
            return false;
        }

        return self::rigaSuValoreMerce($r);
    }

    public static function rigaSuValoreMerce(corrieri_servizi_aggiuntivi $r): bool
    {
        return $r->min_fascia !== null || $r->max_fascia !== null
            || mb_strtolower((string) $r->testo_servizio) === 'contrassegno'
            || mb_strtolower((string) $r->testo_servizio) === 'assicurazione';
    }

    public static function messaggioFasciaNonValida(?float $min, ?float $max, float $valoreMerce): ?string
    {
        if ($valoreMerce <= 0) {
            return 'Inserisci un importo maggiore di zero.';
        }

        $fmt = static fn (?float $v): string => $v === null
            ? ''
            : number_format($v, 2, ',', '.').' €';

        if ($min !== null && $valoreMerce < $min) {
            if ($max !== null) {
                return 'Inserisci un valore compreso tra '.$fmt($min).' e '.$fmt($max).'.';
            }

            return 'Inserisci un valore di almeno '.$fmt($min).'.';
        }

        if ($max !== null && $valoreMerce > $max) {
            if ($min !== null) {
                return 'Inserisci un valore compreso tra '.$fmt($min).' e '.$fmt($max).'.';
            }

            return 'Inserisci un valore di massimo '.$fmt($max).'.';
        }

        return null;
    }

    public static function messaggioFasciaNonValidaRiga(corrieri_servizi_aggiuntivi $r, float $valoreMerce): ?string
    {
        return self::messaggioFasciaNonValida(
            $r->min_fascia !== null ? (float) $r->min_fascia : null,
            $r->max_fascia !== null ? (float) $r->max_fascia : null,
            $valoreMerce,
        );
    }

    public static function risolviRigaPerMerce(Collection $righe, float $valoreMerce): ?corrieri_servizi_aggiuntivi
    {
        if ($righe->isEmpty()) {
            return null;
        }
        if (! self::gruppoRichiedeValoreMerce($righe)) {
            return $righe->first();
        }

        $sorted = $righe->sortBy(fn (corrieri_servizi_aggiuntivi $x) => (float) ($x->min_fascia ?? 0))->values();

        foreach ($sorted as $r) {
            $da = $r->min_fascia !== null ? (float) $r->min_fascia : null;
            $a = $r->max_fascia !== null ? (float) $r->max_fascia : null;
            if ($da === null && $a === null) {
                continue;
            }
            if ($da !== null && $valoreMerce < $da) {
                continue;
            }
            if ($a !== null && $valoreMerce > $a) {
                continue;
            }

            return $r;
        }

        foreach ($sorted->reverse() as $r) {
            if ($r->max_fascia !== null) {
                continue;
            }
            if ($r->min_fascia !== null && $valoreMerce >= (float) $r->min_fascia) {
                return $r;
            }
        }

        return $sorted->last();
    }

    /**
     * Costo netto listino corriere (IVA esc., prima del ricarico cliente k91).
     */
    public static function importoNettoListino(
        ?corrieri_servizi_aggiuntivi $r,
        float $valoreMerce,
        float $trasportoBaseFornitoreIvaEsc
    ): float {
        if (! $r) {
            return 0.0;
        }

        $pct = (float) ($r->percentuale_cor ?? 0);
        $fisso = (float) ($r->valore_fisso_cor ?? 0);
        $base = self::rigaSuValoreMerce($r) ? max(0.0, $valoreMerce) : max(0.0, $trasportoBaseFornitoreIvaEsc);

        $importo = ($base * $pct) + $fisso;

        if ($r->valore_minimo !== null) {
            $importo = max($importo, (float) $r->valore_minimo);
        }
        if ($r->valore_massimo !== null) {
            $importo = min($importo, (float) $r->valore_massimo);
        }

        return round($importo, 4);
    }

    /**
     * Prezzo servizio al cliente (IVA esc.).
     *
     * (costo_corriere + valore_fisso_cor) × (1 + ricarico_k91) + valore_fisso_k91
     *
     * costo_corriere = quota % (merce o trasporto × percentuale_cor); valore_fisso_cor è il fisso corriere.
     * $costoCorriereEsposto passato dal chiamante è già il totale listino (quota % + fisso corriere).
     */
    public static function importoClienteIvaEsc(
        float $costoCorriereEsposto,
        ?corrieri_servizi_aggiuntivi $r = null,
        float $ricaricoTariffaPercent = 0.0,
    ): float {
        if ($r) {
            $rk = (float) ($r->ricarico_k91 ?? 0);
            $fissoCor = (float) ($r->valore_fisso_cor ?? 0);
            $fissoK91 = (float) ($r->valore_fisso_k91 ?? 0);
            $costoTotale = max(0.0, $costoCorriereEsposto);

            return round($costoTotale * (1 + $rk) + $fissoK91, 2);
        }

        $ric = max(-100.0, (float) $ricaricoTariffaPercent);

        return round($costoCorriereEsposto * (1 + $ric / 100), 2);
    }

    /**
     * @param  Collection<int, corrieri_servizi_aggiuntivi>  $tutteLeRighe
     * @return list<array<string, mixed>>
     */
    public static function raggruppaPerCheckout(Collection $tutteLeRighe): array
    {
        $out = [];
        $gruppi = $tutteLeRighe->groupBy(fn (corrieri_servizi_aggiuntivi $r) => $r->id_corriere.'|'.$r->testo_servizio);

        foreach ($gruppi as $gruppo) {
            /** @var Collection<int, corrieri_servizi_aggiuntivi> $gruppo */
            $gruppo = $gruppo->values();
            $first = $gruppo->first();
            if (! $first) {
                continue;
            }

            $merce = self::gruppoRichiedeValoreMerce($gruppo);
            $bands = null;
            if ($merce) {
                $bands = $gruppo->map(fn (corrieri_servizi_aggiuntivi $p) => [
                    'id' => $p->id,
                    'min_fascia' => $p->min_fascia,
                    'max_fascia' => $p->max_fascia,
                    'valore_minimo' => $p->valore_minimo,
                    'percentuale_cor' => $p->percentuale_cor,
                    'valore_fisso_cor' => $p->valore_fisso_cor,
                    'valore_fisso_k91' => $p->valore_fisso_k91,
                    'ricarico_k91' => $p->ricarico_k91,
                ])->sortBy(fn (array $b) => (float) ($b['min_fascia'] ?? 0))->values()->all();
            }

            $pivot = $merce ? null : $gruppo->first();
            $pivotArr = $pivot ? [
                'id' => $pivot->id,
                'percentuale_cor' => $pivot->percentuale_cor,
                'valore_fisso_cor' => $pivot->valore_fisso_cor,
                'valore_fisso_k91' => $pivot->valore_fisso_k91,
                'ricarico_k91' => $pivot->ricarico_k91,
                'testo_servizio' => $pivot->testo_servizio,
            ] : null;

            $out[] = [
                'id' => (int) $first->id,
                'id_servizi_aggiuntivi' => (int) $first->id,
                'testo_servizio' => $first->testo_servizio,
                'modalita' => $merce ? 'valore_merce' : 'fisso',
                'servizio' => [
                    'denominazione_servizio' => $first->testo_servizio,
                ],
                'pivot_singolo' => $pivotArr,
                'bands' => $bands,
            ];
        }

        return $out;
    }

    public static function scopeQueryCorriere(int $corriereId, int $idTipoSped = 0): \Illuminate\Database\Eloquent\Builder
    {
        return corrieri_servizi_aggiuntivi::query()
            ->where('id_corriere', $corriereId)
            ->where('visualizzato', true)
            ->when($idTipoSped > 0, function ($q) use ($idTipoSped) {
                $q->where(function ($w) use ($idTipoSped) {
                    $w->whereNull('id_tipo')->orWhere('id_tipo', $idTipoSped);
                });
            })
            ->orderBy('testo_servizio')
            ->orderBy('min_fascia');
    }

    /**
     * Prezzi indicativi servizi per la pagina preventivi (solo listino; API senza importo utente).
     *
     * @param  list<array<string, mixed>>  $gruppiCheckout
     * @param  Collection<int, corrieri_servizi_aggiuntivi>  $pivotRows
     * @return list<array{nome: string, costo_nostro: float|null, costo_cliente: float|null, nota: string|null, riferimento: string|null, fascia: string|null}>
     */
    public static function indicativiPreventivi(
        array $gruppiCheckout,
        float $trasportoBaseFornitore,
        bool $quoteApiServizi,
        Collection $pivotRows,
    ): array {
        $out = [];

        foreach ($gruppiCheckout as $g) {
            $nome = trim((string) ($g['testo_servizio'] ?? ''));
            if ($nome === '') {
                continue;
            }

            $mod = (string) ($g['modalita'] ?? 'fisso');
            $bands = is_array($g['bands'] ?? null) ? $g['bands'] : [];
            $fascia = self::fasciaHint($bands);

            if ($mod === 'valore_merce' && $quoteApiServizi) {
                $out[] = [
                    'nome' => $nome,
                    'costo_nostro' => null,
                    'costo_cliente' => null,
                    'nota' => 'Quotazione API al checkout (inserisci importo).',
                    'riferimento' => null,
                    'fascia' => $fascia,
                ];

                continue;
            }

            if ($mod === 'fisso') {
                $pid = (int) ($g['pivot_singolo']['id'] ?? 0);
                $pivot = $pid > 0 ? $pivotRows->firstWhere('id', $pid) : null;
                if (! $pivot instanceof corrieri_servizi_aggiuntivi) {
                    $out[] = [
                        'nome' => $nome,
                        'costo_nostro' => null,
                        'costo_cliente' => null,
                        'nota' => 'Configurazione servizio non trovata.',
                        'riferimento' => null,
                        'fascia' => null,
                    ];

                    continue;
                }

                $nostro = self::importoNettoListino($pivot, 0.0, $trasportoBaseFornitore);
                if ($nostro <= 0) {
                    $out[] = [
                        'nome' => $nome,
                        'costo_nostro' => null,
                        'costo_cliente' => null,
                        'nota' => 'Prezzo listino non calcolabile.',
                        'riferimento' => null,
                        'fascia' => null,
                    ];

                    continue;
                }

                $out[] = [
                    'nome' => $nome,
                    'costo_nostro' => round($nostro, 2),
                    'costo_cliente' => self::importoClienteIvaEsc($nostro, $pivot, 0.0),
                    'nota' => null,
                    'riferimento' => null,
                    'fascia' => null,
                ];

                continue;
            }

            $refImporto = self::importoRiferimentoFascia($bands);
            if ($refImporto === null) {
                $out[] = [
                    'nome' => $nome,
                    'costo_nostro' => null,
                    'costo_cliente' => null,
                    'nota' => 'Inserisci importo al checkout per il calcolo.',
                    'riferimento' => null,
                    'fascia' => $fascia,
                ];

                continue;
            }

            $gruppoPivot = $pivotRows->where('testo_servizio', $nome)->values();
            $pivot = self::risolviRigaPerMerce($gruppoPivot, $refImporto);
            if (! $pivot instanceof corrieri_servizi_aggiuntivi) {
                $out[] = [
                    'nome' => $nome,
                    'costo_nostro' => null,
                    'costo_cliente' => null,
                    'nota' => 'Fascia listino non risolvibile.',
                    'riferimento' => null,
                    'fascia' => $fascia,
                ];

                continue;
            }

            $nostro = self::importoNettoListino($pivot, $refImporto, $trasportoBaseFornitore);
            if ($nostro <= 0) {
                $out[] = [
                    'nome' => $nome,
                    'costo_nostro' => null,
                    'costo_cliente' => null,
                    'nota' => 'Prezzo listino non calcolabile.',
                    'riferimento' => null,
                    'fascia' => $fascia,
                ];

                continue;
            }

            $out[] = [
                'nome' => $nome,
                'costo_nostro' => round($nostro, 2),
                'costo_cliente' => self::importoClienteIvaEsc($nostro, $pivot, 0.0),
                'nota' => null,
                'riferimento' => 'su importo '.number_format($refImporto, 2, ',', '.').' € (min. fascia)',
                'fascia' => $fascia,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $bands
     */
    private static function fasciaHint(array $bands): ?string
    {
        if ($bands === []) {
            return null;
        }

        $min = null;
        $max = null;
        foreach ($bands as $b) {
            if (! is_array($b)) {
                continue;
            }
            if (isset($b['min_fascia']) && $b['min_fascia'] !== null && $b['min_fascia'] !== '') {
                $v = (float) $b['min_fascia'];
                $min = $min === null ? $v : min($min, $v);
            }
            if (isset($b['max_fascia']) && $b['max_fascia'] !== null && $b['max_fascia'] !== '') {
                $v = (float) $b['max_fascia'];
                $max = $max === null ? $v : max($max, $v);
            }
        }

        if ($min !== null && $max !== null) {
            return number_format($min, 2, ',', '.').'–'.number_format($max, 2, ',', '.').' €';
        }
        if ($min !== null) {
            return 'da '.number_format($min, 2, ',', '.').' €';
        }
        if ($max !== null) {
            return 'fino a '.number_format($max, 2, ',', '.').' €';
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $bands
     */
    private static function importoRiferimentoFascia(array $bands): ?float
    {
        $min = null;
        foreach ($bands as $b) {
            if (! is_array($b)) {
                continue;
            }
            if (! isset($b['min_fascia']) || $b['min_fascia'] === null || $b['min_fascia'] === '') {
                continue;
            }
            $v = (float) $b['min_fascia'];
            if ($v <= 0) {
                continue;
            }
            $min = $min === null ? $v : min($min, $v);
        }

        return $min;
    }
}
