<?php

namespace App\Services\Rimborso;

use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\User;
use App\Support\RimborsoRecordBuilder;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RimborsoSolicitacaoService
{
    public function __construct(
        private readonly RimborsoElegibilidadeService $elegibilidade,
        private readonly RimborsoEsecuzionePagamentoService $pagamento,
        private readonly RimborsoAnnullamentoEtichettaService $annullamentoEtichetta,
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

        $this->annullamentoEtichetta->annullaAllaRichiesta($spedizione, $motivo);

        /** @var rimborso $rimborso */
        $rimborso = DB::transaction(function () use ($spedizione, $user, $motivo): rimborso {
            $attrs = RimborsoRecordBuilder::attributiRichiestaDaSpedizione($spedizione, $motivo);

            $rimborso = rimborso::query()->create($attrs);
            $rimborso->update(['token' => 'RIMB-'.$rimborso->id]);

            if (RimborsoRecordBuilder::eseguiPagamentoImmediato($rimborso)) {
                $this->pagamento->esegui($rimborso->fresh(), $spedizione->fresh(), $user);
            }

            return $rimborso->fresh(['spedizione', 'ordine']);
        });

        $accreditato = $rimborso->fresh()->isAccreditato();

        return [
            'rimborso' => $rimborso,
            'credito_immediato' => RimborsoRecordBuilder::eseguiPagamentoImmediato($rimborso) && $accreditato,
        ];
    }
}
