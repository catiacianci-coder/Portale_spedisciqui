<header class="sq-header">

    {{-- Sinistra: toggle (autenticato verificato), logo ospite, link legali / assistenza / back-office --}}
    <div class="sq-header-left">
        @auth
            @if(auth()->user()->hasVerifiedEmail())
                <button type="button" class="sq-nav-toggle" aria-expanded="false" aria-controls="sq-app-sidebar">
                    <span class="sq-sr-only">Apri o chiudi il menu di navigazione</span>
                    <span class="sq-nav-toggle-bars" aria-hidden="true"></span>
                </button>
            @endif
        @endauth
        @guest
            <a href="/" class="sq-header-logo-link">
                <img src="{{ asset('images/logoheader.png') }}" alt="Logo" class="sq-header-logo">
            </a>
        @endguest

        <a href="{{ route('politica.rimborso') }}" class="sq-header-link">Politica di rimborso</a>
        <a href="{{ route('termini.legali') }}" class="sq-header-link">Termini e Condizioni</a>
        <a href="{{ route('politica.privacy') }}" class="sq-header-link">Privacy Policy</a>
        <a href="{{ route('faq.index') }}" class="sq-header-link">FAQ</a>
        @auth
            @if(auth()->user()->hasVerifiedEmail())
                <a href="{{ route('assistenza.index') }}" class="sq-header-link">Assistenza</a>
            @else
                <a href="https://spedisciqui.zendesk.com/hc/it" class="sq-header-link" target="_blank" rel="noopener noreferrer">Centrale di Assistenza</a>
            @endif
        @else
            <a href="https://spedisciqui.zendesk.com/hc/it" class="sq-header-link" target="_blank" rel="noopener noreferrer">Centrale di Assistenza</a>
        @endauth
    </div>

    {{-- Destra: nome + wallet, carrello, esci / entra --}}
    <div class="sq-header-right">
        @auth
            <div class="sq-header-user-cluster">
                <span class="sq-header-user-name" title="{{ e(auth()->user()->headerDisplayName()) }}">{{ e(auth()->user()->headerDisplayNameShort()) }}</span>
                @if(auth()->user()->hasVerifiedEmail())
                    <a href="{{ route('wallet.ricarica') }}" class="sq-header-link sq-header-link--brand">
                        Wallet ({{ number_format((float) (auth()->user()->walletSaldo?->saldo ?? 0), 2, ',', '.') }}&nbsp;€)
                    </a>
                @endif
            </div>
        @endauth
        @php
            $cartItems = session('carrello.items', []);
            $cartCount = is_array($cartItems) ? count($cartItems) : 0;
        @endphp
        @auth
            @if(auth()->user()->hasVerifiedEmail())
                @include('partials.header-notificazioni')
            @endif
        @endauth
        @auth
            @if(auth()->user()->hasVerifiedEmail() && auth()->user()->canAccessBackoffice())
                <a href="{{ route('backoffice.index') }}"
                   class="sq-header-bo-icon {{ request()->routeIs('backoffice.*') ? 'sq-header-bo-icon--active' : '' }}"
                   title="Back office"
                   aria-label="Back office">
                    <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                </a>
            @endif
        @endauth
        <a href="{{ route('carrello.index') }}" title="Carrello" aria-label="Carrello" class="sq-cart-link">
            <svg class="sq-cart-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7 4h-2l-1 2v2h2l3.6 7.59-.75 1.41c-.15.28-.24.6-.24.96 0 1.1.9 2 2 2h12v-2h-11.53l.35-.66h8.18l3.5-6.5a1 1 0 0 0-.87-1.48H8.42l-.94-2H7zm0 16a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm12 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z" fill="currentColor"/>
            </svg>
            @if ($cartCount > 0)
                <span class="sq-cart-badge">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
            @endif
        </a>

        @auth
            <form action="{{ route('logout') }}" method="POST" class="sq-header-form-logout">
                @csrf
                <button type="submit" class="sq-header-btn-logout">
                    Esci
                </button>
            </form>
        @else
            @php
                $registerHref = route('register');
                if (in_array(Route::currentRouteName(), ['preventivi', 'simulazione', 'simulazione.index', 'home'], true)) {
                    $p = request()->path();
                    $returnPath = $p === '' ? '/' : '/' . $p;
                    $registerHref = route('register', ['return' => $returnPath]);
                }
            @endphp
            <a href="{{ route('login') }}" class="sq-header-link sq-header-link--assist">Entra</a>
            <a href="{{ $registerHref }}" class="sq-header-btn-register">Registrati</a>
        @endauth
    </div>
</header>
