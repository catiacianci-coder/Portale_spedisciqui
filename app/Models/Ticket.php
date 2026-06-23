<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_stato_id',
        'ticket_tipo_problema_id',
        'ordine_id',
        'spedizione_id',
        'oggetto',
        'cliente_ultima_visualizacao_at',
        'cliente_ultima_messaggio_id_visto',
        'campo_1',
        'campo_2',
        'campo_3',
        'campo_4',
        'campo_5',
        'campo_6',
        'campo_7',
        'campo_8',
        'campo_9',
    ];

    protected function casts(): array
    {
        return [
            'cliente_ultima_visualizacao_at' => 'datetime',
        ];
    }

    /** Ticket con ultimo messaggio del team non ancora «letto» dal cliente. */
    public function scopeComRespostaStaffNaoLidaParaCliente(Builder $query, int $userId): Builder
    {
        return $query
            ->where('tickets.user_id', $userId)
            ->whereExists(function (QueryBuilder $sub): void {
                $sub->selectRaw('1')
                    ->from('ticket_messaggi as tm')
                    ->whereColumn('tm.ticket_id', 'tickets.id')
                    ->whereRaw('tm.id = (SELECT MAX(m.id) FROM ticket_messaggi m WHERE m.ticket_id = tickets.id)')
                    ->where('tm.is_staff', 1)
                    ->whereRaw('tm.id > COALESCE(tickets.cliente_ultima_messaggio_id_visto, 0)');
            })
            ->orderByRaw('(SELECT COALESCE(MAX(id), 0) FROM ticket_messaggi WHERE ticket_messaggi.ticket_id = tickets.id) DESC');
    }

    public static function primeiroComRespostaStaffNaoLidaParaUser(int $userId): ?self
    {
        return static::query()->comRespostaStaffNaoLidaParaCliente($userId)->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stato(): BelongsTo
    {
        return $this->belongsTo(TicketStato::class, 'ticket_stato_id');
    }

    public function tipoProblema(): BelongsTo
    {
        return $this->belongsTo(TicketTipoProblema::class, 'ticket_tipo_problema_id');
    }

    public function ordine(): BelongsTo
    {
        return $this->belongsTo(ordine::class, 'ordine_id');
    }

    public function spedizione(): BelongsTo
    {
        return $this->belongsTo(spedizione::class, 'spedizione_id');
    }

    public function messaggi(): HasMany
    {
        return $this->hasMany(TicketMessaggio::class, 'ticket_id')->orderBy('created_at');
    }

    public function isEmEspera(): bool
    {
        return $this->stato?->codigo === TicketStato::CODIGO_EM_ESPERA;
    }

    public function ultimoMessaggio(): ?TicketMessaggio
    {
        return $this->messaggi()->orderByDesc('id')->first();
    }

    /** ID spedizioni citate (spedizione_id, campo_2 entrega, ID in campo_1 multi). */
    public function referencedSpedizioneIds(): array
    {
        $ids = [];
        if ($this->spedizione_id) {
            $ids[] = (int) $this->spedizione_id;
        }

        $codigo = $this->tipoProblema?->codigo;

        if ($codigo === TicketTipoProblema::CODIGO_ENTREGA) {
            $c2 = trim((string) ($this->campo_2 ?? ''));
            if ($c2 !== '' && ctype_digit($c2)) {
                $ids[] = (int) $c2;
            }

            return array_values(array_unique($ids));
        }

        if (in_array($codigo, [
            TicketTipoProblema::CODIGO_ETIQUETA_NAO_GERADA,
            TicketTipoProblema::CODIGO_TRACKING,
            TicketTipoProblema::CODIGO_RIPRENOTAZIONE_RITIRO,
        ], true)) {
            $c1 = trim((string) ($this->campo_1 ?? ''));
            if ($c1 !== '') {
                if (str_contains($c1, ',')) {
                    foreach (explode(',', $c1) as $part) {
                        $n = (int) trim($part);
                        if ($n > 0) {
                            $ids[] = $n;
                        }
                    }
                } elseif (ctype_digit($c1)) {
                    $ids[] = (int) $c1;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function clientePodeEnviarNovaMensagem(): bool
    {
        $this->loadMissing('stato');
        $codigo = $this->stato?->codigo;

        if ($codigo === TicketStato::CODIGO_RESOLVIDO) {
            return false;
        }

        if ($codigo === TicketStato::CODIGO_EM_TRATAMENTO) {
            return false;
        }

        if ($codigo === TicketStato::CODIGO_EM_ESPERA) {
            return true;
        }

        $ultimo = $this->ultimoMessaggio();
        if ($ultimo === null) {
            return false;
        }

        return $ultimo->is_staff;
    }
}
