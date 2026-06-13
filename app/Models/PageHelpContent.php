<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageHelpContent extends Model
{
    public const PAGE_MITTENTI = 'mittenti';

    public const PAGE_DESTINATARI = 'destinatari';

    public const PAGE_IMBALLAGGI = 'imballaggi';

    protected $table = 'page_help_contents';

    protected $fillable = [
        'page_key',
        'button_label',
        'modal_title',
        'modal_content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function managedPages(): array
    {
        return [
            self::PAGE_MITTENTI => 'Mittenti',
            self::PAGE_DESTINATARI => 'Destinatari',
            self::PAGE_IMBALLAGGI => 'Imballaggi',
            'ordini' => 'Ordini',
            'ordine_dettaglio' => 'Dettaglio ordine',
            'ordine_pagamento' => 'Pagamento ordine',
            'etichette' => 'Etichette',
            'rimborso_etichette' => 'Rimborso etichette',
            'miei_rimborsi' => 'I miei rimborsi',
            'ricarica' => 'Ricarica wallet',
            'ricariche' => 'Ricariche wallet',
            'movimenti' => 'Movimenti wallet',
            'faq' => 'FAQ',
            'profilo_anagrafica' => 'Profilo: anagrafica',
            'profilo_password' => 'Profilo: password',
            'termini_legali' => 'Termini legali',
            'politica_privacy' => 'Informativa privacy',
            'politica_cookie' => 'Politica cookie',
            'politica_rimborso' => 'Politica di rimborso',
        ];
    }

    public static function forPublicPage(string $pageKey): ?self
    {
        return static::query()
            ->where('page_key', $pageKey)
            ->where('is_active', true)
            ->first();
    }

    public static function pageKeyForRoute(?string $routeName): ?string
    {
        if ($routeName === null || $routeName === '') {
            return null;
        }

        static $map = null;
        $map ??= [
            'mittenze.index' => self::PAGE_MITTENTI,
            'mittenze.create' => self::PAGE_MITTENTI,
            'mittenze.edit' => self::PAGE_MITTENTI,
            'destinatari.index' => self::PAGE_DESTINATARI,
            'destinatari.create' => self::PAGE_DESTINATARI,
            'destinatari.edit' => self::PAGE_DESTINATARI,
            'imballaggi.index' => self::PAGE_IMBALLAGGI,
            'imballaggi.create' => self::PAGE_IMBALLAGGI,
            'imballaggi.edit' => self::PAGE_IMBALLAGGI,
            'ordini.index' => 'ordini',
            'ordini.show' => 'ordine_dettaglio',
            'ordini.pagamento.show' => 'ordine_pagamento',
            'etichette.index' => 'etichette',
            'rimborso-etichette.index' => 'rimborso_etichette',
            'miei-rimborsi.index' => 'miei_rimborsi',
            'wallet.ricarica' => 'ricarica',
            'wallet.ricariche' => 'ricariche',
            'wallet.movimenti' => 'movimenti',
            'faq.index' => 'faq',
            'profilo.anagrafica' => 'profilo_anagrafica',
            'profilo.password' => 'profilo_password',
            'termini.legali' => 'termini_legali',
            'politica.privacy' => 'politica_privacy',
            'politica.cookie' => 'politica_cookie',
            'politica.rimborso' => 'politica_rimborso',
        ];

        return $map[$routeName] ?? null;
    }
}
