<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONTRACTS_BY_CORRIERE = [
        4 => 'eyJpdiI6IlFhejdRay9KcUgyVFYrMlQvN25tSWc9PSIsInZhbHVlIjoieEpEOENqY0J4NUZxVi9sZUtRTmsrUE5CL0huZUlwTkRrbU1ldzhVbEFmcz0iLCJtYWMiOiI3MWViZjFjMjgzZjY2NDc3YTI1NTQ1ZjM4NmM4NmQ0MmIwOGZlZDk3NzRmOTUyMGVmNGQ5NjU5MmVhMzFlZTk2IiwidGFnIjoiIn0=',
        5 => 'eyJpdiI6IlFQMDdtcXBXZENBRGZXL3doQlFyTmc9PSIsInZhbHVlIjoieUcwQUR4Mndybzl2UUFsakIyc0VqMHhiQUozTDlvSUgyZUpjUHZGVGdXTT0iLCJtYWMiOiIxZjA5Njk4YjkzYTA2ZGMxMDQyNjViOWIyZDczN2UzZTE1ZTM5MWQ0OTdjYzM4ZTc3MjEwNTFmYTc1NmU5ZjNjIiwidGFnIjoiIn0=',
        13 => 'eyJpdiI6Ii9NZnM5WGIrM1VhYzJ4L0p4MGgzdGc9PSIsInZhbHVlIjoiNkZaNG92dTJjZEVWTlRzN1QyeFNLWDhGNXFkYXdRQ1FFVVFUckdQeXR3az0iLCJtYWMiOiIzZDc2MGNiNzEwYjIwYmQ5YzQ2YTRkMDE5NDg2ZjdjYzlhYmY2ZDE5ZjBhMTc5N2YwMTM3NDVhMzk2MzA5ZjMyIiwidGFnIjoiIn0=',
    ];

    private const LEGACY_PARAM_DENOMS = [
        'spedisci_online_eamulti_contract_gls_light',
        'spedisci_online_eamulti_contract_gls_standard',
        'spedisci_online_eamulti_contract_poste_delivery_business_standard',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('corrieres', 'codice_servizio')) {
            Schema::table('corrieres', function ($table) {
                $table->string('codice_servizio', 512)->nullable()->change();
            });
        }

        $now = now();

        foreach (self::CONTRACTS_BY_CORRIERE as $id => $codice) {
            DB::table('corrieres')
                ->where('id', $id)
                ->update([
                    'codice_servizio' => $codice,
                    'contract_code' => null,
                    'updated_at' => $now,
                ]);
        }

        DB::table('parametri_globalis')
            ->whereIn('denominazione', self::LEGACY_PARAM_DENOMS)
            ->delete();
    }

    public function down(): void
    {
        $now = now();

        foreach (self::CONTRACTS_BY_CORRIERE as $id => $codice) {
            DB::table('corrieres')
                ->where('id', $id)
                ->update([
                    'codice_servizio' => null,
                    'contract_code' => $codice,
                    'updated_at' => $now,
                ]);
        }

        if (Schema::hasColumn('corrieres', 'codice_servizio')) {
            Schema::table('corrieres', function ($table) {
                $table->string('codice_servizio', 64)->nullable()->change();
            });
        }
    }
};
