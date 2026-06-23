<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** contractCode SDA M (eamulti) — sostituisce Poste Delivery Business Standard su id 4. */
    private const SDA_M_CONTRACT = 'eyJpdiI6Ikw3ams3K0t5VE1IN3pSc1BSNS9FdkE9PSIsInZhbHVlIjoibXFzV1poQUMxc0U2TDdJN3gvRUl3ekgrMS9pSW9NSGRRY3ludkFlL0tpST0iLCJtYWMiOiIwNjg3ZDE5NDVhZGU5OTU5Yjg4YTIyNDMxZTZkOGI4OGQwZWMzMjZhYzcwNmIzZjMxZTg5MTIwNzA0MDhiM2RiIiwidGFnIjoiIn0=';

    public function up(): void
    {
        DB::table('corrieres')
            ->where('id', 4)
            ->update([
                'nome_corriere' => 'SDA',
                'nome_corriere_preventivo' => 'M',
                'nome_servizio' => 'SDA M',
                'nome_visualizzato' => 'SDA M',
                'carrier_code' => 'sda',
                'codice_servizio' => self::SDA_M_CONTRACT,
                'contract_code' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('corrieres')
            ->where('id', 4)
            ->update([
                'nome_corriere' => 'Poste Italiane',
                'nome_corriere_preventivo' => 'Delivery Business Standard',
                'nome_servizio' => 'Delivery Business Standard',
                'nome_visualizzato' => 'Poste Delivery Business Standard',
                'carrier_code' => 'postedeliverybusiness',
                'codice_servizio' => 'eyJpdiI6IlFhejdRay9KcUgyVFYrMlQvN25tSWc9PSIsInZhbHVlIjoieEpEOENqY0J4NUZxVi9sZUtRTmsrUE5CL0huZUlwTkRrbU1ldzhVbEFmcz0iLCJtYWMiOiI3MWViZjFjMjgzZjY2NDc3YTI1NTQ1ZjM4NmM4NmQ0MmIwOGZlZDk3NzRmOTUyMGVmNGQ5NjU5MmVhMzFlZTk2IiwidGFnIjoiIn0=',
                'contract_code' => null,
                'updated_at' => now(),
            ]);
    }
};
