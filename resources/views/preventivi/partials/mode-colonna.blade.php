@php

    $mode = trim((string) ($mode ?? ''));

    $modeText = $mode !== '' ? $mode : '—';

    $lower = mb_strtolower($mode);



    $iconClass = 'fa-solid fa-location-dot';

    $toneClass = 'sq-prev-mode-icon--default';



    if (str_contains($lower, 'domicil')) {

        $iconClass = 'fa-solid fa-house';

        $toneClass = 'sq-prev-mode-icon--domicilio';

    } elseif (

        str_contains($lower, 'punto')

        || str_contains($lower, 'tabac')

        || str_contains($lower, 'edicol')

        || str_contains($lower, 'locker')

    ) {

        $iconClass = 'fa-solid fa-building';

        $toneClass = 'sq-prev-mode-icon--punto';

    } elseif (str_contains($lower, 'ufficio') || str_contains($lower, 'poste')) {

        $iconClass = 'fa-solid fa-building';

        $toneClass = 'sq-prev-mode-icon--ufficio';

    }

@endphp



<div class="sq-prev-mode-line">

    <i class="sq-prev-mode-icon {{ $iconClass }} {{ $toneClass }}" aria-hidden="true"></i>

    <span>{{ $modeText }}</span>

</div>


