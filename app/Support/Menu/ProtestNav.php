<?php

namespace App\Support\Menu;

/**
 * Menu lateral de RECLAMAÇÕES (protests), no padrão de docs/PADRAO_MENU_LATERAL.md. Contexto: `variant` (`dispatch`, `services` ou `partner`),
 * o mesmo que cada `protest/*\/menu.blade.php` informa ao chamar `<x-nav.sidebar>`.
 */
final class ProtestNav extends SidebarNav
{
    public const DEFAULT_MODULE = 'reclamacoes';

    public const VARIANTS = ['dispatch', 'services', 'partner'];

    public static function sampleContexts(): array
    {
        return array_map(fn (string $variant) => ['variant' => $variant], self::VARIANTS);
    }

    public static function modules(array $context = []): array
    {
        $variant = (string) ($context['variant'] ?? 'dispatch');

        return match ($variant) {
            'services', 'partner' => self::module('Serviço', [
                self::group('reclamacao', 'Reclamação', 'ri-alarm-warning-line', array_values(array_filter([
                    self::item('aguardando', 'Aguardando resolução', 'protests.services.main', 'ri-todo-line', [], [['components.count.protest.count-protests', ['type' => 'U']]]),
                    $variant === 'services' ? self::item('acompanhamento', 'Acompanhamento de reclamação', 'protests.services.accompany', 'ri-eye-line', [], [['components.count.protest.count-protests', ['type' => 'M']]]) : null,
                    self::item('historico', 'Meu histórico', 'protests.services.history', 'ri-history-line'),
                ]))),
            ]),
            default => self::module('Despacho', [
                self::group('painel', 'Visão geral', 'ri-dashboard-3-line', [
                    self::item('dashboard', 'Painel', 'protests.dashboard', 'ri-dashboard-3-line'),
                ]) + ['flat' => true],
                self::group('cip', 'CIP', 'ri-inbox-line', [
                    self::item('cip-aberto', 'CIP em aberto', 'protests.dispatch.lists', 'ri-inbox-line', [], [['protests.dispatch.menu-open-badge', ['type' => 'normal']]]),
                    self::item('cip-andamento', 'CIP em andamento', 'protests.dispatch.monitoring', 'ri-loader-4-line', [], [['protests.dispatch.menu-monitoring-badge', ['type' => 'normal']]]),
                    self::item('cip-fechados', 'CIP fechados', 'protests.dispatch.closeds', 'ri-checkbox-circle-line'),
                ]),
                self::group('construcao', 'Construção', 'ri-building-line', [
                    self::item('con-aberto', 'Construção em aberto', 'protests.dispatch_btzero.lists', 'ri-inbox-line', [], [['protests.dispatch.menu-open-badge', ['type' => 'btzero']]]),
                    self::item('con-andamento', 'Construção em andamento', 'protests.dispatch_btzero.monitoring', 'ri-loader-4-line', [], [['protests.dispatch.menu-monitoring-badge', ['type' => 'btzero']]]),
                    self::item('con-fechados', 'Construção fechados', 'protests.dispatch_btzero.closeds', 'ri-checkbox-circle-line'),
                ]),
                ['can' => 'admin'] + self::group('configuracao', 'Configuração', 'ri-settings-3-line', [
                    self::item('codigos', 'Códigos CIP/Construção', 'admin.protests.measure_codes', 'ri-settings-3-line', [], [], [], ['can' => 'admin']),
                ]),
            ]),
        };
    }

    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<string, array<string, mixed>>
     */
    private static function module(string $hint, array $groups): array
    {
        return [
            'reclamacoes' => [
                'label'  => 'Reclamações',
                'icon'   => 'ri-alarm-warning-line',
                'hint'   => $hint,
                'groups' => $groups,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private static function group(string $key, string $label, string $icon, array $items): array
    {
        return ['key' => $key, 'label' => $label, 'icon' => $icon, 'items' => $items];
    }
}
