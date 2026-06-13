<?php

namespace App\Models;

use App\Support\CodiceOrdine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ordine extends Model
{
    protected $table = 'ordinis';

    /** Prefisso pubblico ordine: O + id. */
    public const PREFIX_CODICE = 'O';

    public const STATO_NON_PAGATO = 'non_pagato';

    public const STATO_PAGATO = 'pagato';

    public const STATO_ANNULLATO = 'annullato';

    /** Marca in {@see varie4} quando l’operatore conferma il pagamento manualmente dal BO. */
    public const VARIE4_OPERAZIONE_BACKOFFICE = 'Operazione Backoffice';

    protected $fillable = [
        'user_id',
        'stato_ordine_id',
        'metodo_pagamento',
        'metodo_pagamento_ordinis_id',
        'costo_servizo',
        'commissioni',
        'total_pagamento',
        'total_pagamento_wallet',
        'pag_effettivo_or',
        'data_pagamento',
        'annullato_in',
        'cr',
        'payment_id',
        'token',
        'token_2',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_refund_id',
        'stripe_refund_amount',
        'stripe_refunded_at',
        'chiave_causale',
        'revolut_transaction_id',
        'dettaglio_json',
        'varie4',
        'varie5',
    ];

    protected function casts(): array
    {
        return [
            'costo_servizo' => 'float',
            'commissioni' => 'float',
            'total_pagamento' => 'float',
            'total_pagamento_wallet' => 'float',
            'pag_effettivo_or' => 'float',
            'dettaglio_json' => 'array',
            'data_pagamento' => 'datetime',
            'annullato_in' => 'datetime',
            'stripe_refunded_at' => 'datetime',
            'stripe_refund_amount' => 'float',
        ];
    }

    public static function statoId(string $codice): int
    {
        return stato_ordine::idPerCodice($codice);
    }

    /** @param  Builder<self>  $query */
    public function scopeConStatoCodice(Builder $query, string $codice): Builder
    {
        return $query->where('stato_ordine_id', self::statoId($codice));
    }

    public function haStato(string $codice): bool
    {
        return (int) $this->stato_ordine_id === self::statoId($codice);
    }

    public function isNonPagato(): bool
    {
        return $this->haStato(self::STATO_NON_PAGATO);
    }

    public function isPagato(): bool
    {
        return $this->haStato(self::STATO_PAGATO);
    }

    public function isAnnullato(): bool
    {
        return $this->haStato(self::STATO_ANNULLATO);
    }

    public function marcarComoAnnullato(?\DateTimeInterface $quando = null): void
    {
        $quando ??= now();
        $attrs = [
            'stato_ordine_id' => self::statoId(self::STATO_ANNULLATO),
        ];
        if ($this->annullato_in === null) {
            $attrs['annullato_in'] = $quando;
        }
        $this->forceFill($attrs)->save();
    }

    /** @param  Builder<self>  $query */
    public function scopePerIdRecente(Builder $query): Builder
    {
        return $query->orderByDesc('id');
    }

    /** Codice pubblico O{id} (non è colonna DB). */
    public function getCodiceAttribute(): string
    {
        return CodiceOrdine::format((int) $this->id);
    }

    /** Alias compatibilità viste che usavano numero progressivo. */
    public function getNumeroAttribute(): int
    {
        return (int) $this->id;
    }

    /** Codice stato (non_pagato, pagato, annullato) per compatibilità. */
    public function getStatoAttribute(): string
    {
        if ($this->relationLoaded('statoOrdine') && $this->statoOrdine) {
            return (string) $this->statoOrdine->codice;
        }

        return stato_ordine::codicePerId((int) $this->stato_ordine_id);
    }

    /**
     * Ordini con pagamento effettuato (stato pagato, data pagamento o totale incassato).
     * Include ordini poi annullati dopo l’incasso.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeConPagamentoRegistrato(Builder $query): Builder
    {
        $pagatoId = self::statoId(self::STATO_PAGATO);

        return $query->where(function (Builder $q) use ($pagatoId): void {
            $q->where('stato_ordine_id', $pagatoId)
                ->orWhereNotNull('data_pagamento')
                ->orWhere('pag_effettivo_or', '>', 0)
                ->orWhere('total_pagamento', '>', 0);
        });
    }

    /** @deprecated Usare pag_effettivo_or se pagato, altrimenti total_pagamento */
    public function getTotaleIvatoAttribute(): ?float
    {
        if ($this->haStato(self::STATO_PAGATO) && $this->pag_effettivo_or !== null) {
            return (float) $this->pag_effettivo_or;
        }

        return $this->total_pagamento !== null ? (float) $this->total_pagamento : null;
    }

    /** @deprecated Totale netto da dettaglio_json / costo_servizo */
    public function getTotaleNettoIvaEscAttribute(): float
    {
        return (float) $this->costo_servizo;
    }

    /** @deprecated Usare metodo_pagamento_ordinis_id */
    public function getIdMetodoPagamentoOrdinisAttribute(): ?int
    {
        return $this->metodo_pagamento_ordinis_id !== null ? (int) $this->metodo_pagamento_ordinis_id : null;
    }

    public function setIdMetodoPagamentoOrdinisAttribute(?int $value): void
    {
        $this->attributes['metodo_pagamento_ordinis_id'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statoOrdine(): BelongsTo
    {
        return $this->belongsTo(stato_ordine::class, 'stato_ordine_id');
    }

    public function metodoPagamentoOrdine(): BelongsTo
    {
        return $this->belongsTo(metodo_pagamento_ordine::class, 'metodo_pagamento_ordinis_id');
    }

    /** @deprecated Usare metodoPagamentoOrdine() */
    public function metodoPagamento(): BelongsTo
    {
        return $this->metodoPagamentoOrdine();
    }

    public function spedizioni(): HasMany
    {
        return $this->hasMany(spedizione::class, 'ordine_id');
    }

    public function rimborsi(): HasMany
    {
        return $this->hasMany(rimborso::class, 'ordine_id');
    }
}
