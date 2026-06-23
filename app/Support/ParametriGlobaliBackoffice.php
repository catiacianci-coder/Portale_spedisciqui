<?php

namespace App\Support;

final class ParametriGlobaliBackoffice
{
    /** @return list<string> */
    public static function colonneTabella(): array
    {
        return [
            'denominazione',
            'valore_assoluto',
            'valore_percentuale',
            'inizio_validita',
            'fine_validita',
            'valore_testo',
            'varie',
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function regoleUpdate(): array
    {
        return [
            'denominazione' => ['required', 'string', 'max:160'],
            'valore_assoluto' => ['nullable', 'numeric'],
            'valore_percentuale' => ['nullable', 'numeric'],
            'inizio_validita' => ['nullable', 'date'],
            'fine_validita' => ['nullable', 'date', 'after_or_equal:inizio_validita'],
            'id_metodo_pagamentos' => ['nullable', 'integer', 'exists:metodo_pagamentos,id'],
            'valore_testo' => ['nullable', 'string', 'max:2000'],
            'varie' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function regoleDuplica(): array
    {
        return array_merge(self::regoleUpdate(), [
            'inizio_validita' => ['required', 'date'],
        ]);
    }

    /** @return array<string, list<mixed>> */
    public static function regoleStore(): array
    {
        return self::regoleDuplica();
    }

    /** @param  array<string, mixed>  $validated */
    public static function payloadDaValidati(array $validated): array
    {
        return [
            'denominazione' => trim((string) $validated['denominazione']),
            'valore_assoluto' => $validated['valore_assoluto'] !== null && $validated['valore_assoluto'] !== ''
                ? (float) $validated['valore_assoluto']
                : null,
            'valore_percentuale' => $validated['valore_percentuale'] !== null && $validated['valore_percentuale'] !== ''
                ? (float) $validated['valore_percentuale']
                : null,
            'inizio_validita' => $validated['inizio_validita'] ?? null,
            'fine_validita' => ($validated['fine_validita'] ?? null) !== '' ? ($validated['fine_validita'] ?? null) : null,
            'id_metodo_pagamentos' => $validated['id_metodo_pagamentos'] ?? null,
            'valore_testo' => self::nullableTrim($validated['valore_testo'] ?? null),
            'varie' => self::nullableTrim($validated['varie'] ?? null),
        ];
    }

    private static function nullableTrim(mixed $value): ?string
    {
        $s = trim((string) ($value ?? ''));

        return $s === '' ? null : $s;
    }
}
