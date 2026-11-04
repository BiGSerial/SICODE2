<?php

namespace App\Support\Menu\Concerns;

use App\Models\Service;
use App\Services\Closure\ClosureArea;

/**
 * Apoio dos menus de atividade (despacho e serviço): a atividade aberta vem no contexto (`service`) e o desenho do menu varia por
 * `variant` (a pasta da atividade). Mantém um módulo só, com o nome da atividade.
 */
trait ActivityNavHelpers
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array{0: ?object, 1: string}
     */
    protected static function activity(array $context): array
    {
        return [$context['service'] ?? null, (string) ($context['variant'] ?? 'generic')];
    }

    /**
     * Módulo único da atividade.
     *
     * @param array<int, array<string, mixed>> $groups
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function activityModule(?object $service, string $hint, array $groups, string $icon = 'ri-stack-line'): array
    {
        return [
            'atividade' => [
                'label'  => $service?->service ?: 'Atividade',
                'icon'   => $icon,
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
    protected static function group(string $key, string $label, string $icon, array $items): array
    {
        return ['key' => $key, 'label' => $label, 'icon' => $icon, 'items' => $items];
    }

    /**
     * Caixa do Encerramento dentro de Medição (pagamento) e Fiscalização: azul = em acompanhamento (só visão), vermelho = despachadas para resolver.
     *
     * @return array<string, mixed>|null
     */
    protected static function closureInboxGroup(object $service, string $inboxRoute): ?array
    {
        $area = ClosureArea::forFolder((string) ($service->folder ?? ''));

        if (!$area) {
            return null;
        }

        $params = ['service' => $service->uuid];
        $gate   = 'closure.inbox.' . strtolower($area);

        return self::group('encerramento', 'Encerramento', 'ri-door-lock-line', [
            self::item('enc-acomp', 'Em acompanhamento', $inboxRoute, 'ri-eye-line', [], [['components.count.closure-inbox-count', ['area' => $area, 'kind' => 'accompanying']]], $params, ['can' => $gate]),
            self::item('enc-despachadas', 'Despachos para retorno', $inboxRoute, 'ri-send-plane-line', [], [['components.count.closure-inbox-count', ['area' => $area, 'kind' => 'dispatched']]], $params + ['tab' => 'dispatched'], ['can' => $gate]),
        ]);
    }
}
