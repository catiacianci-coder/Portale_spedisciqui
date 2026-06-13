<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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

    public const DENOM_AVVISO_HOMEPAGE = 'avviso_homepage';

    /** @var array<string, string> */
    private static array $cacheTesto = [];

    protected $fillable = [
        'denominazione',
        'valore_assoluto',
        'valore_percentuale',
        'data_inizio',
        'data_fine',
        'id_metodo_pagamentos',
        'varie',
        'valore_testo',
    ];

    protected function casts(): array
    {
        return [
            'valore_assoluto' => 'float',
            'valore_percentuale' => 'float',
            'data_inizio' => 'date',
            'data_fine' => 'date',
        ];
    }

    public function metodoPagamento(): BelongsTo
    {
        return $this->belongsTo(metodo_pagamento::class, 'id_metodo_pagamentos');
    }

    public function scopeAttivoOggi(Builder $query): Builder
    {
        $d = now()->toDateString();

        return $query
            ->where(function (Builder $q) use ($d) {
                $q->whereNull('data_inizio')->orWhereDate('data_inizio', '<=', $d);
            })
            ->where(function (Builder $q) use ($d) {
                $q->whereNull('data_fine')->orWhereDate('data_fine', '>=', $d);
            });
    }

    /** Testo del parametro globale (cache in-request). */
    public static function valoreTesto(string $denominazione): string
    {
        if (! array_key_exists($denominazione, self::$cacheTesto)) {
            $v = static::query()->where('denominazione', $denominazione)->value('valore_testo');
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

    private static function giorniDaDenominazione(string $denominazione, int $default): int
    {
        $v = static::query()
            ->where('denominazione', $denominazione)
            ->attivoOggi()
            ->value('valore_assoluto');

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

        $row = static::query()->where('denominazione', self::DENOM_AVVISO_HOMEPAGE)->first();
        if ($row !== null) {
            $row->update(['valore_testo' => $testo]);
        } else {
            static::query()->create([
                'denominazione' => self::DENOM_AVVISO_HOMEPAGE,
                'valore_testo' => $testo,
            ]);
        }

        self::forgetTestoCache();
    }
}
