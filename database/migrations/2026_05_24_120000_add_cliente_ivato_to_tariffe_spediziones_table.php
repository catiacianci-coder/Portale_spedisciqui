<?php

use App\Support\TariffaSpedizioneClienteIvato;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffe_spediziones', function (Blueprint $table): void {
            if (! Schema::hasColumn('tariffe_spediziones', 'cliente_ivato')) {
                $table->decimal('cliente_ivato', 12, 2)->default(0)->after('margine_lordo');
            }
        });

        TariffaSpedizioneClienteIvato::ricalcolaTuttiGliOrdini();
    }

    public function down(): void
    {
        Schema::table('tariffe_spediziones', function (Blueprint $table): void {
            if (Schema::hasColumn('tariffe_spediziones', 'cliente_ivato')) {
                $table->dropColumn('cliente_ivato');
            }
        });
    }
};
