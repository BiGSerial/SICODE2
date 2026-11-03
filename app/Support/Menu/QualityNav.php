<?php

namespace App\Support\Menu;

use App\Services\Quality\QualityBoard;
use App\Support\Menu\Concerns\NavBuilders;

/**
 * Menu lateral da QUALIDADE, no padrão de docs/PADRAO_MENU_LATERAL.md. Os grupos dependem do papel de quem olha (contexto `mode`:
 * `manager`, ou `member` com `n1` e/ou `n2`): a Gestão vê Operação e Configuração; a equipe vê as etapas do N1 e/ou do N2.
 *
 * As abas (`quality.n1`, `quality.n2`) e as filas (`quality.queue`) compartilham a rota e mudam só pelo parâmetro, então o item ativo é
 * localizado por uma chave `rota:parâmetro` (ex.: `quality.n1:despachar`); quem chama `locate()` monta essa chave com `routeKey()`.
 * As pílulas de contagem são do componente `quality.menu-count`.
 */
final class QualityNav extends SidebarNav
{
    use NavBuilders;

    public const DEFAULT_MODULE = 'qualidade';

    private const N1_ICONS = ['despachar' => 'ri-send-plane-line', 'com-usuarios' => 'ri-team-line', 'analisar' => 'ri-search-line', 'devolvidos' => 'ri-arrow-go-back-line', 'no-n2' => 'ri-timer-2-line'];

    private const N2_ICONS = ['decidir' => 'ri-checkbox-circle-line', 'encerrar' => 'ri-error-warning-line', 'com-n1' => 'ri-arrow-go-back-line'];

    public static function sampleContexts(): array
    {
        return [
            ['mode' => 'manager'],
            ['mode' => 'member', 'n1' => true, 'n2' => false],
            ['mode' => 'member', 'n1' => false, 'n2' => true],
            ['mode' => 'member', 'n1' => true, 'n2' => true],
        ];
    }

    /**
     * Chave usada para localizar o item ativo: o nome da rota, com o parâmetro que distingue abas e filas (`tab` ou `fila`).
     */
    public static function routeKey(?string $route, ?string $tab = null, ?string $fila = null): string
    {
        $route = (string) $route;

        return match ($route) {
            'quality.n1'    => $route . ':' . ($tab ?: 'despachar'),
            'quality.n2'    => $route . ':' . ($tab ?: 'decidir'),
            'quality.queue' => $route . ':' . ($fila ?: 'em-andamento'),
            default         => $route,
        };
    }

    public static function modules(array $context = []): array
    {
        $manager = ($context['mode'] ?? 'manager') === 'manager';
        $history = self::item('historico', 'Histórico', 'quality.history', 'ri-history-line', ['quality.process', 'quality.process.']);
        $groups  = [];

        if (!$manager && !empty($context['n1'])) {
            $tabs = [];

            foreach (QualityBoard::N1_TABS as $key => $tab) {
                $tabs[] = self::tabItem('n1-' . $key, $tab['step'] . ' · ' . $tab['label'], 'quality.n1', ['tab' => $key], self::N1_ICONS[$key], [['quality.menu-count', ['scope' => 'n1', 'tab' => $key]]]);
            }

            $groups[] = self::group('n1', 'N1 · Minhas obras', 'ri-clipboard-line', [
                ...$tabs,
                self::item('n1-usuarios', 'Meus usuários', 'quality.n1.users', 'ri-group-line'),
                $history,
            ]);
        }

        if (!$manager && !empty($context['n2'])) {
            $tabs = [];

            foreach (QualityBoard::N2_TABS as $key => $tab) {
                $tabs[] = self::tabItem('n2-' . $key, $tab['step'] . ' · ' . $tab['label'], 'quality.n2', ['tab' => $key], self::N2_ICONS[$key], [['quality.menu-count', ['scope' => 'n2', 'tab' => $key]]]);
            }

            $groups[] = self::group('n2', 'N2 · Decisões', 'ri-user-star-line', [
                ...$tabs,
                self::tabItem('n2-empresas', 'Por empresa', 'quality.n2', ['tab' => 'empresas'], 'ri-building-line'),
                ...(empty($context['n1']) ? [$history] : []),
            ]);
        }

        if ($manager) {
            $groups[] = self::group('operacao', 'Operação', 'ri-shield-check-line', [
                self::item('visao', 'Visão geral', 'quality.dashboard', 'ri-dashboard-3-line', [], [['quality.menu-count', ['scope' => 'mgmt', 'tab' => 'paradas']]]),
                self::item('pool', 'Pool (despachar ao N1)', 'quality.pool', 'ri-stack-line'),
                self::tabItem('andamento', 'Em andamento', 'quality.queue', ['fila' => 'em-andamento'], 'ri-refresh-line', [['quality.menu-count', ['scope' => 'mgmt', 'tab' => 'andamento']]]),
                self::tabItem('aguardando-n2', 'Aguardando N2', 'quality.queue', ['fila' => 'aguardando-n2'], 'ri-user-star-line', [['quality.menu-count', ['scope' => 'mgmt', 'tab' => 'n2']]]),
                $history,
            ]);

            $groups[] = self::group('configuracao', 'Configuração', 'ri-settings-3-line', [
                self::item('categorias', 'Motivos de rejeição', 'quality.categories', 'ri-price-tag-3-line'),
                self::item('checklist', 'Checklist EDP', 'quality.checklists', 'ri-list-check-2'),
                self::item('equipe', 'Equipe N1 / N2', 'quality.team', 'ri-group-line'),
                self::item('criterios', 'Critérios e atividades', 'quality.settings', 'ri-equalizer-line'),
            ]);
        }

        return self::module('qualidade', 'Qualidade', 'ri-shield-check-line', $manager ? 'Gestão da qualidade' : 'Equipe N1 e N2', $groups);
    }

    /**
     * Item de aba ou fila: a rota é a mesma de outras abas, então só a chave `rota:parâmetro` o deixa ativo.
     *
     * @param array<string, string>                                  $params `tab` ou `fila`
     * @param array<int, array{0: string, 1?: array<string, mixed>}> $counts
     *
     * @return array<string, mixed>
     */
    private static function tabItem(string $key, string $label, string $route, array $params, string $icon, array $counts = []): array
    {
        return ['match' => [$route . ':' . reset($params)]] + self::item($key, $label, $route, $icon, [], $counts, $params);
    }
}
