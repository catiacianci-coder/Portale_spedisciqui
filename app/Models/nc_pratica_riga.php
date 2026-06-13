<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class nc_pratica_riga extends Model
{
    public const STATO_NON_PAGATO = 'non_pagato';

    public const STATO_PAGATO = 'pagato';

    protected $table = 'nc_pratica_righe';

    protected $fillable = [
        'nc_pratica_id',
        'spedizione_id',
        'codice_interno',
        'altezza_dich',
        'larghezza_dich',
        'spessore_dich',
        'peso_dich',
        'altezza_corriere',
        'larghezza_corriere',
        'spessore_corriere',
        'peso_corriere',
        'prezzo_pagato',
        'importo_dovuto',
        'delta',
        'stato_riga',
        'paid_at',
        'data_pagamento_ordine',
        'corriere_nome_visualizzato',
    ];

    protected function casts(): array
    {
        return [
            'altezza_dich' => 'float',
            'larghezza_dich' => 'float',
            'spessore_dich' => 'float',
            'peso_dich' => 'float',
            'altezza_corriere' => 'float',
            'larghezza_corriere' => 'float',
            'spessore_corriere' => 'float',
            'peso_corriere' => 'float',
            'prezzo_pagato' => 'float',
            'importo_dovuto' => 'float',
            'delta' => 'float',
            'paid_at' => 'datetime',
            'data_pagamento_ordine' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $row): void {
            nc_pratica::query()->find($row->nc_pratica_id)?->refreshStatoDaRighe();

            $currentSpedizioneId = $row->spedizione_id ? (int) $row->spedizione_id : null;
            $originalSpedizioneId = $row->getOriginal('spedizione_id')
                ? (int) $row->getOriginal('spedizione_id')
                : null;

            self::syncSpedizioneIntegrazioneFlag($currentSpedizioneId);
            if ($originalSpedizioneId !== null && $originalSpedizioneId !== $currentSpedizioneId) {
                self::syncSpedizioneIntegrazioneFlag($originalSpedizioneId);
            }
        });

        static::deleted(function (self $row): void {
            nc_pratica::query()->find($row->nc_pratica_id)?->refreshStatoDaRighe();
            self::syncSpedizioneIntegrazioneFlag($row->spedizione_id ? (int) $row->spedizione_id : null);
        });
    }

    private static function syncSpedizioneIntegrazioneFlag(?int $spedizioneId): void
    {
        if (! $spedizioneId) {
            return;
        }

        $hasIntegrazioneAperta = self::query()
            ->where('spedizione_id', $spedizioneId)
            ->where('stato_riga', self::STATO_NON_PAGATO)
            ->exists();

        spedizione::query()
            ->whereKey($spedizioneId)
            ->update(['esiste_integrazione' => $hasIntegrazioneAperta]);
    }

    public function pratica(): BelongsTo
    {
        return $this->belongsTo(nc_pratica::class, 'nc_pratica_id');
    }

    public function spedizione(): BelongsTo
    {
        return $this->belongsTo(spedizione::class, 'spedizione_id');
    }
}
