<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class stato_ordine extends Model
{
    protected $table = 'stato_ordinis';

    protected $fillable = ['codice', 'denominazione'];

    /** @var array<string, int>|null */
    private static ?array $idPerCodice = null;

    /** @var array<int, string>|null */
    private static ?array $codicePerId = null;

    public static function idPerCodice(string $codice): int
    {
        self::caricaCache();

        return (int) (self::$idPerCodice[$codice] ?? 1);
    }

    public static function codicePerId(int $id): string
    {
        self::caricaCache();

        return (string) (self::$codicePerId[$id] ?? 'non_pagato');
    }

    private static function caricaCache(): void
    {
        if (self::$idPerCodice !== null) {
            return;
        }
        $rows = self::query()->get(['id', 'codice']);
        self::$idPerCodice = [];
        self::$codicePerId = [];
        foreach ($rows as $row) {
            self::$idPerCodice[$row->codice] = (int) $row->id;
            self::$codicePerId[(int) $row->id] = $row->codice;
        }
    }

    public function ordini(): HasMany
    {
        return $this->hasMany(ordine::class, 'stato_ordine_id');
    }
}
