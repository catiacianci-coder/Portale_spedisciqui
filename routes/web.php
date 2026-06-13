<?php

/*
| Rotte web — caricate da bootstrap/app.php.
| Suddivisione per area; stessi URL e nomi route di prima.
*/

require __DIR__.'/webhooks.php';

if (app()->environment('local', 'staging', 'testing')) {
    require __DIR__.'/dev.php';
}

require __DIR__.'/public.php';
require __DIR__.'/auth.php';
require __DIR__.'/cliente.php';
require __DIR__.'/backoffice.php';
