<?php

use App\Models\corriere;
use App\Support\PiattaformaCorriere;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->boolean('trackingsn')->default(false)->after('punto_consegna');
            $table->string('url_tracking', 512)->nullable()->after('trackingsn');
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->text('tracking_errore')->nullable()->after('traking_consultato_il');
            $table->text('tracking_evento')->nullable()->after('tracking_errore');
        });

        corriere::query()->each(function (corriere $corriere): void {
            $haApi = PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)
                || PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)
                || PiattaformaCorriere::normalizza($corriere->piattaforma) === PiattaformaCorriere::EAMULTIEXP_SPEDISCIONLINE;

            $corriere->forceFill([
                'trackingsn' => $haApi,
            ])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropColumn(['tracking_errore', 'tracking_evento']);
        });

        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn(['trackingsn', 'url_tracking']);
        });
    }
};
