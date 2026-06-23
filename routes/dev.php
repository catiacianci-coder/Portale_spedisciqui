<?php

use App\Http\Controllers\Test\LiccardiTmsTestController;
use App\Http\Controllers\Test\SendcloudEtichettaTestController;
use App\Http\Controllers\Test\SendcloudRatesTestController;
use App\Http\Controllers\Test\SpedisciOnlineCarriersTestController;
use App\Http\Controllers\Test\SpedisciOnlineRatesTestController;
use Illuminate\Support\Facades\Route;

/*
| Rotte di test integrazioni — solo local / staging / testing.
*/

Route::prefix('test')->name('test.')->group(function (): void {
    Route::match(['get', 'post'], '/spedisci-online', [SpedisciOnlineRatesTestController::class, 'show'])
        ->name('spedisci-online');
    Route::get('/spedisci-online/etichetta/{carrier}.pdf', [SpedisciOnlineRatesTestController::class, 'downloadEtichetta'])
        ->where('carrier', 'gls|sda')
        ->name('spedisci-online.pdf');

    Route::match(['get', 'post'], '/spedisci-online/carriers', [SpedisciOnlineCarriersTestController::class, 'show'])
        ->name('spedisci-online-carriers');

    Route::match(['get', 'post'], '/sendcloud-rates', [SendcloudRatesTestController::class, 'show'])
        ->name('sendcloud-rates');

    Route::match(['get', 'post'], '/sendcloud-etichetta', [SendcloudEtichettaTestController::class, 'show'])
        ->name('sendcloud-etichetta');
    Route::get('/sendcloud-etichetta/etichetta/{shipmentId}.pdf', [SendcloudEtichettaTestController::class, 'downloadEtichetta'])
        ->name('sendcloud-etichetta.pdf');

    Route::match(['get', 'post'], '/liccardi-tms', [LiccardiTmsTestController::class, 'show'])
        ->name('liccardi-tms');
    Route::get('/liccardi-tms/etichetta/{spedizioneId}.pdf', [LiccardiTmsTestController::class, 'downloadEtichetta'])
        ->whereNumber('spedizioneId')
        ->name('liccardi-tms.pdf');
});
