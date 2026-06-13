<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use Illuminate\View\View;

class TerminiLegaliController extends Controller
{
    public function __invoke(): View
    {
        $versoes = LegalDocumentVersion::query()
            ->where('slug', LegalDocumentVersion::SLUG_TERMOS)
            ->whereNotNull('publicado_em')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->get();

        return view('legal.documento_versoes_publico', [
            'documentPageTitle' => 'Termini legali — Spedisciqui',
            'bannerTitle' => 'Termini e condizioni',
            'bannerIcon' => 'fa-scale-balanced',
            'emptyMessage' => 'Non ci sono ancora termini pubblicati. Riprova più tardi o contatta l\'assistenza.',
            'versoes' => $versoes,
        ]);
    }
}
