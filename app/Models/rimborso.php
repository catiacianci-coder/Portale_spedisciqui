<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class rimborso extends Model
{
    /** Etichetta non generata (senza tracking o senza PDF). */
    public const MOTIVO_SENZA_ETICHETTA = 0;

    /** Etichetta generata (tracking + PDF): cancel API + attesa giorni lavorativi. */
    public const MOTIVO_CON_ETICHETTA = 1;

    protected $table = 'rimborsi';

    protected $fillable = [
        'spedizione_id',
        'codice_interno',
        'ordine_id',
        'motivo',
        'payment_id',
        'stripe_refund_id',
        'token',
        'id_metodo_pagamento_rimborsi',
        'data_richiesta',
        'valore',
        'giorni',
        'data_prevista',
        'data_reale',
        'stripe_payment_intent_id',
        'credito_avviso_letto_in',
    ];

    protected function casts(): array
    {
        return [
            'valore' => 'float',
            'giorni' => 'integer',
            'data_richiesta' => 'datetime',
            'data_prevista' => 'date',
            'data_reale' => 'datetime',
            'credito_avviso_letto_in' => 'datetime',
        ];
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(ordine::class, 'ordine_id');
    }

    public function spedizione(): BelongsTo
    {
        return $this->belongsTo(spedizione::class, 'spedizione_id');
    }

    public function metodoPagamentoRimborso(): BelongsTo
    {
        return $this->belongsTo(metodo_pagamento_rimborso::class, 'id_metodo_pagamento_rimborsi');
    }

    public function isAccreditato(): bool
    {
        return $this->data_reale !== null;
    }

    public function labelMotivo(): string
    {
        return match ((int) $this->motivo) {
            self::MOTIVO_CON_ETICHETTA => 'Con etichetta',
            self::MOTIVO_SENZA_ETICHETTA => 'Senza etichetta',
            default => '—',
        };
    }

    public static function resolveMotivoFromSpedizione(spedizione $spedizione): int
    {
        $tracking = trim((string) ($spedizione->tracking ?? ''));
        $haPdf = trim((string) ($spedizione->etiqueta_pdf_path ?? '')) !== ''
            || $spedizione->ldv_emessa_il !== null
            || $spedizione->esiste_integrazione;

        if ($tracking !== '' && $haPdf) {
            return self::MOTIVO_CON_ETICHETTA;
        }

        return self::MOTIVO_SENZA_ETICHETTA;
    }
}
