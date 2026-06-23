<aside class="sq-sidebar" id="sq-app-sidebar">

    <div class="sq-sidebar-logo-wrap">
        <a href="/">
            <img src="{{ asset('images/logoheader.png') }}" alt="Logo" class="sq-header-logo">
        </a>
    </div>

    <nav class="sq-sidebar-nav">
        <div class="sq-sidebar-section">
            <p class="sq-sidebar-section-title">Configurazione</p>
            <ul class="sq-sidebar-list">
                <li class="sq-sidebar-item"><a href="{{ route('profilo.anagrafica') }}" class="sq-sidebar-link"><i class="fa-solid fa-user-gear sq-sidebar-ico sq-sidebar-ico--config"></i> Anagrafica</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('profilo.password') }}" class="sq-sidebar-link"><i class="fa-solid fa-key sq-sidebar-ico sq-sidebar-ico--config"></i> Cambia password</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('mittenze.index') }}" class="sq-sidebar-link"><i class="fa-solid fa-address-book sq-sidebar-ico sq-sidebar-ico--config"></i> Rubrica mittenti</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('destinatari.index') }}" class="sq-sidebar-link"><i class="fa-solid fa-user-group sq-sidebar-ico sq-sidebar-ico--config"></i> Rubrica destinatari</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('imballaggi.index') }}" class="sq-sidebar-link"><i class="fa-solid fa-box-open sq-sidebar-ico sq-sidebar-ico--config"></i> Package</a></li>
            </ul>
        </div>

        <div class="sq-sidebar-section">
            <p class="sq-sidebar-section-title">Azioni</p>
            <ul class="sq-sidebar-list">
                <li class="sq-sidebar-item"><a href="{{ route('home') }}" class="sq-sidebar-link"><i class="fa-solid fa-plus sq-sidebar-ico sq-sidebar-ico--brand"></i> Nuova spedizione</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('tariffe_scontate.index') }}" class="sq-sidebar-link @if(request()->routeIs('tariffe_scontate.*')) sq-sidebar-link--active @endif"><i class="fa-solid fa-tags sq-sidebar-ico sq-sidebar-ico--brand"></i> Tariffe scontate</a></li>
                {{-- Gestione resi: voce nascosta in sidebar; route e pagina restano attive --}}
                <li class="sq-sidebar-item" hidden aria-hidden="true"><a href="{{ route('resi.index') }}" class="sq-sidebar-link" tabindex="-1"><i class="fa-solid fa-rotate-left sq-sidebar-ico sq-sidebar-ico--brand"></i> Gestione resi</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('rimborso-etichette.index') }}" class="sq-sidebar-link @if(request()->routeIs('rimborso-etichette.*')) sq-sidebar-link--active @endif"><i class="fa-solid fa-hand-holding-dollar sq-sidebar-ico sq-sidebar-ico--brand"></i> Richiedi rimborso</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('wallet.ricarica') }}" class="sq-sidebar-link"><i class="fa-solid fa-coins sq-sidebar-ico sq-sidebar-ico--brand"></i> Ricarica wallet</a></li>
            </ul>
        </div>

        <div class="sq-sidebar-section">
            <p class="sq-sidebar-section-title">Movimenti</p>
            <ul class="sq-sidebar-list">
                <li class="sq-sidebar-item"><a href="{{ route('etichette.index') }}" class="sq-sidebar-link @if(request()->routeIs('etichette.*')) sq-sidebar-link--active @endif"><i class="fa-solid fa-tag sq-sidebar-ico sq-sidebar-ico--spedizioni"></i> Lettere di vettura</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('ordini.index') }}" class="sq-sidebar-link"><i class="fa-solid fa-receipt sq-sidebar-ico sq-sidebar-ico--spedizioni"></i> Ordini spedizioni</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('miei-rimborsi.index') }}" class="sq-sidebar-link @if(request()->routeIs('miei-rimborsi.*')) sq-sidebar-link--active @endif"><i class="fa-solid fa-money-bill-transfer sq-sidebar-ico sq-sidebar-ico--fin"></i> I miei rimborsi</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('wallet.ricariche') }}" class="sq-sidebar-link"><i class="fa-solid fa-coins sq-sidebar-ico sq-sidebar-ico--wallet"></i> Ricariche wallet</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('wallet.movimenti') }}" class="sq-sidebar-link"><i class="fa-solid fa-file-invoice-dollar sq-sidebar-ico sq-sidebar-ico--wallet"></i> Estratto wallet</a></li>
            </ul>
        </div>

        <div class="sq-sidebar-section">
            <p class="sq-sidebar-section-title">Assistenza</p>
            <ul class="sq-sidebar-list">
                <li class="sq-sidebar-item">
                    <a href="{{ route('assistenza.index') }}"
                       class="sq-sidebar-link @if(request()->routeIs('assistenza.*')) sq-sidebar-link--active @endif">
                        <i class="fa-solid fa-headset sq-sidebar-ico sq-sidebar-ico--brand"></i>
                        Assistenza
                    </a>
                </li>
            </ul>
        </div>

        <div class="sq-sidebar-section">
            <p class="sq-sidebar-section-title">Finanziario</p>
            <ul class="sq-sidebar-list">
                <li class="sq-sidebar-item"><a href="{{ route('finanziario.fatture.index') }}" class="sq-sidebar-link @if(request()->routeIs('finanziario.fatture.*')) sq-sidebar-link--active @endif"><i class="fa-solid fa-file-invoice sq-sidebar-ico sq-sidebar-ico--fin"></i> Fatture</a></li>
                <li class="sq-sidebar-item"><a href="{{ route('finanziario.nc.index') }}" class="sq-sidebar-link"><i class="fa-solid fa-file-circle-exclamation sq-sidebar-ico sq-sidebar-ico--fin"></i> Pratiche NC</a></li>
            </ul>
        </div>
    </nav>
</aside>
