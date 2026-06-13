<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->reorderColumns();
        $this->applyGroupComments();
        $this->createDebugView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS spedizionis_debug');
    }

    private function reorderColumns(): void
    {
        $columns = $this->columnsMeta();
        if (empty($columns)) {
            return;
        }

        $existing = array_keys($columns);
        $desired = [
            // ID / riferimenti
            'id',
            'numero_ordine_spedizione',
            'codice_interno',
            'user_id',
            'ordine_id',
            'id_corrieres',
            'id_tariffas',
            'id_metodo_pagamentos',
            'stato_interno_spedizione_id',

            // Mittente
            'mittente_nome',
            'mittente_cognome',
            'mittente_indirizzo',
            'mittente_numero',
            'mittente_cap',
            'mittente_citta',
            'mittente_provincia',
            'mittente_json',

            // Destinatario
            'destinatario_nome',
            'destinatario_cognome',
            'destinatario_indirizzo',
            'destinatario_numero',
            'destinatario_cap',
            'destinatario_citta',
            'destinatario_provincia',
            'destinatario_json',

            // Dati spedizione / economici
            'tracking',
            'data_ritiro',
            'pacco_peso_kg',
            'pacco_altezza_cm',
            'pacco_larghezza_cm',
            'pacco_spessore_cm',
            'pacco_json',
            'importo_netto_iva_esc',
            'vendita_trasporto_netto_iva_esc',
            'vendita_servizi_netto_iva_esc',
            'nostro_acquisto_trasporto_iva_esc',
            'nostro_acquisto_servizi_iva_esc',
            'nostro_acquisto_totale_iva_esc',
            'esiste_integrazione',

            // Resi
            'reso',
            'spedizione_padre',
            'codice_reso',

            // Varie / audit
            'varie1',
            'varie2',
            'varie3',
            'varie4',
            'created_at',
            'updated_at',
        ];

        $ordered = array_values(array_intersect($desired, $existing));
        $remaining = array_values(array_diff($existing, $ordered));
        $finalOrder = array_merge($ordered, $remaining);

        $previous = null;
        foreach ($finalOrder as $col) {
            $meta = $columns[$col] ?? null;
            if (! $meta) {
                continue;
            }
            $this->alterColumn($meta, $previous);
            $previous = $col;
        }
    }

    private function applyGroupComments(): void
    {
        $columns = $this->columnsMeta();
        if (empty($columns)) {
            return;
        }

        $groups = [
            '[ID]',
            '[ID]',
            '[ID]',
            '[ID]',
            '[ID]',
            '[ID]',
            '[ID]',
            '[ID]',
            '[ID]',
            '[MITT]',
            '[MITT]',
            '[MITT]',
            '[MITT]',
            '[MITT]',
            '[MITT]',
            '[MITT]',
            '[MITT]',
            '[DEST]',
            '[DEST]',
            '[DEST]',
            '[DEST]',
            '[DEST]',
            '[DEST]',
            '[DEST]',
            '[DEST]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[SPED]',
            '[RESO]',
            '[RESO]',
            '[RESO]',
            '[VAR]',
            '[VAR]',
            '[VAR]',
            '[VAR]',
            '[VAR]',
            '[VAR]',
        ];
        $cols = [
            'id', 'numero_ordine_spedizione', 'codice_interno', 'user_id', 'ordine_id', 'id_corrieres', 'id_tariffas', 'id_metodo_pagamentos', 'stato_interno_spedizione_id',
            'mittente_nome', 'mittente_cognome', 'mittente_indirizzo', 'mittente_numero', 'mittente_cap', 'mittente_citta', 'mittente_provincia', 'mittente_json',
            'destinatario_nome', 'destinatario_cognome', 'destinatario_indirizzo', 'destinatario_numero', 'destinatario_cap', 'destinatario_citta', 'destinatario_provincia', 'destinatario_json',
            'tracking', 'data_ritiro', 'pacco_peso_kg', 'pacco_altezza_cm', 'pacco_larghezza_cm', 'pacco_spessore_cm', 'pacco_json',
            'importo_netto_iva_esc', 'vendita_trasporto_netto_iva_esc', 'vendita_servizi_netto_iva_esc', 'nostro_acquisto_trasporto_iva_esc', 'nostro_acquisto_servizi_iva_esc', 'nostro_acquisto_totale_iva_esc', 'esiste_integrazione',
            'reso', 'spedizione_padre', 'codice_reso',
            'varie1', 'varie2', 'varie3', 'varie4', 'created_at', 'updated_at',
        ];

        foreach ($cols as $i => $col) {
            $meta = $columns[$col] ?? null;
            if (! $meta) {
                continue;
            }
            $old = trim((string) ($meta->COLUMN_COMMENT ?? ''));
            $prefix = $groups[$i] ?? '[VAR]';
            $base = preg_replace('/^\[[A-Z]+\]\s*/', '', $old ?? '');
            $comment = trim($prefix.' '.($base !== '' ? $base : $col));
            $this->alterColumn($meta, null, $comment);
        }
    }

    private function createDebugView(): void
    {
        DB::statement('DROP VIEW IF EXISTS spedizionis_debug');
        DB::statement(<<<'SQL'
CREATE VIEW spedizionis_debug AS
SELECT
    -- ID / riferimenti
    s.id,
    s.numero_ordine_spedizione,
    s.codice_interno,
    s.user_id,
    s.ordine_id,
    s.id_corrieres,
    s.id_tariffas,
    s.id_metodo_pagamentos,
    s.stato_interno_spedizione_id,

    -- Mittente
    s.mittente_nome,
    s.mittente_cognome,
    s.mittente_indirizzo,
    s.mittente_numero,
    s.mittente_cap,
    s.mittente_citta,
    s.mittente_provincia,
    s.mittente_json,

    -- Destinatario
    s.destinatario_nome,
    s.destinatario_cognome,
    s.destinatario_indirizzo,
    s.destinatario_numero,
    s.destinatario_cap,
    s.destinatario_citta,
    s.destinatario_provincia,
    s.destinatario_json,

    -- Dati spedizione
    s.tracking,
    s.data_ritiro,
    s.pacco_peso_kg,
    s.pacco_altezza_cm,
    s.pacco_larghezza_cm,
    s.pacco_spessore_cm,
    s.pacco_json,
    s.importo_netto_iva_esc,
    s.vendita_trasporto_netto_iva_esc,
    s.vendita_servizi_netto_iva_esc,
    s.nostro_acquisto_trasporto_iva_esc,
    s.nostro_acquisto_servizi_iva_esc,
    s.nostro_acquisto_totale_iva_esc,
    s.esiste_integrazione,

    -- Resi
    s.reso,
    s.spedizione_padre,
    s.codice_reso,

    -- Varie / audit
    s.varie1,
    s.varie2,
    s.varie3,
    s.varie4,
    s.created_at,
    s.updated_at
FROM spedizionis s
SQL);
    }

    /** @return array<string, object> */
    private function columnsMeta(): array
    {
        $dbName = DB::getDatabaseName();
        $rows = DB::select(
            "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'spedizionis' ORDER BY ORDINAL_POSITION",
            [$dbName]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r->COLUMN_NAME] = $r;
        }

        return $out;
    }

    private function alterColumn(object $meta, ?string $after = null, ?string $overrideComment = null): void
    {
        $name = (string) $meta->COLUMN_NAME;
        $type = (string) $meta->COLUMN_TYPE;
        $nullable = ((string) $meta->IS_NULLABLE) === 'YES' ? ' NULL' : ' NOT NULL';
        $default = $this->defaultSql($meta);
        $extra = trim((string) ($meta->EXTRA ?? ''));
        $commentText = $overrideComment ?? (string) ($meta->COLUMN_COMMENT ?? '');
        $comment = $commentText !== '' ? " COMMENT '".$this->escapeSqlString($commentText)."'" : '';
        $pos = $after ? ' AFTER `'.$after.'`' : ' FIRST';
        $extraSql = $extra !== '' ? ' '.$extra : '';

        $sql = "ALTER TABLE `spedizionis` MODIFY COLUMN `{$name}` {$type}{$nullable}{$default}{$extraSql}{$comment}{$pos}";
        DB::statement($sql);
    }

    private function defaultSql(object $meta): string
    {
        $default = $meta->COLUMN_DEFAULT;
        if ($default === null) {
            return '';
        }

        $raw = (string) $default;
        $upper = strtoupper($raw);
        if ($upper === 'NULL') {
            return ' DEFAULT NULL';
        }
        if (str_contains($upper, 'CURRENT_TIMESTAMP')) {
            return " DEFAULT {$raw}";
        }

        $dataType = strtolower((string) ($meta->DATA_TYPE ?? ''));
        $isNumeric = in_array($dataType, ['tinyint', 'smallint', 'mediumint', 'int', 'bigint', 'decimal', 'float', 'double'], true);
        if ($isNumeric && preg_match('/^-?\d+(\.\d+)?$/', $raw)) {
            return " DEFAULT {$raw}";
        }

        return " DEFAULT '".$this->escapeSqlString($raw)."'";
    }

    private function escapeSqlString(string $value): string
    {
        return str_replace("'", "''", $value);
    }
};

