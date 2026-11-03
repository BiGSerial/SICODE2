<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral de ADS (Anúncios de obra), no padrão de docs/PADRAO_MENU_LATERAL.md. A tela hoje é uma só (o painel de ADS solicitadas);
 * novas telas de ADS entram aqui.
 */
final class AdsNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'ads';

    public static function modules(array $context = []): array
    {
        return self::module('ads', 'ADS', 'ri-survey-line', 'Solicitações de ADS', [
            self::group('painel', 'Painel', 'ri-dashboard-3-line', [
                self::item('painel', 'Painel de ADS', 'ads.dashboard', 'ri-dashboard-3-line'),
            ], ['flat' => true]),
        ]);
    }

    /**
     * Itens de ADS no dropdown "BUSCAR" do topo (mesma definição do lateral).
     *
     * @return array<int, array<string, string>>
     */
    public static function topItems(): array
    {
        return [['label' => 'ADS SOLICITADAS', 'route' => 'ads.dashboard', 'icon' => 'ri-survey-line']];
    }
}
