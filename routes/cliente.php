<?php

use App\Http\Controllers\AssistenzaSolicitacaoController;
use App\Http\Controllers\CarrelloController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClienteNotificazioneController;
use App\Http\Controllers\EtichetteClienteController;
use App\Http\Controllers\FinanziarioFattureController;
use App\Http\Controllers\FinanziarioNonConformitaController;
use App\Http\Controllers\MieiRimborsoController;
use App\Http\Controllers\OrdiniController;
use App\Http\Controllers\OrdinePagamentoCheckoutController;
use App\Http\Controllers\PagamentoNcController;
use App\Http\Controllers\ProfiloAnagraficaController;
use App\Http\Controllers\ProfiloPasswordController;
use App\Http\Controllers\ResiController;
use App\Http\Controllers\RimborsoEtichettaController;
use App\Http\Controllers\SpedizioneEtichettaController;
use App\Http\Controllers\SpedizioneTrackingController;
use App\Http\Controllers\StripeOrdineController;
use App\Http\Controllers\StripeRimborsoController;
use App\Http\Controllers\TariffeScontateController;
use App\Http\Controllers\TicketClienteController;
use App\Http\Controllers\UserDestinatarioController;
use App\Http\Controllers\UserImballaggioController;
use App\Http\Controllers\UserMittenzaController;
use App\Http\Controllers\WalletMovimentiController;
use App\Http\Controllers\WalletRicaricaController;
use App\Http\Controllers\WalletRicaricheController;
use Illuminate\Support\Facades\Route;

