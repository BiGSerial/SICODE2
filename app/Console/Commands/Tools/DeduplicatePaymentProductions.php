<?php

namespace App\Console\Commands\Tools;

use App\Custom\Notestatus;
use App\Models\{FiveNote, MeasurementProductionContext, Partial, Production, WorkReport, WorkReportFlowProduction};
use App\Services\Payment\MeasurementProductionContextService;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Support\{Collection, Str};
use Illuminate\Support\Facades\{DB, Storage};
use Throwable;

/**
 * Saneamento assistido de duplicidades abertas de Medição/Pagamento.
 *
 * Nenhuma inferência é feita no modo de escrita: o operador informa a
 * Production sobrevivente, as removidas e o ciclo. Isso impede que um Ciclo 1
 * com D5 seja confundido com o retorno (Ciclo 2), erro que a tabela legada não
 * consegue resolver somente pelo note_id/dfive.
 */
class DeduplicatePaymentProductions extends Command
{
    protected $signature = 'sicode:deduplicate-payment-productions
                            {--json : Emite o diagnóstico em JSON}
                            {--limit=200 : Máximo de grupos exibidos}
                            {--inspect-d5=* : IDs de D5 para detalhar antes do saneamento}
                            {--plan : Gera plano de saneamento; sugere keep somente quando houver um único histórico fechado compatível}
                            {--keep= : ID da Production correta a preservar}
                            {--remove=* : IDs das Productions duplicadas a remover}
                            {--remove-others : Remove todas as abertas equivalentes à Production mantida}
                            {--cycle= : partial, cycle_1 ou d5_return}
                            {--apply : Executa o saneamento explicitamente informado}
                            {--yes : Confirma a exclusão definitiva das duplicadas abertas}
                            {--force-assigned : Permite remover abertas já atribuídas; exige --apply e --reason}
                            {--reason= : Justificativa obrigatória do saneamento}';

    protected $description = 'Localiza duplicidades de Medição/Pagamento e, com confirmação explícita, remove apenas duplicadas abertas';

    public function handle(): int
    {
        if (!$this->option('apply')) {
            return $this->report();
        }

        return $this->apply();
    }

