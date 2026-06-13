<?php

namespace App\Services\Cliente;

use App\Models\nc_pratica;
use App\Models\parametri_globali;
use App\Models\rimborso;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Cliente\ClienteNotificazioneItem;
use App\Support\Cliente\ClienteNotificazioniRiepilogo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ClienteNotificazioniRiepilogoService
{
    private const CACHE_SECONDS = 20;

    public function riepilogoPerUtente(User $user, ?string $avvisoPiattaformaDismissHash = null): ClienteNotificazioniRiepilogo
    {
        $uid = (int) $user->id;
        $cacheKey = 'cliente_notif_riepilogo_'.$uid;

        $payload = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_SECONDS), function () use ($uid): array {
            $ncCount = (int) nc_pratica::query()
                ->where('user_id', $uid)
                ->where('stato', nc_pratica::STATO_APERTO)
                ->count();

            $ticketInEvidenza = Ticket::primeiroComRespostaStaffNaoLidaParaUser($uid);
            $ticketCount = (int) Ticket::query()
                ->comRespostaStaffNaoLidaParaCliente($uid)
                ->count();

            $rimborsoInEvidenza = rimborso::query()
                ->whereHas('spedizione', fn ($q) => $q->where('user_id', $uid))
                ->whereNotNull('data_reale')
                ->whereNull('credito_avviso_letto_in')
                ->orderByDesc('data_reale')
                ->orderByDesc('id')
                ->first();

            $rimborsoCount = (int) rimborso::query()
                ->whereHas('spedizione', fn ($q) => $q->where('user_id', $uid))
                ->whereNotNull('data_reale')
                ->whereNull('credito_avviso_letto_in')
                ->count();

            return [
                'nc_count' => $ncCount,
                'ticket_evidenza_id' => $ticketInEvidenza?->id,
                'ticket_count' => $ticketCount,
                'rimborso_evidenza_id' => $rimborsoInEvidenza?->id,
                'rimborso_count' => $rimborsoCount,
            ];
        });

        $ticketInEvidenza = null;
        if (! empty($payload['ticket_evidenza_id'])) {
            $ticketInEvidenza = Ticket::query()->find($payload['ticket_evidenza_id']);
        }

        $rimborsoInEvidenza = null;
        if (! empty($payload['rimborso_evidenza_id'])) {
            $rimborsoInEvidenza = rimborso::query()->find($payload['rimborso_evidenza_id']);
        }

        $items = [];

        $ncCount = (int) ($payload['nc_count'] ?? 0);
        if ($ncCount > 0) {
            $descNc = $ncCount === 1
                ? '1 pratica di non conformità aperta.'
                : $ncCount.' pratiche di non conformità aperte.';

            $items[] = new ClienteNotificazioneItem(
                id: 'nc_pratiche',
                titolo: 'Non conformità',
                descrizione: $descNc,
                url: route('finanziario.nc.index'),
                contagem: $ncCount,
            );
        }

        $ticketCount = (int) ($payload['ticket_count'] ?? 0);
        if ($ticketCount > 0 && $ticketInEvidenza !== null) {
            $descTicket = $ticketCount === 1
                ? 'Nuova risposta del team nel ticket #'.$ticketInEvidenza->id.'.'
                : $ticketCount.' ticket con risposta del team non letta.';

            $items[] = new ClienteNotificazioneItem(
                id: 'assistenza',
                titolo: 'Assistenza',
                descrizione: $descTicket,
                url: route('assistenza.ticket.show', $ticketInEvidenza),
                contagem: $ticketCount,
            );
        }

        $rimborsoCount = (int) ($payload['rimborso_count'] ?? 0);
        if ($rimborsoCount > 0 && $rimborsoInEvidenza !== null) {
            $descRimborso = $rimborsoCount === 1
                ? 'Rimborso #'.$rimborsoInEvidenza->id.' accreditato sul Wallet.'
                : $rimborsoCount.' rimborsi accreditati in attesa di visualizzazione.';

            $items[] = new ClienteNotificazioneItem(
                id: 'rimborso',
                titolo: 'Rimborsi',
                descrizione: $descRimborso,
                url: route('miei-rimborsi.index', [
                    'destacar' => $rimborsoInEvidenza->id,
                    'situazione' => 'rimborsato',
                ]),
                contagem: $rimborsoCount,
            );
        }

        $avvisoTesto = parametri_globali::homepageAvvisoTesto();
        if ($avvisoTesto !== null) {
            $hash = md5($avvisoTesto);
            if ($avvisoPiattaformaDismissHash !== $hash) {
                $items[] = new ClienteNotificazioneItem(
                    id: 'avviso_piattaforma',
                    titolo: 'Avviso piattaforma',
                    descrizione: Str::limit(trim(strip_tags($avvisoTesto)), 140),
                    url: route('home'),
                    informativo: true,
                );
            }
        }

        $badgeTotal = 0;
        foreach ($items as $item) {
            if ($item->contaPerBadge()) {
                $badgeTotal += $item->contagem;
            }
        }

        return new ClienteNotificazioniRiepilogo(
            items: $items,
            badgeTotal: $badgeTotal,
            ticketInEvidenza: $ticketInEvidenza,
            rimborsoInEvidenza: $rimborsoInEvidenza,
            ncPraticheAperte: $ncCount,
        );
    }

    public static function pulisciCacheUtente(int $userId): void
    {
        Cache::forget('cliente_notif_riepilogo_'.$userId);
        Cache::forget('assistenza_notif_'.$userId);
    }
}
