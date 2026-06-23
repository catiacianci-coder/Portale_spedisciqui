<?php

return [

    /*
    | Rimborso etichetta: accredito sempre sul wallet (indipendentemente dal pagamento ordine).
    | Bonifico wallet → conto cliente: flusso separato, non gestito qui.
    */

    'giorni_uteis' => (int) env('RIMBORSO_GIORNI_UTEIS', 15),

    'dias_elegibilidade_etiqueta' => (int) env('RIMBORSO_DIAS_ELEGIBILIDADE', 15),

    'messaggio_annullamento_fallito' => 'Non è stato possibile annullare l’etichetta sul corriere. Riprova più tardi.',

    'messaggio_corriere_non_risponde_cancellazione' => 'Il sistema del trasportatore non permette in questo momento di effettuare la cancellazione. Riprovare tra qualche minuto.',

    'messaggio_corriere_non_risponde_pagamento' => 'Il server del corriere non risponde: impossibile verificare lo stato dell’etichetta. Riprovare tra qualche minuto.',

    'messaggio_etichetta_gia_spedita_richiesta' => 'L’etichetta risulta già affidata al corriere: non è possibile procedere con il rimborso.',

    'messaggio_etichetta_gia_spedita' => 'L’etichetta risulta già affidata al corriere: impossibile accreditare il rimborso.',

    'messaggio_etichetta_generata_dopo_richiesta' => 'L’etichetta risulta ora generata sul corriere (possibile ritardo): verificare prima di accreditare il rimborso.',

    'messaggio_stato_non_in_attesa' => 'La spedizione non risulta «in attesa di rimborso»: verificare lo stato sul portale prima dell’accredito.',

    'messaggio_etichetta_ancora_attiva_corriere' => 'L’etichetta risulta ancora attiva sul corriere: impossibile accreditare il rimborso.',

    'stato_corriere_annullato_fragmenti' => [
        'cancel',
        'cancell',
        'annull',
        'void',
        'deleted',
        'eliminat',
        'removed',
    ],

];
