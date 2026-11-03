<?php

namespace App\Support\Menu;

use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral da CONTRATAÇÃO de obra (telas `construction.*`), no padrão de docs/PADRAO_MENU_LATERAL.md. Contexto: `service`, a atividade
 * aberta (o uuid vai nos parâmetros de cada rota e o nome no título da lista).
 */
final class HiringNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'contratacao';

    public static function sampleContexts(): array
    {
        return [['service' => (object) ['uuid' => 'uuid-teste', 'service' => 'Contratação']]];
    }

    public static function modules(array $context = []): array
    {
        $service = $context['service'] ?? null;

        if (!$service) {
            return [];
        }

        $params = ['service' => $service->uuid];
        $name   = (string) $service->service;

        return self::module('contratacao', $name !== '' ? $name : 'Contratação', 'ri-hammer-line', 'Contratação de obras', [
            self::group('acompanhamento', 'Acompanhamento', 'ri-list-check-2', [
                self::item('lista', "Lista de {$name}", 'construction.main', 'ri-money-dollar-box-line', ['construction.production', 'construction.responser.main'], [['components.count.countnotes', ['service' => $service->uuid]]], $params),
                self::item('contratar', 'À contratar', 'construction.accompany', 'ri-timer-line', [], [['construction.hiring.counts.countmycontrol']], $params),
                self::item('contratadas', 'Obras contratadas', 'construction.historic', 'ri-checkbox-circle-line', [], [['construction.hiring.counts.check-hiring']], $params),
                self::item('aguardando', 'Aguardando retorno interno', 'construction.waiting', 'ri-timer-2-line', ['construction.returned'], [['construction.hiring.counts.count-return']], $params),
            ]),
            self::group('consulta', 'Consulta', 'ri-search-eye-line', [
                self::item('notas', 'Consulta de notas/OV', 'construction.lookatnotes', 'ri-search-eye-line', [], [], $params),
            ]),
        ]);
    }
}
