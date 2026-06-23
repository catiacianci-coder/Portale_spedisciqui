<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class parametri_globali extends Model
{
    protected $table = 'parametri_globalis';

    public const DENOM_NOME_IMPRESA = 'nome_impresa';

    public const DENOM_INDIRIZZO_IMPRESA = 'indirizzo_impresa';

    public const DENOM_P_IVA_IMPRESA = 'p_iva_impresa';

    public const DENOM_SITO_IMPRESA = 'sito_impresa';

    public const DENOM_IBAN_CC_R_B = 'iban_cc_r_b';

    public const DENOM_GIORNI_LDV_NO = 'giorni_ldv_no';

    public const DENOM_GIORNI_LDV_SI = 'giorni_ldv_si';

    public const DENOM_GIORNI_RITIRO = 'giorni_ritiro';

    public const DENOM_AVVISO_HOMEPAGE = 'avviso_homepage';

    /** @var array<string, string> */
    private static array $cacheTesto = [];

    protected $fillable = [
        'denominazione',
        'valore_assoluto',
        'valore_percentuale',
        'inizio_validita',
        'fine_validita',
        'id_metodo_pagamentos',
        'varie',
        'valore_testo',
    ];

    protected function casts(): array
    {
        return [
            'valore_assoluto' => 'float',
            'valore_percentuale' => 'float',
            'inizio_validita' => 'date',
            'fine_validita' => 'date',
        ];
    }

    public function metodoPagamento(): BelongsTo
    {
        return $this->belongsTo(metodo_pagamento::class, 'id_metodo_pagamentos');
    }

    /**
     * @deprecated Preferire {@see recordAttivo()} per lookup per denominazione.
     */
    public function scopeAttivoOggi(Builder $query): Builder
    {
        return $query->validiOggi();
    }

    /**
     * Record il cui intervallo contiene la data indicata (uso generico / legacy).
     *
     * @deprecated Per lookup per denominazione usare {@see recordAttivo()}.
     */
    public function scopeValidiOggi(Builder $query, mixed $at = null): Builder
    {
        $d = Carbon::parse($at ?? now())->toDateString();

        return $query
            ->where(function (Builder $q) use ($d) {
                $q->whereNull('inizio_validita')->orWhereDate('inizio_validita', '<=', $d);
            })
            ->where(function (Builder $q) use ($d) {
                $q->whereNull('fine_validita')->orWhereDate('fine_validita', '>=', $d);
            });
    }

    /**
     * Record attivo per denominazione alla data indicata.
     *
     * 1. Cerca il record con fine_validita nulla.
     * 2. Se oggi >= inizio_validita → record corretto.
     * 3. Altrimenti cerca il record chiuso con inizio <= oggi e fine >= oggi.
     */
    public static function recordAttivo(string $denominazione, mixed $at = null): ?self
    {
        $at = Carbon::parse($at ?? now())->startOfDay();
        $d = $at->toDateString();

        $aperto = static::query()
            ->where('denominazione', $denominazione)
            ->whereNull('fine_validita')
            ->orderByDesc('inizio_validita')
            ->orderByDesc('id')
            ->first();

        if ($aperto !== null && self::dataInIntervaloAperto($aperto, $d)) {
            return $aperto;
        }

        return static::query()
            ->where('denominazione', $denominazione)
            ->whereNotNull('inizio_validita')
            ->whereDate('inizio_validita', '<=', $d)
            ->whereNotNull('fine_validita')
            ->whereDate('fine_validita', '>=', $d)
            ->orderByDesc('inizio_validita')
            ->orderByDesc('id')
            ->first();
    }

    private static function dataInIntervaloAperto(self $record, string $date): bool
    {
        if ($record->inizio_validita === null) {
            return true;
        }

        return $record->inizio_validita->toDateString() <= $date;
    }

    /** Testo del parametro globale attivo (cache in-request). */
    public static function valoreTesto(string $denominazione): string
    {
        if (! array_key_exists($denominazione, self::$cacheTesto)) {
            $v = self::recordAttivo($denominazione)?->valore_testo;
            self::$cacheTesto[$denominazione] = trim((string) ($v ?? ''));
        }

        return self::$cacheTesto[$denominazione];
    }

    public static function forgetTestoCache(): void
    {
        self::$cacheTesto = [];
    }

    /** Giorni lavorativi rimborso senza etichetta/LDV (parametro giorni_ldv_no). */
    public static function giorniLdvNo(): int
    {
        return self::giorniDaDenominazione(self::DENOM_GIORNI_LDV_NO, 0);
    }

    /** Giorni lavorativi rimborso con etichetta/LDV (parametro giorni_ldv_si). */
    public static function giorniLdvSi(): int
    {
        return self::giorniDaDenominazione(self::DENOM_GIORNI_LDV_SI, 15);
    }

    public static function giorniRimborsoPerMotivo(int $motivo): int
    {
        return $motivo === \App\Models\rimborso::MOTIVO_CON_ETICHETTA
            ? self::giorniLdvSi()
            : self::giorniLdvNo();
    }

    /** Giorni lavorativi (lun–ven) selezionabili per data ritiro (parametro giorni_ritiro). */
    public static function giorniRitiro(): int
    {
        return max(1, self::giorniDaDenominazione(self::DENOM_GIORNI_RITIRO, 4));
    }

    private static function giorniDaDenominazione(string $denominazione, int $default): int
    {
        $v = self::recordAttivo($denominazione)?->valore_assoluto;

        if ($v === null) {
            return $default;
        }

        return max(0, (int) $v);
    }

    /** Testo avviso homepage (null se assente). */
    public static function homepageAvvisoTesto(): ?string
    {
        $testo = self::valoreTesto(self::DENOM_AVVISO_HOMEPAGE);

        return $testo !== '' ? $testo : null;
    }

    public static function salvaHomepageAvviso(string $testo): void
    {
        $testo = trim(preg_replace('/\s+/u', ' ', $testo) ?? '');

        $row = self::recordAttivo(self::DENOM_AVVISO_HOMEPAGE)
            ?? static::query()->where('denominazione', self::DENOM_AVVISO_HOMEPAGE)->first();

        if ($row !== null) {
            $row->update(['valore_testo' => $testo]);
        } else {
            static::query()->create([
                'denominazione' => self::DENOM_AVVISO_HOMEPAGE,
                'valore_testo' => $testo,
                'inizio_validita' => '2026-04-01',
            ]);
        }

        self::forgetTestoCache();
    }
}
