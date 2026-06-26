<?php

use App\Http\Controllers\BackofficeCorrieriController;
use App\Http\Controllers\BackofficeDashboardController;
use App\Http\Controllers\BackofficeErroriController;
use App\Http\Controllers\BackofficeFaqController;
use App\Http\Controllers\BackofficeGestaoDocumentosController;
use App\Http\Controllers\BackofficeHomepageAvvisoController;
use App\Http\Controllers\BackofficeMetodiPagamentoController;
use App\Http\Controllers\BackofficeMsgTracciamentoController;
use App\Http\Controllers\BackofficeNonConformitaController;
use App\Http\Controllers\BackofficeOrdiniController;
use App\Http\Controllers\BackofficeParametriGlobaliController;
use App\Http\Controllers\BackofficeRimborsoController;
use App\Http\Controllers\BackofficeSpedizioniController;
use App\Http\Controllers\BackofficeStripeEstrattoController;
use App\Http\Controllers\BackofficeTicketController;
use App\Http\Controllers\BackofficeTrasferimentoWalletController;
use App\Http\Controllers\BackofficeUtentiController;
use App\Http\Controllers\BackofficeUtilitiesController;
use App\Http\Controllers\BackofficeWalletController;
use App\Http\Controllers\SpedizioneEtichettaController;
use App\Http\Controllers\SpedizioneTrackingController;
use App\Http\Controllers\StripeRimborsoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'backoffice'])->prefix('backoffice')->group(function (): void {
    Route::get('/', [BackofficeDashboardController::class, 'index'])->name('backoffice.index');
    Route::post('/unlock-db-panel', [BackofficeDashboardController::class, 'unlockDbPanel'])->name('backoffice.hub.unlock_db');
    Route::post('/lock-db-panel', [BackofficeDashboardController::class, 'lockDbPanel'])->name('backoffice.hub.lock_db');
    Route::redirect('/pagamenti', '/backoffice/ordini')->name('backoffice.pagamenti');

    Route::get('/metodi-pagamento', [BackofficeMetodiPagamentoController::class, 'index'])->name('backoffice.metodi_pagamento.index');
    Route::put('/metodi-pagamento/{contesto}/{id}', [BackofficeMetodiPagamentoController::class, 'update'])->name('backoffice.metodi_pagamento.update');
    Route::post('/metodi-pagamento/{contesto}/{id}/toggle', [BackofficeMetodiPagamentoController::class, 'toggleAbilitato'])->name('backoffice.metodi_pagamento.toggle');

    Route::get('/utenti', [BackofficeUtentiController::class, 'index'])->name('backoffice.utenti.index');
    Route::post('/utenti/{user}/abilitazione-postagem', [BackofficeUtentiController::class, 'toggleHabilitacaoPostagem'])
        ->name('backoffice.utenti.habilitacao_postagem.toggle');
    Route::post('/utenti/{user}/liccardi', [BackofficeUtentiController::class, 'toggleLiccardi'])
        ->name('backoffice.utenti.liccardi.toggle');
    Route::post('/utenti/{user}/anagrafica', [BackofficeUtentiController::class, 'updateAnagrafica'])
        ->name('backoffice.utenti.anagrafica.update');
    Route::post('/utenti/{user}/mittenti/{mittenza}/sede-liccardi', [BackofficeUtentiController::class, 'toggleSedeLiccardiMittenza'])
        ->name('backoffice.utenti.mittenze.sede_liccardi.toggle');
    Route::get('/utenti/{user}/{section}', [BackofficeUtentiController::class, 'section'])->name('backoffice.utenti.section');

    Route::get('/ordini', [BackofficeOrdiniController::class, 'index'])->name('backoffice.ordini.index');
    Route::post('/ordini/{ordine}/segna-pagato', [BackofficeOrdiniController::class, 'segnaPagato'])->name('backoffice.ordini.segna_pagato');
    Route::post('/ordini/{ordine}/anular', [BackofficeOrdiniController::class, 'anular'])->name('backoffice.ordini.anular');
    Route::post('/ordini/{ordine}/rimborso-stripe', [StripeRimborsoController::class, 'store'])->name('backoffice.ordini.rimborso_stripe');

    Route::get('/spedizioni', [BackofficeSpedizioniController::class, 'index'])->name('backoffice.spedizioni.index');
    Route::get('/spedizioni/{spedizione}/etichetta', [SpedizioneEtichettaController::class, 'showBackoffice'])
        ->name('backoffice.spedizioni.etichetta');
    Route::get('/spedizioni/{spedizione}/dettaglio', [BackofficeSpedizioniController::class, 'dettaglio'])
        ->name('backoffice.spedizioni.dettaglio');
    Route::get('/spedizioni/{spedizione}/opcoes', [BackofficeSpedizioniController::class, 'opcoes'])
        ->name('backoffice.spedizioni.opcoes');
    Route::put('/spedizioni/{spedizione}', [BackofficeSpedizioniController::class, 'update'])
        ->name('backoffice.spedizioni.update');
    Route::post('/spedizioni/{spedizione}/manual', [BackofficeSpedizioniController::class, 'manual'])
        ->name('backoffice.spedizioni.manual');
    Route::post('/spedizioni/{spedizione}/retry', [BackofficeSpedizioniController::class, 'retry'])
        ->name('backoffice.spedizioni.retry');
    Route::get('/spedizioni/{spedizione}/tracking', [SpedizioneTrackingController::class, 'showBackoffice'])
        ->name('backoffice.spedizioni.tracking');

    Route::get('/stripe-estratto', [BackofficeStripeEstrattoController::class, 'index'])->name('backoffice.stripe_estratto.index');

    Route::get('/rimborsi', [BackofficeRimborsoController::class, 'index'])->name('backoffice.rimborsi.index');
    Route::get('/rimborsi/pendenti', [BackofficeRimborsoController::class, 'pendentes'])->name('backoffice.rimborsi.pendentes');
    Route::get('/rimborsi/rimborsati', [BackofficeRimborsoController::class, 'rimborsati'])->name('backoffice.rimborsi.rimborsati');
    Route::get('/rimborsi/per-ordine', [BackofficeRimborsoController::class, 'perOrdine'])->name('backoffice.rimborsi.per_ordine');
    Route::post('/rimborsi/{rimborso}/paga', [BackofficeRimborsoController::class, 'paga'])->name('backoffice.rimborsi.paga');
    Route::get('/rimborsi/trasferimento-wallet', [BackofficeTrasferimentoWalletController::class, 'index'])->name('backoffice.rimborsi.trasferimento_wallet');
    Route::post('/rimborsi/{rimborso}/trasferimento-wallet/richiesta', [BackofficeTrasferimentoWalletController::class, 'registraRichiesta'])->name('backoffice.rimborsi.trasferimento_wallet.richiesta');
    Route::post('/rimborsi/{rimborso}/trasferimento-wallet/carta', [BackofficeTrasferimentoWalletController::class, 'trasferisciCarta'])->name('backoffice.rimborsi.trasferimento_wallet.carta');
    Route::post('/rimborsi/{rimborso}/trasferimento-wallet/bonifico', [BackofficeTrasferimentoWalletController::class, 'trasferisciBonifico'])->name('backoffice.rimborsi.trasferimento_wallet.bonifico');
    Route::post('/rimborsi/{rimborso}/trasferimento-wallet/completato', [BackofficeTrasferimentoWalletController::class, 'segnaCompletato'])->name('backoffice.rimborsi.trasferimento_wallet.completato');

    Route::get('/ricariche', [BackofficeWalletController::class, 'ricariche'])->name('backoffice.ricariche.index');
    Route::post('/ricariche/{id}/accredita', [BackofficeWalletController::class, 'accreditaRicarica'])->name('backoffice.ricariche.accredita');
    Route::get('/wallet-cliente', [BackofficeWalletController::class, 'walletCliente'])->name('backoffice.wallet.cliente');
    Route::post('/wallet-cliente/{user}/movimento', [BackofficeWalletController::class, 'storeMovimentoCliente'])
        ->name('backoffice.wallet.movimento.store');
    Route::patch('/wallet-cliente/movimenti/{movimento}/nota-interna', [BackofficeWalletController::class, 'updateNotaInternaMovimento'])
        ->name('backoffice.wallet.movimento.nota_interna');

    Route::get('/non-conformita', [BackofficeNonConformitaController::class, 'index'])->name('backoffice.nc.index');
    Route::post('/non-conformita/import', [BackofficeNonConformitaController::class, 'importCsv'])->name('backoffice.nc.import');

    Route::get('/corrieri', [BackofficeCorrieriController::class, 'index'])->name('backoffice.corrieri.index');
    Route::get('/corrieri/{corriere}/edit', [BackofficeCorrieriController::class, 'edit'])->name('backoffice.corrieri.edit');
    Route::post('/corrieri/{corriere}/carosello', [BackofficeCorrieriController::class, 'toggleCarosello'])->name('backoffice.corrieri.carosello.toggle');
    Route::post('/corrieri/{corriere}/attivo', [BackofficeCorrieriController::class, 'toggleAttivo'])->name('backoffice.corrieri.attivo.toggle');
    Route::post('/corrieri/campo/{campo}', [BackofficeCorrieriController::class, 'updateCampo'])->name('backoffice.corrieri.update_campo');
    Route::post('/corrieri/{corriere}', [BackofficeCorrieriController::class, 'updateCorriere'])->name('backoffice.corrieri.update');

    Route::get('/parametri-globali', [BackofficeParametriGlobaliController::class, 'edit'])->name('backoffice.parametri_globali.edit');
    Route::post('/parametri-globali', [BackofficeParametriGlobaliController::class, 'update'])->name('backoffice.parametri_globali.update');

    Route::get('/homepage-avviso', [BackofficeHomepageAvvisoController::class, 'edit'])->name('backoffice.homepage_avviso.edit');
    Route::put('/homepage-avviso', [BackofficeHomepageAvvisoController::class, 'update'])->name('backoffice.homepage_avviso.update');

    Route::get('/errori', [BackofficeErroriController::class, 'index'])->name('backoffice.errori.index');
    Route::get('/errori/{log_errore_applicativo}', [BackofficeErroriController::class, 'show'])->name('backoffice.errori.show');

    Route::get('/utilities', [BackofficeUtilitiesController::class, 'index'])->name('backoffice.utilities.index');
    Route::post('/utilities/parametri-globali', [BackofficeUtilitiesController::class, 'storeParametro'])
        ->name('backoffice.utilities.parametri_globali.store');
    Route::post('/utilities/parametri-globali/{parametriGlobali}', [BackofficeUtilitiesController::class, 'updateParametro'])
        ->name('backoffice.utilities.parametri_globali.update');
    Route::post('/utilities/parametri-globali/{parametriGlobali}/duplica', [BackofficeUtilitiesController::class, 'duplicaParametro'])
        ->name('backoffice.utilities.parametri_globali.duplica');
    Route::post('/utilities/ricarichi/{ricarico}', [BackofficeUtilitiesController::class, 'updateRicarico'])
        ->name('backoffice.utilities.ricarichi.update');
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
