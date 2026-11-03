<?php

namespace App\Services\Ads;

use App\Services\Reports\AdsRequestedReportService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Throwable;

/**
 * Tudo que o painel de ADS (e o Excel dele) mostra sai daqui, então tela e planilha nunca divergem.
 *
 * Cada parte é uma "seção" `{state, reason, data}` que declara de qual conexão depende:
 *  - banco local (espelho `ads_requests`): resumo, fluxo diário, reaproveitamento e histórico;
 *  - SQL Server (`sqlsrv2`): a fila em tempo real (rosca e tabela).
 * Sem conexão, só as seções que dependem dela ficam `disabled` (com o motivo); as demais continuam. O SQL Server só é consultado depois de
 * o AdsConnectionProbe confirmar que está de pé, porque uma tentativa em servidor fora do ar trava ~30 s.
 */
class AdsDashboardAnalytics
{
    public const STATE_OK = 'ok';

    public const STATE_DISABLED = 'disabled';

    private const REASON_LOCAL = 'Sem conexão com o banco local. Esta parte volta sozinha quando a conexão for restabelecida.';

    private const REASON_LIVE = 'Sem conexão com o SQL Server. A fila em tempo real volta sozinha quando a conexão for restabelecida.';

    public function __construct(
        private readonly AdsRequestedReportService $ads,
        private readonly AdsConnectionProbe $probe,
    ) {
    }

    /**
     * @param array<string, mixed> $filters filtros do AdsRequestedReportService
     *
     * @return array{health: array<string, mixed>, summary: array<string, mixed>, flow: array<string, mixed>, queue: array<string, mixed>, reuse: array<string, mixed>}
     */
    public function snapshot(array $filters, bool $freshHealth = false): array
    {
        $health = $this->probe->status($freshHealth);

        return [
            'health'  => $health,
            'summary' => $this->section($health, AdsConnectionProbe::LOCAL, fn () => $this->ads->summarize($filters)),
            'flow'    => $this->section($health, AdsConnectionProbe::LOCAL, fn () => $this->ads->demandVsDeliverySeries($filters)),
            'queue'   => $this->section($health, AdsConnectionProbe::LIVE, fn () => $this->ads->queueDonutSeries($filters + ['live_queue_only' => true])),
            'reuse'   => $this->section($health, AdsConnectionProbe::LOCAL, fn () => $this->ads->reuseEconomyDonutSeries($filters)),
        ];
    }

    /**
     * Fila atual (tabela). Vem do SQL Server, então só existe com a conexão de pé.
     *
     * @param array<string, mixed> $filters
     *
     * @return array{state: string, reason: ?string, data: ?LengthAwarePaginator}
     */
    public function queueRows(array $filters, int $perPage, string $pageName = 'queue_page'): array
    {
        return $this->section($this->probe->status(), AdsConnectionProbe::LIVE, fn () => $this->ads->paginateQueue($filters, $perPage, $pageName));
    }

    /**
     * Histórico de ADS (espelho local).
     *
     * @param array<string, mixed> $filters
     *
     * @return array{state: string, reason: ?string, data: ?LengthAwarePaginator}
     */
    public function historyRows(array $filters, int $perPage): array
    {
        return $this->section($this->probe->status(), AdsConnectionProbe::LOCAL, fn () => $this->ads->paginate($filters, $perPage));
    }

    /**
     * Tudo o que o Excel leva. Seções sem conexão vêm desabilitadas e a planilha avisa em vez de falhar.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function report(array $filters): array
    {
        $snapshot = $this->snapshot($filters, true);

        $queue = $snapshot['queue']['state'] === self::STATE_OK
            ? $this->collect(fn () => $this->ads->buildQueueQuery($filters)->limit(5000)->get()->map(fn ($row) => $this->ads->enrichRow($row))->all(), AdsConnectionProbe::LIVE)
            : [];

        $history = $snapshot['summary']['state'] === self::STATE_OK
            ? $this->collect(fn () => $this->ads->buildQuery($filters)->limit(20000)->get()->map(fn ($row) => $this->ads->enrichRow($row))->all(), AdsConnectionProbe::LOCAL)
            : [];

        return $snapshot + ['queue_rows' => $queue, 'history_rows' => $history, 'generated_at' => now()];
    }

    /**
     * Roda a consulta de uma seção. Sem conexão (teste do probe) ou com falha na consulta, devolve a seção desabilitada com o motivo;
     * a falha também marca a conexão como fora do ar para as próximas leituras não insistirem.
     *
     * @param array<string, array<string, mixed>> $health
     */
    private function section(array $health, string $needs, callable $load): array
    {
        if (!($health[$needs]['up'] ?? false)) {
            return $this->disabled($needs);
        }

        try {
            return ['state' => self::STATE_OK, 'reason' => null, 'data' => $load()];
        } catch (Throwable $e) {
            report($e);
            $this->probe->markDown($needs, $e);

            return $this->disabled($needs);
        }
    }

    /** @return array<int, mixed> */
    private function collect(callable $load, string $needs): array
    {
        try {
            return $load();
        } catch (Throwable $e) {
            report($e);
            $this->probe->markDown($needs, $e);

            return [];
        }
    }

    /** @return array{state: string, reason: string, data: null} */
    private function disabled(string $needs): array
    {
        return [
            'state'  => self::STATE_DISABLED,
            'reason' => $needs === AdsConnectionProbe::LIVE ? self::REASON_LIVE : self::REASON_LOCAL,
            'data'   => null,
        ];
    }

    /** Paginador vazio para a tela, quando a seção está desabilitada. */
    public static function emptyPage(int $perPage, string $path = ''): LengthAwarePaginator
    {
        return new Paginator([], 0, $perPage, 1, ['path' => $path]);
    }
}
