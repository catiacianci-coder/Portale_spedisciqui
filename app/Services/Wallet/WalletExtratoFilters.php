<?php

namespace App\Services\Wallet;

use App\Support\FiltriTabella;
use Illuminate\Http\Request;

final class WalletExtratoFilters
{
    public function __construct(
        public int $perPage = 10,
        public string $periodo = '',
        public string $dataDe = '',
        public string $dataAte = '',
        public int $walletDescrizioneId = 0,
        public string $usuario = '',
        public int $userId = 0,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $periodo = (string) $request->input('periodo', '');
        if (! in_array($periodo, ['', 'oggi', '7', '30', 'custom'], true)) {
            $periodo = '';
        }

        $dataDe = is_string($request->input('data_de')) ? trim($request->input('data_de')) : '';
        $dataAte = is_string($request->input('data_ate')) ? trim($request->input('data_ate')) : '';

        return new self(
            perPage: FiltriTabella::perPage($request),
            periodo: $periodo,
            dataDe: $dataDe,
            dataAte: $dataAte,
            walletDescrizioneId: max(0, (int) $request->input('wallet_descrizione_id', 0)),
            usuario: trim((string) $request->input('usuario', $request->input('email', ''))),
            userId: max(0, (int) $request->input('user_id', 0)),
        );
    }

    public function hasActiveFilters(): bool
    {
        return $this->periodo !== ''
            || $this->walletDescrizioneId > 0
            || $this->usuario !== ''
            || $this->userId > 0;
    }

    public function customPeriodoSemDatas(): bool
    {
        return $this->periodo === 'custom'
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataDe)
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->dataAte);
    }
}
