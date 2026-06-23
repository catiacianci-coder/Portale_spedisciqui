<?php

namespace App\Support;

final class BackofficeHub
{
    /**
     * @return list<array{id: string, label: string, description: string, route: string, icon: string}>
     */
    public static function items(): array
    {
        return [
            [
                'id' => 'utenti',
                'label' => 'Utenti',
                'description' => 'Clienti registrati: ricerca avanzata, abilitazione postagem e collegamenti operativi.',
                'route' => 'backoffice.utenti.index',
                'icon' => 'fa-solid fa-user-shield',
            ],
            [
                'id' => 'tickets',
                'label' => 'Ticket assistenza',
                'description' => 'Nuovi, aperti, in attesa e risolti: coda richieste di assistenza dei clienti.',
                'route' => 'backoffice.tickets.index',
                'icon' => 'fa-solid fa-headset',
            ],
            [
                'id' => 'errori',
                'label' => 'Errori utenti',
                'description' => 'Log errori applicativi segnalati o registrati dal portale; dettaglio stack e contesto.',
                'route' => 'backoffice.errori.index',
                'icon' => 'fa-solid fa-triangle-exclamation',
            ],
            [
                'id' => 'stripe_estratto',
                'label' => 'Estratto Stripe',
                'description' => 'Movimenti Stripe (addebiti, rimborsi, commissioni, bonifici) per periodo, con export CSV.',
                'route' => 'backoffice.stripe_estratto.index',
                'icon' => 'fa-brands fa-stripe-s',
            ],
            [
                'id' => 'rimborsi',
                'label' => 'Gestione rimborsi',
                'description' => 'Pendenti da pagare, storico rimborsati, ricerca per ordine e trasferimenti wallet.',
                'route' => 'backoffice.rimborsi.index',
                'icon' => 'fa-solid fa-money-bill-transfer',
            ],
            [
                'id' => 'ricariche',
                'label' => 'Ricariche wallet',
                'description' => 'Richieste di ricarica wallet: elenco, filtri e accredito manuale.',
                'route' => 'backoffice.ricariche.index',
                'icon' => 'fa-solid fa-coins',
            ],
            [
                'id' => 'wallet',
                'label' => 'Estratto wallet',
                'description' => 'Saldo e movimenti per cliente: filtri per data e tipo, come in area personale.',
                'route' => 'backoffice.wallet.cliente',
                'icon' => 'fa-solid fa-file-invoice-dollar',
            ],
            [
                'id' => 'nc',
                'label' => 'Non conformità',
                'description' => 'Import CSV pratiche NC e gestione back-office.',
                'route' => 'backoffice.nc.index',
                'icon' => 'fa-solid fa-file-circle-exclamation',
            ],
            [
                'id' => 'ordini',
                'label' => 'Ordini',
                'description' => 'Visione globale ordini, pagamenti e spedizioni collegate.',
                'route' => 'backoffice.ordini.index',
                'icon' => 'fa-solid fa-receipt',
            ],
            [
                'id' => 'spedizioni',
                'label' => 'Spedizioni',
                'description' => 'Elenco globale spedizioni con filtri, PDF etichetta e tracking.',
                'route' => 'backoffice.spedizioni.index',
                'icon' => 'fa-solid fa-truck-fast',
            ],
            [
                'id' => 'corrieri',
                'label' => 'Corrieri',
                'description' => 'Anagrafica corrieri, carosello home e modifica campi per servizio o per attributo.',
                'route' => 'backoffice.corrieri.index',
                'icon' => 'fa-solid fa-dolly',
            ],
            [
                'id' => 'homepage_avviso',
                'label' => 'Avviso homepage',
                'description' => 'Testo breve sopra il calcolatore spedizioni in home (megafono grigio).',
                'route' => 'backoffice.homepage_avviso.edit',
                'icon' => 'fa-solid fa-bullhorn',
            ],
            [
                'id' => 'parametri',
                'label' => 'Utilities',
                'description' => 'Parametri globali (tabella), ricarichi e configurazioni di sistema.',
                'route' => 'backoffice.utilities.index',
                'icon' => 'fa-solid fa-sliders',
            ],
            [
                'id' => 'tracking',
                'label' => 'Messaggi tracking',
                'description' => 'Mappa messaggi API corriere verso testo mostrato al cliente.',
                'route' => 'backoffice.utilities.msg_tracciamento.index',
                'icon' => 'fa-solid fa-route',
            ],
            [
                'id' => 'documenti',
                'label' => 'Gestione documenti',
                'description' => 'Termini, privacy, cookie, rimborso, condizioni wallet e testi di aiuto.',
                'route' => 'backoffice.gestao_documentos.index',
                'icon' => 'fa-solid fa-file-contract',
            ],
            [
                'id' => 'faq',
                'label' => 'Gestione FAQ',
                'description' => 'Domande frequenti pubbliche: creazione, modifica e ordine.',
                'route' => 'backoffice.faq.index',
                'icon' => 'fa-solid fa-circle-question',
            ],
        ];
    }
}
