<?php

namespace App\Console\Commands;

use App\Models\spedizione;
use App\Models\tariffa_spedizione;
use App\Support\TariffaSpedizioneClienteIvato;
use Illuminate\Console\Command;

class ImportTariffeSpedizioni extends Command
{
    protected $signature = 'import:tariffe-spedizioni
                            {file? : File in storage/app (default tabella_tariffe_spedizioni.csv)}
                            {--truncate : Svuota tariffe_spediziones prima dell\'import}
                            {--append : Aggiunge righe senza svuotare}';

    protected $description = 'Importa breakdown economico spedizioni da CSV (tabella_tariffe_spedizioni.csv)';

    public function handle(): int
    {
        $relative = $this->argument('file') ?? 'tabella_tariffe_spedizioni.csv';
        $path = str_contains($relative, DIRECTORY_SEPARATOR)
            ? $relative
            : storage_path('app/'.$relative);

        if (! file_exists($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        if ($this->option('truncate') && ! $this->option('append')) {
            tariffa_spedizione::query()->delete();
            $this->info('Tabella tariffe_spediziones svuotata.');
        }

        $imported = $this->importFile($path);
        $this->info("Import completato da ".basename($path).". Righe importate: {$imported}");

        return self::SUCCESS;
    }

    private function importFile(string $path): int
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines || count($lines) < 2) {
            $this->warn('Nessuna riga dati nel CSV (solo intestazione o file vuoto).');

            return 0;
        }

        $first = trim(preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]) ?? $lines[0]);
        if (! str_contains($first, ';')) {
            $this->warn(
                'Il file contiene solo la definizione colonne (una per riga). '
                .'Per importare dati usa un CSV con separatore ; e una riga di intestazione orizzontale.'
            );

            return 0;
        }

        $headers = array_map('trim', explode(';', $first));

        $imported = 0;
        foreach (array_slice($lines, 1) as $index => $line) {
            $values = array_map('trim', explode(';', $line));
            if (count($values) !== count($headers)) {
                $this->warn('Riga '.($index + 2).' saltata: colonne non coerenti.');

                continue;
            }

            $row = array_combine($headers, $values);
            $spedizioneId = $this->toInt($row['spedizione_id'] ?? null);
            if ($spedizioneId === null || $spedizioneId < 1) {
                $this->warn('Riga '.($index + 2).' saltata: spedizione_id mancante.');

                continue;
            }

            if (! spedizione::query()->whereKey($spedizioneId)->exists()) {
                $this->warn('Riga '.($index + 2)." saltata: spedizione #{$spedizioneId} non trovata.");

                continue;
            }

            $totaleSpedizione = $this->toDecimal($row['totale_spedizione'] ?? null) ?? 0;
            $clienteIvato = $this->toDecimal($row['cliente_ivato'] ?? null);
            if ($clienteIvato === null) {
                $clienteIvato = TariffaSpedizioneClienteIvato::calcolaDaNetto(
                    $totaleSpedizione,
                    TariffaSpedizioneClienteIvato::aliquotaIva(),
                );
            }

            $attrs = [
                'spedizione_id' => $spedizioneId,
                'codice_interno' => $this->strOrNull($row['codice_interno'] ?? null),
                'costo_trasporto' => $this->toDecimal($row['costo_trasporto'] ?? null) ?? 0,
                'costo_fuel' => $this->toDecimal($row['costo_fuel'] ?? null) ?? 0,
                'ricarico_trasporto' => $this->toDecimal($row['ricarico_trasporto'] ?? null) ?? 0,
                'totale_cliente' => $this->toDecimal($row['totale_cliente'] ?? null) ?? 0,
                'costo_servizi_aggiuntivi' => $this->toDecimal($row['costo_servizi_aggiuntivi'] ?? null) ?? 0,
                'cliente_servizi_aggiuntivi' => $this->toDecimal($row['cliente_servizi_aggiuntivi'] ?? null) ?? 0,
                'totale_spedizione' => $totaleSpedizione,
                'margine_lordo' => $this->toDecimal($row['margine_lordo'] ?? null) ?? 0,
                'cliente_ivato' => $clienteIvato,
            ];

            tariffa_spedizione::query()->updateOrCreate(
                ['spedizione_id' => $spedizioneId],
                $attrs,
            );

            $imported++;
        }

        return $imported;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function toDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $v = str_replace(',', '.', trim((string) $value));
        if (! is_numeric($v)) {
            return null;
        }

        return round((float) $v, 2);
    }

    private function strOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $t = trim((string) $value);

        return $t !== '' ? $t : null;
    }
}
