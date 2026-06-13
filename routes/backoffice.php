<?php

use App\Http\Controllers\BackofficeCorrieriController;
use App\Http\Controllers\BackofficeDashboardController;
use App\Http\Controllers\BackofficeErroriController;
use App\Http\Controllers\BackofficeFaqController;
use App\Http\Controllers\BackofficeGestaoDocumentosController;
use App\Http\Controllers\BackofficeHomepageAvvisoController;
use App\Http\Controllers\BackofficeMsgTracciamentoController;
use App\Http\Controllers\BackofficeNonConformitaController;
use App\Http\Controllers\BackofficeOrdiniController;
use App\Http\Controllers\BackofficeParametriGlobaliController;
use App\Http\Controllers\BackofficeRimborsoController;
use App\Http\Controllers\BackofficeSpedizioniController;
use App\Http\Controllers\BackofficeTicketController;
use App\Http\Controllers\BackofficeUtilitiesController;
use App\Http\Controllers\BackofficeWalletController;
use App\Http\Controllers\SpedizioneEtichettaController;
use App\Http\Controllers\SpedizioneTrackingController;
use App\Http\Controllers\StripeRimborsoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'backoffice'])->prefix('backoffice')->group(function (): void {
    Route::get('/', [BackofficeDashboardController::class, 'index'])->name('backoffice.index');
    Route::redirect('/pagamenti', '/backoffice/ordini')->name('backoffice.pagamenti');

    Route::get('/ordini', [BackofficeOrdiniController::class, 'index'])->name('backoffice.ordini.index');
    Route::post('/ordini/{ordine}/segna-pagato', [BackofficeOrdiniController::class, 'segnaPagato'])->name('backoffice.ordini.segna_pagato');
    Route::post('/ordini/{ordine}/anular', [BackofficeOrdiniController::class, 'anular'])->name('backoffice.ordini.anular');
    Route::post('/ordini/{ordine}/rimborso-stripe', [StripeRimborsoController::class, 'store'])->name('backoffice.ordini.rimborso_stripe');

    Route::get('/spedizioni', [BackofficeSpedizioniController::class, 'index'])->name('backoffice.spedizioni.index');
    Route::get('/spedizioni/{spedizione}/etichetta', [SpedizioneEtichettaController::class, 'showBackoffice'])
        ->name('backoffice.spedizioni.etichetta');
    Route::get('/spedizioni/{spedizione}/tracking', [SpedizioneTrackingController::class, 'showBackoffice'])
        ->name('backoffice.spedizioni.tracking');

    Route::get('/rimborsi', [BackofficeRimborsoController::class, 'index'])->name('backoffice.rimborsi.index');
    Route::get('/rimborsi/pendenti', [BackofficeRimborsoController::class, 'pendentes'])->name('backoffice.rimborsi.pendentes');
    Route::get('/rimborsi/rimborsati', [BackofficeRimborsoController::class, 'rimborsati'])->name('backoffice.rimborsi.rimborsati');
    Route::get('/rimborsi/per-ordine', [BackofficeRimborsoController::class, 'perOrdine'])->name('backoffice.rimborsi.per_ordine');
    Route::post('/rimborsi/{rimborso}/paga', [BackofficeRimborsoController::class, 'paga'])->name('backoffice.rimborsi.paga');

    Route::get('/ricariche', [BackofficeWalletController::class, 'ricariche'])->name('backoffice.ricariche.index');
    Route::post('/ricariche/{id}/accredita', [BackofficeWalletController::class, 'accreditaRicarica'])->name('backoffice.ricariche.accredita');
    Route::get('/wallet-cliente', [BackofficeWalletController::class, 'walletCliente'])->name('backoffice.wallet.cliente');

    Route::get('/non-conformita', [BackofficeNonConformitaController::class, 'index'])->name('backoffice.nc.index');
    Route::post('/non-conformita/import', [BackofficeNonConformitaController::class, 'importCsv'])->name('backoffice.nc.import');

    Route::get('/corrieri', [BackofficeCorrieriController::class, 'index'])->name('backoffice.corrieri.index');
    Route::get('/corrieri/{corriere}/edit', [BackofficeCorrieriController::class, 'edit'])->name('backoffice.corrieri.edit');
    Route::post('/corrieri/{corriere}', [BackofficeCorrieriController::class, 'update'])->name('backoffice.corrieri.update');

    Route::get('/parametri-globali', [BackofficeParametriGlobaliController::class, 'edit'])->name('backoffice.parametri_globali.edit');
    Route::post('/parametri-globali', [BackofficeParametriGlobaliController::class, 'update'])->name('backoffice.parametri_globali.update');

    Route::get('/homepage-avviso', [BackofficeHomepageAvvisoController::class, 'edit'])->name('backoffice.homepage_avviso.edit');
    Route::put('/homepage-avviso', [BackofficeHomepageAvvisoController::class, 'update'])->name('backoffice.homepage_avviso.update');

    Route::get('/errori', [BackofficeErroriController::class, 'index'])->name('backoffice.errori.index');
    Route::get('/errori/{log_errore_applicativo}', [BackofficeErroriController::class, 'show'])->name('backoffice.errori.show');

    Route::get('/utilities', [BackofficeUtilitiesController::class, 'index'])->name('backoffice.utilities.index');
    Route::prefix('utilities/msg-tracciamento')->name('backoffice.utilities.msg_tracciamento.')->group(function (): void {
        Route::get('/', [BackofficeMsgTracciamentoController::class, 'index'])->name('index');
        Route::get('/create', [BackofficeMsgTracciamentoController::class, 'create'])->name('create');
        Route::post('/', [BackofficeMsgTracciamentoController::class, 'store'])->name('store');
        Route::post('/bulk-update', [BackofficeMsgTracciamentoController::class, 'bulkUpdate'])->name('bulk_update');
        Route::get('/{msg_traccaimento}/edit', [BackofficeMsgTracciamentoController::class, 'edit'])->name('edit');
        Route::post('/{msg_traccaimento}', [BackofficeMsgTracciamentoController::class, 'update'])->name('update');
        Route::delete('/{msg_traccaimento}', [BackofficeMsgTracciamentoController::class, 'destroy'])->name('destroy');
    });

    Route::get('/gestione-documenti', [BackofficeGestaoDocumentosController::class, 'index'])->name('backoffice.gestao_documentos.index');
    Route::post('/gestione-documenti', [BackofficeGestaoDocumentosController::class, 'store'])->name('backoffice.gestao_documentos.store');
    Route::get('/gestione-documenti/versao/{versao}/modifica', [BackofficeGestaoDocumentosController::class, 'edit'])->name('backoffice.gestao_documentos.edit');
    Route::put('/gestione-documenti/versao/{versao}', [BackofficeGestaoDocumentosController::class, 'update'])->name('backoffice.gestao_documentos.update');
    Route::post('/gestione-documenti/formatta-testo', [BackofficeGestaoDocumentosController::class, 'formatarTexto'])->name('backoffice.gestao_documentos.formatar_texto');
    Route::post('/gestione-documenti/aiuto-pagine', [BackofficeGestaoDocumentosController::class, 'updateHelpPage'])->name('backoffice.gestao_documentos.ajuda_pagina');

    Route::get('/faq', [BackofficeFaqController::class, 'index'])->name('backoffice.faq.index');
    Route::post('/faq', [BackofficeFaqController::class, 'store'])->name('backoffice.faq.store');
    Route::put('/faq/{faq}', [BackofficeFaqController::class, 'update'])->name('backoffice.faq.update');
    Route::delete('/faq/{faq}', [BackofficeFaqController::class, 'destroy'])->name('backoffice.faq.destroy');
    Route::post('/faq/{faq}/move', [BackofficeFaqController::class, 'move'])->name('backoffice.faq.move');

    Route::get('/tickets', [BackofficeTicketController::class, 'index'])->name('backoffice.tickets.index');
    Route::get('/tickets/{ticket}', [BackofficeTicketController::class, 'show'])->name('backoffice.tickets.show');
    Route::post('/tickets/{ticket}/messaggio', [BackofficeTicketController::class, 'storeMessaggio'])->name('backoffice.tickets.mensagem');
    Route::post('/tickets/{ticket}/stato', [BackofficeTicketController::class, 'updateStato'])->name('backoffice.tickets.stato');
});
