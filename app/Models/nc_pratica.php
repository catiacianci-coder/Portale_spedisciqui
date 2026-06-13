<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class nc_pratica extends Model
{
    public const STATO_APERTO = 'aperto';

    public const STATO_CHIUSO = 'chiuso';

    public const PREFIX_NUMERO_PRATICA = 'PRATNC-';

    protected $table = 'nc_pratiche';

    protected $fillable = [
        'user_id',
        'numero_pratica',
        'stato',
        'pdf_path',
        'creato_da_user_id',
    ];

    protected static function booted(): void
    {
        static::created(function (self $row): void {
            $num = self::PREFIX_NUMERO_PRATICA.$row->id;
            if ($row->numero_pratica === $num) {
                return;
            }
            $row->forceFill(['numero_pratica' => $num])->saveQuietly();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creatoDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creato_da_user_id');
    }

    public function righe(): HasMany
    {
        return $this->hasMany(nc_pratica_riga::class, 'nc_pratica_id')->orderBy('id');
    }

    public function totaleDeltaAperto(): float
    {
        return (float) $this->righe()
            ->where('stato_riga', nc_pratica_riga::STATO_NON_PAGATO)
            ->sum('delta');
    }

    public function isParziale(): bool
    {
        $pagate = $this->righe()->where('stato_riga', nc_pratica_riga::STATO_PAGATO)->exists();
        $nonPagate = $this->righe()->where('stato_riga', nc_pratica_riga::STATO_NON_PAGATO)->exists();

        return $pagate && $nonPagate;
    }

    public function refreshStatoDaRighe(): void
    {
        if ($this->righe()->count() === 0) {
            return;
        }
        $haNonPagate = $this->righe()->where('stato_riga', nc_pratica_riga::STATO_NON_PAGATO)->exists();
        $nuovo = $haNonPagate ? self::STATO_APERTO : self::STATO_CHIUSO;
        if ($this->stato !== $nuovo) {
            $this->forceFill(['stato' => $nuovo])->saveQuietly();
        }
    }
}
