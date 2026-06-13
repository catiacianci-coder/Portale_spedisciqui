<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Struttura da storage/app/tabella_s_a_c.csv — servizi aggiuntivi per corriere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('spedizione_servizio_aggiuntivis')) {
            Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
                if (Schema::hasColumn('spedizione_servizio_aggiuntivis', 'id_servizi_aggiuntivi')) {
                    $table->dropForeign('fk_spsa_srv');
                    $table->dropIndex('idx_spsa_sped_srv');
                    $table->dropColumn('id_servizi_aggiuntivi');
                }
            });
            if (! Schema::hasColumn('spedizione_servizio_aggiuntivis', 'testo_servizio')) {
                Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
                    $table->string('testo_servizio', 120)->nullable()->after('id_corrieri_servizi_aggiuntivis');
                });
            }
        }

        if (Schema::hasTable('spedizione_servizio_aggiuntivis') && $this->foreignKeyExists('spedizione_servizio_aggiuntivis', 'fk_spsa_csrv')) {
            Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
                $table->dropForeign('fk_spsa_csrv');
            });
        }

        if (! Schema::hasTable('corrieri_servizi_aggiuntivis')
            || ! Schema::hasColumn('corrieri_servizi_aggiuntivis', 'testo_servizio')) {
            Schema::dropIfExists('corrieri_servizi_aggiuntivis');
        }

        if (! Schema::hasTable('corrieri_servizi_aggiuntivis')) {
            Schema::create('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
            $table->id();
            $table->string('fonte_servizio', 32)->default('corriere');
            $table->foreignId('id_tipo')->nullable()->constrained('tipo_spediziones')->nullOnDelete();
            $table->foreignId('id_corriere')->constrained('corrieres')->cascadeOnDelete();
            $table->string('codice_servizio_corriere', 32)->nullable();
            $table->string('testo_servizio', 120);
            $table->boolean('visualizzato')->default(true);
            $table->decimal('min_fascia', 14, 4)->nullable();
            $table->decimal('max_fascia', 14, 4)->nullable();
            $table->decimal('percentuale_cor', 12, 6)->default(0);
            $table->decimal('ricarico_k91', 12, 6)->default(0);
            $table->decimal('valore_fisso_cor', 12, 2)->default(0);
            $table->decimal('valore_fisso_k91', 12, 2)->default(0);
            $table->decimal('valore_percentuale', 12, 6)->default(0);
            $table->decimal('valore_minimo', 12, 2)->nullable();
            $table->decimal('valore_massimo', 12, 2)->nullable();
            $table->string('varie1', 255)->nullable();
            $table->string('varie2', 255)->nullable();
            $table->string('varie3', 255)->nullable();
            $table->string('varie4', 255)->nullable();
            $table->timestamps();

            $table->index(['id_corriere', 'id_tipo', 'visualizzato'], 'idx_csac_corr_tipo_vis');
            $table->index(['testo_servizio', 'id_corriere'], 'idx_csac_testo_corr');
            });
        }

        if (Schema::hasTable('spedizione_servizio_aggiuntivis')) {
            Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
                if (! $this->foreignKeyExists('spedizione_servizio_aggiuntivis', 'fk_spsa_csrv')) {
                    $table->foreign('id_corrieri_servizi_aggiuntivis', 'fk_spsa_csrv')
                        ->references('id')->on('corrieri_servizi_aggiuntivis')->nullOnDelete();
                }
            });
        }

        if (DB::table('corrieri_servizi_aggiuntivis')->count() === 0) {
            $this->importaDaCsv();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function importaDaCsv(): void
    {
        $path = storage_path('app/tabella_s_a_c.csv');
        if (! is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines || count($lines) < 2) {
            return;
        }

        $headers = array_map(function (string $h): string {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h);
        }, explode(';', $lines[0]));

        $now = now();
        foreach (array_slice($lines, 1) as $line) {
            $values = array_map('trim', explode(';', $line));
            if (count($values) !== count($headers)) {
                continue;
            }
            $row = array_combine($headers, $values);

            $idCorriere = $this->toInt($row['id_corriere'] ?? null);
            if ($idCorriere === null || $idCorriere < 1) {
                continue;
            }
            if (! DB::table('corrieres')->where('id', $idCorriere)->exists()) {
                continue;
            }

            $idTipo = $this->toInt($row['id_tipo'] ?? null);
            if ($idTipo !== null && $idTipo > 0 && ! DB::table('tipo_spediziones')->where('id', $idTipo)->exists()) {
                $idTipo = null;
            }

            $legacyId = $this->toInt($row['id'] ?? null);

            $attrs = [
                'fonte_servizio' => $this->strOrDefault($row['fonte_servizio'] ?? null, 'corriere'),
                'id_tipo' => $idTipo,
                'id_corriere' => $idCorriere,
                'codice_servizio_corriere' => $this->strOrNull($row['codice_servizio_corriere'] ?? null),
                'testo_servizio' => $this->strOrDefault($row['testo_servizio'] ?? null, 'Servizio'),
                'visualizzato' => $this->toBool($row['visualizzato'] ?? '1'),
                'min_fascia' => $this->toDecimal($row['min_fascia'] ?? null),
                'max_fascia' => $this->toDecimal($row['max_fascia'] ?? null),
                'percentuale_cor' => $this->toDecimal($row['percentuale_cor'] ?? null) ?? 0,
                'ricarico_k91' => $this->toDecimal($row['ricarico_k91'] ?? null) ?? 0,
                'valore_fisso_cor' => $this->toDecimal($row['valore_fisso_cor'] ?? null) ?? 0,
                'valore_fisso_k91' => $this->toDecimal($row['valore_fisso_k91'] ?? null) ?? 0,
                'valore_percentuale' => $this->toDecimal($row['valore_percentuale'] ?? null) ?? 0,
                'valore_minimo' => $this->toDecimal($row['valore_minimo'] ?? null),
                'valore_massimo' => $this->toDecimal($row['valore_massimo'] ?? null),
                'varie1' => $this->strOrNull($row['varie1'] ?? null),
                'varie2' => $this->strOrNull($row['varie2'] ?? null),
                'varie3' => $this->strOrNull($row['varie3'] ?? null),
                'varie4' => $this->strOrNull($row['varie4'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($legacyId !== null && $legacyId > 0) {
                DB::table('corrieri_servizi_aggiuntivis')->insert(array_merge(['id' => $legacyId], $attrs));
            } else {
                DB::table('corrieri_servizi_aggiuntivis')->insert($attrs);
            }
        }
    }

    private function strOrNull(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $t = trim($v);
        if ($t === '' || strtoupper($t) === 'NULL') {
            return null;
        }

        return $t;
    }

    private function strOrDefault(?string $v, string $default): string
    {
        return $this->strOrNull($v) ?? $default;
    }

    private function toInt(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (int) $v;
    }

    private function toDecimal(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $t = trim((string) $v);
        if (strtoupper($t) === 'NULL') {
            return null;
        }
        $t = str_replace(',', '.', $t);
        if (! is_numeric($t)) {
            return null;
        }

        return round((float) $t, 6);
    }

    private function toBool(mixed $v): bool
    {
        $t = trim((string) $v);

        return in_array($t, ['1', 'true', 'yes', 'si', 'sì'], true);
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$db, $table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }

    public function down(): void
    {
        //
    }
};
