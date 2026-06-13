<?php

namespace App\Services\Rimborso;

use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\User;
use App\Services\SpedisciOnline\SpedisciOnlineLabelService;
use App\Services\SpedizioneStatoService;
use App\Support\PiattaformaCorriere;
use App\Support\RimborsoRecordBuilder;
use App\Support\SpedisciOnlineIntegrazione;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RimborsoSolicitacaoService
{
    public function __construct(
        private readonly RimborsoElegibilidadeService $elegibilidade,
        private readonly RimborsoEsecuzionePagamentoService $pagamento,
        private readonly SpedisciOnlineLabelService $labelService,
        private readonly OrdineCrRimborsoSyncService $ordineCr,
    ) {}

    /**
     * @return array{rimborso: rimborso, credito_immediato: bool}
     */
    public function solicitar(spedizione $spedizione, User $user): array
    {
        $spedizione->loadMissing(['ordine', 'rimborso', 'corriereRecord']);

        if ((int) $spedizione->user_id !== (int) $user->id) {
            throw new DomainException('Spedizione non autorizzata.');
        }

        $this->elegibilidade->assertElegivel($spedizione);

        $motivo = rimborso::resolveMotivoFromSpedizione($spedizione);
        $conEtichetta = $motivo === rimborso::MOTIVO_CON_ETICHETTA;

        if ($conEtichetta) {
            $this->cancelarSuSpedisciOnline($spedizione);
        }

        /** @var rimborso $rimborso */
        $rimborso = DB::transaction(function () use ($spedizione, $user, $motivo, $conEtichetta): rimborso {
            $attrs = RimborsoRecordBuilder::attributiRichiestaDaSpedizione($spedizione, $motivo);

            $rimborso = rimborso::query()->create($attrs);
            $rimborso->update(['token' => 'RIMB-'.$rimborso->id]);

            if ($conEtichetta) {
                $spedizione->update(['cancellata_il' => $attrs['data_richiesta']]);
                SpedizioneStatoService::segnaAnnullata($spedizione->fresh());
            }

            if (RimborsoRecordBuilder::eseguiPagamentoImmediato($rimborso)) {
                $this->pagamento->esegui($rimborso->fresh(), $spedizione->fresh(), $user);
            }

            $this->ordineCr->syncPerOrdineId($spedizione->ordine_id);

            return $rimborso->fresh(['spedizione', 'ordine']);
        });

        $accreditato = $rimborso->fresh()->isAccreditato();

        return [
            'rimborso' => $rimborso,
            'credito_immediato' => RimborsoRecordBuilder::eseguiPagamentoImmediato($rimborso) && $accreditato,
        ];
    }

    private function cancelarSuSpedisciOnline(spedizione $spedizione): void
    {
        $piattaforma = $spedizione->corriereRecord?->piattaforma;
        if (! PiattaformaCorriere::usaAcquistoSpedisciOnline($piattaforma)) {
            return;
        }

        if (SpedisciOnlineIntegrazione::etichettaCancellata($spedizione)) {
            return;
        }

        if (! $spedizione->esiste_integrazione && ! $spedizione->ldv_emessa_il) {
            return;
        }

        $outcome = $this->labelService->deleteFromSpedizione($spedizione);
        if (! $outcome->ok) {
            throw new DomainException(
                'Non è stato possibile annullare l’etichetta. Riprova più tardi.'
            );
        }
    }
}