    private function report(): int
    {
        $services = $this->paymentServiceIds();
        $result   = [
            'read_only'       => true,
            'open_definition' => 'completed = false AND confirmed = false',
            'groups'          => [
                'contextual'                => $this->contextualGroups($services),
                'legacy_partial'            => $this->legacyPartialGroups($services),
                'legacy_cycle_1_without_d5' => $this->legacyCycleOneGroups($services),
                // A mesma D5 pode participar de C1 e C2. Estes grupos não são
                // automaticamente removíveis sem --cycle explícito.
                'legacy_d5_needs_cycle_review' => $this->legacyD5Groups($services),
            ],
        ];

        $d5Ids = collect($this->option('inspect-d5'))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($d5Ids->isNotEmpty()) {
            $result['d5_inspection'] = $this->inspectD5($d5Ids, $services);
        }

        if ($this->option('plan')) {
            $result['remediation_plan'] = $this->d5RemediationPlan($services);
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('DIAGNÓSTICO — nenhuma Production foi alterada.');
        $this->line('Aberta: ' . $result['open_definition']);
        $this->line('Para executar, informe --keep, --remove ou --remove-others, --cycle, --reason e --apply.');

        foreach ($result['groups'] as $name => $groups) {
            $this->newLine();
            $this->comment(Str::headline($name) . ': ' . count($groups));

            if ($groups !== []) {
                $this->table(['identidade', 'productions_abertas', 'sugestão_manter', 'observação'], $groups);
            }
        }

        if (isset($result['d5_inspection'])) {
            foreach ($result['d5_inspection'] as $inspection) {
                $this->newLine();
                $this->comment('D5 #' . $inspection['d5']['id'] . ' — ' . $inspection['cycle_hint']);
                $this->table(array_keys($inspection['d5']), [$inspection['d5']]);
                $this->table(array_keys($inspection['productions'][0] ?? ['mensagem' => 'Nenhuma Production de Pagamento encontrada']), $inspection['productions'] ?: [['mensagem' => 'Nenhuma Production de Pagamento encontrada']]);
            }
        }

        if (isset($result['remediation_plan'])) {
            $this->newLine();
            $this->comment('PLANO PROPOSTO — não executa remoção:');

            if ($result['remediation_plan'] === []) {
                $this->line('Nenhum grupo elegível para sugestão automática.');
            } else {
                $this->table(array_keys($result['remediation_plan'][0]), $result['remediation_plan']);
            }
        }

        return self::SUCCESS;
    }

    private function apply(): int
    {
        $keepId    = (int) $this->option('keep');
        $removeIds = collect($this->option('remove'))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $cycle     = (string) $this->option('cycle');
        $reason    = trim((string) $this->option('reason'));

        if (!$keepId || (!$this->option('remove-others') && $removeIds->isEmpty()) || !$reason || !in_array($cycle, MeasurementProductionContext::cycles(), true)) {
            $this->error('Modo --apply exige --keep, --cycle válido, --reason e --remove ou --remove-others. Nada foi alterado.');

            return self::FAILURE;
        }

        if ($this->option('remove-others')) {
            try {
                $removeIds = $this->openEquivalentProductionIds($keepId, $cycle);
            } catch (Throwable $exception) {
                $this->error('Não foi possível localizar as duplicadas: ' . $exception->getMessage());

                return self::FAILURE;
            }

            if ($removeIds->isEmpty()) {
                $this->warn('Nenhuma Production aberta equivalente foi encontrada. Nada foi alterado.');

                return self::SUCCESS;
            }
        }

        if ($removeIds->contains($keepId)) {
            $this->error('A Production preservada não pode constar em --remove.');

            return self::FAILURE;
        }

        if (!$this->option('yes') && !$this->confirm("Manter Production {$keepId} e remover as abertas: " . $removeIds->implode(', ') . '?')) {
            $this->warn('Saneamento cancelado pelo usuário.');

            return self::SUCCESS;
        }

        try {
            $audit = DB::transaction(function () use ($keepId, $removeIds, $cycle, $reason): array {
                /** @var Collection<int, Production> $productions */
                $productions = Production::query()
                    ->whereIn('id', $removeIds->merge([$keepId])->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($productions->count() !== $removeIds->count() + 1 || !$productions->has($keepId)) {
                    throw new DomainException('Uma ou mais Productions não existem mais. Atualize o diagnóstico antes de tentar novamente.');
                }

                $serviceIds = $this->paymentServiceIds();

                foreach ($productions as $production) {
                    if (!in_array($production->service_id, $serviceIds, true)) {
                        throw new DomainException("Production {$production->id} não é de Medição/Pagamento.");
                    }

                    if ($production->id !== $keepId && !$this->isOpen($production)) {
                        throw new DomainException("Production {$production->id} já não está aberta; histórico não é removido por esta ferramenta.");
                    }
                }

                $identity = $this->assertSameExplicitIdentity($productions, $cycle);
                $keep     = $productions->get($keepId);
                $this->assertCanRemove($productions->only($removeIds->all()), (bool) $this->option('force-assigned'));
                $this->ensureCorrectContext($keep, $cycle, $identity);

                // work_report_flow_productions não possui cascade para production_id.
                // Estes vínculos pertencem às duplicadas e são removidos junto
                // com elas dentro da mesma transação.
                WorkReportFlowProduction::query()->whereIn('production_id', $removeIds)->delete();
                Production::query()->whereIn('id', $removeIds)->delete();

                return [
                    'at'                     => now()->toIso8601String(),
                    'kept_production_id'     => $keep->id,
                    'removed_production_ids' => $removeIds->all(),
                    'cycle'                  => $cycle,
                    'identity'               => $identity,
                    'reason'                 => $reason,
                    'force_assigned'         => (bool) $this->option('force-assigned'),
                ];
            }, 3);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Saneamento cancelado: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $path = 'audits/payment-production-deduplication/' . now()->format('Ymd_His_u') . '_keep_' . $keepId . '.json';
        Storage::disk('local')->put($path, json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info('Saneamento concluído. Production mantida: ' . $keepId . '. Removidas: ' . implode(', ', $audit['removed_production_ids']));
        $this->line('Relatório de auditoria: storage/app/' . $path);

        return self::SUCCESS;
    }

    /** @return array{kind:string,partial_id?:int,work_report_id?:int,five_note_id?:int} */
    private function assertSameExplicitIdentity(Collection $productions, string $cycle): array
    {
        $identities = $productions->map(fn (Production $production) => $this->identityFor($production, $cycle));

        if ($identities->contains(null) || $identities->unique(fn ($identity) => json_encode($identity))->count() !== 1) {
            throw new DomainException('As Productions não possuem a mesma identidade explícita. Não houve remoção.');
        }

        return $identities->first();
    }

    private function identityFor(Production $production, string $cycle): ?array
    {
        $context = $production->measurementContext()->first();

        if ($context && $context->cycle === $cycle) {
            return match ($cycle) {
                MeasurementProductionContext::CYCLE_PARTIAL   => ['kind' => 'partial', 'partial_id' => (int) $context->partial_id],
                MeasurementProductionContext::CYCLE_ONE       => ['kind' => 'cycle_1', 'work_report_id' => (int) $context->work_report_id, 'five_note_id' => $context->five_note_id ? (int) $context->five_note_id : null],
                MeasurementProductionContext::CYCLE_D5_RETURN => ['kind' => 'd5_return', 'work_report_id' => $context->work_report_id ? (int) $context->work_report_id : null, 'five_note_id' => (int) $context->five_note_id],
            };
        }

        $partials = $production->partialInforms()->pluck('partials.id')->map(fn ($id) => (int) $id)->unique()->values();
        $d5s      = $production->fiveNotes()->pluck('five_notes.id')->map(fn ($id) => (int) $id)->unique()->values();
        $reports  = $production->WorkReportFlowProductions()
            ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
            ->pluck('work_report_id')->map(fn ($id) => (int) $id)->unique()->values();

        if ($cycle === MeasurementProductionContext::CYCLE_PARTIAL && $production->partial && $partials->count() === 1 && $d5s->isEmpty()) {
            return ['kind' => 'partial', 'partial_id' => $partials->first()];
        }

        if ($cycle === MeasurementProductionContext::CYCLE_ONE && !$production->partial && $partials->isEmpty() && $d5s->count() <= 1) {
            $workReportId = $reports->count() === 1 ? $reports->first() : null;

            // Legado: antes do pivot de fluxo, o vínculo inequívoco era gravado
            // na própria D5. Só D5 não passiva pode fornecer Informe para C1.
            if (!$workReportId && $d5s->count() === 1) {
                $d5           = FiveNote::find($d5s->first());
                $workReportId = $d5 && !$d5->isPassive ? $d5->work_report_id : null;
            }

            if ($workReportId) {
                return ['kind' => 'cycle_1', 'work_report_id' => (int) $workReportId, 'five_note_id' => $d5s->first()];
            }
        }

        if ($cycle === MeasurementProductionContext::CYCLE_D5_RETURN && !$production->partial && $d5s->count() === 1 && $reports->count() <= 1 && $partials->isEmpty()) {
            return ['kind' => 'd5_return', 'work_report_id' => $reports->first(), 'five_note_id' => $d5s->first()];
        }

        return null;
    }

    private function ensureCorrectContext(Production $production, string $cycle, array $identity): void
    {
        $existing = $production->measurementContext()->lockForUpdate()->first();

        if ($existing) {
            if ($existing->cycle !== $cycle || $this->identityFor($production, $cycle) !== $identity) {
                throw new DomainException("A Production {$production->id} possui contexto divergente e não pode ser corrigida automaticamente.");
            }

            return;
        }

        $context = app(MeasurementProductionContextService::class);
        match ($cycle) {
            MeasurementProductionContext::CYCLE_PARTIAL => $context->recordPartial($production, Partial::findOrFail($identity['partial_id'])),
            MeasurementProductionContext::CYCLE_ONE     => $context->recordCycleOne(
                $production,
                WorkReport::findOrFail($identity['work_report_id']),
                isset($identity['five_note_id']) && $identity['five_note_id'] ? FiveNote::findOrFail($identity['five_note_id']) : null,
            ),
            MeasurementProductionContext::CYCLE_D5_RETURN => $context->recordD5Return(
                $production,
                !empty($identity['work_report_id']) ? WorkReport::findOrFail($identity['work_report_id']) : null,
                FiveNote::findOrFail($identity['five_note_id']),
            ),
        };
    }

    private function assertCanRemove(Collection $productions, bool $forceAssigned): void
    {
        foreach ($productions as $production) {
            if ($production->Analise()->exists() || $production->d5Return()->exists()) {
                throw new DomainException("Production {$production->id} possui análise ou retorno D5; ela exige saneamento manual e não será apagada.");
            }

            $hasAssignment = $production->user_id || $production->att_by || $production->att_at;

            if ($hasAssignment && !$forceAssigned) {
                throw new DomainException("Production {$production->id} possui atribuição ou evidência de execução; ela exige saneamento manual e não será apagada.");
            }
        }
    }

    private function isOpen(Production $production): bool
    {
        return !$production->completed && !$production->confirmed;
    }

    /** @return Collection<int, int> */
    private function openEquivalentProductionIds(int $keepId, string $cycle): Collection
    {
        $keep     = Production::query()->findOrFail($keepId);
        $identity = $this->identityFor($keep, $cycle);

        if (!$identity) {
            throw new DomainException('A Production mantida não possui uma identidade explícita compatível com o ciclo informado.');
        }

        $query = Production::query()
            ->whereIn('service_id', $this->paymentServiceIds())
            ->where('completed', false)
            ->where('confirmed', false)
            ->where('id', '<>', $keepId);

        match ($identity['kind']) {
            'partial' => $query->whereHas('partialInforms', fn ($relation) => $relation->whereKey($identity['partial_id'])),
            'cycle_1', 'd5_return' => isset($identity['five_note_id']) && $identity['five_note_id']
                ? $query->whereHas('fiveNotes', fn ($relation) => $relation->whereKey($identity['five_note_id']))
                : $query->whereHas('WorkReportFlowProductions', fn ($relation) => $relation
                    ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                    ->where('work_report_id', $identity['work_report_id'])),
        };

        return $query->get()
            ->filter(fn (Production $candidate) => $this->identityFor($candidate, $cycle) === $identity)
            ->pluck('id')->map(fn ($id) => (int) $id)->values();
    }

    /** @return array<int, string> */
    private function paymentServiceIds(): array
    {
        return DB::table('services')->select(['uuid', 'service'])->get()
            ->filter(fn (object $service) => in_array(Str::lower(trim(Str::ascii((string) $service->service))), ['pagamento', 'medicao'], true))
            ->pluck('uuid')->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function contextualGroups(array $services): array
    {
        return DB::table('measurement_production_contexts as c')->join('productions as p', 'p.id', '=', 'c.production_id')
            ->whereIn('p.service_id', $services)->where('p.completed', false)->where('p.confirmed', false)
            ->groupBy(['c.cycle', 'c.work_report_id', 'c.partial_id', 'c.five_note_id'])
            ->havingRaw('COUNT(*) > 1')->selectRaw('c.cycle, c.work_report_id, c.partial_id, c.five_note_id, GROUP_CONCAT(p.id ORDER BY p.dispatch_at, p.id) as production_ids')
            ->limit((int) $this->option('limit'))->get()->map(fn ($row) => $this->reportRow($row, 'contexto persistido'))->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function legacyPartialGroups(array $services): array
    {
        return $this->legacyPivotGroups($services, Partial::class, 'partial', 'partial_id', 'Informe Parcial');
    }

    /** @return array<int, array<string, mixed>> */
    private function legacyD5Groups(array $services): array
    {
        return $this->legacyPivotGroups($services, FiveNote::class, 'd5', 'five_note_id', 'D5: execute --inspect-d5=ID; --cycle é obrigatório antes de qualquer remoção', false);
    }

    /** @return array<int, array<string, mixed>> */
    private function legacyPivotGroups(array $services, string $type, string $label, string $idColumn, string $note, bool $suggestFirst = true): array
    {
        return DB::table('productions as p')->join('productionables as pa', function ($join) use ($type) {
            $join->on('pa.production_id', '=', 'p.id')->where('pa.productionable_type', $type);
        })->leftJoin('measurement_production_contexts as c', 'c.production_id', '=', 'p.id')
            ->whereIn('p.service_id', $services)->whereNull('c.id')->where('p.completed', false)->where('p.confirmed', false)
            ->groupBy('pa.productionable_id')->havingRaw('COUNT(*) > 1')
            ->selectRaw("'{$label}' as cycle, null as work_report_id, "
                . ($idColumn === 'partial_id' ? 'pa.productionable_id' : 'null') . ' as partial_id, '
                . ($idColumn === 'five_note_id' ? 'pa.productionable_id' : 'null') . ' as five_note_id, '
                . 'GROUP_CONCAT(p.id ORDER BY p.dispatch_at, p.id) as production_ids')
            ->limit((int) $this->option('limit'))->get()->map(fn ($row) => $this->reportRow($row, $note, $suggestFirst))->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function legacyCycleOneGroups(array $services): array
    {
        return DB::table('productions as p')->join('work_report_flow_productions as f', function ($join) {
            $join->on('f.production_id', '=', 'p.id')->where('f.stage', WorkReportFlowProduction::STAGE_PAYMENT);
        })->leftJoin('measurement_production_contexts as c', 'c.production_id', '=', 'p.id')
            ->whereIn('p.service_id', $services)->whereNull('c.id')->where('p.partial', false)->where('p.dfive', false)
            ->where('p.completed', false)->where('p.confirmed', false)->groupBy('f.work_report_id')->havingRaw('COUNT(DISTINCT p.id) > 1')
            ->selectRaw("'cycle_1' as cycle, f.work_report_id, null as partial_id, null as five_note_id, GROUP_CONCAT(DISTINCT p.id ORDER BY p.id) as production_ids")
            ->limit((int) $this->option('limit'))->get()->map(fn ($row) => $this->reportRow($row, 'legado sem D5'))->all();
    }

    private function reportRow(object $row, string $note, bool $suggestFirst = true): array
    {
        $ids = array_values(array_filter(explode(',', (string) $row->production_ids)));

        return [
            'identidade'          => ($row->cycle ?? 'desconhecida') . ' / relatório:' . ($row->work_report_id ?? '-') . ' / parcial:' . ($row->partial_id ?? '-') . ' / d5:' . ($row->five_note_id ?? '-'),
            'productions_abertas' => implode(', ', $ids),
            'sugestão_manter'     => $suggestFirst ? ($ids[0] ?? '-') : 'revisar ciclo',
            'observação'          => $note,
        ];
    }

    /** @return array<int, array{d5: array<string, mixed>, cycle_hint: string, productions: array<int, array<string, mixed>>}> */
    private function inspectD5(Collection $d5Ids, array $services): array
    {
        return FiveNote::query()->whereIn('id', $d5Ids)->get()->map(function (FiveNote $d5) use ($services): array {
            $productions = Production::query()
                ->whereIn('service_id', $services)
                ->whereHas('fiveNotes', fn ($query) => $query->whereKey($d5->id))
                ->with([
                    'measurementContext',
                    'WorkReportFlowProductions' => fn ($query) => $query->where('stage', WorkReportFlowProduction::STAGE_PAYMENT),
                ])
                ->orderBy('dispatch_at')->orderBy('id')->get()
                ->map(fn (Production $production) => [
                    'production_id'      => $production->id,
                    'aberta'             => $this->isOpen($production) ? 'sim' : 'não',
                    'status'             => $production->status . ' — ' . (Notestatus::status($production->status)->status ?? 'desconhecido'),
                    'usuario'            => $production->user_id ?? '-',
                    'atribuido_em'       => $production->att_at?->toDateTimeString() ?? '-',
                    'despachado_em'      => $production->dispatch_at?->toDateTimeString() ?? '-',
                    'contexto'           => $production->measurementContext?->cycle ?? 'legado sem contexto',
                    'informe_no_vinculo' => $production->WorkReportFlowProductions->pluck('work_report_id')->unique()->implode(', ') ?: '-',
                ])->all();

            $hint = $d5->isEligibleForPaymentReturn()
                ? 'estado atual elegível para Ciclo 2 (confirme o histórico antes de remover)'
                : 'estado atual não elegível para Ciclo 2; se for Medição, trate como possível Ciclo 1';

            return [
                'd5' => [
                    'id'          => $d5->id,
                    'nota'        => $d5->note_id,
                    'informe'     => $d5->work_report_id ?? '-',
                    'passiva'     => $d5->isPassive ? 'sim' : 'não',
                    'concluida'   => $d5->is_completed ? 'sim' : 'não',
                    'fiscalizada' => $d5->is_supervisioned ? 'sim' : 'não',
                    'arquivada'   => $d5->is_archived ? 'sim' : 'não',
                    'retornada'   => $d5->returned ? 'sim' : 'não',
                ],
                'cycle_hint'  => $hint,
                'productions' => $productions,
            ];
        })->values()->all();
    }

    /**
     * Produz comandos somente quando a escolha não é uma heurística: uma D5
     * não elegível a C2, ligada a um Informe, e uma única Production histórica
     * fechada para o mesmo C1. Demais situações ficam em revisão humana.
     *
     * @return array<int, array<string, string>
     */
    private function d5RemediationPlan(array $services): array
    {
        $d5Ids = DB::table('productions as p')
            ->join('productionables as pa', function ($join) {
                $join->on('pa.production_id', '=', 'p.id')->where('pa.productionable_type', FiveNote::class);
            })
            ->leftJoin('measurement_production_contexts as c', 'c.production_id', '=', 'p.id')
            ->whereIn('p.service_id', $services)->whereNull('c.id')
            ->where('p.completed', false)->where('p.confirmed', false)
            ->groupBy('pa.productionable_id')->havingRaw('COUNT(*) > 1')
            ->limit((int) $this->option('limit'))->pluck('pa.productionable_id');

        return FiveNote::query()->whereIn('id', $d5Ids)->get()->map(function (FiveNote $d5) use ($services): array {
            if ($d5->isEligibleForPaymentReturn()) {
                return [
                    'd5'      => (string) $d5->id,
                    'ciclo'   => 'revisar',
                    'manter'  => '-',
                    'abertas' => '-',
                    'comando' => 'D5 elegível a Ciclo 2: revisar histórico antes de decidir.',
                ];
            }

            if ($d5->isPassive || !$d5->work_report_id) {
                return [
                    'd5'      => (string) $d5->id,
                    'ciclo'   => 'revisar',
                    'manter'  => '-',
                    'abertas' => '-',
                    'comando' => 'D5 passiva/sem Informe: não há keep automático.',
                ];
            }

            $cycle       = MeasurementProductionContext::CYCLE_ONE;
            $productions = Production::query()->whereIn('service_id', $services)
                ->whereHas('fiveNotes', fn ($relation) => $relation->whereKey($d5->id))
                ->orderBy('id')->get()
                ->filter(fn (Production $production) => $this->identityFor($production, $cycle) === [
                    'kind'           => 'cycle_1',
                    'work_report_id' => (int) $d5->work_report_id,
                    'five_note_id'   => (int) $d5->id,
                ]);

            $closed = $productions->filter(fn (Production $production) => !$this->isOpen($production));
            $open   = $productions->filter(fn (Production $production) => $this->isOpen($production));

            if ($closed->count() !== 1 || $open->count() < 2) {
                return [
                    'd5'      => (string) $d5->id,
                    'ciclo'   => 'revisar',
                    'manter'  => $closed->pluck('id')->implode(', ') ?: '-',
                    'abertas' => $open->pluck('id')->implode(', ') ?: '-',
                    'comando' => 'Não há uma única Production fechada compatível.',
                ];
            }

            $keep = (int) $closed->sole()->id;

            return [
                'd5'      => (string) $d5->id,
                'ciclo'   => 'cycle_1',
                'manter'  => (string) $keep,
                'abertas' => $open->pluck('id')->implode(', '),
                'comando' => "--keep={$keep} --remove-others --cycle=cycle_1 --force-assigned --reason=\"REVISAR_JUSTIFICATIVA\" --apply",
            ];
        })->values()->all();
    }
}
