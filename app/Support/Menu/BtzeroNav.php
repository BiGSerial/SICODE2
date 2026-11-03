<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral do BT ZERO (informes de patrimônio do SMC), no padrão de docs/PADRAO_MENU_LATERAL.md. O dropdown "SMC" do topo lê daqui
 * (`topItems()`).
 */
final class BtzeroNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'btzero';

    public static function modules(array $context = []): array
    {
        return self::module('btzero', 'BT Zero', 'ri-flashlight-line', 'Patrimônio do SMC', [
            self::group('informes', 'Informes de patrimônio', 'ri-file-list-3-line', [
                self::item('principal', 'Principal', 'btzero.main', 'ri-bank-line'),
                self::item('informar', 'Informar patrimônio SMC', 'btzero.btzeroReport', 'ri-information-line'),
                self::item('rejeitados', 'Informes SMC rejeitados', 'btzero.smcRejecteds', 'ri-close-circle-line', [], [['btzero.count.return-ramal-count']]),
                self::item('informados', 'Patrimônios SMC informados', 'btzero.histInform', 'ri-list-check'),
            ]),
        ]);
    }

    /**
     * Itens do dropdown "SMC" do topo (mesma definição do lateral).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function topItems(): array
    {
        return [['label' => 'INFORME SMC', 'route' => 'btzero.main', 'icon' => 'ri-eye-fill']];
    }
}
