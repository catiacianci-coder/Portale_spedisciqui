<?php

namespace App\Support;

use App\Models\User;
use App\Models\spedizione;

/**
 * Abilitazione post-vendita etichette: nuovi utenti partono disabilitati;
 * l'operatore BO abilita manualmente. Il pagamento resta consentito.
 */
final class UserPostingEnablement
{
    public const MSG_NUOVO = 'Conta non ancora abilitata dal back office per la generazione automatica delle etichette. Il pagamento è registrato; l\'etichetta non verrà generata finché non abiliti il cliente dalla scheda utenti.';

    public const MSG_BO = 'Conta disabilitata dall\'operatore back office per la postagem automatica. Il pagamento è registrato; l\'etichetta non verrà generata finché non riabiliti il cliente.';

    public static function utenteBloccato(?User $user): bool
    {
        return $user !== null && (bool) $user->is_account_disabled;
    }

    public static function messaggioBlocco(?User $user): string
    {
        if ($user !== null && (bool) $user->postagem_bloqueado_pelo_bo) {
            return self::MSG_BO;
        }

        return self::MSG_NUOVO;
    }

    /**
     * @return array{abbr: string, class: string, title: string}
     */
    public static function badgeMeta(User $user): array
    {
        if ((bool) $user->postagem_bloqueado_pelo_bo) {
            return [
                'abbr' => 'BO',
                'class' => 'sq-bo-user-badge sq-bo-user-badge--bo',
                'title' => 'Conta bloccata dal back office.',
            ];
        }

        return [
            'abbr' => 'N',
            'class' => 'sq-bo-user-badge sq-bo-user-badge--novo',
            'title' => 'Nuovo utente — in attesa di abilitazione back office.',
        ];
    }

    /**
     * Se l'utente non è abilitato, registra errore etichetta e restituisce true (blocca API corriere).
     */
    public static function tentaSegnaBloccoPostPagamento(spedizione $spedizione): bool
    {
        $spedizione->loadMissing(['user', 'corriereRecord']);
        if (! self::utenteBloccato($spedizione->user)) {
            return false;
        }

        self::persistiErroreGenerazione($spedizione, self::messaggioBlocco($spedizione->user));

        return true;
    }

    public static function persisteErroreGenerazione(spedizione $spedizione, string $message): void
    {
        $corriere = $spedizione->corriereRecord;
        $payload = [
            'last_error_at' => now()->toIso8601String(),
            'last_error' => $message,
            'posting_blocked' => true,
        ];

        if ($corriere && PiattaformaCorriere::corriereUsaAcquistoSendcloud($corriere)) {
            SendcloudIntegrazione::encode($spedizione, array_merge(
                SendcloudIntegrazione::decode($spedizione),
                $payload,
            ));
        } elseif ($corriere && PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere)) {
            LiccardiTmsIntegrazione::encode($spedizione, array_merge(
                LiccardiTmsIntegrazione::decode($spedizione),
                $payload,
            ));
        } elseif ($corriere && PiattaformaCorriere::usaAcquistoSpedisciOnline($corriere->piattaforma ?? null)) {
            SpedisciOnlineIntegrazione::encode($spedizione, array_merge(
                SpedisciOnlineIntegrazione::decode($spedizione),
                $payload,
            ));
        }

        $spedizione->forceFill(['ldverro' => true])->saveQuietly();
    }
}
