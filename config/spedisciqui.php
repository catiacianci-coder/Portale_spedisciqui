<?php

return [

    /**
     * Radice dati fuori dal progetto (etichette LdV, fatture, …).
     * Esempio locale: C:\spedisciqui-data
     */
    'data_path' => env('SPEDISCIQUI_DATA_PATH', PHP_OS_FAMILY === 'Windows'
        ? 'C:\\spedisciqui-data'
        : '/var/spedisciqui-data'),

];
