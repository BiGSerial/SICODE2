<?php

namespace App\Support\Menu;

use App\Services\Closure\ClosureServiceLocator;
use App\Support\Menu\Concerns\ActivityNavHelpers;

/**
 * Menu lateral das telas de DESPACHO de cada atividade, no padrão de docs/PADRAO_MENU_LATERAL.md. Contexto: `service` (a atividade
 * aberta) e `variant` (o desenho do menu daquela atividade; ver VARIANTS). Os arquivos `dispatchs/**\/menu.blade.php` só chamam
 * `<x-nav.sidebar>` com a variante certa.
 */
final class DispatchNav extends SidebarNav
{
    use ActivityNavHelpers;

    public const DEFAULT_MODULE = 'atividade';

    /** Variantes (pasta da atividade em `dispatchs/`). `generic` é o `dispatchs/menu.blade.php`. */
    public const VARIANTS = ['generic', 'default', 'analises', 'analises_pre', 'comissionamento', 'desenho', 'encerramento', 'fiscalizacao', 'fluxo_reverso', 'levantamento', 'pagamento', 'publicacao'];

    public static function sampleContexts(): array
    {
        $service = (object) ['uuid' => 'uuid-teste', 'service' => 'Atividade', 'folder' => 'pagamento'];

        return array_map(fn (string $variant) => ['service' => $service, 'variant' => $variant], self::VARIANTS);
    }

    public static function modules(array $context = []): array
    {
        [$service, $variant] = self::activity($context);

        if ($variant === 'encerramento') {
            $service ??= ClosureServiceLocator::service();

            return self::closureCycle($service);
        }

        if (!$service) {
            return [];
        }

        $name   = (string) $service->service;
        $params = ['service' => $service->uuid];

        $main     = self::item('main', "Lista para {$name}", 'dispatch.main', 'ri-list-check-2', [], [], $params);
        $stack    = self::item('stack', "Controle de {$name}", 'dispatch.stack', 'ri-eye-line', [], [], $params);
        $dash     = self::item('dashboard', "Painel de {$name}", 'dispatch.dashboard', 'ri-dashboard-3-line', [], [], $params);
        $historic = self::item('historic', 'Histórico de despachos', 'dispatch.historic', 'ri-history-line', [], [], $params);
        $transfer = self::item('transfer', "Transferência de {$name}", 'dispatch.transprod', 'ri-arrow-left-right-line', [], [['components.count.counttransferdispatch', ['service' => $service->uuid]]], $params);
        $return   = self::item('return', "Retorno interno (RI) de {$name}", 'dispatch.d5', 'ri-arrow-go-back-line', [], [['components.count.count-return', ['service' => $service->uuid]]], $params);
        $ads      = self::item('ads', 'Solicitações ADS', 'dispatch.ads.requests', 'ri-file-add-line', [], [], $params);

        $follow = fn (array $items) => self::group('acompanhamento', 'Acompanhamento', 'ri-list-check-2', $items);

        $groups = match ($variant) {
            'analises', 'analises_pre', 'desenho', 'fluxo_reverso' => [$follow([$main, $stack])],
            'default'         => [$follow([$main, $stack, $historic])],
            'levantamento'    => [$follow([$dash, $main, $stack, $transfer, $return])],
            'comissionamento' => [$follow([$main, $stack, $transfer])],
            'fiscalizacao'    => array_values(array_filter([$follow([$main, $stack, $transfer, $ads]), self::closureInboxGroup($service, 'dispatch.closure_inbox')])),
            'pagamento'       => array_values(array_filter([
                $follow([$main, $stack, $transfer, self::item('waiting-d5', 'Aguardando D5', 'dispatch.waitingFiveNote', 'ri-timer-flash-line', [], [], $params)]),
                self::group('cancelamento', 'Cancelamento', 'ri-close-circle-line', [
                    self::item('can-controle', 'Controle de cancelamento', 'dispatch.cancellation.queue', 'ri-inbox-archive-line', [], [
                        ['components.count.cancellation-requests', ['mode' => 'unassigned']],
                        ['components.count.cancellation-requests', ['mode' => 'in_progress']],
                    ], $params),
                    self::item('can-categorias', 'Configuração de categorias', 'dispatch.cancellation.categories', 'ri-settings-3-line', [], [], $params),
                    self::item('can-hist', 'Histórico de cancelamento', 'dispatch.cancellation.history', 'ri-history-line', [], [], $params),
                ]),
                self::closureInboxGroup($service, 'dispatch.closure_inbox'),
            ])),
            'publicacao' => [$follow([$main, $stack, self::item('informes', 'Informes de obra', 'reports.workreport', 'ri-file-list-3-line')])],
            default      => [$follow(array_values(array_filter([
                $dash,
                $main,
                auth()->user()?->usesContractDispatch() ? self::item('partner', 'Minhas atividades despachadas', 'dispatch.partner', 'ri-user-star-line', [], [], $params) : null,
                $stack,
                $historic,
                $return,
                $ads,
            ])))],
        };

        return self::activityModule($service, 'Despacho', $groups);
    }

    /**
     * Encerramento: grupos protegidos pelos gates do ciclo (despacho e gestão).
     *
     * @return array<string, array<string, mixed>>
     */
    private static function closureCycle(?object $service): array
    {
        $groups = [];

        if ($service) {
            $params = ['service' => $service->uuid];
            $name   = (string) $service->service;

            $groups[] = ['can' => 'closure.dispatch'] + self::group('acompanhamento', 'Acompanhamento', 'ri-list-check-2', [
                self::item('main', "Lista para {$name}", 'dispatch.main', 'ri-list-check-2', [], [], $params),
                self::item('stack', "Controle de {$name}", 'dispatch.stack', 'ri-eye-line', [], [], $params),
                self::item('transfer', "Transferência de {$name}", 'dispatch.transprod', 'ri-arrow-left-right-line', [], [
                    ['components.count.closure-priority-requests'],
                    ['components.count.closure-transfers'],
                ], $params),
                self::item('mapa', 'Caixas das atividades', 'closure.status_map', 'ri-flow-chart'),
                self::item('painel', 'Painel analítico', 'dispatch.dashboard', 'ri-bar-chart-box-line', [], [], $params),
            ]);
        }

        $management = [
            self::item('visao-geral', 'Visão geral', 'closure.overview', 'ri-dashboard-3-line', [], [], [], ['can' => 'closure.manager']),
            self::item('meta', 'Meta', 'closure.meta', 'ri-focus-3-line', [], [], [], ['can' => 'closure.manager']),
            self::item('passivo', 'Passivo', 'closure.passive', 'ri-timer-line', [], [], [], ['can' => 'closure.manager']),
        ];

        if ($service) {
            $management[] = self::item('depositos', 'Calendário de depósitos', 'dispatch.deposits', 'ri-calendar-check-line', [], [], ['service' => $service->uuid], ['can' => 'closure.dispatch']);
        }

        $groups[] = self::group('gestao', 'Gestão do ciclo', 'ri-honour-line', $management);

        return self::activityModule($service, 'Despacho', $groups, 'ri-door-lock-line');
    }
}
