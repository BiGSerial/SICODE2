<?php

namespace App\Support\Menu;

/**
 * Base do menu lateral padrão (docs/PADRAO_MENU_LATERAL.md). Cada área define uma subclasse com `modules()` e o menu lateral
 * (`<x-nav.sidebar>`) e o dropdown do topo leem dela, então nunca divergem.
 *
 * Módulo: label, icon, hint, groups. Grupo: key, label, icon, items, `flat` (sem cabeçalho) e `can` (gate). Item: key, label, route,
 * `params` (parâmetros da rota), icon, `match` (nomes de rota ou prefixos terminados em ".") que o deixam ativo, `counts`
 * (componentes Livewire de contador) e `can` (gate; sem permissão o item some e grupo vazio some junto).
 *
 * Menus que dependem do contexto (ex.: a atividade aberta) recebem `$context` em todos os métodos.
 */
abstract class SidebarNav
{
    /** Módulo aberto quando a rota não pertence a nenhum item. */
    public const DEFAULT_MODULE = '';

    /** Tema visual (classe `snav--<tema>` no menu). `null` = o da empresa; a área da parceira usa `partner` (docs/PADRAO_MENU_LATERAL.md). */
    public const THEME = null;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, array<string, mixed>>
     */
    abstract public static function modules(array $context = []): array;

    /**
     * Contextos válidos para validar a estrutura nos testes (menus com contexto devolvem vários).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sampleContexts(): array
    {
        return [[]];
    }

    /**
     * Módulos já filtrados pelas permissões (`can`): item ou grupo sem permissão some; grupo ou módulo vazio some.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, array<string, mixed>>
     */
    public static function resolve(array $context = []): array
    {
        $user    = auth()->user();
        $allowed = fn (array $node) => static::allows($node, $user);

        $modules = [];

        foreach (static::modules($context) as $key => $module) {
            $groups = [];

            foreach ($module['groups'] as $group) {
                if (!$allowed($group)) {
                    continue;
                }

                $group['items'] = array_values(array_filter($group['items'], $allowed));

                if ($group['items'] !== []) {
                    $groups[] = $group;
                }
            }

            if ($groups !== []) {
                $modules[$key] = ['groups' => $groups] + $module;
            }
        }

        return $modules;
    }

    /**
     * Se o usuário pode ver o grupo ou o item (`can`). Por padrão `can` é o nome de um gate; uma área com outro modelo de permissão
     * (ex.: a parceira, que tem permissões próprias) sobrescreve este método.
     *
     * @param array<string, mixed> $node
     */
    protected static function allows(array $node, ?object $user): bool
    {
        return empty($node['can']) || ($user && $user->can($node['can']));
    }

    /**
     * Onde a rota está: módulo, grupo e item ativos (item null quando a rota não pertence a nenhum item).
     *
     * @param array<string, mixed> $context
     *
     * @return array{module: string, group: ?string, item: ?string}
     */
    public static function locate(?string $route, array $context = []): array
    {
        $modules = static::resolve($context);

        if ($route) {
            foreach ($modules as $moduleKey => $module) {
                foreach ($module['groups'] as $group) {
                    foreach ($group['items'] as $item) {
                        if (static::matches($item, $route)) {
                            return ['module' => $moduleKey, 'group' => $group['key'], 'item' => $item['key']];
                        }
                    }
                }
            }
        }

        $default = static::DEFAULT_MODULE !== '' && isset($modules[static::DEFAULT_MODULE]) ? static::DEFAULT_MODULE : (string) array_key_first($modules);

        return ['module' => $default, 'group' => null, 'item' => null];
    }

    /**
     * Primeiro destino do módulo (seletor de módulos).
     *
     * @param array<string, mixed> $context
     *
     * @return array{route: string, params: array<string, mixed>}
     */
    public static function entry(string $module, array $context = []): array
    {
        $item = static::resolve($context)[$module]['groups'][0]['items'][0];

        return ['route' => $item['route'], 'params' => $item['params']];
    }

    /** @param array<string, mixed> $context */
    public static function entryRoute(string $module, array $context = []): string
    {
        return static::entry($module, $context)['route'];
    }

    /**
     * @param array<int, string>                                    $match  rotas extras (ou prefixos terminados em ".")
     * @param array<int, array{0: string, 1?: array<string, mixed>}> $counts componentes de contador (nome, parâmetros)
     * @param array<string, mixed>                                  $params parâmetros da rota
     * @param array<string, mixed>                                  $extra  chaves extras (ex.: `can`)
     *
     * @return array<string, mixed>
     */
    protected static function item(string $key, string $label, string $route, string $icon, array $match = [], array $counts = [], array $params = [], array $extra = []): array
    {
        return [
            'key'    => $key,
            'label'  => $label,
            'route'  => $route,
            'params' => $params,
            'icon'   => $icon,
            'match'  => array_merge([$route], $match),
            'counts' => $counts,
        ] + $extra;
    }

    /** @param array<string, mixed> $item */
    protected static function matches(array $item, string $route): bool
    {
        foreach ($item['match'] as $pattern) {
            if ($pattern === $route || (str_ends_with($pattern, '.') && str_starts_with($route, $pattern))) {
                return true;
            }
        }

        return false;
    }
}
