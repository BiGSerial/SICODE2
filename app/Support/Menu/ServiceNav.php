<?php

namespace App\Support\Menu;

use App\Services\Closure\{ClosureServiceLocator, ClosureSettings};
use App\Support\Menu\Concerns\ActivityNavHelpers;

/**
 * Menu lateral das telas de SERVIÇO (a atividade do usuário) de cada atividade, no padrão de docs/PADRAO_MENU_LATERAL.md.
 * Contexto: `service` (a atividade aberta) e `variant` (o desenho do menu daquela atividade; ver VARIANTS). Os arquivos
 * `services/**\/menu.blade.php` só chamam `<x-nav.sidebar>` com a variante certa.
 */
final class ServiceNav extends SidebarNav
{
    use ActivityNavHelpers;

    public const DEFAULT_MODULE = 'atividade';

    /** Variantes (pasta da atividade em `services/`). `generic` é o `services/menu.blade.php`. */
    public const VARIANTS = ['generic', 'analises', 'analises_inc', 'analises_pre', 'cadastro', 'comissionamento', 'contratacao', 'desenho', 'encerramento', 'fiscalizacao', 'fluxo_reverso', 'levantamento', 'oexterno', 'pagamento', 'publicacao'];

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

            return $service ? self::closureWork($service) : [];
        }

        if (!$service) {
            return [];
        }

        $name   = (string) $service->service;
        $params = ['service' => $service->uuid];
        $notes  = [['components.count.countnotes', ['service' => $service->uuid]]];

        $list      = self::item('lista', "Lista de {$name}", 'services.main', 'ri-list-check-2', [], [], $params);
        $activity  = self::item('atividade', "Atividade {$name}", 'services.main', 'ri-list-check-2', [], $notes, $params);
        $accompany = self::item('acompanhamento', 'Acompanhamento', 'services.accompany', 'ri-eye-line', [], $notes, $params);
        $historic  = self::item('historico', 'Meu histórico', 'services.historic', 'ri-history-line', [], [], $params);

        $follow = fn (array $items) => self::group('acompanhamento', 'Acompanhamento', 'ri-list-check-2', $items);

        $groups = match ($variant) {
            'analises', 'analises_inc', 'analises_pre', 'cadastro', 'fluxo_reverso' => [$follow([$list, $accompany, $historic])],
            'comissionamento' => [$follow([$activity, $historic])],
            'contratacao'     => [$follow([
                self::item('atividade', "Atividade {$name}", 'construction.main', 'ri-list-check-2', [], $notes, $params),
                self::item('historico', 'Meu histórico', 'construction.historic', 'ri-history-line', [], [], $params),
            ])],
            'desenho' => [$follow([
                self::item('acompanhamento', 'Acompanhamento', 'services.main', 'ri-eye-line', [], $notes, $params),
                $historic,
            ])],
            'fiscalizacao' => array_values(array_filter([
                $follow([$activity, $historic, self::item('ads', 'Solicitações ADS', 'services.ads.requests', 'ri-file-add-line', [], [], $params)]),
                self::closureInboxGroup($service, 'services.closure_inbox'),
            ])),
            'levantamento' => [$follow([$activity, self::item('viabilidade', 'Em viabilidade', 'services.hiringsurvey', 'ri-radar-line', [], [], $params), $historic])],
            'oexterno'     => [self::group('entidade-externa', 'Entidade externa', 'ri-building-2-line', [
                self::item('dashboard', 'Painel', 'services.oexterno.dashboard', 'ri-dashboard-3-line', [], [], $params),
                self::item('a-protocolar', 'À protocolar', 'services.main', 'ri-survey-line', [], [], $params),
                self::item('obras-liberadas', 'Obras liberadas', 'services.oexterno.released_works', 'ri-checkbox-circle-line', [], [['services.oexterno.counts.released-works-count']], $params),
                self::item('indefinidos', 'Status indefinidos', 'services.oexterno.undefined', 'ri-question-line', [], [['services.oexterno.counts.count-status', ['values' => ['INDEFINIDO'], 'null' => true]]], $params),
                self::item('aguardando-pagamento', 'Aguardando pagamento', 'services.oexterno.waiting_payment', 'ri-money-dollar-circle-line', [], [['services.oexterno.counts.count-status', ['values' => ['AGUARDANDO_PAGAMENTO'], 'null' => false]]], $params),
                self::item('aguardando-taxa', 'Aguardando taxa', 'services.oexterno.waiting_taxa', 'ri-hand-coin-line', [], [['services.oexterno.counts.count-status', ['values' => ['AGUARDANDO_TAXA'], 'null' => false]]], $params),
                self::item('aguardando-orgao', 'Aguardando órgão externo', 'services.oexterno.waiting_orgao', 'ri-building-line', [], [['services.oexterno.counts.count-status', ['values' => ['AGUARDANDO_ORGAO'], 'null' => false]]], $params),
                self::item('aguardando-ri', 'Aguardando retorno interno', 'services.waiting_return', 'ri-arrow-go-back-line', [], [['components.count.oexterno.count-return']], $params),
                $historic,
            ])],
            'pagamento' => array_values(array_filter([
                $follow([$list, $accompany, $historic, self::item('d5-pendentes', 'D5 pendentes', 'services.waiting_d5_create', 'ri-file-text-line', [], [], $params)]),
                self::group('cancelamento', 'Cancelamento', 'ri-close-circle-line', [
                    self::item('can-fila', 'Lista para cancelamento', 'services.cancellations.queue', 'ri-inbox-archive-line', [], [['components.count.cancellation-requests', ['mode' => 'unassigned']]], $params),
                    self::item('can-andamento', 'Em andamento', 'services.cancellations.ongoing', 'ri-loader-4-line', [], [['components.count.cancellation-requests', ['mode' => 'in_progress', 'userId' => (string) auth()->id()]]], $params),
                    self::item('can-hist', 'Histórico', 'services.cancellations.history', 'ri-history-line', [], [], $params),
                ]),
                self::group('descancelamento', 'Descancelamento', 'ri-refresh-line', [
                    self::item('des-fila', 'Lista para descancelamento', 'services.uncancellations.queue', 'ri-inbox-unarchive-line', [], [], $params),
                    self::item('des-andamento', 'Em andamento', 'services.uncancellations.ongoing', 'ri-loader-4-line', [], [], $params),
                    self::item('des-hist', 'Histórico', 'services.uncancellations.history', 'ri-history-line', [], [], $params),
                ]),
                self::closureInboxGroup($service, 'services.closure_inbox'),
            ])),
            'publicacao' => [$follow([$accompany, $historic])],
            default      => [$follow([$list, $accompany, self::item('espera', 'Lista de espera', 'services.waiting', 'ri-timer-line', [], [['components.count.countwaiting', ['service' => $service->uuid]]], $params), $historic])],
        };

        return self::activityModule($service, 'Serviço', $groups);
    }

    /**
     * Encerramento: a atividade do usuário, protegida pelo gate `closure.work`.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function closureWork(object $service): array
    {
        $params = ['service' => $service->uuid];
        $name   = (string) $service->service;
        $items  = [self::item('lista', "Lista de {$name}", 'services.main', 'ri-list-check-2', [], [], $params)];

        if (app(ClosureSettings::class)->selfAssignEnabled()) {
            $items[] = self::item('disponiveis', 'Atividades disponíveis', 'services.main', 'ri-inbox-line', [], [], $params + ['aba' => 'disponiveis']);
        }

        $items[] = self::item('acompanhamento', 'Acompanhamento', 'services.accompany', 'ri-eye-line', [], [], $params);
        $items[] = self::item('transferencias', 'Transferências', 'services.transfers', 'ri-arrow-left-right-line', [], [['components.count.closure-transfers']], $params);
        $items[] = self::item('historico', 'Meu histórico', 'services.historic', 'ri-history-line', [], [], $params);

        return self::activityModule($service, 'Serviço', [
            ['can' => 'closure.work'] + self::group('acompanhamento', 'Acompanhamento', 'ri-list-check-2', $items),
        ], 'ri-door-lock-line');
    }
}
