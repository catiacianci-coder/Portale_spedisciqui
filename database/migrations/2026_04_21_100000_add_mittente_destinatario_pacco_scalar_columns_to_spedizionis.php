<?php

use App\Support\SpedizioneCampiScalariFromJson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->string('mittente_nome', 120)->nullable()->after('destinatario_json');
            $table->string('mittente_cognome', 120)->nullable();
            $table->string('mittente_indirizzo', 255)->nullable();
            $table->string('mittente_numero', 32)->nullable();
            $table->string('mittente_cap', 16)->nullable();
            $table->string('mittente_citta', 160)->nullable();
            $table->string('mittente_provincia', 4)->nullable();

            $table->string('destinatario_nome', 120)->nullable();
            $table->string('destinatario_cognome', 120)->nullable();
            $table->string('destinatario_indirizzo', 255)->nullable();
            $table->string('destinatario_numero', 32)->nullable();
            $table->string('destinatario_cap', 16)->nullable();
            $table->string('destinatario_citta', 160)->nullable();
            $table->string('destinatario_provincia', 4)->nullable();

            $table->decimal('pacco_peso_kg', 12, 4)->nullable();
            $table->decimal('pacco_altezza_cm', 10, 4)->nullable();
            $table->decimal('pacco_larghezza_cm', 10, 4)->nullable();
            $table->decimal('pacco_spessore_cm', 10, 4)->nullable();
        });

        DB::table('spedizionis')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $m = self::decodeJson($row->mittente_json ?? null);
                $d = self::decodeJson($row->destinatario_json ?? null);
                $p = self::decodeJson($row->pacco_json ?? null);
                $attrs = SpedizioneCampiScalariFromJson::estrai($m, $d, $p);
                DB::table('spedizionis')->where('id', $row->id)->update($attrs);
            }
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropColumn([
                'mittente_nome',
                'mittente_cognome',
                'mittente_indirizzo',
                'mittente_numero',
                'mittente_cap',
                'mittente_citta',
                'mittente_provincia',
                'destinatario_nome',
                'destinatario_cognome',
                'destinatario_indirizzo',
                'destinatario_numero',
                'destinatario_cap',
                'destinatario_citta',
                'destinatario_provincia',
                'pacco_peso_kg',
                'pacco_altezza_cm',
                'pacco_larghezza_cm',
                'pacco_spessore_cm',
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJson(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }
};
