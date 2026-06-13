<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('corrieri_servizi_aggiuntivis')) {
            Schema::create('corrieri_servizi_aggiuntivis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_corriere')->constrained('corrieres')->cascadeOnDelete();
                $table->foreignId('id_servizi_aggiuntivi')->constrained('servizi_aggiuntivis')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['id_corriere', 'id_servizi_aggiuntivi'], 'corriere_servizio_aggiuntivo_unique');
            });
        }

        if (DB::table('corrieri_servizi_aggiuntivis')->count() > 0) {
            return;
        }

        $idsCorrieri = [1, 2, 3];
        $haCorrieri = DB::table('corrieres')->whereIn('id', $idsCorrieri)->count() === count($idsCorrieri);
        $haServizi = DB::table('servizi_aggiuntivis')->whereIn('id', [1, 2, 3, 4])->count() === 4;

        if (! $haCorrieri || ! $haServizi) {
            return;
        }

        $poste = 1;
        $velociraptor = 2;
        $tirannosauro = 3;

        $now = now();

        $rows = [];

        foreach ([1, 2, 3, 4] as $sid) {
            $rows[] = ['id_corriere' => $poste, 'id_servizi_aggiuntivi' => $sid, 'created_at' => $now, 'updated_at' => $now];
        }

        foreach ([1, 2, 3, 4] as $sid) {
            $rows[] = ['id_corriere' => $tirannosauro, 'id_servizi_aggiuntivi' => $sid, 'created_at' => $now, 'updated_at' => $now];
        }

        foreach ([2, 3, 4] as $sid) {
            $rows[] = ['id_corriere' => $velociraptor, 'id_servizi_aggiuntivi' => $sid, 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table('corrieri_servizi_aggiuntivis')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('corrieri_servizi_aggiuntivis');
    }
};
