<?php

namespace App\Console\Commands\Tools;

use App\Enum\ProtestJobPriority;
use App\Enum\ProtestJobStatus;
use App\Models\MedProtest;
use App\Models\ProtestJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class migrate_to_protestjob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:migrate_to_protestjob {--chunk=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = max(25, (int) $this->option('chunk'));

        $this->info("Iniciando conversão (chunk {$chunkSize})...");

        $processed   = 0;
        $created     = 0;
        $skipped     = 0;
        $errors      = 0;
        $expectedMed = [];

        MedProtest::query()
            ->whereHas('Assignments', fn ($q) => $q->where('responsible', true))
            ->whereHas('Assignments', fn ($q) => $q->where('user', true))
            ->with([
                'Protest',
                'Comments' => fn ($q) => $q->orderBy('created_at'),
                'Assignments' => function ($q) {
                    $q->where(function ($inner) {
                        $inner->where('responsible', true)
                              ->orWhere('user', true);
                    })
                    ->with('user')
                    ->orderBy('created_at');
                },
                'ProtestJobs',
            ])
            ->orderBy('id')
            ->chunkById($chunkSize, function ($medProtests) use (&$processed, &$created, &$skipped, &$errors) {
                foreach ($medProtests as $medProtest) {
                    $processed++;

                    if ($medProtest->ProtestJobs->isNotEmpty()) {
                        $skipped++;
                        continue;
                    }

                    $assignments = $medProtest->Assignments
                        ->sortByDesc(fn ($assignment) => $assignment->created_at ?? $assignment->id);

                    $responsibleAssignment = $assignments->firstWhere('responsible', true);
                    $userAssignment        = $assignments->firstWhere('user', true);

                    if (!$responsibleAssignment || !$userAssignment) {
                        $skipped++;
                        continue;
                    }

                    if (!$responsibleAssignment->user_id || !$userAssignment->user_id) {
                        $skipped++;
                        continue;
                    }

                    if (!$medProtest->protest_id && !$medProtest->Protest) {
                        $skipped++;
                        continue;
                    }

                    $ownerUser          = $userAssignment->user;
                    $assignmentCreated  = $userAssignment->created_at ?? now();
                    $finishedAt         = $userAssignment->ended_at;
                    $slaDueAt           = $userAssignment->due_at ?? $responsibleAssignment->due_at;
                    $status             = $userAssignment->completed ? ProtestJobStatus::DONE->value : ($userAssignment->started_at ? ProtestJobStatus::IN_PROGRESS->value : ProtestJobStatus::ASSIGNED->value);
                    $firstComment       = $medProtest->Comments->first();
                    $notes              = $firstComment?->message;

                    $expectedMed[] = $medProtest->id;

                    try {
                        DB::transaction(function () use (
                            $medProtest,
                            $responsibleAssignment,
                            $userAssignment,
                            $ownerUser,
                            $assignmentCreated,
                            $finishedAt,
                            $slaDueAt,
                            $status,
                            $notes
                        ) {
                            ProtestJob::create([
                                'protest_id'     => $medProtest->protest_id ?? $medProtest->Protest?->id,
                                'med_protest_id' => $medProtest->id,
                                'created_by'     => $responsibleAssignment->user_id,
                                'owner_id'       => $userAssignment->user_id,
                                'priority'       => ProtestJobPriority::NORMAL->value,
                                'status'         => $status,
                                'sent_at'        => $assignmentCreated,
                                'started_at'     => $userAssignment->started_at ?? null,
                                'accepted_at'    => $userAssignment->started_at ?? $assignmentCreated,
                                'finished_at'    => $finishedAt,
                                'closed_at'      => $userAssignment->completed ? $finishedAt : null,
                                'closed_by'      => $userAssignment->completed ? $userAssignment->user_id : null,
                                'sla_due_at'     => $slaDueAt,
                                'notes'          => $notes,
                                'need_evidence'  => (bool) ($medProtest->needsConfirmation ?? false),
                                'is_advance'     => (bool) optional($ownerUser)->onlyparner,
                                'confirmed'      => (bool) $userAssignment->completed,
                                'confirmed_at'   => $userAssignment->completed ? $finishedAt : null,
                                'close_reason'   => null,
                            ]);
                        });

                        $created++;
                    } catch (Throwable $e) {
                        $errors++;
                        $skipped++;
                        Log::error('Erro convertendo MedProtest para ProtestJob', [
                            'med_protest_id' => $medProtest->id,
                            'error'          => $e->getMessage(),
                        ]);
                        $this->error("Falha ao converter MedProtest {$medProtest->id}: {$e->getMessage()}");
                    }
                }
            });

        $expectedMed = array_values(array_unique($expectedMed));
        $expectedTotal = count($expectedMed);
        $insertedTotal = 0;
        $missingIds    = [];

        if ($expectedTotal > 0) {
            $insertedIds = ProtestJob::query()
                ->whereIn('med_protest_id', $expectedMed)
                ->pluck('med_protest_id')
                ->all();

            $insertedTotal = count($insertedIds);
            $missingIds    = array_values(array_diff($expectedMed, $insertedIds));
        }

        $this->info("Processados: {$processed} | Planejados: {$expectedTotal} | Criados: {$created} | Confirmados no banco: {$insertedTotal} | Ignorados: {$skipped} | Erros: {$errors}");

        if (!empty($missingIds)) {
            $this->warn('Os seguintes med_protest_id não geraram ProtestJob (verifique logs/dados): ' . implode(', ', $missingIds));
        }



        $jobsProcessed      = 0;
        $protestResumeLinks = 0;
        $jobNotesFilled     = 0;

        ProtestJob::query()
            ->with([
                'protest.Comments' => fn ($q) => $q->orderBy('created_at'),
                // 'medProtest.TechnicalReport',
            ])
            ->chunkById($chunkSize, function ($protestJobs) use (&$jobsProcessed, &$protestResumeLinks, &$jobNotesFilled) {
                foreach ($protestJobs as $protestJob) {
                    $jobsProcessed++;

                    $protest = $protestJob->protest;
                    if ($protest) {
                        $firstComment = $protest->Comments->first();
                        $commentMessage = $firstComment ? trim((string) $firstComment->message) : '';

                        if ($commentMessage !== '' && blank($protest->resume)) {
                            $protest->resume = $commentMessage;
                            $protest->save();
                            $protestResumeLinks++;
                        }
                    }

                    $technicalReportContent = $protestJob->medProtest?->TechnicalReport?->content;
                    if (!blank($technicalReportContent) && blank($protestJob->close_reason)) {
                        $protestJob->close_reason = $technicalReportContent;
                        $protestJob->save();
                        $jobNotesFilled++;
                    }
                }
            });

        $this->info("Jobs avaliados: {$jobsProcessed} | Resumos preenchidos: {$protestResumeLinks} | Notes preenchidas via Relatorios Tecnicos: {$jobNotesFilled}");

        return $errors === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
