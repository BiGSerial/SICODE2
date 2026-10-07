<?php

namespace App\Console\Commands\Tools;

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
                            {--keep= : ID da Production correta a preservar}
                            {--remove=* : IDs das Productions duplicadas a remover}
                            {--cycle= : partial, cycle_1 ou d5_return}
                            {--apply : Executa o saneamento explicitamente informado}
                            {--yes : Confirma a exclusão definitiva das duplicadas abertas}
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

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('DIAGNÓSTICO — nenhuma Production foi alterada.');
        $this->line('Aberta: ' . $result['open_definition']);
        $this->line('Para executar, informe --keep, todos os --remove, --cycle, --reason, --apply e --yes.');

        foreach ($result['groups'] as $name => $groups) {
            $this->newLine();
            $this->comment(Str::headline($name) . ': ' . count($groups));

            if ($groups !== []) {
                $this->table(['identidade', 'productions_abertas', 'sugestão_manter', 'observação'], $groups);
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

        if (!$this->option('yes') || !$keepId || $removeIds->isEmpty() || !$reason || !in_array($cycle, MeasurementProductionContext::cycles(), true)) {
            $this->error('Modo --apply exige --yes, --keep, ao menos um --remove, --cycle válido e --reason. Nada foi alterado.');

            return self::FAILURE;
        }

        if ($removeIds->contains($keepId)) {
            $this->error('A Production preservada não pode constar em --remove.');

            return self::FAILURE;
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

                    if (!$this->isOpen($production)) {
                        throw new DomainException("Production {$production->id} já não está aberta; histórico não é removido por esta ferramenta.");
                    }
                }

                $identity = $this->assertSameExplicitIdentity($productions, $cycle);
                $keep     = $productions->get($keepId);
                $this->assertCanRemove($productions->only($removeIds->all()));
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

        if ($cycle === MeasurementProductionContext::CYCLE_ONE && !$production->partial && $reports->count() === 1 && $partials->isEmpty() && $d5s->count() <= 1) {
            return ['kind' => 'cycle_1', 'work_report_id' => $reports->first(), 'five_note_id' => $d5s->first()];
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

    private function assertCanRemove(Collection $productions): void
    {
        foreach ($productions as $production) {
            $hasExecutionEvidence = $production->user_id || $production->att_by || $production->att_at || $production->completed_at
                || $production->Analise()->exists() || $production->d5Return()->exists();

            if ($hasExecutionEvidence) {
                throw new DomainException("Production {$production->id} possui atribuição ou evidência de execução; ela exige saneamento manual e não será apagada.");
            }
        }
    }

    private function isOpen(Production $production): bool
    {
        return !$production->completed && !$production->confirmed;
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
        return $this->legacyPivotGroups($services, FiveNote::class, 'd5', 'five_note_id', 'D5: informe --cycle antes de qualquer remoção');
    }

    /** @return array<int, array<string, mixed>> */
    private function legacyPivotGroups(array $services, string $type, string $label, string $idColumn, string $note): array
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
            ->limit((int) $this->option('limit'))->get()->map(fn ($row) => $this->reportRow($row, $note))->all();
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

    private function reportRow(object $row, string $note): array
    {
        $ids = array_values(array_filter(explode(',', (string) $row->production_ids)));

        return [
            'identidade'          => ($row->cycle ?? 'desconhecida') . ' / relatório:' . ($row->work_report_id ?? '-') . ' / parcial:' . ($row->partial_id ?? '-') . ' / d5:' . ($row->five_note_id ?? '-'),
            'productions_abertas' => implode(', ', $ids),
            'sugestão_manter'     => $ids[0] ?? '-',
            'observação'          => $note,
        ];
    }
}
