<?php

namespace App\Support\Menu\Concerns;

/**
 * Montadores de módulo e de grupo para os `*Nav` de uma área só (um módulo, poucos grupos). Os menus de atividade usam
 * `ActivityNavHelpers`; este trait é o equivalente para as demais áreas.
 */
trait NavBuilders
{
    /**
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function module(string $key, string $label, string $icon, string $hint, array $groups): array
    {
        return [$key => ['label' => $label, 'icon' => $icon, 'hint' => $hint, 'groups' => $groups]];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed>             $extra `flat` (sem cabeçalho) ou `can` (gate do grupo)
     *
     * @return array<string, mixed>
     */
    protected static function group(string $key, string $label, string $icon, array $items, array $extra = []): array
    {
        return ['key' => $key, 'label' => $label, 'icon' => $icon, 'items' => $items] + $extra;
    }
}
