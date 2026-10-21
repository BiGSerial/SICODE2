<?php

namespace App\Console\Commands\Tools;

use App\Models\{FiveNote, Partial, WorkReport, WorkReportFlowProduction};
use App\Services\D5\{D5WorkReportAssociationResolver, D5WorkflowService};
use App\Services\Retrofill\LegacyMarkerMatcher;
use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\{DB, Storage};

/**
 * Retrofill dos legados: associa cada Production (Fiscalização, Pagamento/Medição, Publicação) ao Informe Final ou
 * à Parcial corretos e dá a baixa da D5 quando a Fiscalização já foi concluída.
 *
 * O `dispatch_at` é ação humana (varia sempre) e só funciona como PISO: nunca pode ser anterior ao marco.
 *   Parcial   : Fiscalização >= partials.decision_at · Pagamento >= partials.supervision_at
 *   Final     : Fiscalização/Publicação >= informe (informed_at; com rejeição, o menor entre created_at e informed_at) · Pagamento >= conclusão da Fiscalização do informe
 * Dentro do piso a escolha é FIFO (ver LegacyMarkerMatcher); o que for ambíguo vai ao relatório sem vínculo.
 */
class RetrofillLegacyWorkReportD5 extends Command
{
    protected $signature = 'sicode:retrofill-legacy-work-report-d5
                            {--apply : Persiste as alterações (padrão = dry-run)}
                            {--phase=all : all|partials|finals|d5}
                            {--note_id= : Processa apenas uma nota por ID local}
                            {--limit=0 : Limite de notas processadas}
                            {--chunk=200 : Tamanho do lote de notas}
                            {--report= : Caminho do CSV (disco local); padrão retrofill/legacy-d5-<data>.csv}
                            {--no-refresh : Não recalcula o status materializado dos informes afetados}';

    protected $description = 'Retrofill de Productions legadas com Informe/Parcial/D5 corretos, incluindo a baixa da D5 na Fiscalização';

    private const SOURCE = 'retrofill_legacy';

    private const TOLERANCE_SECONDS = 120;

    private bool $apply = false;

    /** @var array<string, string> uuid => stage */
    private array $stageByService = [];

    private ?string $fiscalId = null;

    private ?string $paymentId = null;

    private array $report = [];

    /** Diário da nota em processamento (alimenta o rollback): linhas alteradas, ids criados e `is_current` anterior. */
    private array $note = ['rows' => [], 'created' => [], 'current' => []];

    private ?string $reportPath = null;

    private ?string $journalPath = null;

    private array $stats = [];

    /** @var array<int, true> */
    private array $affectedReports = [];

    private LegacyMarkerMatcher $matcher;

