<?php

use App\Http\Controllers\CarrelloController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\IndirizziSpedizioneController;
use App\Http\Controllers\PoliticaCookieController;
use App\Http\Controllers\PoliticaPrivacyController;
use App\Http\Controllers\PoliticaRimborsoController;
use App\Http\Controllers\PreventiviController;
use App\Http\Controllers\SimulazioneController;
use App\Http\Controllers\TariffeScontateController;
use App\Http\Controllers\TerminiLegaliController;
use App\Http\Controllers\VincoliSpedizioneController;
use Illuminate\Support\Facades\Route;

/*
| Funnel pubblico (sessione, senza login obbligatorio).
| Pagamento e creazione ordine → routes/cliente.php (auth + verified).
*/

Route::get('/', [VincoliSpedizioneController::class, 'create'])->name('home');

Route::get('/simulalzione', fn () => redirect()->route('simulazione.index'));

Route::get('/simulazione', [SimulazioneController::class, 'index'])->name('simulazione.index');
Route::get('/simulazione/{id_corriere}', [SimulazioneController::class, 'showByCorriere'])->name('simulazione');

Route::redirect('/vincoli-spedizione', '/', 301)->name('vincoli.spedizione');
Route::post('/vincoli-spedizione', [VincoliSpedizioneController::class, 'store'])->name('vincoli.spedizione.store');

Route::get('/api/comuni/suggest', [VincoliSpedizioneController::class, 'suggestComuni'])
    ->name('api.comuni.suggest');

Route::get('/preventivi', [PreventiviController::class, 'show'])->name('preventivi');
Route::get('/preventivi/punti-mittente', [PreventiviController::class, 'puntiMittente'])->name('preventivi.punti-mittente');
Route::get('/preventivi/punti-servizio', [PreventiviController::class, 'puntiServizio'])->name('preventivi.punti-servizio');

Route::get('/spedizione/indirizzi', [IndirizziSpedizioneController::class, 'show'])->name('spedizione.indirizzi');
Route::post('/spedizione/indirizzi', [IndirizziSpedizioneController::class, 'store'])->name('spedizione.indirizzi.store');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/quote-servizio', [CheckoutController::class, 'quoteServizio'])->name('checkout.quote-servizio');

Route::get('/carrello', [CarrelloController::class, 'index'])->name('carrello.index');
Route::post('/carrello/aggiungi', [CarrelloController::class, 'aggiungi'])->name('carrello.aggiungi');
Route::post('/carrello/rimuovi', [CarrelloController::class, 'rimuovi'])->name('carrello.rimuovi');

Route::get('/tariffe-scontate', [TariffeScontateController::class, 'index'])->name('tariffe_scontate.index');

Route::get('/termini-legali', TerminiLegaliController::class)->name('termini.legali');
Route::get('/politica-privacy', PoliticaPrivacyController::class)->name('politica.privacy');
Route::redirect('/privacy', '/politica-privacy', 301);
Route::get('/politica-cookie', PoliticaCookieController::class)->name('politica.cookie');
Route::get('/politica-rimborso', PoliticaRimborsoController::class)->name('politica.rimborso');
Route::get('/faq', FaqController::class)->name('faq.index');
