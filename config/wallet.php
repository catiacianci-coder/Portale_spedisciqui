<?php

return [

    /*
    | Minimo ricarica in euro (intero). Usato se non è definito il parametro globale
    | "Ricarica wallet minimo (EUR)" attivo oggi.
    */
    'ricarica_min_default' => (int) env('WALLET_RICARICA_MIN', 150),

    /*
    | Solo sviluppo: se true, al submit viene creato subito il movimento di credito
    | senza passare dal gateway (non usare in produzione).
    */
    'ricarica_accredita_senza_gateway' => (bool) env('WALLET_RICARICA_DEV', false),

];
