<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocumentVersion extends Model
{
    public const SLUG_TERMOS = 'termos';

    public const SLUG_PRIVACIDADE = 'privacidade';

    public const SLUG_COOKIES = 'cookies';

    public const SLUG_REEMBOLSO = 'reembolso';

    public const SLUG_CONDICOES_WALLET = 'condicoes_wallet';

    /**
     * @return list<string>
     */
    public static function allowedSlugs(): array
    {
        return [
            self::SLUG_TERMOS,
            self::SLUG_PRIVACIDADE,
            self::SLUG_COOKIES,
            self::SLUG_REEMBOLSO,
            self::SLUG_CONDICOES_WALLET,
        ];
    }

    public static function defaultTituloForSlug(string $slug): string
    {
        return match ($slug) {
            self::SLUG_PRIVACIDADE => 'Informativa sulla privacy',
            self::SLUG_COOKIES => 'Politica dei cookie',
            self::SLUG_REEMBOLSO => 'Politica di rimborso',
            self::SLUG_CONDICOES_WALLET => 'Condizioni Wallet (ricarica)',
            default => 'Termini e condizioni di utilizzo',
        };
    }

    public static function publicRouteNameForSlug(string $slug): ?string
    {
        return match ($slug) {
            self::SLUG_TERMOS => 'termini.legali',
            self::SLUG_PRIVACIDADE => 'politica.privacy',
            self::SLUG_COOKIES => 'politica.cookie',
            self::SLUG_REEMBOLSO => 'politica.rimborso',
            default => null,
        };
    }

    public static function labelPaginaPublica(string $slug): string
    {
        return match ($slug) {
            self::SLUG_PRIVACIDADE => 'Informativa privacy',
            self::SLUG_COOKIES => 'Politica cookie',
            self::SLUG_REEMBOLSO => 'Politica di rimborso',
            self::SLUG_CONDICOES_WALLET => 'Condizioni Wallet',
            default => 'Termini legali',
        };
    }

    public static function ultimaVersaoPublicada(string $slug): ?self
    {
        if (! in_array($slug, self::allowedSlugs(), true)) {
            return null;
        }

        return self::query()
            ->where('slug', $slug)
            ->whereNotNull('publicado_em')
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->first();
    }

    protected $fillable = [
        'slug',
        'titulo',
        'conteudo_html',
        'vigente_desde',
        'publicado_em',
        'published_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vigente_desde' => 'date',
            'publicado_em' => 'datetime',
        ];
    }

    public function publishedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function isPublicado(): bool
    {
        return $this->publicado_em !== null;
    }
}
