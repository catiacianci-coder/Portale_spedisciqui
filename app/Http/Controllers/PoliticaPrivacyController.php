<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use Illuminate\View\View;

class PoliticaPrivacyController extends Controller
{
    public function __invoke(): View
    {
        $versoes = LegalDocumentVersion::query()
            ->where('slug', LegalDocumentVersion::SLUG_PRIVACIDADE)
            ->whereNotNull('publicado_em')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->get();

        return view('legal.documento_versoes_publico', [
            'documentPageTitle' => 'Informativa privacy — Spedisciqui',
            'bannerTitle' => 'Informativa privacy',
            'bannerIcon' => 'fa-user-shield',
            'emptyMessage' => 'Non c\'è ancora un\'informativa privacy pubblicata. Riprova più tardi o contatta l\'assistenza.',
            'versoes' => $versoes,
        ]);
    }
}
