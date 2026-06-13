<?php

namespace App\Console\Commands;

use App\Models\comune;
use Illuminate\Console\Command;

class FixComuniCap extends Command
{
    protected $signature = 'fix:comuni-cap';
    protected $description = 'Normalizza i CAP dei comuni a 5 cifre.';

    public function handle(): int
    {
        $rows = comune::query()
            ->whereRaw('CHAR_LENGTH(cap) < 5')
            ->get(['id', 'cap']);

        foreach ($rows as $row) {
            $row->cap = str_pad((string) $row->cap, 5, '0', STR_PAD_LEFT);
            $row->save();
        }

        $remaining = comune::query()->whereRaw('CHAR_LENGTH(cap) < 5')->count();
        $this->info("CAP corretti: {$rows->count()}; ancora corti: {$remaining}");

        return self::SUCCESS;
    }
}
