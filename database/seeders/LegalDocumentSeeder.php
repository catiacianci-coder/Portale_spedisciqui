<?php

namespace Database\Seeders;

use App\Models\LegalDocumentVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFromZendesk(
            LegalDocumentVersion::SLUG_TERMOS,
            'Condizioni Valide a partire dal 20/01/2026',
            'Termini e condizioni di utilizzo',
            '2026-01-20',
            'data/termini_legali.html',
        );

        $this->seedFromZendesk(
            LegalDocumentVersion::SLUG_REEMBOLSO,
            'Politica di rimborso — aggiornamento giugno 2026',
            'Politica di rimborso',
            '2026-06-12',
            'data/politica_rimborso.html',
        );

        $this->seedFromZendesk(
            LegalDocumentVersion::SLUG_PRIVACIDADE,
            'Informativa sul Trattamento dei Dati Personali (Privacy Policy)',
            'Informativa sulla privacy',
            '2026-01-22',
            'data/politica_privacy.html',
        );

        $this->seedFromZendesk(
            LegalDocumentVersion::SLUG_COOKIES,
            'Informativa sui Cookie (Cookie Policy) - Ai sensi del Provvedimento del Garante Privacy dell\'8 maggio 2014 e del Regolamento UE 2016/679 (GDPR)',
            'Politica dei cookie',
            '2026-01-22',
            'data/politica_cookie.html',
        );

        $this->seedPlaceholder(
            LegalDocumentVersion::SLUG_CONDICOES_WALLET,
            'Condizioni Wallet (ricarica)',
            '<p>Testo da completare. Descrivi qui le condizioni del pagamento con Wallet mostrate nel pop-up della pagina Ricarica wallet.</p>',
        );
    }

    private function seedFromZendesk(
        string $slug,
        string $titulo,
        string $defaultTitulo,
        string $vigenteDesde,
        string $relativeHtmlFile,
    ): void {
        if (LegalDocumentVersion::query()->where('slug', $slug)->whereNotNull('publicado_em')->exists()) {
            return;
        }

        $path = database_path('seeders/'.$relativeHtmlFile);
        $html = File::exists($path) ? trim(File::get($path)) : '<p>Contenuto non disponibile.</p>';

        LegalDocumentVersion::query()->create([
            'slug' => $slug,
            'titulo' => $titulo !== '' ? $titulo : LegalDocumentVersion::defaultTituloForSlug($slug),
            'conteudo_html' => $html,
            'vigente_desde' => $vigenteDesde,
            'publicado_em' => now(),
            'published_by_user_id' => null,
        ]);
    }

    private function seedPlaceholder(string $slug, string $titulo, string $html): void
    {
        if (LegalDocumentVersion::query()->where('slug', $slug)->exists()) {
            return;
        }

        LegalDocumentVersion::query()->create([
            'slug' => $slug,
            'titulo' => $titulo,
            'conteudo_html' => $html,
            'vigente_desde' => null,
            'publicado_em' => null,
            'published_by_user_id' => null,
        ]);
    }
}
