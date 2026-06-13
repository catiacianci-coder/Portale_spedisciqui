<?php

namespace Database\Seeders;

use App\Models\LegalDocumentVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Importa privacy e cookie da Zendesk (sostituisce le bozze placeholder se presenti).
 */
class ImportZendeskPrivacyCookieSeeder extends Seeder
{
    public function run(): void
    {
        $this->importPublished(
            LegalDocumentVersion::SLUG_PRIVACIDADE,
            'Informativa sul Trattamento dei Dati Personali (Privacy Policy)',
            '2026-01-22',
            'data/politica_privacy.html',
        );

        $this->importPublished(
            LegalDocumentVersion::SLUG_COOKIES,
            'Informativa sui Cookie (Cookie Policy) - Ai sensi del Provvedimento del Garante Privacy dell\'8 maggio 2014 e del Regolamento UE 2016/679 (GDPR)',
            '2026-01-22',
            'data/politica_cookie.html',
        );
    }

    private function importPublished(string $slug, string $titulo, string $vigenteDesde, string $relativeHtmlFile): void
    {
        if (LegalDocumentVersion::query()->where('slug', $slug)->whereNotNull('publicado_em')->exists()) {
            return;
        }

        $path = database_path('seeders/'.$relativeHtmlFile);
        $html = File::exists($path) ? trim(File::get($path)) : '<p>Contenuto non disponibile.</p>';

        $existing = LegalDocumentVersion::query()->where('slug', $slug)->first();
        if ($existing !== null) {
            $existing->update([
                'titulo' => $titulo,
                'conteudo_html' => $html,
                'vigente_desde' => $vigenteDesde,
                'publicado_em' => now(),
            ]);

            return;
        }

        LegalDocumentVersion::query()->create([
            'slug' => $slug,
            'titulo' => $titulo,
            'conteudo_html' => $html,
            'vigente_desde' => $vigenteDesde,
            'publicado_em' => now(),
            'published_by_user_id' => null,
        ]);
    }
}
