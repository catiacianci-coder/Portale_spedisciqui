<!DOCTYPE html>
<html lang="it" class="sq-html" data-sq-accent="teal-saffron">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Spedisciqui - Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Font Awesome 6 (icone home vantaggi + riuso futuro). Per solo self-host: npm @fortawesome/fontawesome-free e build con Vite. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
</head>
<body class="sq-body">

    <div class="sq-shell">

        <div class="sq-shell-row">

            @if(Auth::check() && Auth::user()->hasVerifiedEmail())
                @include('partials.sidebar')
            @endif

            {{-- Main Content: aggiunto overflow-x hidden e min-width 0 per bloccare i 1032px --}}
            <main class="sq-main {{ request()->routeIs('backoffice.*') ? 'sq-main--backoffice' : '' }}">

                @include('partials.header')

                @php
                    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';

                    $checkoutFlowStep = match (true) {
                        $routeName === 'preventivi' => 2,
                        $routeName === 'spedizione.indirizzi' => 3,
                        $routeName === 'checkout.show' => 4,
                        in_array($routeName, ['carrello.index', 'carrello.riepilogo'], true) => 5,
                        default => null,
                    };

                    $frontTitleMap = [
                        'profilo.anagrafica' => 'Anagrafica',
                        'profilo.password' => 'Cambia password',
                        'imballaggi.index' => 'Package',
                        'imballaggi.create' => 'Nuovo Package',
                        'imballaggi.edit' => 'Modifica Package',
                        'mittenze.index' => 'Rubrica mittenti',
                        'mittenze.create' => 'Nuovo mittente',
                        'mittenze.edit' => 'Modifica mittente',
                        'destinatari.index' => 'Rubrica destinatari',
                        'destinatari.create' => 'Nuovo destinatario',
                        'destinatari.edit' => 'Modifica destinatario',
                        'resi.index' => 'Gestione resi',
                        'wallet.ricarica' => 'Ricarica wallet',
                        'wallet.ricariche' => 'Ordini wallet',
                        'tariffe_scontate.index' => 'Tariffe scontate',
                        'finanziario.fatture.index' => 'Fatture',
                        'finanziario.nc.index' => 'Pratiche NC',
                        'finanziario.nc.show' => 'Pratica NC',
                        'pagamento_nc.index' => 'Pagamento NC',
                        'carrello.index' => 'Carrello',
                        'carrello.riepilogo' => 'Riepilogo ordine',
                        'faq.index' => 'Domande frequenti (FAQ)',
                        'termini.legali' => 'Termini e condizioni',
                        'politica.privacy' => 'Informativa privacy',
                        'politica.cookie' => 'Politica dei cookie',
                        'politica.rimborso' => 'Politica di rimborso',
                    ];

                    $frontTitleIconMap = [
                        'profilo.anagrafica' => 'fa-user',
                        'profilo.password' => 'fa-key',
                        'imballaggi.index' => 'fa-box',
                        'imballaggi.create' => 'fa-box',
                        'imballaggi.edit' => 'fa-box',
                        'mittenze.index' => 'fa-truck',
                        'mittenze.create' => 'fa-truck',
                        'mittenze.edit' => 'fa-truck',
                        'destinatari.index' => 'fa-location-dot',
                        'destinatari.create' => 'fa-location-dot',
                        'destinatari.edit' => 'fa-location-dot',
                        'resi.index' => 'fa-rotate-left',
                        'wallet.ricarica' => 'fa-coins',
                        'wallet.ricariche' => 'fa-list',
                        'tariffe_scontate.index' => 'fa-tags',
                        'finanziario.fatture.index' => 'fa-file-invoice',
                        'finanziario.nc.index' => 'fa-triangle-exclamation',
                        'finanziario.nc.show' => 'fa-triangle-exclamation',
                        'pagamento_nc.index' => 'fa-credit-card',
                        'carrello.index' => 'fa-cart-shopping',
                        'carrello.riepilogo' => 'fa-clipboard-list',
                        'faq.index' => 'fa-circle-question',
                        'termini.legali' => 'fa-scale-balanced',
                        'politica.privacy' => 'fa-user-shield',
                        'politica.cookie' => 'fa-cookie-bite',
                        'politica.rimborso' => 'fa-money-bill-transfer',
                    ];

                    $backofficeTitleMap = [
                        'backoffice.ordini.index' => ['title' => 'Ordini', 'icon' => 'fa-boxes'],
                        'backoffice.spedizioni.index' => ['title' => 'Spedizioni', 'icon' => 'fa-truck'],
                        'backoffice.rimborsi.index' => ['title' => 'Gestione rimborsi', 'icon' => 'fa-money-bill-transfer'],
                        'backoffice.rimborsi.pendentes' => ['title' => 'Gestione rimborsi', 'icon' => 'fa-money-bill-transfer'],
                        'backoffice.rimborsi.rimborsati' => ['title' => 'Gestione rimborsi', 'icon' => 'fa-money-bill-transfer'],
                        'backoffice.rimborsi.per_ordine' => ['title' => 'Gestione rimborsi', 'icon' => 'fa-money-bill-transfer'],
        'backoffice.rimborsi.trasferimento_wallet' => ['title' => 'Trasferimento da wallet', 'icon' => 'fa-wallet'],
                        'backoffice.ricariche.index' => ['title' => 'Ricariche wallet', 'icon' => 'fa-coins'],
                        'backoffice.wallet.cliente' => ['title' => 'Movimenti wallet', 'icon' => 'fa-receipt'],
                        'backoffice.nc.index' => ['title' => 'Non conformità', 'icon' => 'fa-file-circle-exclamation'],
                        'backoffice.corrieri.index' => ['title' => 'Corrieri', 'icon' => 'fa-truck-fast'],
                        'backoffice.corrieri.edit' => ['title' => 'Modifica corriere', 'icon' => 'fa-truck-fast'],
                        'backoffice.parametri_globali.edit' => ['title' => 'Parametri globali', 'icon' => 'fa-sliders'],
                        'backoffice.utilities.index' => ['title' => 'Utilities', 'icon' => 'fa-screwdriver-wrench'],
                        'backoffice.homepage_avviso.edit' => ['title' => 'Avviso homepage', 'icon' => 'fa-bullhorn'],
                        'backoffice.errori.index' => ['title' => 'Errori applicativi', 'icon' => 'fa-bug'],
                        'backoffice.gestao_documentos.index' => ['title' => 'Gestione documenti', 'icon' => 'fa-file-lines'],
                        'backoffice.gestao_documentos.edit' => ['title' => 'Modifica documento', 'icon' => 'fa-file-lines'],
                        'backoffice.faq.index' => ['title' => 'Gestione FAQ', 'icon' => 'fa-circle-question'],
                        'backoffice.tickets.index' => ['title' => 'Ticket assistenza', 'icon' => 'fa-headset'],
                        'backoffice.utenti.index' => ['title' => 'Utenti', 'icon' => 'fa-user-shield'],
                        'backoffice.utenti.section' => ['title' => 'Utente', 'icon' => 'fa-user-shield'],
                        'backoffice.utilities.msg_tracciamento.index' => ['title' => 'Messaggi tracking', 'icon' => 'fa-route'],
                    ];

                    $backofficeIndexBanner = ['title' => 'Back office', 'icon' => 'fa-screwdriver-wrench'];

                    // Pagine con fascia titolo nel contenuto (ordini, spedizioni, …)
                    $frontTitleExcluded = [
                        'ordini.index',
                        'ordini.show',
                        'ordini.pagamento.show',
                        'etichette.index',
                        'spedizioni.index',
                        'rimborso-etichette.index',
                        'home',
                        'preventivi',
                        'spedizione.indirizzi',
                        'checkout.show',
                        'carrello.index',
                        'carrello.riepilogo',
                        'simulazione',
                        'simulazione.index',
                        'assistenza.index',
                        'assistenza.ticket.show',
                        'tariffe_scontate.index',
                        'wallet.movimenti',
                        'wallet.ricariche',
                        'miei-rimborsi.index',
                    ];

                    $showFrontTitleBar = ! request()->routeIs('backoffice.*')
                        && ! in_array($routeName, $frontTitleExcluded, true)
                        && isset($frontTitleMap[$routeName]);
                    $frontTitleBarText = $showFrontTitleBar ? $frontTitleMap[$routeName] : '';
                    $frontTitleBarIcon = $showFrontTitleBar ? ($frontTitleIconMap[$routeName] ?? 'fa-file-lines') : 'fa-file-lines';

                    $showBackofficeTitleBar = request()->routeIs('backoffice.*')
                        && ! request()->routeIs('backoffice.utilities.index')
                        && (request()->routeIs('backoffice.index') || isset($backofficeTitleMap[$routeName]));
                    $backofficeTitleBar = request()->routeIs('backoffice.index')
                        ? $backofficeIndexBanner
                        : ($backofficeTitleMap[$routeName] ?? null);

                    $showLayoutPageBanner = $showFrontTitleBar || $showBackofficeTitleBar;
                    $isFrontCyclePage = in_array($routeName, $frontTitleExcluded, true);
                    $isFrontEndPage = ! request()->routeIs('backoffice.*');
                    $showBackofficePageBanner = request()->routeIs('backoffice.*')
                        && (View::hasSection('pageBanner') || ($showBackofficeTitleBar && $backofficeTitleBar));
                @endphp

                @if ($showBackofficePageBanner)
                    <div class="sq-bo-top-banner">
                        @hasSection('pageBanner')
                            @yield('pageBanner')
                        @else
                            <x-sq-page-banner
                                variant="backoffice"
                                :title="$backofficeTitleBar['title']"
                                :icon="$backofficeTitleBar['icon']"
                                class="sq-page-banner--full"
                            />
                        @endif
                    </div>
                @endif

                <section class="sq-content {{ ($showLayoutPageBanner || View::hasSection('pageBanner')) ? 'sq-content--with-page-banner' : '' }} {{ $isFrontEndPage ? 'sq-content--frontend' : '' }} {{ $isFrontEndPage && ! $isFrontCyclePage ? 'sq-content--front-text-main' : '' }}">
                    @if (! request()->routeIs('backoffice.*'))
                        @hasSection('pageBanner')
                            <div class="sq-bleed-layout">
                                @yield('pageBanner')
                            </div>
                        @elseif ($showFrontTitleBar)
                            <div class="sq-bleed-layout">
                                <x-sq-page-banner
                                    :title="$frontTitleBarText"
                                    :icon="$frontTitleBarIcon"
                                    class="sq-page-banner--full"
                                />
                            </div>
                        @endif
                    @endif
                    @if ($checkoutFlowStep !== null)
                        @include('partials.checkout-stepper', ['step' => $checkoutFlowStep])
                    @endif
                    @yield('content')
                </section>

            </main>
        </div>

        @if(Auth::check() && Auth::user()->hasVerifiedEmail())
            <div class="sq-sidebar-backdrop" aria-hidden="true"></div>
        @endif

      @include('partials.footer')

    </div>
</body>
</html>
