<?php

use App\Models\spedizione;
use App\Support\LdvStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('spedizionis')
            ->where(function ($query) {
                $query->whereNull('frazione_o')
                    ->orWhere('frazione_o', '');
            })
            ->update(['frazione_o' => 'Italia']);

        DB::table('spedizionis')
            ->where(function ($query) {
                $query->whereNull('frazione_d')
                    ->orWhere('frazione_d', '');
            })
            ->update(['frazione_d' => 'Italia']);

        DB::table('spedizionis')
            ->whereNull('tipo_id')
            ->update(['tipo_id' => 1]);

        $rows = DB::table('spedizionis')
            ->whereNotNull('etiqueta_pdf_path')
            ->where('etiqueta_pdf_path', '!=', '')
            ->get(['id', 'codice_interno', 'etiqueta_pdf_path', 'ldv_emessa_il', 'created_at']);

        foreach ($rows as $row) {
            $oldPath = trim((string) $row->etiqueta_pdf_path);
            if ($oldPath === '' || LdvStorage::isLdVRelativePath($oldPath)) {
                continue;
            }

            $legacyFull = storage_path('app/'.$oldPath);
            if (! is_file($legacyFull)) {
                continue;
            }

            $model = spedizione::query()->find((int) $row->id);
            if ($model === null) {
                continue;
            }

            $relative = LdvStorage::relativePath($model);
            $absolute = LdvStorage::absolutePath($relative);
            $dir = dirname($absolute);

            if (! is_dir($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            if (! @rename($legacyFull, $absolute)) {
                $binary = file_get_contents($legacyFull);
                if ($binary === false || file_put_contents($absolute, $binary) === false) {
                    continue;
                }
                @unlink($legacyFull);
            }

            DB::table('spedizionis')
                ->where('id', $row->id)
                ->update(['etiqueta_pdf_path' => $relative]);
        }
    }

    public function down(): void
    {
        // Nessun rollback automatico: i PDF restano nella cartella esterna.
    }
};
