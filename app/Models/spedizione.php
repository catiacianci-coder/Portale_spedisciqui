<?php

namespace App\Models;

use App\Support\CodiceInternoSpedizione;
use App\Support\SpedizioneCampiPersistenza;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class spedizione extends Model
{
    protected $table = 'spedizionis';

    /** @deprecated Non si usa più il prefisso COD-; vedi {@see CodiceInternoSpedizione}. */
    public const PREFIX_CODICE_INTERNO = '';

    protected $fillable = [
        'codice_interno',
        'id_shipment',
        'user_id',
        'ordine_id',
        'stripe_payment_intent_id',
        'revolut_transaction_id',
        'spedizione_stato_id',
        'carrello_id',
        'tipo_id',
        'id_codice_servizio',
        'codice_servizio',
        'service_description',
        'corriere',
        'nome_o',
        'cognome_o',
        'ragione_sociale_o',
        'cap_o',
        'citta_o',
        'indirizzo_o',
        'numero_o',
        'frazione_o',
        'stato_o',
        'tel_o',
        'email_o',
        'note_o',
        'nome_d',
        'sobrenome_d',
        'ragione_sociale_d',
        'cap_d',
        'citta_d',
        'indirizzo_d',
        'numero_d',
        'frazione_d',
        'stato_d',
        'tel_d',
        'email_d',
        'note_d',
        'to_service_point',
        'nome_punto',
        'to_post_number',
        'altezza',
        'larghezza',
        'spessore',
        'peso',
        'cancellata_il',
        'compensata',
        'padre_reso',
        'padre_comp',
        'tracking',
        'etiqueta_pdf_path',
        'ldv_emessa_il',
        'ldverro',
        'tracking_status',
        'traking_evento_em',
        'traking_consultato_il',
        'tracking_errore',
        'tracking_evento',
        'data_ritiro',
        'codice_reso',
        'esiste_integrazione',
        'reso',
    ];

    protected function casts(): array
    {
        return [
            'altezza' => 'float',
            'larghezza' => 'float',
            'spessore' => 'float',
            'peso' => 'float',
            'compensata' => 'boolean',
            'ldverro' => 'boolean',
            'data_ritiro' => 'datetime',
            'cancellata_il' => 'datetime',
            'ldv_emessa_il' => 'datetime',
            'traking_evento_em' => 'datetime',
            'traking_consultato_il' => 'datetime',
            'reso' => 'boolean',
            'esiste_integrazione' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $row): void {
            $attuale = trim((string) ($row->codice_interno ?? ''));
            if ($attuale !== '' && ! preg_match('/^COD-\d+$/i', $attuale)) {
                return;
            }
            $row->forceFill([
                'codice_interno' => CodiceInternoSpedizione::assegnaUnivoco($row->created_at ?? now()),
            ])->saveQuietly();
        });
    }

    /** Codice tracking corriere (colonna `tracking`). */
    public function codigoRastreio(): ?string
    {
        $t = trim((string) ($this->tracking ?? ''));

        return $t !== '' ? $t : null;
    }

    /** Spedizioni pagate collegate a un ordine pagato. */
    public function scopePagasNoPedido(Builder $query, int $ordineId): Builder
    {
        return $query
            ->where('ordine_id', $ordineId)
            ->where('spedizione_stato_id', stato_spedizione::PAGATA)
            ->whereHas('ordine', fn (Builder $b) => $b->conStatoCodice(ordine::STATO_PAGATO));
    }

    /** Senza codice tracking (non ancora generato o non assegnato). */
    public function scopeSemRastreio(Builder $query): Builder
    {
        return $query->where(function (Builder $w): void {
            $w->whereNull('tracking')->orWhere('tracking', '');
        });
    }

    public function prezzoNettoCliente(): ?float
    {
        return SpedizioneCampiPersistenza::prezzoNettoDaOrdine($this);
    }

    public function prezzoClienteIvato(): ?float
    {
        return SpedizioneCampiPersistenza::prezzoClienteIvatoDaOrdine($this);
    }

    public function prezzoClienteIvatoWallet(): ?float
    {
        return SpedizioneCampiPersistenza::prezzoClienteIvatoWalletDaOrdine($this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(ordine::class, 'ordine_id');
    }

    public function spedizioneStato(): BelongsTo
    {
        return $this->belongsTo(stato_spedizione::class, 'spedizione_stato_id');
    }

    /** @deprecated Usare spedizioneStato() */
    public function statoInternoSpedizione(): BelongsTo
    {
        return $this->spedizioneStato();
    }

    public function tipoSpedizione(): BelongsTo
    {
        return $this->belongsTo(tipo_spedizone::class, 'tipo_id');
    }

    public function corriereRecord(): BelongsTo
    {
        return $this->belongsTo(corriere::class, 'id_codice_servizio');
    }

    public function padreReso(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_reso');
    }

    /** @deprecated Usare padreReso() */
    public function spedizionePadre(): BelongsTo
    {
        return $this->padreReso();
    }

    public function serviziAggiuntiviRighe(): HasMany
    {
        return $this->hasMany(spedizione_servizio_aggiuntivi::class, 'id_spedizionis');
    }

    public function tariffaSpedizione(): HasOne
    {
        return $this->hasOne(tariffa_spedizione::class, 'spedizione_id');
    }

    public function rimborso(): HasOne
    {
        return $this->hasOne(rimborso::class, 'spedizione_id');
    }
}
