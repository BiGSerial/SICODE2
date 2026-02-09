<?php

namespace App\Console\Commands\Ads;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\AdsRequestDefaultUser;
use App\Models\Adsform;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTacitAds extends Command
{
    protected $signature = 'ads:generate-tacit';

    protected $description = 'Cria ADS tácita automaticamente para WorkReports vencidos sem ADS.';

    public function handle(): int
    {
        $startAt = Carbon::parse('2026-02-01 00:00:00');
        $cutoff = now()->subDays(6)->startOfDay();
        $testMode = SystemSetting::getBool('ads_auto_test_mode', false);
        $defaultRecipients = AdsRequestDefaultUser::query()
            ->where('active', true)
            ->pluck('user_id')
            ->filter()
            ->values();

        $query = WorkReport::query()
            ->where('rejected', false)
            ->where('created_at', '>=', $startAt)
            ->where('created_at', '<=', $cutoff)
            ->whereHas('Note.Orders', function ($orderQuery) {
                $orderQuery->where('statusSist', 'like', 'ABER%');
            })
            ->whereDoesntHave('Adsform');

        $adsCreated = 0;
        $requestsCreated = 0;
        $sqlMirrored = 0;
        $sqlMirrorFailures = 0;

        $query->chunkById(200, function ($workReports) use (&$adsCreated, &$requestsCreated, &$sqlMirrored, &$sqlMirrorFailures, $defaultRecipients, $testMode) {
            foreach ($workReports as $workReport) {
                $userId = $workReport->user_id ?: User::query()->value('id');
                if (!$userId) {
                    $this->warn("WorkReport {$workReport->id} sem user_id e nenhum usuário disponível.");
                    continue;
                }

                if (!$workReport->company_id) {
                    $this->warn("WorkReport {$workReport->id} sem company_id. ADS tácita não processada.");
                    continue;
                }

                $dueAt = Carbon::parse($workReport->created_at)->endOfDay()->addDays(6);
                $batchId = (string) Str::uuid();
                $createdRequests = [];

                DB::transaction(function () use ($workReport, $userId, $dueAt, $batchId, $defaultRecipients, &$adsCreated, &$requestsCreated, &$createdRequests) {
                    if ($workReport->Adsform()->exists()) {
                        return;
                    }

                    Adsform::create([
                        'work_report_id' => $workReport->id,
                        'note_id' => $workReport->note_id,
                        'user_id' => $userId,
                        'name' => null,
                        'obs' => 'ADS tácita criada automaticamente pelo sistema.',
                        'contract' => null,
                        'center' => null,
                        'deposit' => null,
                        'amount' => 0.00,
                        'partial' => false,
                        'tacit' => true,
                        'tacit_due_at' => $dueAt,
                        'tacit_delivered_at' => null,
                    ]);

                    $adsCreated++;

                    foreach ($defaultRecipients as $recipientUserId) {
                        $version = (int) AdsRequest::query()
                            ->where('note_id', $workReport->note_id)
                            ->max('version');

                        $request = AdsRequest::query()->create([
                            'requested_by' => $recipientUserId,
                            'company_id' => $workReport->company_id,
                            'note_id' => $workReport->note_id,
                            'batch_id' => $batchId,
                            'partner' => true,
                            'completed' => false,
                            'status' => AdsRequestStatus::QUEUED,
                            'version' => $version + 1,
                            'description' => 'Solicitação automática gerada por ADS tácita.',
                        ]);

                        $createdRequests[] = $request;
                        $requestsCreated++;
                    }
                });

                if (!$testMode) {
                    foreach ($createdRequests as $request) {
                        if ($this->mirrorToSqlServer($request, (string) $workReport->Note?->note)) {
                            $sqlMirrored++;
                        } else {
                            $sqlMirrorFailures++;
                        }
                    }
                }
            }
        });

        if ($defaultRecipients->isEmpty()) {
            $this->warn('Nenhum destinatário padrão configurado em ADS automática. Apenas ADS tácita foi criada.');
        }

        $this->info("ADS tácitas criadas: {$adsCreated}");
        $this->info("Solicitações ADS automáticas criadas: {$requestsCreated}");
        if ($testMode) {
            $this->warn('MODO TESTE ATIVO: espelhamento no SQL Server está desabilitado por configuração.');
        } else {
            $this->info("Solicitações espelhadas no SQL Server: {$sqlMirrored}");
            $this->info("Falhas de espelhamento no SQL Server: {$sqlMirrorFailures}");
        }

        return Command::SUCCESS;
    }

    private function mirrorToSqlServer(AdsRequest $request, string $noteNumber): bool
    {
        try {
            $user = $request->requestedBy()->first();
            $company = $request->company()->first();

            DB::connection('sqlsrv2')
                ->table('sicode.dbo.ads_requests')
                ->insert([
                    'sicode_id' => $request->id,
                    'batch_id' => $request->batch_id,
                    'note' => $noteNumber,
                    'company' => $company?->name,
                    'status' => $request->status->value,
                    'attempts' => $request->attempts ?? 0,
                    'partner' => $request->partner ? 1 : 0,
                    'register' => $user?->Registration,
                    'user' => $user?->name,
                    'email' => $user?->email,
                    'description' => $request->description,
                    'completed_at' => $request->completed_at,
                    'created_at' => $request->created_at,
                    'updated_at' => $request->updated_at,
                ]);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
