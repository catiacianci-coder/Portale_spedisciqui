<?php

namespace App\Support;

final class BackofficeShortcuts
{
    /**
     * @return list<array{route: string, label: string, icon: string}>
     */
    public static function piuUsate(): array
    {
        return [
            [
                'route' => 'backoffice.ordini.index',
                'label' => 'Ordini',
                'icon' => 'fa-boxes',
            ],
            [
                'route' => 'backoffice.spedizioni.index',
                'label' => 'Spedizioni',
                'icon' => 'fa-truck',
            ],
            [
                'route' => 'backoffice.ricariche.index',
                'label' => 'Ricariche wallet',
                'icon' => 'fa-coins',
            ],
            [
                'route' => 'backoffice.rimborsi.index',
                'label' => 'Rimborsi',
                'icon' => 'fa-money-bill-transfer',
            ],
        ];
    }
}
