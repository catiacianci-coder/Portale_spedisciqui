<?php

use App\Models\nc_pratica;
use App\Services\NcPraticaPdfService;
use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
        nc_pratica::query()->orderBy('id')->chunkById(200, function ($pratiche): void {
            foreach ($pratiche as $p) {
                $p->forceFill(['numero_pratica' => 'PRATNC-'.$p->id])->saveQuietly();
            }
        });

        nc_pratica::query()->with('righe')->orderBy('id')->chunkById(100, function ($pratiche): void {
            foreach ($pratiche as $p) {
                if ($p->righe->isEmpty()) {
                    continue;
                }
                app(NcPraticaPdfService::class)->genera($p);
            }
        });
    }

    public function down(): void
    {
        nc_pratica::query()->orderBy('id')->chunkById(200, function ($pratiche): void {
            foreach ($pratiche as $p) {
                $num = 'PR-NC-'.str_pad((string) $p->id, 6, '0', STR_PAD_LEFT);
                $p->forceFill(['numero_pratica' => $num])->saveQuietly();
            }
        });
    }
};
