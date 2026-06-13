<?php

return [

    /*
    | Rimborso etichetta: accredito sempre sul wallet (indipendentemente dal pagamento ordine).
    | Bonifico wallet → conto cliente: flusso separato, non gestito qui.
    */

    /*
    | Giorni lavorativi (lun–ven) per rimborso con etichetta (motivo 1), dopo la richiesta.
    */
    'giorni_uteis' => (int) env('RIMBORSO_GIORNI_UTEIS', 15),

    /*
    | Finestra elegibilità dalla data creazione spedizione.
    */
    'dias_elegibilidade_etiqueta' => (int) env('RIMBORSO_DIAS_ELEGIBILIDADE', 15),

];
