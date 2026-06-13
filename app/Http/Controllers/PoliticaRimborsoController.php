<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use Illuminate\View\View;

class PoliticaRimborsoController extends Controller
{
    public function __invoke(): View
    {
        $versoes = LegalDocumentVersion::query()
            ->where('slug', LegalDocumentVersion::SLUG_REEMBOLSO)
            ->whereNotNull('publicado_em')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->get();

        return view('legal.documento_versoes_publico', [
            'documentPageTitle' => 'Politica di rimborso — Spedisciqui',
            'bannerTitle' => 'Politica di rimborso',
            'bannerIcon' => 'fa-money-bill-transfer',
            'emptyMessage' => 'Non c\'è ancora una politica di rimborso pubblicata. Riprova più tardi o contatta l\'assistenza.',
            'versoes' => $versoes,
        ]);
    }
}
