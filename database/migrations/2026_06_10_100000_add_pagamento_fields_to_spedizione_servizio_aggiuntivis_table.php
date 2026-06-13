<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
            $table->decimal('costo_cliente', 12, 2)->nullable()->after('nostro_acquisto_stimato_iva_esc');
            $table->string('link_banca', 512)->nullable()->after('costo_cliente');
            $table->date('d_p_p_t')->nullable()->after('link_banca');
            $table->date('d_r_p_t')->nullable()->after('d_p_p_t');
            $table->date('d_p_p_c')->nullable()->after('d_r_p_t');
            $table->date('d_r_p_c')->nullable()->after('d_p_p_c');
        });
    }

    public function down(): void
    {
        Schema::table('spedizione_servizio_aggiuntivis', function (Blueprint $table) {
            $table->dropColumn([
                'costo_cliente',
                'link_banca',
                'd_p_p_t',
                'd_r_p_t',
                'd_p_p_c',
                'd_r_p_c',
            ]);
        });
    }
};
