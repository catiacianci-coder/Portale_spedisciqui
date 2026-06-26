<?php

namespace App\Support;

final class BackofficeHub
{
    public const SECTION_DATABASE_DOCUMENTI = 'database_documenti';

    public const SECTION_CLIENTI_SPEDIZIONI = 'clienti_spedizioni';

    public const SECTION_MISCELLANEA = 'miscellanea';

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Gestione Clienti e spedizioni',
            ],
            [
                'id' => self::SECTION_MISCELLANEA,
                'label' => 'Miscellanea',
            ],
            [
                'id' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Database e Documenti',
                'locked' => true,
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, description: string, route: string, icon: string, section: string}>
     */
    public static function items(): array
    {
        return [
            [
                'id' => 'utenti',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Utenti',
                'description' => 'Clienti registrati: ricerca avanzata, abilitazione postagem e collegamenti operativi.',
                'route' => 'backoffice.utenti.index',
                'icon' => 'fa-solid fa-user-shield',
            ],
            [
                'id' => 'tickets',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Ticket assistenza',
                'description' => 'Nuovi, aperti, in attesa e risolti: coda richieste di assistenza dei clienti.',
                'route' => 'backoffice.tickets.index',
                'icon' => 'fa-solid fa-headset',
            ],
            [
                'id' => 'errori',
                'section' => self::SECTION_MISCELLANEA,
                'label' => 'Errori utenti',
                'description' => 'Log errori applicativi segnalati o registrati dal portale; dettaglio stack e contesto.',
                'route' => 'backoffice.errori.index',
                'icon' => 'fa-solid fa-triangle-exclamation',
            ],
            [
                'id' => 'stripe_estratto',
                'section' => self::SECTION_MISCELLANEA,
                'label' => 'Estratto Stripe',
                'description' => 'Movimenti Stripe (addebiti, rimborsi, commissioni, bonifici) per periodo, con export CSV.',
                'route' => 'backoffice.stripe_estratto.index',
                'icon' => 'fa-brands fa-stripe-s',
            ],
            [
                'id' => 'rimborsi',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Gestione rimborsi',
                'description' => 'Pendenti da pagare, storico rimborsati, ricerca per ordine e trasferimenti wallet.',
                'route' => 'backoffice.rimborsi.index',
                'icon' => 'fa-solid fa-money-bill-transfer',
            ],
            [
                'id' => 'ricariche',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Ricariche wallet',
                'description' => 'Richieste di ricarica wallet: elenco, filtri e accredito manuale.',
                'route' => 'backoffice.ricariche.index',
                'icon' => 'fa-solid fa-coins',
            ],
            [
                'id' => 'wallet',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Estratto wallet',
                'description' => 'Saldo e movimenti per cliente: filtri per data e tipo, come in area personale.',
                'route' => 'backoffice.wallet.cliente',
                'icon' => 'fa-solid fa-file-invoice-dollar',
            ],
            [
                'id' => 'nc',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Non conformità',
                'description' => 'Import CSV pratiche NC e gestione back-office.',
                'route' => 'backoffice.nc.index',
                'icon' => 'fa-solid fa-file-circle-exclamation',
            ],
            [
                'id' => 'ordini',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Ordini',
                'description' => 'Visione globale ordini, pagamenti e spedizioni collegate.',
                'route' => 'backoffice.ordini.index',
                'icon' => 'fa-solid fa-receipt',
            ],
            [
                'id' => 'spedizioni',
                'section' => self::SECTION_CLIENTI_SPEDIZIONI,
                'label' => 'Spedizioni',
                'description' => 'Elenco globale spedizioni con filtri, PDF etichetta e tracking.',
                'route' => 'backoffice.spedizioni.index',
                'icon' => 'fa-solid fa-truck-fast',
            ],
            [
                'id' => 'corrieri',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Corrieri',
                'description' => 'Anagrafica corrieri, carosello home e modifica campi per servizio o per attributo.',
                'route' => 'backoffice.corrieri.index',
                'icon' => 'fa-solid fa-dolly',
            ],
            [
                'id' => 'homepage_avviso',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Avviso homepage',
                'description' => 'Testo breve sopra il calcolatore spedizioni in home (megafono grigio).',
                'route' => 'backoffice.homepage_avviso.edit',
                'icon' => 'fa-solid fa-bullhorn',
            ],
            [
                'id' => 'parametri',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Utilities',
                'description' => 'Parametri globali (tabella), ricarichi e configurazioni di sistema.',
                'route' => 'backoffice.utilities.index',
                'icon' => 'fa-solid fa-sliders',
            ],
            [
                'id' => 'tracking',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Messaggi tracking',
                'description' => 'Mappa messaggi API corriere verso testo mostrato al cliente.',
                'route' => 'backoffice.utilities.msg_tracciamento.index',
                'icon' => 'fa-solid fa-route',
            ],
            [
                'id' => 'metodi_pagamento',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Metodi di pagamento',
                'description' => 'Configurazione metodi per ordini, ricariche wallet e rimborsi.',
                'route' => 'backoffice.metodi_pagamento.index',
                'icon' => 'fa-solid fa-credit-card',
            ],
            [
                'id' => 'documenti',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Gestione documenti',
                'description' => 'Termini, privacy, cookie, rimborso, condizioni wallet e testi di aiuto.',
                'route' => 'backoffice.gestao_documentos.index',
                'icon' => 'fa-solid fa-file-contract',
            ],
            [
                'id' => 'faq',
                'section' => self::SECTION_DATABASE_DOCUMENTI,
                'label' => 'Gestione FAQ',
                'description' => 'Domande frequenti pubbliche: creazione, modifica e ordine.',
                'route' => 'backoffice.faq.index',
                'icon' => 'fa-solid fa-circle-question',
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, items: list<array<string, string>>}>
     */
    public static function sectionsWithItems(): array
    {
        /** @var array<string, list<array<string, string>>> $grouped */
        $grouped = [];
        foreach (self::items() as $item) {
            $grouped[$item['section']][] = $item;
        }

        $sections = [];
        foreach (self::sections() as $section) {
            $sections[] = [
                ...$section,
                'items' => $grouped[$section['id']] ?? [],
            ];
        }

        return $sections;
    }
}
