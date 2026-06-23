@php
    use App\Support\SpedizioneIndirizzoTabella;

    $nomeDest = SpedizioneIndirizzoTabella::destinatarioNome($spedizione);
    $viaDest = SpedizioneIndirizzoTabella::destinatarioVia($spedizione);
    $locDest = SpedizioneIndirizzoTabella::destinatarioLocalita($spedizione);
@endphp
@if ($nomeDest !== '')
    <span class="sq-ordine-remessa-indirizzo">{{ $nomeDest }}</span>
@endif
@if ($viaDest !== '')
    <span class="sq-ordine-remessa-indirizzo">{{ $viaDest }}</span>
@endif
@if ($locDest !== '')
    <span class="sq-ordine-remessa-indirizzo">{{ $locDest }}</span>
@endif
@if ($nomeDest === '' && $viaDest === '' && $locDest === '')
    —
@endif
