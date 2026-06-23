<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class StripeEstrattoFilters
{
    public function __construct(
        public string $period = '30',
        public string $dataDa = '',
        public string $dataA = '',
        public int $limit = 50,
        public ?string $startingAfter = null,
        public ?string $endingBefore = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $period = (string) $request->input('period', '30');
        $allowedPeriod = ['oggi', '7', '15', '30', 'custom'];
        if (! in_array($period, $allowedPeriod, true)) {
            $period = '30';
        }

        $limit = (int) $request->input('limit', 50);
        if (! in_array($limit, [25, 50, 100], true)) {
            $limit = 50;
        }

        $startingAfter = trim((string) $request->input('starting_after', ''));
        $endingBefore = trim((string) $request->input('ending_before', ''));

        return new self(
            period: $period,
            dataDa: trim((string) $request->input('data_da', '')),
            dataA: trim((string) $request->input('data_a', '')),
            limit: $limit,
            startingAfter: $startingAfter !== '' ? $startingAfter : null,
            endingBefore: $endingBefore !== '' ? $endingBefore : null,
        );
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: list<string>}
     */
    public function intervallo(): array
    {
        $errors = [];
        $now = now();

        if ($this->period === 'custom') {
            if ($this->dataDa === '' || $this->dataA === '') {
                $errors[] = 'Per il periodo personalizzato servono entrambe le date (da/a).';

                return [null, null, $errors];
            }
            try {
                $d1 = Carbon::createFromFormat('Y-m-d', $this->dataDa)->startOfDay();
                $d2 = Carbon::createFromFormat('Y-m-d', $this->dataA)->endOfDay();
                if ($d1->gt($d2)) {
                    $errors[] = 'La data "da" non può essere successiva alla data "a".';
                }

                return [$d1, $d2, $errors];
            } catch (\Throwable) {
                $errors[] = 'Date non valide nel periodo personalizzato.';

                return [null, null, $errors];
            }
        }

        return match ($this->period) {
            'oggi' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), $errors],
            '7' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay(), $errors],
            '15' => [$now->copy()->subDays(15)->startOfDay(), $now->copy()->endOfDay(), $errors],
            default => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay(), $errors],
        };
    }

    /**
     * @return array<string, string>
     */
    public function queryParams(): array
    {
        return array_filter([
            'period' => $this->period,
            'data_da' => $this->dataDa,
            'data_a' => $this->dataA,
            'limit' => (string) $this->limit,
        ], static fn (string $v): bool => $v !== '');
    }
}
