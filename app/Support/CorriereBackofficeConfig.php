<?php

namespace App\Support;

final class CorriereBackofficeConfig
{
    /** @return list<string> */
    public static function tipoOdOptions(): array
    {
        return [
            'italia_italia',
            'origine_italias',
            'italia_destinos',
            'origine_destinos',
        ];
    }

    /**
     * @return array<string, array{label: string, type: string, rules: list<mixed>, hint?: string}>
     */
    public static function campi(): array
    {
        return [
            'nome_corriere' => [
                'label' => 'nome_corriere',
                'type' => 'text',
                'rules' => ['required', 'string', 'max:255'],
            ],
            'nome_corriere_preventivo' => [
                'label' => 'nome_corriere_preventivo',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'nome_servizio' => [
                'label' => 'nome_servizio',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'nome_visualizzato' => [
                'label' => 'nome_visualizzato',
                'type' => 'text',
                'rules' => ['required', 'string', 'max:255'],
            ],
            'nome_area' => [
                'label' => 'nome_area',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'tipo_o_d' => [
                'label' => 'tipo_o_d',
                'type' => 'select_tipo_od',
                'rules' => ['required', 'in:italia_italia,origine_italias,italia_destinos,origine_destinos'],
            ],
            'attivo' => [
                'label' => 'attivo',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
            ],
            'ord_carosello' => [
                'label' => 'ord_carosello',
                'type' => 'integer',
                'rules' => ['nullable', 'integer', 'min:0', 'max:999'],
                'hint' => '0 = escluso dal carosello home.',
            ],
            'tariffa_interna' => [
                'label' => 'tariffa_interna',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
            ],
            'id_ricarico' => [
                'label' => 'id_ricarico',
                'type' => 'ricarico',
                'rules' => ['nullable', 'integer', 'exists:ricarichi,id'],
            ],
            'istat' => [
                'label' => 'istat',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:32'],
            ],
            'numero_contratto' => [
                'label' => 'numero_contratto',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'piattaforma' => [
                'label' => 'piattaforma',
                'type' => 'textarea',
                'rules' => ['nullable', 'string', 'max:2000'],
            ],
            'carrier_code' => [
                'label' => 'carrier_code',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:64'],
            ],
            'contract_code' => [
                'label' => 'contract_code',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'codice_servizio' => [
                'label' => 'codice_servizio',
                'type' => 'textarea',
                'rules' => ['nullable', 'string', 'max:512'],
            ],
            'fuel' => [
                'label' => 'fuel',
                'type' => 'decimal',
                'rules' => ['nullable', 'numeric', 'min:0'],
            ],
            'soglia_esenzione' => [
                'label' => 'soglia_esenzione',
                'type' => 'decimal',
                'rules' => ['nullable', 'numeric', 'min:0'],
            ],
            'sicilia' => [
                'label' => 'sicilia',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
            ],
            'calabria' => [
                'label' => 'calabria',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
            ],
            'sardegna' => [
                'label' => 'sardegna',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
            ],
            'pickup' => [
                'label' => 'pickup',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'consegna' => [
                'label' => 'consegna',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'punto_ritiro' => [
                'label' => 'punto_ritiro',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'punto_consegna' => [
                'label' => 'punto_consegna',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:255'],
            ],
            'trackingsn' => [
                'label' => 'trackingsn',
                'type' => 'boolean',
                'rules' => ['nullable', 'boolean'],
            ],
            'url_tracking' => [
                'label' => 'url_tracking',
                'type' => 'text',
                'rules' => ['nullable', 'string', 'max:512'],
            ],
        ];
    }

    public static function hasCampo(string $campo): bool
    {
        return array_key_exists($campo, self::campi());
    }

    /**
     * @return list<mixed>
     */
    public static function rulesFor(string $campo): array
    {
        return self::campi()[$campo]['rules'] ?? ['nullable'];
    }

    public static function normalize(string $campo, mixed $raw): mixed
    {
        $type = self::campi()[$campo]['type'] ?? 'text';

        return match ($type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'integer' => $raw === '' || $raw === null ? null : (int) $raw,
            'decimal' => $raw === '' || $raw === null ? null : (float) str_replace(',', '.', (string) $raw),
            'ricarico' => $raw === '' || $raw === null ? null : (int) $raw,
            'textarea', 'text', 'select_tipo_od' => self::nullableString($raw),
            default => self::nullableString($raw),
        };
    }

    private static function nullableString(mixed $raw): ?string
    {
        $s = trim((string) ($raw ?? ''));

        return $s === '' ? null : $s;
    }

    public static function displayValue(string $campo, mixed $value): string
    {
        $type = self::campi()[$campo]['type'] ?? 'text';

        if ($value === null || $value === '') {
            return '—';
        }

        return match ($type) {
            'boolean' => $value ? 'Sì' : 'No',
            default => (string) $value,
        };
    }

    public static function labelCorriere(\App\Models\corriere $c): string
    {
        $nome = trim((string) $c->nome_visualizzato);

        return $nome !== '' ? $nome : (string) $c->nome_corriere;
    }
}
