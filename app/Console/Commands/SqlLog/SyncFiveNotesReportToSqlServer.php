<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\Notestatus;
use App\Models\SicodeSql\LogFiveNotesReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFiveNotesReportToSqlServer extends Command
{
    use ShowsProgress;

    private const SQLSERVER_BIND_LIMIT = 2000;
    private const SQLSERVER_BIND_BUFFER = 50;

    private array $columnLimits = [];
    private ?string $fiscalizationServiceId = null;
    private ?string $paymentServiceId = null;

    protected $signature = 'sicode:sync-log-five-notes-report
        {--hours=2 : Janela incremental com sobreposicao}
        {--since= : Data/hora inicial da janela incremental}
        {--all : Sincroniza todas as D5}
        {--chunk=300 : Tamanho do lote lido do MySQL}
        {--if-empty : Faz carga completa se a tabela destino estiver vazia}
        {--dry-run : Simula sem gravar no SQL Server}
        {--force : Permite executar fora de producao}';

    protected $description = 'Sincroniza a Consulta Geral D5 para dbo.log_five_notes_report_sync.';

    public function handle(): int
    {
        if ((env('APP_QA') || env('APP_ENV') === 'local') && !$this->option('force')) {
            $this->info('NAO E AMBIENTE DE PRODUCAO, ABORTANDO LOG PARA SQL SERVER.');
            $this->line('Use --dry-run --force para validar localmente.');

            return self::SUCCESS;
        }

        try {
            $dryRun = (bool) $this->option('dry-run');
            $chunk = max(50, (int) $this->option('chunk'));
            $since = $this->incrementalSince();
            $full = (bool) $this->option('all') || $this->destinationIsEmpty();

            $this->resolveServiceIds();
            $this->loadColumnLimits();

            $ids = $full ? null : $this->affectedFiveNoteIds($since);

            $this->info('Sync Consulta Geral D5 -> SQL Server');
            $this->line('Destino: sqlsrv2.dbo.log_five_notes_report_sync');
            $this->line('Modo: ' . ($full ? 'FULL' : "INCREMENTAL desde {$since->toDateTimeString()}"));

            if (is_array($ids)) {
                $this->line('D5 afetadas: ' . count($ids));
            }

            $query = DB::table('five_notes as fn')
                ->leftJoin('notes as n', 'n.id', '=', 'fn.note_id')
                ->leftJoin('companies as c', 'c.id', '=', 'fn.company_id')
                ->when(is_array($ids), fn ($builder) => $builder->whereIn('fn.id', $ids))
                ->orderBy('fn.id')
                ->select([
                    'fn.*',
                    'n.note as note_number',
                    'n.rubrica',
                    'c.name as company_name',
                ]);

            $total = (int) (clone $query)->count();

            if ($total === 0) {
                $this->info('Nada para sincronizar.');
                return self::SUCCESS;
            }

            $this->line("Registros para enviar: {$total}");

            $bar = $this->createProgressBar($total);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %message%');
            $bar->setMessage('Preparando...');
            $bar->start();

            $prepared = 0;
            $written = 0;

            DB::disableQueryLog();

            $query->chunkById($chunk, function (Collection $fiveNotes) use (
                &$prepared,
                &$written,
                $bar,
                $dryRun
            ) {
                $context = $this->loadChunkContext($fiveNotes);
                $rows = [];
                $columnsPerRow = 0;

                foreach ($fiveNotes as $five) {
                    $row = $this->mapRow($five, $context);
                    $columnsPerRow = $columnsPerRow ?: count($row);
                    $rows[] = $row;
                    $prepared++;

                    $bar->setMessage("five_note_id={$five->id}");
                    $bar->advance();
                }

                if ($dryRun || !$rows) {
                    return;
                }

                foreach (array_chunk($rows, $this->safeBatchSize($columnsPerRow)) as $batch) {
                    LogFiveNotesReport::query()->upsert(
                        $batch,
                        ['id_local'],
                        $this->upsertColumns()
                    );

                    $written += count($batch);
                }
            }, 'fn.id', 'id');

            $bar->finish();
            $this->newLine(2);

            if (!$dryRun) {
                $this->deactivateDeletedRows();
            }

            $this->info('Sync finalizado.');
            $this->line("Preparados: {$prepared}" . ($dryRun ? ' (dry-run)' : ''));
            $this->line("Gravados (upsert): {$written}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Erro no sync: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }

    private function incrementalSince(): Carbon
    {
        $since = trim((string) $this->option('since'));

        return $since !== ''
            ? Carbon::parse($since)
            : now()->subHours(max(1, (int) $this->option('hours')));
    }

    private function destinationIsEmpty(): bool
    {
        if (!$this->option('if-empty')) {
            return false;
        }

        try {
            return LogFiveNotesReport::query()->doesntExist();
        } catch (\Throwable) {
            return true;
        }
    }

    private function affectedFiveNoteIds(Carbon $since): array
    {
        $collections = [
            DB::table('five_notes')->where('updated_at', '>=', $since)->pluck('id'),
            DB::table('timeline_events')->where('updated_at', '>=', $since)->pluck('five_note_id'),
            DB::table('notes as n')
                ->join('five_notes as fn', 'fn.note_id', '=', 'n.id')
                ->where('n.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('companies as c')
                ->join('five_notes as fn', 'fn.company_id', '=', 'c.id')
                ->where('c.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('companies as c')
                ->join('productions as p', 'p.company_id', '=', 'c.id')
                ->join('five_notes as fn', 'fn.note_id', '=', 'p.note_id')
                ->where('c.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('productions as p')
                ->join('five_notes as fn', 'fn.note_id', '=', 'p.note_id')
                ->where('p.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('users as u')
                ->join('productions as p', 'p.user_id', '=', 'u.id')
                ->join('five_notes as fn', 'fn.note_id', '=', 'p.note_id')
                ->where('u.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('orders as o')
                ->join('five_notes as fn', 'fn.note_id', '=', 'o.note_id')
                ->where('o.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('work_reports as wr')
                ->join('five_notes as fn', 'fn.note_id', '=', 'wr.note_id')
                ->where('wr.updated_at', '>=', $since)
                ->pluck('fn.id'),
            DB::table('order_work_report as owr')
                ->join('work_reports as wr', 'wr.id', '=', 'owr.work_report_id')
                ->join('five_notes as fn', 'fn.note_id', '=', 'wr.note_id')
                ->where('owr.updated_at', '>=', $since)
                ->pluck('fn.id'),
            // O tempo de espera muda mesmo sem alteracao na origem.
            DB::table('five_notes')->where('is_archived', false)->pluck('id'),
        ];

        if (DB::table('services')->where('updated_at', '>=', $since)->exists()) {
            $collections[] = DB::table('five_notes')->pluck('id');
        }

        return collect($collections)
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function loadChunkContext(Collection $fiveNotes): array
    {
        $fiveIds = $fiveNotes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $noteIds = $fiveNotes->pluck('note_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();

        $timeline = DB::table('timeline_events')
            ->whereIn('five_note_id', $fiveIds)
            ->select([
                'five_note_id',
                DB::raw("MAX(CASE WHEN event_type = 'd5_archived' THEN occurred_at END) as archived_at"),
                DB::raw("MAX(CASE WHEN event_type = 'd5_returned_with_pending' THEN occurred_at END) as pending_return_at"),
                DB::raw("SUM(CASE WHEN event_type = 'd5_returned_with_pending' THEN 1 ELSE 0 END) as pending_return_count"),
                DB::raw("MAX(CASE WHEN event_type = 'd5_created_from_supervision' THEN 1 ELSE 0 END) as created_from_supervision"),
                DB::raw('MAX(updated_at) as timeline_updated_at'),
            ])
            ->groupBy('five_note_id')
            ->get()
            ->keyBy('five_note_id');

        $orders = $this->loadOrderNumbers($noteIds);

        $productions = DB::table('productions as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('companies as c', 'c.id', '=', 'p.company_id')
            ->whereIn('p.note_id', $noteIds)
            ->whereIn('p.service_id', array_filter([
                $this->fiscalizationServiceId,
                $this->paymentServiceId,
            ]))
            ->select([
                'p.id',
                'p.note_id',
                'p.service_id',
                'p.user_id',
                'p.company_id',
                'p.created_at',
                'p.att_at',
                'p.completed',
                'p.completed_at',
                'p.status',
                'u.name as user_name',
                'c.name as user_company_name',
            ])
            ->get()
            ->groupBy('note_id');

        return compact('timeline', 'orders', 'productions');
    }

    private function loadOrderNumbers(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $workReportOrders = DB::table('work_reports as wr')
            ->join('order_work_report as owr', 'owr.work_report_id', '=', 'wr.id')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->whereIn('wr.note_id', $noteIds)
            ->where('wr.canceled', false)
            ->select(['wr.note_id', 'o.ordem'])
            ->orderBy('o.ordem')
            ->get()
            ->groupBy('note_id')
            ->map(fn (Collection $rows) => $rows->first()->ordem)
            ->all();

        $directOrders = DB::table('orders')
            ->whereIn('note_id', $noteIds)
            ->select(['note_id', 'ordem'])
            ->orderBy('ordem')
            ->get()
            ->groupBy('note_id')
            ->map(fn (Collection $rows) => $rows->first()->ordem)
            ->all();

        $result = [];
        foreach ($noteIds as $noteId) {
            $result[$noteId] = $workReportOrders[$noteId] ?? $directOrders[$noteId] ?? null;
        }

        return $result;
    }

    private function mapRow(object $five, array $context): array
    {
        $timeline = $context['timeline']->get($five->id);
        $activity = $this->activity($five);
        $assignee = $this->assignee(
            $five,
            $activity['key'],
            $context['productions']->get($five->note_id, collect())
        );
        $waitingSince = $this->waitingSince($five, $activity['key']);
        $waitingDays = $waitingSince
            ? (int) Carbon::parse($waitingSince)->startOfDay()->diffInDays(now()->startOfDay())
            : null;
        $eligible = (bool) $five->visible_partner
            || ($this->isBlank($five->note_d5) && (bool) ($timeline->created_from_supervision ?? false));

        return $this->truncateRow([
            'id_local' => (int) $five->id,
            'note_id_local' => (int) $five->note_id,
            'note_d5' => $five->note_d5,
            'note_number' => $five->note_number,
            'order_number' => $context['orders'][$five->note_id] ?? null,
            'rubrica' => $five->rubrica,
            'company_id_local' => $five->company_id,
            'company_name' => $five->company_name,
            'pep' => $five->pep,
            'e_pep' => $five->e_pep,
            'location' => $five->loc_install,
            'conjunto' => $five->conjunto,
            'reason' => $five->reason,
            'codify' => $five->codify,
            'symptoms' => $five->sintoms,
            'description' => $five->description,
            'd5_created_at' => $five->created_at,
            'dispatched_at' => $five->dispatch_at,
            'partner_returned_at' => $five->completed_at,
            'fiscalization_completed_at' => $five->supervisioned_at,
            'payment_completed_at' => $five->payed_at,
            'archived_at' => $timeline->archived_at ?? null,
            'pending_return_at' => $timeline->pending_return_at ?? null,
            'pending_return_count' => (int) ($timeline->pending_return_count ?? 0),
            'phase_key' => $activity['phase_key'],
            'phase_label' => $activity['phase_label'],
            'status_label' => $activity['status_label'],
            'responsible_user_id' => $assignee['user_id'],
            'responsible_name' => $assignee['name'],
            'responsible_company' => $assignee['company'],
            'assignment_status' => $assignee['status'],
            'waiting_since_at' => $waitingSince,
            'waiting_days' => $waitingDays,
            'deadline_status' => $this->deadlineStatus($waitingDays),
            'visible_partner' => (bool) $five->visible_partner,
            'is_completed' => (bool) $five->is_completed,
            'is_supervisioned' => (bool) $five->is_supervisioned,
            'is_payed' => (bool) $five->is_payed,
            'is_archived' => (bool) $five->is_archived,
            'is_passive' => (bool) $five->isPassive,
            'returned' => (bool) $five->returned,
            'is_report_eligible' => $eligible,
            'active' => $eligible,
            'source_created_at' => $five->created_at,
            'source_updated_at' => $five->updated_at,
            'timeline_updated_at' => $timeline->timeline_updated_at ?? null,
            'synced_at' => now(),
        ]);
    }

    private function activity(object $five): array
    {
        if ((bool) $five->is_archived) {
            return [
                'key' => 'finalizado',
                'phase_key' => 'finalizado',
                'phase_label' => 'Finalizado',
                'status_label' => 'Finalizado',
            ];
        }

        if ($this->isBlank($five->note_d5) && !(bool) $five->visible_partner) {
            return [
                'key' => 'aguardando_geracao_d5',
                'phase_key' => 'pagamento',
                'phase_label' => 'Pagamento',
                'status_label' => 'Aguardando Geracao de D5',
            ];
        }

        if ((bool) $five->is_supervisioned) {
            return [
                'key' => 'aguardando_pagamento',
                'phase_key' => 'pagamento',
                'phase_label' => 'Pagamento',
                'status_label' => 'Aguardando Pagamento',
            ];
        }

        if ((bool) $five->is_completed) {
            return [
                'key' => 'aguardando_fiscalizacao',
                'phase_key' => 'fiscalizacao',
                'phase_label' => 'Fiscalizacao',
                'status_label' => 'Aguardando Fiscalizacao',
            ];
        }

        return [
            'key' => 'aguardando_fornecedor',
            'phase_key' => 'fornecedor',
            'phase_label' => 'Fornecedor',
            'status_label' => 'Aguardando Fornecedor',
        ];
    }

    private function assignee(object $five, string $activityKey, Collection $productions): array
    {
        $empty = [
            'user_id' => null,
            'name' => null,
            'company' => null,
            'status' => 'Nao Atribuido',
        ];

        if (!in_array($activityKey, [
            'aguardando_fiscalizacao',
            'aguardando_pagamento',
            'aguardando_geracao_d5',
        ], true)) {
            return $empty;
        }

        $serviceId = $activityKey === 'aguardando_fiscalizacao'
            ? $this->fiscalizationServiceId
            : $this->paymentServiceId;

        $forService = $productions->where('service_id', $serviceId);
        $open = $forService->where('completed', false);
        $partnerReturnAt = $five->completed_at ? Carbon::parse($five->completed_at) : null;
        $strictPartnerWindow = $activityKey === 'aguardando_fiscalizacao' && $partnerReturnAt;

        if ($partnerReturnAt) {
            $open = $open->filter(function ($production) use ($partnerReturnAt) {
                $mark = $production->att_at ?? $production->created_at;
                return $mark && Carbon::parse($mark)->greaterThanOrEqualTo($partnerReturnAt);
            });
        }

        $candidate = $this->latestProduction($open->whereNotNull('user_id'))
            ?? $this->latestProduction($open);

        if ($strictPartnerWindow && !$candidate) {
            return $empty;
        }

        $candidate ??= $this->latestProduction($forService->whereNotNull('user_id'));
        $candidate ??= $this->latestProduction($forService->where('completed', false));

        if (!$candidate) {
            return $empty;
        }

        return [
            'user_id' => $candidate->user_id,
            'name' => $candidate->user_name,
            'company' => $candidate->user_company_name,
            'status' => $this->assignmentStatus($candidate->status),
        ];
    }

    private function latestProduction(Collection $productions): ?object
    {
        return $productions
            ->sortByDesc(fn ($production) => $production->att_at ?? $production->created_at ?? $production->id)
            ->first();
    }

    private function waitingSince(object $five, string $activityKey)
    {
        return match ($activityKey) {
            'aguardando_pagamento' => $five->supervisioned_at,
            'aguardando_fiscalizacao' => $five->completed_at,
            'aguardando_geracao_d5' => $five->created_at,
            'aguardando_fornecedor' => $five->dispatch_at,
            default => null,
        };
    }

    private function deadlineStatus(?int $waitingDays): ?string
    {
        if ($waitingDays === null) {
            return null;
        }

        if ($waitingDays > 5) {
            return 'Atrasado';
        }

        if ($waitingDays > 3) {
            return 'Atencao';
        }

        return 'No prazo';
    }

    private function assignmentStatus($status): string
    {
        try {
            return Notestatus::status(is_null($status) ? 1 : (int) $status)->status ?? 'Nao Atribuido';
        } catch (\Throwable) {
            return 'Nao Atribuido';
        }
    }

    private function resolveServiceIds(): void
    {
        $this->fiscalizationServiceId = DB::table('services')
            ->whereIn('service', ['Fiscalizacao', 'Fiscalização'])
            ->value('uuid');

        $this->paymentServiceId = DB::table('services')
            ->where('service', 'Pagamento')
            ->value('uuid');
    }

    private function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function safeBatchSize(int $columnsPerRow): int
    {
        if ($columnsPerRow <= 0) {
            return 1;
        }

        return max(1, (int) floor(
            (self::SQLSERVER_BIND_LIMIT - self::SQLSERVER_BIND_BUFFER) / $columnsPerRow
        ));
    }

    private function upsertColumns(): array
    {
        return [
            'note_id_local',
            'note_d5',
            'note_number',
            'order_number',
            'rubrica',
            'company_id_local',
            'company_name',
            'pep',
            'e_pep',
            'location',
            'conjunto',
            'reason',
            'codify',
            'symptoms',
            'description',
            'd5_created_at',
            'dispatched_at',
            'partner_returned_at',
            'fiscalization_completed_at',
            'payment_completed_at',
            'archived_at',
            'pending_return_at',
            'pending_return_count',
            'phase_key',
            'phase_label',
            'status_label',
            'responsible_user_id',
            'responsible_name',
            'responsible_company',
            'assignment_status',
            'waiting_since_at',
            'waiting_days',
            'deadline_status',
            'visible_partner',
            'is_completed',
            'is_supervisioned',
            'is_payed',
            'is_archived',
            'is_passive',
            'returned',
            'is_report_eligible',
            'active',
            'source_created_at',
            'source_updated_at',
            'timeline_updated_at',
            'synced_at',
        ];
    }

    private function deactivateDeletedRows(): void
    {
        $sourceIds = DB::table('five_notes')
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true]);

        $deletedIds = LogFiveNotesReport::query()
            ->where('active', true)
            ->pluck('id_local')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => isset($sourceIds[$id]))
            ->values();

        foreach ($deletedIds->chunk(500) as $ids) {
            LogFiveNotesReport::query()
                ->whereIn('id_local', $ids->all())
                ->update([
                    'is_report_eligible' => false,
                    'active' => false,
                    'synced_at' => now(),
                ]);
        }
    }

    private function loadColumnLimits(): void
    {
        try {
            $rows = DB::connection('sqlsrv2')
                ->table('INFORMATION_SCHEMA.COLUMNS')
                ->select([
                    'COLUMN_NAME as column',
                    'CHARACTER_MAXIMUM_LENGTH as max_length',
                ])
                ->where('TABLE_SCHEMA', 'dbo')
                ->where('TABLE_NAME', 'log_five_notes_report_sync')
                ->get();

            foreach ($rows as $row) {
                $limit = (int) ($row->max_length ?? 0);
                if ($limit > 0) {
                    $this->columnLimits[(string) $row->column] = $limit;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel carregar metadados da tabela D5 no SQL Server.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function truncateRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (!isset($this->columnLimits[$key]) || !is_string($value)) {
                continue;
            }

            $limit = $this->columnLimits[$key];
            if ($limit > 0 && mb_strlen($value) > $limit) {
                $row[$key] = mb_substr($value, 0, $limit);
            }
        }

        return $row;
    }
}
