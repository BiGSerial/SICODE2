<?php

namespace App\Console\Commands\Tools;

use App\Models\WorkReportFlowProduction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetrofillWorkReportFlowProductions extends Command
{
    protected $signature = 'sicode:retrofill-work-report-flow-productions
                            {--apply : Persiste os vinculos (padrao = dry-run)}
                            {--note_id= : Processa apenas uma nota por ID local}
                            {--limit=0 : Limite de notas processadas}
                            {--chunk=200 : Tamanho do lote de notas}
                            {--replace-retrofill : Recalcula vinculos criados anteriormente por retrofill}';

    protected $description = 'Retrofill dos vinculos entre informes finais e producoes de Fiscalizacao/Pagamento';

    private array $stageByService = [];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $replaceRetrofill = (bool) $this->option('replace-retrofill');
        $noteId = $this->option('note_id');
        $limit = (int) $this->option('limit');
        $chunk = max(1, (int) $this->option('chunk'));

        $this->resolveStages();

        if (empty($this->stageByService)) {
            $this->error('Servicos Fiscalizacao/Pagamento nao encontrados.');

            return self::FAILURE;
        }

        $query = DB::table('notes as n')
            ->whereExists(function ($exists) {
                $exists->selectRaw('1')
                    ->from('work_reports as wr')
                    ->whereColumn('wr.note_id', 'n.id');
            })
            ->orderBy('n.id')
            ->select(['n.id']);

        if ($noteId) {
            $query->where('n.id', $noteId);
        }

        $stats = [
            'notes' => 0,
            'productions' => 0,
            'linked' => 0,
            'existing_explicit' => 0,
            'without_cycle' => 0,
            'without_date' => 0,
            'created_at_fallback' => 0,
        ];

        $query->chunkById($chunk, function (Collection $notes) use ($apply, $replaceRetrofill, $limit, &$stats) {
            foreach ($notes as $note) {
                if ($limit > 0 && $stats['notes'] >= $limit) {
                    return false;
                }

                $stats['notes']++;
                $this->retrofillNote((int) $note->id, $apply, $replaceRetrofill, $stats);
            }
        }, 'n.id', 'id');

        $this->line('Modo: '.($apply ? 'APPLY' : 'DRY-RUN'));
        $this->line('Notas processadas: '.$stats['notes']);
        $this->line('Producoes analisadas: '.$stats['productions']);
        $this->line('Vinculos '.($apply ? 'gravados' : 'previstos').': '.$stats['linked']);
        $this->line('Etapas ignoradas por vinculo explicito existente: '.$stats['existing_explicit']);
        $this->line('Producoes sem ciclo compativel: '.$stats['without_cycle']);
        $this->line('Producoes sem att_at: '.$stats['without_date']);
        $this->line('Vinculos usando fallback por created_at: '.$stats['created_at_fallback']);

        return self::SUCCESS;
    }

    private function resolveStages(): void
    {
        $services = DB::table('services')
            ->select(['uuid', 'service'])
            ->get();

        foreach ($services as $service) {
            $normalized = $this->normalizeServiceName((string) $service->service);

            if ($normalized === 'fiscalizacao') {
                $this->stageByService[$service->uuid] = WorkReportFlowProduction::STAGE_FISCALIZATION;
            }

            if ($normalized === 'pagamento') {
                $this->stageByService[$service->uuid] = WorkReportFlowProduction::STAGE_PAYMENT;
            }
        }
    }

    private function retrofillNote(int $noteId, bool $apply, bool $replaceRetrofill, array &$stats): void
    {
        $workReports = DB::table('work_reports')
            ->where('note_id', $noteId)
            ->select(['id', 'note_id', 'informed_at', 'created_at', 'canceled'])
            ->orderByRaw('COALESCE(informed_at, created_at)')
            ->orderBy('id')
            ->get();

        if ($workReports->isEmpty()) {
            return;
        }

        $productions = DB::table('productions')
            ->where('note_id', $noteId)
            ->whereIn('service_id', array_keys($this->stageByService))
            ->where(function ($query) {
                $query->whereNull('partial')->orWhere('partial', false);
            })
            ->select([
                'id',
                'note_id',
                'service_id',
                'user_id',
                'company_id',
                'status',
                'att_at',
                'created_at',
                'completed',
                'completed_at',
                'confirmed_at',
            ])
            ->orderBy('att_at')
            ->orderBy('id')
            ->get();

        $links = [];

        foreach ($productions as $production) {
            $stats['productions']++;

            if (!$production->att_at) {
                $stats['without_date']++;
                continue;
            }

            $match = $this->matchWorkReport($production, $workReports);

            if (!$match) {
                $stats['without_cycle']++;
                continue;
            }

            if ($match['source'] === 'created_at') {
                $stats['created_at_fallback']++;
            }

            $stage = $this->stageByService[$production->service_id];
            $key = "{$match['work_report']->id}:{$stage}";

            $links[$key][] = [
                'work_report' => $match['work_report'],
                'production' => $production,
                'stage' => $stage,
                'match_source' => $match['source'],
                'cycle_started_at' => $match['cycle_started_at'],
                'cycle_ended_at' => $match['cycle_ended_at'],
            ];
        }

        foreach ($links as $items) {
            $first = $items[0];

            if ($this->hasExplicitLink((int) $first['work_report']->id, $first['stage'])) {
                $stats['existing_explicit'] += count($items);
                continue;
            }

            usort($items, fn (array $a, array $b) => [
                (string) $a['production']->att_at,
                (int) $a['production']->id,
            ] <=> [
                (string) $b['production']->att_at,
                (int) $b['production']->id,
            ]);

            $stats['linked'] += count($items);

            if (!$apply) {
                continue;
            }

            DB::transaction(function () use ($items, $replaceRetrofill) {
                $workReportId = (int) $items[0]['work_report']->id;
                $stage = $items[0]['stage'];

                if ($replaceRetrofill) {
                    DB::table('work_report_flow_productions')
                        ->where('work_report_id', $workReportId)
                        ->where('stage', $stage)
                        ->where('source', 'retrofill_inference')
                        ->delete();
                }

                DB::table('work_report_flow_productions')
                    ->where('work_report_id', $workReportId)
                    ->where('stage', $stage)
                    ->where('source', 'retrofill_inference')
                    ->update(['is_current' => false, 'updated_at' => now()]);

                $lastIndex = count($items) - 1;

                foreach ($items as $index => $item) {
                    DB::table('work_report_flow_productions')->updateOrInsert(
                        [
                            'work_report_id' => (int) $item['work_report']->id,
                            'production_id' => (int) $item['production']->id,
                            'stage' => $item['stage'],
                        ],
                        [
                            'is_current' => $index === $lastIndex,
                            'linked_at' => $item['production']->att_at,
                            'linked_by' => null,
                            'source' => 'retrofill_inference',
                            'metadata' => json_encode([
                                'rule' => 'production_att_at_inside_work_report_cycle',
                                'match_source' => $item['match_source'],
                                'cycle_started_at' => $item['cycle_started_at'],
                                'cycle_ended_at' => $item['cycle_ended_at'],
                                'production_status' => $item['production']->status,
                                'production_user_id' => $item['production']->user_id,
                            ], JSON_UNESCAPED_UNICODE),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }, 1);
        }
    }

    private function matchWorkReport(object $production, Collection $workReports): ?array
    {
        return $this->matchWorkReportByDateSource($production, $workReports, 'informed_at')
            ?? $this->matchWorkReportByDateSource($production, $workReports, 'created_at');
    }

    private function matchWorkReportByDateSource(object $production, Collection $workReports, string $dateSource): ?array
    {
        $attAt = Carbon::parse($production->att_at);
        $reports = $workReports
            ->filter(fn ($workReport) => !empty($workReport->{$dateSource}))
            ->sortBy([
                [$dateSource, 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        foreach ($reports as $index => $workReport) {
            $start = Carbon::parse($workReport->{$dateSource});
            $next = $reports->get($index + 1);
            $end = $next ? Carbon::parse($next->{$dateSource}) : null;

            if ($attAt->lt($start)) {
                continue;
            }

            if ($end && !$attAt->lt($end)) {
                continue;
            }

            return [
                'work_report' => $workReport,
                'source' => $dateSource,
                'cycle_started_at' => $start->toDateTimeString(),
                'cycle_ended_at' => $end?->toDateTimeString(),
            ];
        }

        return null;
    }

    private function hasExplicitLink(int $workReportId, string $stage): bool
    {
        return DB::table('work_report_flow_productions')
            ->where('work_report_id', $workReportId)
            ->where('stage', $stage)
            ->where('source', '<>', 'retrofill_inference')
            ->exists();
    }

    private function normalizeServiceName(string $service): string
    {
        return Str::lower(trim(Str::ascii($service)));
    }
}
