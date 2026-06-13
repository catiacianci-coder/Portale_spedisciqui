<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Punto di ritiro destinatario salvato in sessione preventivo (checkout).
 */
final class PuntoConsegnaSessione
{
    /**
     * Consegna non a domicilio: locker, Punto Poste, ufficio, ecc.
     */
    public static function consegnaRichiedePunto(?string $consegna): bool
    {
        $consegna = trim((string) $consegna);
        if ($consegna === '') {
            return false;
        }

        return ! str_contains(mb_strtolower($consegna), 'domicilio');
    }

    public static function richiestoPerRiga(array $riga): bool
    {
        if (! self::consegnaRichiedePunto((string) ($riga['corriere']['consegna'] ?? ''))) {
            return false;
        }

        return PiattaformaCorriere::normalizza($riga['corriere']['piattaforma'] ?? '') === PiattaformaCorriere::SENDCLOUD;
    }

    public static function messaggioSelezionaObbligatorio(array $riga): string
    {
        $label = CorrierePuntoEtichetta::etichettaSelezionaCheckout(
            (string) ($riga['corriere']['punto_consegna'] ?? ''),
        );

        return $label !== null
            ? $label.'.'
            : 'Seleziona un punto di consegna.';
    }

    public static function destinazioneHaPunto(array $dest): bool
    {
        return (int) ($dest['to_service_point'] ?? 0) > 0
            && trim((string) ($dest['via'] ?? '')) !== '';
    }

    /**
     * @param  array<string, mixed>  $punto  id, name, street, house_number, postal_code, city, to_post_number?
     */
    public static function applicaInPreventivo(array &$preventivo, array $punto): void
    {
        $ind = is_array($preventivo['indirizzi'] ?? null) ? $preventivo['indirizzi'] : [];
        $dest = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];