/*
| Area cliente: login + email verificata.
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('imballaggi', UserImballaggioController::class)
        ->parameters(['imballaggi' => 'imballaggio'])
        ->except(['show']);
    Route::post('imballaggi/{imballaggio}/preferito', [UserImballaggioController::class, 'setPreferito'])
        ->name('imballaggi.preferito');

    Route::resource('mittenze', UserMittenzaController::class)
        ->parameters(['mittenze' => 'mittenza'])
        ->except(['show']);
    Route::post('mittenze/{mittenza}/duplica', [UserMittenzaController::class, 'duplica'])
        ->name('mittenze.duplica');
    Route::post('mittenze/{mittenza}/preferito', [UserMittenzaController::class, 'preferito'])
        ->name('mittenze.preferito');

    Route::resource('destinatari', UserDestinatarioController::class)
        ->parameters(['destinatari' => 'destinatario'])
        ->except(['show']);
    Route::post('destinatari/{destinatario}/duplica', [UserDestinatarioController::class, 'duplica'])
        ->name('destinatari.duplica');

    Route::post('/tariffe-scontate', [TariffeScontateController::class, 'store'])->name('tariffe_scontate.store');

    Route::get('/profilo/anagrafica', [ProfiloAnagraficaController::class, 'edit'])->name('profilo.anagrafica');
    Route::post('/profilo/anagrafica', [ProfiloAnagraficaController::class, 'update'])->name('profilo.anagrafica.update');

    Route::get('/profilo/password', [ProfiloPasswordController::class, 'edit'])->name('profilo.password');
    Route::post('/profilo/password', [ProfiloPasswordController::class, 'update'])->name('profilo.password.update');

    Route::get('/carrello/riepilogo', [CarrelloController::class, 'riepilogo'])->name('carrello.riepilogo');
    Route::post('/carrello/conferma', [CarrelloController::class, 'conferma'])->name('carrello.conferma');
    Route::post('/checkout/conferma-ordine', [CarrelloController::class, 'creaOrdineSingoloDaCheckout'])
        ->name('checkout.conferma_ordine');
    Route::post('/checkout/paga', [CheckoutController::class, 'paga'])->name('checkout.paga');

    Route::get('/ordini', [OrdiniController::class, 'index'])->name('ordini.index');
    Route::get('/ordini/{ordine}', [OrdiniController::class, 'show'])->name('ordini.show');
    Route::get('/ordini/{ordine}/pagamento', [OrdiniController::class, 'pagamentoShow'])->name('ordini.pagamento.show');
    Route::get('/ordini/{ordine}/pagamento/wallet', [OrdinePagamentoCheckoutController::class, 'wallet'])->name('ordini.pagamento.wallet');
    Route::get('/ordini/{ordine}/pagamento/carta', [OrdinePagamentoCheckoutController::class, 'carta'])->name('ordini.pagamento.carta');
    Route::get('/ordini/{ordine}/pagamento/bonifico', [OrdinePagamentoCheckoutController::class, 'bonifico'])->name('ordini.pagamento.bonifico');
    Route::post('/ordini/{ordine}/pagamento', [OrdiniController::class, 'pagamento'])->name('ordini.pagamento');
    Route::post('/ordini/{ordine}/pagamento-carta', [OrdiniController::class, 'pagamentoCarta'])->name('ordini.pagamento.carta.process');
    Route::get('/ordini/{ordine}/stripe/success', [StripeOrdineController::class, 'success'])->name('ordini.stripe.success');
    Route::get('/ordini/{ordine}/stripe/cancel', [StripeOrdineController::class, 'cancel'])->name('ordini.stripe.cancel');
    Route::post('/ordini/{ordine}/annulla', [OrdiniController::class, 'annulla'])->name('ordini.annulla');
    Route::post('/ordini/{ordine}/rimborso-stripe', [StripeRimborsoController::class, 'store'])->name('ordini.rimborso_stripe');

    Route::get('/etichette', [EtichetteClienteController::class, 'index'])->name('etichette.index');
    Route::get('/etichette/spedizione/{spedizione}/dettaglio', [EtichetteClienteController::class, 'dettaglio'])
        ->name('etichette.spedizione.dettaglio');
    Route::get('/etichette/spedizione/{spedizione}/correcao', [EtichetteClienteController::class, 'correcao'])
        ->name('etichette.spedizione.correcao');
    Route::post('/etichette/spedizione/{spedizione}/correcao', [EtichetteClienteController::class, 'correcaoSalvar'])
        ->name('etichette.spedizione.correcao.salvar');
    Route::post('/etichette/spedizione/{spedizione}/retry', [EtichetteClienteController::class, 'retry'])
        ->name('etichette.spedizione.retry');
    Route::redirect('/spedizioni', '/etichette')->name('spedizioni.index');
    Route::get('/spedizioni/{spedizione}/etichetta', [SpedizioneEtichettaController::class, 'showCliente'])
        ->name('spedizioni.etichetta');
    Route::get('/spedizioni/{spedizione}/tracking', [SpedizioneTrackingController::class, 'showCliente'])
        ->name('spedizioni.tracking');

    Route::get('/rimborso-etichette', [RimborsoEtichettaController::class, 'index'])->name('rimborso-etichette.index');
    Route::post('/rimborso-etichette/cerca', [RimborsoEtichettaController::class, 'buscar'])->name('rimborso-etichette.buscar');
    Route::post('/rimborso-etichette/richiedi', [RimborsoEtichettaController::class, 'solicitar'])->name('rimborso-etichette.solicitar');
    Route::get('/miei-rimborsi', [MieiRimborsoController::class, 'index'])->name('miei-rimborsi.index');

    Route::post('/notifiche/dispensar-avviso', [ClienteNotificazioneController::class, 'dispensarAvvisoPiattaforma'])
        ->name('notifiche.dispensar_avviso');

    Route::get('/resi', [ResiController::class, 'index'])->name('resi.index');
    Route::post('/resi/cerca', [ResiController::class, 'search'])->name('resi.search');
    Route::post('/resi/{spedizione}/crea', [ResiController::class, 'creaLetteraVettura'])->name('resi.crea');

    Route::get('/wallet/ricarica', [WalletRicaricaController::class, 'show'])->name('wallet.ricarica');
    Route::post('/wallet/ricarica', [WalletRicaricaController::class, 'store'])->name('wallet.ricarica.store');
    Route::get('/wallet/movimenti', [WalletMovimentiController::class, 'index'])->name('wallet.movimenti');
    Route::get('/wallet/ricariche', [WalletRicaricheController::class, 'index'])->name('wallet.ricariche');
    Route::delete('/wallet/ricariche/{ricarica}', [WalletRicaricheController::class, 'destroy'])->name('wallet.ricariche.destroy');

    Route::get('/finanziario/fatture', [FinanziarioFattureController::class, 'index'])->name('finanziario.fatture.index');
    Route::get('/finanziario/non-conformita', [FinanziarioNonConformitaController::class, 'index'])->name('finanziario.nc.index');
    Route::get('/finanziario/non-conformita/{pratica}', [FinanziarioNonConformitaController::class, 'show'])->name('finanziario.nc.show');
    Route::get('/finanziario/non-conformita/{pratica}/pdf', [FinanziarioNonConformitaController::class, 'pdf'])->name('finanziario.nc.pdf');
    Route::get('/pagamento-nc', [PagamentoNcController::class, 'show'])->name('pagamento_nc.index');

    Route::prefix('assistenza')->name('assistenza.')->group(function (): void {
        Route::get('/', [AssistenzaSolicitacaoController::class, 'index'])->name('index');
        Route::get('/invia-richiesta', [AssistenzaSolicitacaoController::class, 'create'])->name('solicitar');
        Route::post('/invia-richiesta', [AssistenzaSolicitacaoController::class, 'store'])->name('solicitar.store');
        Route::get('/api/spedizioni-per-ordine', [AssistenzaSolicitacaoController::class, 'spedizioniPorPedido'])->name('api.spedizioni_ordine');
        Route::get('/api/spedizione-per-codice', [AssistenzaSolicitacaoController::class, 'spedizionePorCodigo'])->name('api.spedizione_codice');
        Route::get('/api/spedizione-per-tracking', [AssistenzaSolicitacaoController::class, 'spedizionePorTracking'])->name('api.spedizione_tracking');
        Route::get('/api/corrieri-cliente', [AssistenzaSolicitacaoController::class, 'corrieriCliente'])->name('api.corrieri_cliente');
        Route::get('/ticket/{ticket}', [TicketClienteController::class, 'show'])->name('ticket.show');
        Route::post('/ticket/{ticket}/messaggio', [TicketClienteController::class, 'storeMensagem'])->name('ticket.mensagem');
    });
});
