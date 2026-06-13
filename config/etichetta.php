<?php

return [

    /*
    | Fragmenti (case-insensitive, senza accenti) nell'ultimo evento tracking che
    | indicano che la lettera di vettura è già in circolazione / non più annullabile.
    */
    'correcao_tracking_status_blocca' => [
        'shipped',
        'in transit',
        'in transito',
        'in consegna',
        'out for delivery',
        'delivered',
        'consegnat',
        'consegna effettuata',
        'handed over',
        'picked up',
        'sorting',
        'sorted',
        'in lavorazione',
        'in hub',
        'partito',
        'in viaggio',
        'in consegna al destinatario',
        'preso in carico',
        'presa in carico',
        'affidato al corriere',
    ],

    'correcao_messaggio_gia_utilizzata' => 'Questa Lettera di vettura è già stata utilizzata, non è possibile sostituirla con un\'altra lettera di vettura',

    'correcao_messaggio_corriere_non_risponde' => 'Il server del corriere non risponde in questo momento. Riprovare tra qualche minuto.',

];
