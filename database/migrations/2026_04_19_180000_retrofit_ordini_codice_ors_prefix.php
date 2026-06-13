<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Allinea i codici ordine al formato ORS-{id} (prima: ORD- + stringa casuale).
     */
    public function up(): void
    {
        DB::table('ordinis')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                $id = (int) $row->id;
                DB::table('ordinis')
                    ->where('id', $id)
                    ->update(['codice' => 'ORS-'.$id]);
            }
        });
    }

    public function down(): void
    {
        // Irreversibile: non ripristiniamo codici alfanumerici precedenti.
    }
};
