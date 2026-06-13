<?php

use App\Models\nc_pratica_riga;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('spedizionis')->update(['esiste_integrazione' => false]);

        $idsConIntegrazioneAperta = nc_pratica_riga::query()
            ->whereNotNull('spedizione_id')
            ->where('stato_riga', nc_pratica_riga::STATO_NON_PAGATO)
            ->distinct()
            ->pluck('spedizione_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($idsConIntegrazioneAperta->isNotEmpty()) {
            DB::table('spedizionis')
                ->whereIn('id', $idsConIntegrazioneAperta->all())
                ->update(['esiste_integrazione' => true]);
        }
    }

    public function down(): void
    {
        DB::table('spedizionis')->update(['esiste_integrazione' => false]);
    }
};