    public function handle(LegacyMarkerMatcher $matcher): int
    {
        $this->matcher = $matcher;
        $this->apply   = (bool) $this->option('apply');
        $phase         = (string) $this->option('phase');

        if (!in_array($phase, ['all', 'partials', 'finals', 'd5'], true)) {
            $this->error('--phase inválida. Use all, partials, finals ou d5.');

            return self::FAILURE;
        }

        if (!$this->resolveServices()) {
            $this->error('Serviços Fiscalização/Pagamento não encontrados.');

            return self::FAILURE;
        }

        $noteIds = $this->candidateNoteIds();
        $limit   = (int) $this->option('limit');

        if ($limit > 0) {
            $noteIds = $noteIds->take($limit);
        }

        $this->preparePaths();

        $bar = $this->output->createProgressBar($noteIds->count());
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%%  decorrido %elapsed:6s%  restante ~%remaining:-6s%  nota %message%");
        $bar->setMessage('-');
        $bar->start();

        foreach ($noteIds->chunk(max(1, (int) $this->option('chunk'))) as $chunk) {
            foreach ($chunk as $noteId) {
                $this->stats['notes'] = ($this->stats['notes'] ?? 0) + 1;
                $bar->setMessage((string) $noteId);

                $work = function () use ($noteId, $phase): void {
                    if (in_array($phase, ['all', 'partials'], true)) {
                        $this->partials((int) $noteId);
                    }

                    if (in_array($phase, ['all', 'finals'], true)) {
                        $this->finals((int) $noteId);
                    }

                    if (in_array($phase, ['all', 'd5'], true)) {
                        $this->d5((int) $noteId);
                    }
                };

                $this->note = ['rows' => [], 'created' => [], 'current' => []];

                if ($this->apply) {
                    DB::transaction(function () use ($work): void {
                        $work();
                        $this->finalizeNoteJournal();
                    });
                    $this->flushJournal((int) $noteId);
                } else {
                    $work();
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->apply && !$this->option('no-refresh') && $this->affectedReports) {
            $refresher = app(WorkReportCurrentStatusRefresher::class);

            $this->line('Atualizando o status de ' . count($this->affectedReports) . ' informe(s)...');
            $refreshBar = $this->output->createProgressBar(count($this->affectedReports));
            $refreshBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  decorrido %elapsed:6s%  restante ~%remaining:-6s%');
            $refreshBar->start();

            foreach (array_keys($this->affectedReports) as $reportId) {
                $refresher->refresh($reportId);
                $refreshBar->advance();
            }

            $refreshBar->finish();
            $this->newLine(2);
        }

        $this->writeFiles();

        $this->line('Modo: ' . ($this->apply ? 'APPLY' : 'DRY-RUN'));
        ksort($this->stats);

        foreach ($this->stats as $key => $value) {
            $this->line(str_pad($key, 40) . $value);
        }

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ Seleção

    private function resolveServices(): bool
    {
        foreach (DB::table('services')->get(['uuid', 'service']) as $service) {
            match (Str::lower(trim(Str::ascii((string) $service->service)))) {
                'fiscalizacao' => [$this->stageByService[$service->uuid] = WorkReportFlowProduction::STAGE_FISCALIZATION, $this->fiscalId = $service->uuid],
                'pagamento'    => [$this->stageByService[$service->uuid] = WorkReportFlowProduction::STAGE_PAYMENT, $this->paymentId = $service->uuid],
                'publicacao'   => $this->stageByService[$service->uuid] = WorkReportFlowProduction::STAGE_PUBLICATION,
                default        => null,
            };
        }

        return $this->fiscalId && $this->paymentId;
    }

    /** Notas com produção sem vínculo (informe/parcial) ou D5 ainda sem informe/baixa. */
    private function candidateNoteIds(): Collection
    {
        if ($single = $this->option('note_id')) {
            return collect([(int) $single]);
        }

        $services = array_keys($this->stageByService);

        $productions = DB::table('productions as p')
            ->whereIn('p.service_id', $services)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('work_report_flow_productions as w')->whereColumn('w.production_id', 'p.id'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('productionables as pa')->whereColumn('pa.production_id', 'p.id')->where('pa.productionable_type', Partial::class))
            ->distinct()->pluck('p.note_id');

        $fives = DB::table('five_notes')->where('is_archived', false)->distinct()->pluck('note_id');

        return $productions->merge($fives)->filter()->unique()->sort()->values();
    }

    // ------------------------------------------------------------------ Parciais

    private function partials(int $noteId): void
    {
        $partials = DB::table('partials')->where('note_id', $noteId)->get()->keyBy('id');

        if ($partials->isEmpty()) {
            return;
        }

        $productions = $this->productions($noteId)->where('partial', true)->where('dfive', false);

        // Fiscalização parcial: marco = decisão (allow) da parcial. Pagamento parcial: marco = conclusão da Fiscalização.
        foreach ([[$this->fiscalId, 'decision_at'], [$this->paymentId, 'supervision_at']] as [$serviceId, $column]) {
            $group = $productions->where('service_id', $serviceId);

            if ($group->isEmpty()) {
                continue;
            }

            $partials = DB::table('partials')->where('note_id', $noteId)->get()->keyBy('id');

            $markers = $partials->filter(fn ($p) => $p->{$column})->map(fn ($p) => Carbon::parse($p->{$column}))->all();

            $linkedPartial = DB::table('productionables')->where('productionable_type', Partial::class)
                ->whereIn('production_id', $group->pluck('id'))->pluck('productionable_id', 'production_id');

            $fixed = $group->filter(fn ($p) => $linkedPartial->has($p->id))
                ->map(fn ($p) => ['id' => $p->id, 'at' => $this->dispatchAt($p), 'marker' => $linkedPartial[$p->id]])->values()->all();

            $open = $group->reject(fn ($p) => $linkedPartial->has($p->id) || $this->hasFlowLink($p->id))
                ->map(fn ($p) => ['id' => $p->id, 'at' => $this->dispatchAt($p)])->values()->all();

            // Evidência direta: a data de baixa da parcial é o completed_at da produção que a baixou.
            [$byDate, $open, $fixed] = $this->partialsByCompletionDate($partials, $markers, $open, $fixed, $group, $serviceId === $this->fiscalId ? 'supervision_at' : 'payment_at');

            $result          = $this->matcher->match($markers, $open, $fixed, false);
            $result['links'] = $byDate + $result['links'];

            foreach ($result['links'] as $productionId => $link) {
                $this->bump('parciais_vinculadas');
                $this->log('parcial', $noteId, 'production', $productionId, 'vincular_parcial', '', ['partial_id' => $link['marker'], 'marco' => $column]);

                if ($this->apply && !DB::table('productionables')->where('production_id', $productionId)->where('productionable_type', Partial::class)->where('productionable_id', $link['marker'])->exists()) {
                    $this->note['created']['productionables'][] = DB::table('productionables')->insertGetId([
                        'production_id'     => $productionId, 'productionable_type' => Partial::class,
                        'productionable_id' => $link['marker'], 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            foreach ($result['rejected'] as $productionId => $reason) {
                $this->bump("parciais_ignoradas_{$reason}");
                $this->log('parcial', $noteId, 'production', $productionId, 'ignorar', $reason, ['marco' => $column]);
            }

            $this->repairPartialDates($noteId, $group, $result['links'], $linkedPartial, $serviceId === $this->fiscalId);
        }
    }

    /**
     * Casa produção concluída com a parcial cuja data de baixa (`supervision_at`/`payment_at`) coincide com o seu
     * `completed_at` (±2 min), respeitando o piso do despacho. Só vale quando o casamento é único.
     *
     * @return array{0: array<int, array{marker: int, reassigned: bool}>, 1: array, 2: array}
     */
    private function partialsByCompletionDate(Collection $partials, array $markers, array $open, array $fixed, Collection $group, string $column): array
    {
        $byId     = $group->keyBy('id');
        $consumed = array_flip(array_map(fn (array $item) => (int) $item['marker'], $fixed));
        $links    = [];

        foreach ($open as $item) {
            $production = $byId[$item['id']];

            if (!$production->completed || !$production->completed_at) {
                continue;
            }

            $matches = $partials->filter(fn ($partial) => $partial->{$column}
                && !isset($consumed[$partial->id])
                && isset($markers[$partial->id])
                && $markers[$partial->id]->lte($item['at'])
                && abs(Carbon::parse($partial->{$column})->diffInSeconds(Carbon::parse($production->completed_at), false)) <= self::TOLERANCE_SECONDS);

            if ($matches->count() !== 1) {
                continue;
            }

            $partialId            = (int) $matches->first()->id;
            $consumed[$partialId] = true;
            $links[$item['id']]   = ['marker' => $partialId, 'reassigned' => false];
            $fixed[]              = ['id' => $item['id'], 'at' => $item['at'], 'marker' => $partialId];
        }

        $open = array_values(array_filter($open, fn (array $item) => !isset($links[$item['id']])));

        return [$links, $open, $fixed];
    }

    /** `supervision_at`/`payment_at` da parcial = `completed_at` da produção que a baixou. */
    private function repairPartialDates(int $noteId, Collection $group, array $newLinks, Collection $linkedPartial, bool $fiscal): void
    {
        $column = $fiscal ? 'supervision_at' : 'payment_at';
        $flag   = $fiscal ? 'supervision' : 'payment';

        $byPartial = [];

        foreach ($group as $production) {
            $partialId = $newLinks[$production->id]['marker'] ?? $linkedPartial[$production->id] ?? null;

            if ($partialId && $production->completed && $production->completed_at) {
                $byPartial[$partialId][] = $production;
            }
        }

        foreach ($byPartial as $partialId => $items) {
            $latest  = collect($items)->sortBy([['completed_at', 'asc'], ['id', 'asc']])->last();
            $partial = DB::table('partials')->find($partialId);
            $target  = Carbon::parse($latest->completed_at);

            if ($partial->{$column} && abs(Carbon::parse($partial->{$column})->diffInSeconds($target, false)) <= self::TOLERANCE_SECONDS) {
                continue;
            }

            if (!$fiscal && !$partial->supervision) {
                $this->log('parcial', $noteId, 'partial', $partialId, 'ignorar', 'pagamento_sem_fiscalizacao_da_parcial', []);

                continue;
            }

            $this->bump($fiscal ? 'parciais_data_fiscalizacao_corrigida' : 'parciais_data_pagamento_corrigida');
            $this->log('parcial', $noteId, 'partial', $partialId, "corrigir_{$column}", '', ['de' => $partial->{$column}, 'para' => $target->toDateTimeString(), 'production_id' => $latest->id]);

            if ($this->apply) {
                $this->snapshot('partials', $partialId, $partial);
                DB::table('partials')->where('id', $partialId)->update([$column => $target, $flag => true, 'updated_at' => now()]);
            }
        }
    }

    // ------------------------------------------------------------------ Finais

    private function finals(int $noteId): void
    {
        $all      = WorkReport::query()->withCount('Returnwork')->where('note_id', $noteId)->get();
        $reports  = $all->where('canceled', false)->values();
        $canceled = $all->where('canceled', true)->values();

        if ($all->isEmpty()) {
            return;
        }

        $productions = $this->productions($noteId)->filter(fn ($p) => !$p->partial && !$p->dfive);

        // Fiscalização e Publicação primeiro: o marco do Pagamento depende da conclusão da Fiscalização do informe.
        $order = [
            WorkReportFlowProduction::STAGE_FISCALIZATION,
            WorkReportFlowProduction::STAGE_PUBLICATION,
            WorkReportFlowProduction::STAGE_PAYMENT,
        ];

        $plannedFiscal = [];

        foreach ($order as $stage) {
            $group = $productions->filter(fn ($p) => ($this->stageByService[$p->service_id] ?? null) === $stage);

            if ($group->isEmpty()) {
                continue;
            }

            $links = DB::table('work_report_flow_productions')->whereIn('production_id', $group->pluck('id'))->get()->groupBy('production_id');

            $markers = $stage === WorkReportFlowProduction::STAGE_PAYMENT
                ? $this->paymentMarkers($reports, $productions, $plannedFiscal)
                : $reports->mapWithKeys(fn (WorkReport $r) => [$r->id => $this->reportMarker($r)])->all();

            $fixed = $group->filter(fn ($p) => $links->has($p->id) && $links[$p->id]->where('final_scope', WorkReportFlowProduction::SCOPE_GENERAL)->isNotEmpty())
                ->map(fn ($p) => ['id' => $p->id, 'at' => $this->dispatchAt($p), 'marker' => $links[$p->id]->where('final_scope', WorkReportFlowProduction::SCOPE_GENERAL)->first()->work_report_id])->values()->all();

            $open = $group->reject(fn ($p) => $links->has($p->id))
                ->map(fn ($p) => ['id' => $p->id, 'at' => $this->dispatchAt($p)])->values()->all();

            // Informe cancelado: a produção cujas datas caem dentro dele pertence a ele, não ao informe vigente.
            $viaCanceled = [];
            $open        = array_values(array_filter($open, function (array $item) use ($canceled, $all, &$viaCanceled): bool {
                $report = $this->canceledReportFor($item['at'], $canceled, $all);

                if ($report) {
                    $viaCanceled[$item['id']] = $report;
                }

                return !$report;
            }));

            $result = $this->matcher->match($markers, $open, $fixed, true);

            foreach ($viaCanceled as $productionId => $report) {
                $markers[$report->id]           = $this->reportMarker($report);
                $result['links'][$productionId] = ['marker' => $report->id, 'reassigned' => false, 'canceled' => true];
            }

            $byId = $group->keyBy('id');

            foreach ($result['links'] as $productionId => $link) {
                $this->bump("finais_vinculadas_{$stage}");
                $this->log('final', $noteId, 'production', $productionId, "vincular_{$stage}", $link['reassigned'] ? 'reatribuicao' : (($link['canceled'] ?? false) ? 'informe_cancelado' : ''), [
                    'work_report_id' => $link['marker'], 'marco' => $markers[$link['marker']]->toDateTimeString(), 'dispatch_at' => $this->dispatchAt($byId[$productionId])->toDateTimeString(),
                ]);

                // Publicação não entra no cálculo do status do informe; só Fiscalização e Pagamento exigem refresh.
                if ($stage !== WorkReportFlowProduction::STAGE_PUBLICATION) {
                    $this->affectedReports[(int) $link['marker']] = true;
                }

                if ($stage === WorkReportFlowProduction::STAGE_FISCALIZATION && $byId[$productionId]->completed_at) {
                    $plannedFiscal[(int) $link['marker']][] = Carbon::parse($byId[$productionId]->completed_at);
                }

                if ($this->apply) {
                    $this->insertFlowLink($byId[$productionId], (int) $link['marker'], $stage, $markers[$link['marker']], (bool) $link['reassigned']);
                }
            }

            foreach ($result['rejected'] as $productionId => $reason) {
                $this->bump("finais_ignoradas_{$reason}");
                $this->log('final', $noteId, 'production', $productionId, 'ignorar', $reason, ['stage' => $stage, 'dispatch_at' => $this->dispatchAt($byId[$productionId])->toDateTimeString()]);
            }
        }
    }

    /**
     * Informe cancelado cuja janela contém a data (da produção/D5): de seu marco até `canceled_at` (ou até o marco
     * do informe seguinte, quando não há data de cancelamento).
     */
    private function canceledReportFor(Carbon $at, Collection $canceled, Collection $all): ?WorkReport
    {
        return $canceled->filter(function (WorkReport $report) use ($at, $all): bool {
            $start = $this->reportMarker($report);

            if ($at->lt($start)) {
                return false;
            }

            $end = $report->canceled_at
                ? Carbon::parse($report->canceled_at)
                : $all->map(fn (WorkReport $other) => $this->reportMarker($other))->filter(fn (Carbon $date) => $date->gt($start))->sort()->first();

            return !$end || $at->lte($end);
        })->sortBy(fn (WorkReport $report) => $this->reportMarker($report))->last();
    }

    /**
     * Marco do Pagamento por informe: conclusão mais recente da Fiscalização ligada a ele (já existente ou planejada
     * agora); sem Fiscalização concluída, vale a data do informe.
     *
     * @param  array<int, array<int, Carbon>>  $plannedFiscal
     * @return array<int, Carbon>
     */
    private function paymentMarkers(Collection $reports, Collection $productions, array $plannedFiscal): array
    {
        $fiscalIds = $productions->where('service_id', $this->fiscalId)->where('completed', true)->pluck('completed_at', 'id');

        $existing = DB::table('work_report_flow_productions')->whereIn('production_id', $fiscalIds->keys())
            ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)->whereNull('reversed_at')->get()->groupBy('work_report_id');

        $markers = [];

        foreach ($reports as $report) {
            $dates = collect($plannedFiscal[$report->id] ?? [])
                ->merge(($existing[$report->id] ?? collect())->map(fn ($l) => Carbon::parse($fiscalIds[$l->production_id])));

            $markers[$report->id] = $dates->isNotEmpty() ? $dates->max() : $this->reportMarker($report);
        }

        return $markers;
    }

    /**
     * Data de referência do informe. Em informe com rejeição, `informed_at` é a re-informada (ou fica nulo), e o
     * ciclo anterior já nasceu em `created_at`: vale a data mais antiga, para não descartar despachos legítimos.
     */
    private function reportMarker(WorkReport $report): Carbon
    {
        $created  = Carbon::parse($report->created_at);
        $informed = $report->informed_at ? Carbon::parse($report->informed_at) : null;

        $rejected = (bool) $report->rejected || (int) $report->retry > 0 || (int) ($report->returnwork_count ?? 0) > 0;

        if ($rejected) {
            return $informed ? $created->min($informed) : $created;
        }

        return $informed ?? $created;
    }

    private function insertFlowLink(object $production, int $workReportId, string $stage, Carbon $marker, bool $reassigned): void
    {
        $scope = WorkReportFlowProduction::SCOPE_GENERAL;

        $key = ['work_report_id' => $workReportId, 'production_id' => $production->id, 'stage' => $stage, 'final_scope' => $scope];

        if (DB::table('work_report_flow_productions')->where($key)->exists()) {
            return;
        }

        $this->note['created']['work_report_flow_productions'][] = DB::table('work_report_flow_productions')->insertGetId($key + [
            'is_current' => false,
            'linked_at'  => $this->dispatchAt($production),
            'linked_by'  => null,
            'source'     => self::SOURCE,
            'metadata'   => json_encode([
                'rule'        => 'dispatch_at_not_before_marker_fifo',
                'marker_at'   => $marker->toDateTimeString(),
                'dispatch_at' => $this->dispatchAt($production)->toDateTimeString(),
                'reassigned'  => $reassigned,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->refreshCurrent($workReportId, $stage, $scope);
    }

    /** O vínculo corrente é o do despacho mais recente; vínculos explícitos (não retrofill) nunca são alterados. */
    private function refreshCurrent(int $workReportId, string $stage, string $scope): void
    {
        $links = DB::table('work_report_flow_productions as w')->join('productions as p', 'p.id', '=', 'w.production_id')
            ->where('w.work_report_id', $workReportId)->where('w.stage', $stage)->where('w.final_scope', $scope)->whereNull('w.reversed_at')
            ->get(['w.id', 'w.source', 'w.is_current', 'p.dispatch_at', 'p.created_at as production_created_at', 'p.id as production_id']);

        if ($links->contains(fn ($l) => !in_array($l->source, ['retrofill_inference', self::SOURCE], true))) {
            return;
        }

        $current = $links->sortBy(fn ($l) => [$l->dispatch_at ?? $l->production_created_at, $l->production_id])->last();

        $created = $this->note['created']['work_report_flow_productions'] ?? [];

        foreach ($links as $link) {
            if (!in_array($link->id, $created, true)) {
                $this->note['current'][$link->id]['before'] ??= (bool) $link->is_current;
            }
        }

        DB::table('work_report_flow_productions')->whereIn('id', $links->pluck('id'))->update(['is_current' => false]);
        DB::table('work_report_flow_productions')->where('id', $current->id)->update(['is_current' => true, 'updated_at' => now()]);
    }

    // ------------------------------------------------------------------ D5

    private function d5(int $noteId): void
    {
        $fives = FiveNote::query()->where('note_id', $noteId)->where('is_archived', false)->orderBy('created_at')->orderBy('id')->get();

        foreach ($fives as $five) {
            $this->associateReport($five);
            $this->settleCycle($five->fresh());
        }
    }

    /** D5 legada (sem informe): informe do vínculo das suas produções; senão o informe causal mais próximo. */
    private function associateReport(FiveNote $five): void
    {
        if ($five->work_report_id) {
            return;
        }

        if ($five->isPassive) {
            $this->associatePassiveReport($five);

            return;
        }

        $activeReports = DB::table('work_reports')->where('note_id', $five->note_id)->where('canceled', false)->pluck('id');
        $everyReport   = WorkReport::query()->where('note_id', $five->note_id)->get();
        $viaCanceled   = $this->canceledReportFor(Carbon::parse($five->created_at), $everyReport->where('canceled', true)->values(), $everyReport);

        $viaProduction = DB::table('work_report_flow_productions as w')
            ->join('productionables as pa', fn ($j) => $j->on('pa.production_id', '=', 'w.production_id')->where('pa.productionable_type', FiveNote::class)->where('pa.productionable_id', $five->id))
            ->whereNull('w.reversed_at')->distinct()->pluck('w.work_report_id');

        $reportId = match (true) {
            (bool) $viaCanceled           => (int) $viaCanceled->id, // D5 nascida dentro de um informe depois cancelado
            $activeReports->count() === 1 => (int) $activeReports->first(), // um informe final vigente por nota: sem ambiguidade
            $viaProduction->count() === 1 => (int) $viaProduction->first(),
            default                       => app(D5WorkReportAssociationResolver::class)->closestFor($five)?->id,
        };

        if (!$reportId) {
            $this->log('d5', $five->note_id, 'five_note', $five->id, 'ignorar', 'informe_nao_resolvido', ['candidatos_via_producao' => $viaProduction->count()]);

            return;
        }

        $this->applyReportAssociation($five, $reportId, '');
    }

    /**
     * D5 passiva nasce na empreiteira, sem informe. Quando a nota tem informe, associa o que as produções do ciclo
     * já usam; senão o informe cancelado que cobria a data; senão o vigente mais recente anterior à conclusão da D5
     * (o informe nunca pode ser posterior à D5).
     */
    private function associatePassiveReport(FiveNote $five): void
    {
        $reference   = Carbon::parse($five->completed_at ?? $five->created_at);
        $everyReport = WorkReport::query()->withCount('Returnwork')->where('note_id', $five->note_id)->get();

        if ($everyReport->isEmpty()) {
            return;
        }

        // D5 passiva ainda não concluída pela empreiteira: as produções da nota estão no ciclo 1 (são do informe, não da D5)
        // e não servem de evidência para a D5.
        $viaFlow = !$five->is_completed ? collect() : DB::table('work_report_flow_productions as w')->join('productions as p', 'p.id', '=', 'w.production_id')
            ->where('p.note_id', $five->note_id)->whereIn('p.service_id', [$this->fiscalId, $this->paymentId])
            ->where('p.dispatch_at', '>=', $reference)->whereNull('w.reversed_at')->distinct()->pluck('w.work_report_id');

        $viaCanceled = $this->canceledReportFor($reference, $everyReport->where('canceled', true)->values(), $everyReport);
        $prior       = $everyReport->where('canceled', false)->filter(fn (WorkReport $r) => $this->reportMarker($r)->lte($reference))
            ->sortBy(fn (WorkReport $r) => $this->reportMarker($r))->values();

        $reportId = match (true) {
            $viaFlow->count() === 1 => (int) $viaFlow->first(),
            (bool) $viaCanceled     => (int) $viaCanceled->id,
            $prior->isNotEmpty()    => (int) $prior->last()->id,
            default                 => null,
        };

        if (!$reportId) {
            $this->log('d5', $five->note_id, 'five_note', $five->id, 'ignorar', 'passiva_sem_informe_anterior', ['referencia' => $reference->toDateTimeString()]);

            return;
        }

        $this->applyReportAssociation($five, $reportId, 'd5_passiva');
    }

    private function applyReportAssociation(FiveNote $five, int $reportId, string $reason): void
    {
        $this->bump('d5_informe_associado');
        $this->log('d5', $five->note_id, 'five_note', $five->id, 'associar_informe', $reason, ['work_report_id' => $reportId]);
        $this->affectedReports[$reportId] = true;

        if ($this->apply) {
            $this->snapshot('five_notes', $five->id, $five->getAttributes());
            $five->forceFill(['work_report_id' => $reportId])->save();
        }
    }

    /**
     * Ciclos da D5 (sequência fixa: Fiscalização → Pagamento → Empreiteira → Fiscalização → Pagamento).
     *
     *  C1  Fiscalização que criou a D5: concluída até D5.created_at. Não é dfive e não supervisiona.
     *  M1  1º Pagamento com dispatch >= C1.completed_at. Concluído: dfive; D5 dispatch_at/payed_at = completed_at,
     *      visible_partner e is_payed = true (libera a empreiteira).
     *  --  Empreiteira conclui a D5 (is_completed + completed_at).
     *  C2  1ª Fiscalização com dispatch >= D5.completed_at: dfive. Concluída: is_supervisioned e supervisioned_at = completed_at
     *      (com pendências a D5 volta à empreiteira).
     *  M2  1º Pagamento com dispatch >= supervisioned_at: dfive. Concluído: D5 arquivada.
     *
     * D5 passiva (isPassive) não tem C1/M1: parte da empreiteira (Parceira → Fiscalização → Pagamento), usando o
     * completed_at da D5 como referência do ciclo 2. Enquanto não estiver concluída (is_completed), as produções da nota
     * estão no ciclo 1 (pertencem ao informe): nada é ligado à D5 nem marcado dfive.
     */
    private function settleCycle(FiveNote $five): void
    {
        $note = (int) $five->note_id;
        // Nota com mais de uma D5: cada ciclo só enxerga as produções anteriores à criação da D5 seguinte.
        $nextCreatedAt = DB::table('five_notes')->where('note_id', $note)
            ->where(fn ($q) => $q->where('created_at', '>', $five->created_at)->orWhere(fn ($w) => $w->where('created_at', $five->created_at)->where('id', '>', $five->id)))
            ->min('created_at');

        $productions = $this->productions($note)->filter(fn ($p) => !$p->partial && (!$nextCreatedAt || $this->dispatchAt($p)->lt(Carbon::parse($nextCreatedAt))));
        $fiscal      = $productions->where('service_id', $this->fiscalId)->values();
        $payments    = $productions->where('service_id', $this->paymentId)->values();
        $service     = app(D5WorkflowService::class);

        // D5 passiva nasce na empreiteira (sem informe, sem C1/M1): o ciclo parte da conclusão dela.
        $m1 = null;

        if (!$five->isPassive) {
            $first = $this->firstCycle($five, $fiscal, $payments, $service);

            if ($first === false) {
                return;
            }

            $m1   = $first['m1'];
            $five = $first['five'];
        }

        // Empreiteira: sem a conclusão da D5 não há Fiscalização do ciclo 2.
        if (!$five->is_completed) {
            return;
        }

        if (!$five->completed_at) {
            $this->log('d5', $note, 'five_note', $five->id, 'ignorar', 'd5_concluida_sem_completed_at', []);

            return;
        }

        // C2: 1ª Fiscalização despachada depois da conclusão da empreiteira.
        $c2 = $fiscal->first(fn ($p) => $this->dispatchAt($p)->gte($five->completed_at));

        if ($c2) {
            $this->linkD5Production($five, $c2, true);
        }

        if (!$five->is_supervisioned) {
            if (!$c2 || !$c2->completed || !$c2->completed_at) {
                return; // aguardando a Fiscalização do ciclo 2
            }

            $conclusion = DB::table('analises')->where('production_id', $c2->id)->orderByDesc('id')->value('conclusion');
            $pending    = in_array($conclusion, ['FISCALIZADO COM PENDENCIAS', 'NAO RESOLVIDO'], true);

            if (!$conclusion || (!$pending && $conclusion !== 'FISCALIZADO SEM PENDENCIAS')) {
                $this->log('d5', $note, 'five_note', $five->id, 'ignorar', $conclusion ? 'conclusao_' . Str::slug($conclusion, '_') : 'sem_analise', ['production_id' => $c2->id]);

                return;
            }

            $from = $service->currentStage($five);

            if ($pending) {
                $this->bump('d5_devolvida_com_pendencia');
                $this->log('d5', $note, 'five_note', $five->id, 'devolver_com_pendencia', '', ['production_id' => $c2->id, 'conclusao' => $conclusion]);

                if ($this->apply) {
                    $this->snapshot('five_notes', $five->id, $five->getAttributes());
                    $five->forceFill(['is_completed' => false, 'completed_at' => null, 'returned' => true])->save();
                    $this->emit($five, fn () => $service->onReturnedWithPending($five, $from, null, $five->productions()->find($c2->id)));
                }

                return;
            }

            $this->bump('d5_fiscalizada');
            $this->log('d5', $note, 'five_note', $five->id, 'baixar_d5', '', ['production_id' => $c2->id, 'supervisioned_at' => $c2->completed_at]);

            if ($five->work_report_id) {
                $this->affectedReports[(int) $five->work_report_id] = true;
            }

            if ($this->apply) {
                $this->snapshot('five_notes', $five->id, $five->getAttributes());
                $five->forceFill(['returned' => false, 'is_supervisioned' => true, 'supervisioned_at' => $c2->completed_at])->save();
                $this->emit($five, fn () => $service->onSupervisionApproved($five, $from, null, $five->productions()->find($c2->id)));
            }
        }

        // M2: 1º Pagamento depois da supervisão; concluído, arquiva a D5.
        $reference = $c2?->completed_at ? Carbon::parse($c2->completed_at) : ($five->supervisioned_at ? Carbon::parse($five->supervisioned_at) : null);

        if (!$reference) {
            return;
        }

        $m2 = $payments->first(fn ($p) => $p->id !== $m1?->id && $this->dispatchAt($p)->gte($reference));

        if (!$m2) {
            return;
        }

        $this->linkD5Production($five, $m2, true);

        if ($m2->completed && $m2->completed_at) {
            $this->bump('d5_arquivada');
            $this->log('d5', $note, 'five_note', $five->id, 'arquivar_d5', '', ['production_id' => $m2->id]);

            if ($this->apply) {
                $from = $service->currentStage($five);
                $this->snapshot('five_notes', $five->id, $five->getAttributes());
                $five->forceFill(['is_archived' => true])->save();
                $this->emit($five, fn () => $service->onArchived($five, $from, null, $five->productions()->find($m2->id)));
            }
        }
    }

    /**
     * Ciclo 1 (D5 de informe): C1 cria a D5, M1 libera a empreiteira.
     *
     * @return array{m1: object, five: FiveNote}|false false = a cadeia para aqui
     */
    private function firstCycle(FiveNote $five, Collection $fiscal, Collection $payments, D5WorkflowService $service): array|false
    {
        $note = (int) $five->note_id;

        // C1: a Fiscalização que criou a D5 (a última concluída até a criação, com folga de 5 min).
        $c1 = $fiscal->filter(fn ($p) => $p->completed && $p->completed_at && Carbon::parse($p->completed_at)->lte(Carbon::parse($five->created_at)->addMinutes(5)))
            ->sortBy([['completed_at', 'asc'], ['id', 'asc']])->last();

        if (!$c1) {
            $this->log('d5', $note, 'five_note', $five->id, 'ignorar', 'sem_fiscalizacao_ciclo1', []);

            return false;
        }

        $this->linkD5Production($five, $c1, false);

        // M1: libera a empreiteira.
        $m1 = $payments->first(fn ($p) => $this->dispatchAt($p)->gte(Carbon::parse($c1->completed_at)));

        if (!$m1 || !$m1->completed || !$m1->completed_at) {
            return false; // aguardando o Pagamento do ciclo 1
        }

        $this->linkD5Production($five, $m1, true);

        if (!$five->is_payed || !$five->visible_partner || !$five->dispatch_at || !$five->payed_at) {
            $this->bump('d5_liberada_empreiteira');
            $this->log('d5', $note, 'five_note', $five->id, 'liberar_empreiteira', '', ['production_id' => $m1->id, 'dispatch_at' => $m1->completed_at]);

            if ($this->apply) {
                $from = $service->currentStage($five);
                $this->snapshot('five_notes', $five->id, $five->getAttributes());
                $five->forceFill([
                    'dispatch_at'     => $five->dispatch_at ?? $m1->completed_at,
                    'visible_partner' => true,
                    'is_payed'        => true,
                    'payed_at'        => $five->payed_at ?? $m1->completed_at,
                ])->save();
                $this->emit($five, fn () => $service->onReleasedToPartner($five, $from, null, $five->productions()->find($m1->id)));
            }
        }

        return ['m1' => $m1, 'five' => $five];
    }

    /** Vincula a produção à D5 (pivot), marca dfive quando for produção D5 e garante o vínculo com o informe. */
    private function linkD5Production(FiveNote $five, object $production, bool $markDfive): void
    {
        if (!DB::table('productionables')->where('production_id', $production->id)->where('productionable_type', FiveNote::class)->where('productionable_id', $five->id)->exists()) {
            $this->bump('d5_producoes_vinculadas');
            $this->log('d5', $five->note_id, 'production', $production->id, 'vincular_d5', '', ['five_note_id' => $five->id]);

            if ($this->apply) {
                $this->note['created']['productionables'][] = DB::table('productionables')->insertGetId([
                    'production_id'     => $production->id, 'productionable_type' => FiveNote::class,
                    'productionable_id' => $five->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if ($markDfive && !$production->dfive) {
            $this->bump('producoes_marcadas_dfive');
            $this->log('d5', $five->note_id, 'production', $production->id, 'marcar_dfive', '', ['five_note_id' => $five->id]);
            $production->dfive = true;

            if ($this->apply) {
                $this->snapshot('productions', $production->id, DB::table('productions')->find($production->id));
                DB::table('productions')->where('id', $production->id)->update(['dfive' => true, 'updated_at' => now()]);
            }
        }

        if ($five->work_report_id && !$this->hasFlowLink($production->id)) {
            $stage = $this->stageByService[$production->service_id];
            $this->bump('d5_producoes_ligadas_ao_informe');
            $this->log('d5', $five->note_id, 'production', $production->id, "vincular_{$stage}", 'producao_d5', ['work_report_id' => $five->work_report_id]);

            if ($this->apply) {
                $this->insertFlowLink($production, (int) $five->work_report_id, $stage, $this->dispatchAt($production), false);
            }
        }
    }

    // ------------------------------------------------------------------ Suporte

    private function productions(int $noteId): Collection
    {
        return DB::table('productions')->where('note_id', $noteId)->whereIn('service_id', array_keys($this->stageByService))
            ->orderBy('dispatch_at')->orderBy('id')->get()
            ->map(function ($p) {
                $p->partial = (bool) $p->partial;
                $p->dfive   = (bool) $p->dfive;

                return $p;
            });
    }

    private function hasFlowLink(int $productionId): bool
    {
        return DB::table('work_report_flow_productions')->where('production_id', $productionId)->exists();
    }

    /** Data do despacho (ação humana); sem ela cai em att_at/created_at. */
    private function dispatchAt(object $production): Carbon
    {
        return Carbon::parse($production->dispatch_at ?? $production->att_at ?? $production->created_at);
    }

    private function emit(FiveNote $five, callable $callback): void
    {
        $before = DB::table('timeline_events')->where('five_note_id', $five->id)->pluck('id')->all();

        try {
            $callback();
        } catch (\Throwable $e) {
            $this->bump('eventos_timeline_ignorados');
        }

        $new = array_diff(DB::table('timeline_events')->where('five_note_id', $five->id)->pluck('id')->all(), $before);

        foreach ($new as $id) {
            $this->note['created']['timeline_events'][] = (int) $id;
        }
    }

    /** Guarda a linha original (primeira vez que a nota a altera). */
    private function snapshot(string $table, int|string $id, array|object $row): void
    {
        $this->note['rows'][$table][$id]['before'] ??= (array) $row;
    }

    /** Reduz o diário da nota ao que realmente mudou (coluna: [antes, depois]) para o rollback checar conflitos. */
    private function finalizeNoteJournal(): void
    {
        foreach ($this->note['rows'] as $table => $rows) {
            foreach ($rows as $id => $entry) {
                $now     = (array) DB::table($table)->find($id);
                $changes = [];

                foreach ($entry['before'] as $column => $value) {
                    if ($column === 'updated_at') {
                        continue;
                    }

                    $after = $now[$column] ?? null;

                    if (is_null($value) !== is_null($after) || (string) $value !== (string) $after) {
                        $changes[$column] = [$value, $after];
                    }
                }

                if ($changes) {
                    $this->note['rows'][$table][$id] = ['changes' => $changes];
                } else {
                    unset($this->note['rows'][$table][$id]);
                }
            }

            if (empty($this->note['rows'][$table])) {
                unset($this->note['rows'][$table]);
            }
        }

        foreach ($this->note['current'] as $id => $entry) {
            $now = DB::table('work_report_flow_productions')->where('id', $id)->value('is_current');

            if ($now === null || (bool) $now === $entry['before']) {
                unset($this->note['current'][$id]);

                continue;
            }

            $this->note['current'][$id]['after'] = (bool) $now;
        }
    }

    private function flushJournal(int $noteId): void
    {
        if (!array_filter([$this->note['rows'], $this->note['created'], $this->note['current']])) {
            return;
        }

        Storage::disk('local')->append($this->journalPath, json_encode(['note_id' => $noteId] + $this->note, JSON_UNESCAPED_UNICODE));
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    private function log(string $phase, int $noteId, string $entity, int|string $entityId, string $action, string $reason, array $details): void
    {
        $this->report[] = [$phase, $noteId, $entity, $entityId, $action, $reason, json_encode($details, JSON_UNESCAPED_UNICODE)];
    }

    private function preparePaths(): void
    {
        $this->reportPath = $this->option('report') ?: 'retrofill/legacy-d5-' . now()->format('Ymd-His') . ($this->apply ? '' : '-dryrun') . '.csv';

        if ($this->apply) {
            $this->journalPath = preg_replace('/\.csv$/', '', $this->reportPath) . '-journal.jsonl';

            Storage::disk('local')->put($this->journalPath, json_encode(['meta' => ['version' => 1, 'started_at' => now()->toDateTimeString(), 'source' => self::SOURCE]], JSON_UNESCAPED_UNICODE) . "\n");
            $this->line('Diário (para rollback): ' . Storage::disk('local')->path($this->journalPath));
        }
    }

    private function writeFiles(): void
    {
        $rows = [implode(';', ['fase', 'nota', 'entidade', 'id', 'acao', 'motivo', 'detalhes'])];

        foreach ($this->report as $row) {
            $rows[] = implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row));
        }

        Storage::disk('local')->put($this->reportPath, implode("\n", $rows));
        $this->line('Relatório: ' . Storage::disk('local')->path($this->reportPath));
    }
}
