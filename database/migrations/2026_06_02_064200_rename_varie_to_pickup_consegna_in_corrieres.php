<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE corrieres CHANGE COLUMN varie_2 pickup TEXT NULL');
        DB::statement('ALTER TABLE corrieres CHANGE COLUMN varie_3 consegna TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE corrieres CHANGE COLUMN pickup varie_2 TEXT NULL');
        DB::statement('ALTER TABLE corrieres CHANGE COLUMN consegna varie_3 TEXT NULL');
    }
};
