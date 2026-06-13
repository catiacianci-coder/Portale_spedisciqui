<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use Illuminate\View\View;

class PoliticaCookieController extends Controller
{
    public function __invoke(): View
    {
        $versoes = LegalDocumentVersion::query()
            ->where('slug', LegalDocumentVersion::SLUG_COOKIES)
            ->whereNotNull('publicado_em')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->get();

        return view('legal.documento_versoes_publico', [
            'documentPageTitle' => 'Politica cookie — Spedisciqui',
            'bannerTitle' => 'Politica dei cookie',
            'bannerIcon' => 'fa-cookie-bite',
            'emptyMessage' => 'Non c\'è ancora una politica cookie pubblicata. Riprova più tardi o contatta l\'assistenza.',
            'versoes' => $versoes,
        ]);
    }
}
