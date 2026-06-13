<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Anagrafica extends Model
{
    use HasFactory;

    protected $table = 'anagrafiche';

    protected $fillable = [
        'user_id',
        'attivo',
        'codice_fiscale',
        'partita_iva',
        'denominazione_ragione_sociale',
        'nome',
        'cognome',
        'indirizzo',
        'civico',
        'cap',
        'citta',
        'provincia',
        'telefono',
        'pec',
        'codice_sdi',
    ];

    protected function casts(): array
    {
        return [
            'attivo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeAttiva(Builder $query): Builder
    {
        return $query->where('attivo', true);
    }

    /**
     * Nuova revisione: disattiva le precedenti e crea l’unica riga attiva (indirizzo + fatturazione).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function creaRevisioneAttiva(array $attributes): self
    {
        $userId = (int) ($attributes['user_id'] ?? 0);
        if ($userId < 1) {
            throw new \InvalidArgumentException('user_id obbligatorio per creare anagrafica attiva.');
        }

        return DB::transaction(function () use ($attributes, $userId) {
            static::query()->where('user_id', $userId)->update(['attivo' => false]);
            $attributes['attivo'] = true;

            return static::query()->create($attributes);
        });
    }
}