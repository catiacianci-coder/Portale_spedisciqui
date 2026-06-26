<footer class="main-footer">
    <div class="footer-container">
        
        {{-- Blocco 1: Social (Orizzontali) --}}
        <div class="footer-section footer-social">
            <a href="https://www.facebook.com/profile.php?id=61577430493642" target="_blank" rel="noopener noreferrer">
                <img src="{{ asset('images/facebook.png') }}" alt="Facebook" class="sq-footer-social-img">
            </a>
            <a href="https://www.instagram.com/spedisciqui/" target="_blank" rel="noopener noreferrer">
                <img src="{{ asset('images/instagram.png') }}" alt="Instagram" class="sq-footer-social-img">
            </a>
        </div>

        {{-- Blocco 2: Link Assistenza --}}
        <div class="footer-section footer-links">
            <a href="{{ route('assistenza.index') }}">Assistenza</a>
            <a href="{{ route('politica.rimborso') }}">Politica di Rimborso</a>
            <a href="{{ route('faq.index') }}">FAQ</a>
        </div>

        {{-- Blocco 3: Link Legali --}}
        <div class="footer-section footer-links">
            <a href="{{ route('termini.legali') }}">Termini e Condizioni</a>
            <a href="{{ route('politica.privacy') }}">Privacy</a>
            <a href="{{ route('politica.cookie') }}">Politica dei cookies</a>
        </div>

    </div>
</footer>