        $ind['destinazione'] = self::unisciDestinatarioConPunto($dest, $punto);
        $preventivo['indirizzi'] = $ind;
    }

    /**
     * Sostituisce l'indirizzo fisico del destinatario con quello del punto; mantiene contatti.
     *
     * @param  array<string, mixed>  $dest
     * @param  array<string, mixed>  $punto
     * @return array<string, mixed>
     */
    public static function unisciDestinatarioConPunto(array $dest, array $punto): array
    {
        $streetRaw = trim((string) ($punto['street'] ?? ''));
        if ($streetRaw === '—') {
            $streetRaw = '';
        }
        $house = trim((string) ($punto['house_number'] ?? ''));
        if ($house === '—') {
            $house = '';
        }
        $addressLine = trim((string) ($punto['address_line'] ?? ''));
        if ($addressLine === '—') {
            $addressLine = '';
        }

        $fonteVia = $streetRaw !== '' ? $streetRaw : $addressLine;
        [$street, $house] = IndirizzoViaCivico::perSendcloud(
            $fonteVia,
            $house,
            $streetRaw !== '' ? $streetRaw : null,
        );

        if ($addressLine === '') {
            $addressLine = IndirizzoSpedizioneSnapshot::componeIndirizzo($street, $house);
        }

        $capPunto = self::normalizzaValorePunto($punto['postal_code'] ?? '');
        $cittaPunto = self::normalizzaValorePunto($punto['city'] ?? '');

        $dest['via'] = $street;
        $dest['numero'] = $house;
        $dest['indirizzo'] = $street;
        if ($capPunto !== '') {
            $dest['cap'] = $capPunto;
        }
        if ($cittaPunto !== '') {
            $dest['comune'] = $cittaPunto;
        }
        $dest['to_service_point'] = (int) ($punto['id'] ?? 0);
        $dest['nome_punto'] = trim((string) ($punto['name'] ?? ''));
        $dest['to_post_number'] = trim((string) ($punto['to_post_number'] ?? ''));
        $dest['consegna_a_punto'] = true;
        $dest['punto_consegna'] = [
            'id' => (int) ($punto['id'] ?? 0),
            'name' => trim((string) ($punto['name'] ?? '')),
            'to_post_number' => trim((string) ($punto['to_post_number'] ?? '')),
            'street' => $street,
            'house_number' => $house,
            'address_line' => $addressLine,
            'postal_code' => $capPunto !== '' ? $capPunto : null,
            'city' => $cittaPunto !== '' ? $cittaPunto : null,
        ];

        return $dest;
    }

    /**
     * Applica indirizzo punto su snapshot destinazione (es. prima di salvare su spedizionis).
     *
     * @param  array<string, mixed>  $dest
     * @return array<string, mixed>
     */
    public static function destinazioneConIndirizzoPunto(array $dest): array
    {
        $punto = self::puntoDaDestinazione($dest);
        if ($punto === null) {
            return $dest;
        }

        return self::unisciDestinatarioConPunto($dest, $punto);
    }

    /**
     * @param  array<string, mixed>  $dest
     * @return array<string, mixed>|null
     */
    public static function puntoDaDestinazione(array $dest): ?array
    {
        $id = (int) ($dest['to_service_point'] ?? 0);
        if ($id < 1) {
            $snapshot = is_array($dest['punto_consegna'] ?? null) ? $dest['punto_consegna'] : [];
            $id = (int) ($snapshot['id'] ?? 0);
            if ($id < 1) {
                return null;
            }

            return self::normalizzaPunto([
                'id' => $id,
                'name' => $snapshot['name'] ?? $dest['nome_punto'] ?? '',
                'to_post_number' => $snapshot['to_post_number'] ?? $dest['to_post_number'] ?? '',
                'street' => $snapshot['street'] ?? $dest['via'] ?? '',
                'house_number' => $snapshot['house_number'] ?? $dest['numero'] ?? '',
                'address_line' => $snapshot['address_line'] ?? $dest['indirizzo'] ?? '',
                'postal_code' => $snapshot['postal_code'] ?? $dest['cap'] ?? '',
                'city' => $snapshot['city'] ?? $dest['comune'] ?? '',
            ]);
        }

        return self::normalizzaPunto([
            'id' => $id,
            'name' => $dest['nome_punto'] ?? '',
            'to_post_number' => $dest['to_post_number'] ?? '',
            'street' => $dest['via'] ?? '',
            'house_number' => $dest['numero'] ?? '',
            'address_line' => $dest['indirizzo'] ?? '',
            'postal_code' => $dest['punto_consegna']['postal_code'] ?? $dest['cap'] ?? '',
            'city' => $dest['punto_consegna']['city'] ?? $dest['comune'] ?? '',
        ]);
    }

    /**
     * Valida e salva il punto da checkout (JSON o campi singoli).
     *
     * @return string|null Messaggio errore
     */
    public static function sincronizzaDaRichiesta(array &$preventivo, array $riga, Request $request): ?string
    {
        if (! self::richiestoPerRiga($riga)) {
            return null;
        }

        $punto = self::puntoDaRichiesta($request);
        if ($punto === null) {
            return self::messaggioSelezionaObbligatorio($riga);
        }

        self::applicaInPreventivo($preventivo, $punto);

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function puntoDaRichiesta(Request $request): ?array
    {
        $json = $request->input('punto_consegna_json');
        if (is_string($json) && trim($json) !== '') {
            $decoded = json_decode($json, true);

            return is_array($decoded) ? self::normalizzaPunto($decoded) : null;
        }

        $id = (int) $request->input('to_service_point', 0);
        if ($id < 1) {
            return null;
        }

        return self::normalizzaPunto([
            'id' => $id,
            'name' => $request->input('nome_punto'),
            'to_post_number' => $request->input('to_post_number'),
            'street' => $request->input('punto_street'),
            'house_number' => $request->input('punto_house_number'),
            'address_line' => $request->input('punto_address_line'),
            'postal_code' => $request->input('punto_postal_code'),
            'city' => $request->input('punto_city'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $punto
     * @return array<string, mixed>|null
     */
    private static function normalizzaPunto(array $punto): ?array
    {
        $id = (int) ($punto['id'] ?? 0);
        if ($id < 1) {
            return null;
        }

        $street = trim((string) ($punto['street'] ?? ''));
        if ($street === '' || $street === '—') {
            $line = trim((string) ($punto['address_line'] ?? ''));
            if ($line !== '' && $line !== '—') {
                $street = $line;
            }
        }

        if ($street === '' || $street === '—') {
            return null;
        }

        $house = trim((string) ($punto['house_number'] ?? ''));
        if ($house === '—') {
            $house = '';
        }

        $postalCode = trim((string) ($punto['postal_code'] ?? ''));
        $city = trim((string) ($punto['city'] ?? ''));

        return [
            'id' => $id,
            'name' => trim((string) ($punto['name'] ?? '')),
            'to_post_number' => trim((string) ($punto['to_post_number'] ?? '')),
            'street' => $street,
            'house_number' => $house,
            'address_line' => trim((string) ($punto['address_line'] ?? '')),
            'postal_code' => self::normalizzaValorePunto($postalCode),
            'city' => self::normalizzaValorePunto($city),
        ];
    }

    private static function normalizzaValorePunto(mixed $value): string
    {
        $value = trim((string) $value);

        return ($value === '' || $value === '—') ? '' : $value;
    }
}
