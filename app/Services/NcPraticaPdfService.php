<?php

namespace App\Services;

use App\Models\nc_pratica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class NcPraticaPdfService
{
    public function genera(nc_pratica $pratica): void
    {
        $pratica->load(['righe.spedizione', 'user']);

        $dir = storage_path('app/nc-pratiche/'.$pratica->id);
        File::ensureDirectoryExists($dir);

        $baseName = ($pratica->numero_pratica !== null && $pratica->numero_pratica !== '')
            ? $pratica->numero_pratica
            : nc_pratica::PREFIX_NUMERO_PRATICA.$pratica->id;

        $relative = 'nc-pratiche/'.$pratica->id.'/'.$baseName.'.pdf';
        $full = storage_path('app/'.$relative);

        $legacy = $dir.DIRECTORY_SEPARATOR.'pratica.pdf';
        if (File::isFile($legacy)) {
            File::delete($legacy);
        }

        Pdf::loadView('pdf.nc-pratica', ['pratica' => $pratica])
            ->setPaper('a4', 'portrait')
            ->save($full);

        $pratica->forceFill(['pdf_path' => $relative])->saveQuietly();
    }
}
