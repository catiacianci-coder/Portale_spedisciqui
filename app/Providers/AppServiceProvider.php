<?php

namespace App\Providers;

use App\Models\ordine;
use App\Models\spedizione;
use App\Models\UserImballaggio;
use App\Policies\OrdinePolicy;
use App\Policies\SpedizionePolicy;
use App\Services\Cliente\ClienteNotificazioniRiepilogoService;
use App\Services\UserImballaggiDefault;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.sq');
        Paginator::defaultSimpleView('vendor.pagination.sq');

        Gate::policy(ordine::class, OrdinePolicy::class);
        Gate::policy(spedizione::class, SpedizionePolicy::class);

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reimpostazione password – Spedisciqui')
                ->line('Hai ricevuto questa email perché è stata richiesta una nuova password per il tuo account.')
                ->action('Imposta una nuova password', $url)
                ->line("Il link è valido per {$minutes} minuti.")
                ->line('Se non hai richiesto il recupero password, ignora questa email.');
        });

        Event::listen(Verified::class, function (Verified $event) {
            app(UserImballaggiDefault::class)->ensureDefaults($event->user);
        });

        Route::bind('imballaggio', function (string $value) {
            return UserImballaggio::query()
                ->where('user_id', auth()->id())
                ->findOrFail($value);
        });

        Route::bind('ordine', function (string $value) {
            return ordine::query()
                ->where('user_id', auth()->id())
                ->findOrFail($value);
        });

        View::composer('partials.header', function ($view): void {
            $clienteNotificazioni = null;
            if (auth()->check() && auth()->user()->hasVerifiedEmail()) {
                $clienteNotificazioni = app(ClienteNotificazioniRiepilogoService::class)->riepilogoPerUtente(
                    auth()->user(),
                    session('cliente_avviso_piattaforma_hash'),
                );
            }
            $view->with('clienteNotificazioni', $clienteNotificazioni);
        });

        View::composer('vincoli-spedizione', function ($view): void {
            $view->with('homepageAvviso', \App\Models\parametri_globali::homepageAvvisoTesto() ?? '');
        });
    }
}
