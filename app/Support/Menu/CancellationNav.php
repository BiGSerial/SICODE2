<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral de quem SOLICITA cancelamento e descancelamento de obras (as telas `cancellations.*`), no padrão de
 * docs/PADRAO_MENU_LATERAL.md. A aprovação é da Engenharia (módulo Cancelamentos do `EngineerNav`).
 */
final class CancellationNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'cancelamentos';

    public static function modules(array $context = []): array
    {
        return self::module('cancelamentos', 'Cancelamentos', 'ri-close-circle-line', 'Pedidos de cancelamento', [
            self::group('solicitar', 'Solicitar', 'ri-send-plane-line', [
                self::item('cancelar', 'Solicitar cancelamento', 'cancellations.index', 'ri-close-circle-line'),
                self::item('descancelar', 'Solicitar descancelamento', 'cancellations.uncancellation', 'ri-arrow-go-back-line'),
            ]),
            self::group('acompanhar', 'Acompanhar', 'ri-history-line', [
                self::item('minhas', 'Minhas solicitações', 'cancellations.history', 'ri-history-line', ['cancellations.show']),
            ]),
        ]);
    }
}
