<?php

namespace App\Console\Commands\Ads;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\AdsRequestDefaultUser;
use App\Models\Adsform;
use App\Models\Edp_depc\BaseCosts;
use App\Models\Production;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkReport;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class GenerateTacitAds extends Command
{
    use ShowsProgress;

    protected $signature = 'ads:generate-tacit {--dry : Simula a execução sem criar/espelhar registros}';

    protected $description = 'Cria ADS tácita automaticamente para WorkReports vencidos sem ADS.';

    public function handle(): int
    {
        $log = null;

        try {
            $dryRun = (bool) $this->option('dry');

            $this->info('Starting GenerateTacitAds...');
            if ($dryRun) {
                $this->warn('DRY RUN ATIVO: nada será gravado (nem ADS, nem AdsRequest, nem espelhamento).');
            }

            // Log no mesmo padrão do seu BaseEP
            $log = new RegistroJson('ads_generate_tacit', $this->options());

            // Regra: prazo de 6 dias a partir do informe (informed_at), vencendo no fim do 6o dia.
            // Ex.: informe em 18/02 14:00 -> vence em 24/02 23:59:59; em 25/02 00:00 ja esta vencido.
            $startAt = Carbon::parse('2026-02-01 00:00:00');
            $tacitOverdueThreshold = now()->subDays(6)->startOfDay();

            $testMode = SystemSetting::getBool('ads_auto_test_mode', false);
            $defaultServiceId = SystemSetting::getValue('ads_auto_default_service_id');

            $defaultRecipients = AdsRequestDefaultUser::query()
                ->where('active', true)
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values();

            $query = WorkReport::query()
                ->where('rejected', false)
                ->where('canceled', false)
                ->whereNotNull('informed_at')
                ->where('informed_at', '>=', $startAt)
                ->where('informed_at', '<', $tacitOverdueThreshold)
                ->whereHas('note.orders', function ($orderQuery) {
                    $orderQuery->where('canceled', false)
                        ->where(function ($statusQuery) {
                            $statusQuery->where('statusSist', 'like', 'ABER%')
                                ->orWhere('statusSist', 'like', 'LIB%');
                        });
                })
                ->whereDoesntHave('adsform')
                ->with(['note:id,note']);

            $adsCreated = 0;
            $requestsCreated = 0;
            $sqlMirrored = 0;
            $sqlMirrorFailures = 0;
            $skippedNoUser = 0;
            $skippedNoCompany = 0;
            $candidates = 0;
            $orderCostCache = [];
            $dryPreviewRows = [];
            $requestsCompletedFromExisting = 0;
            $notifiedDone = 0;

            $total = (clone $query)->count();
            $this->info("WorkReports elegíveis: {$total}");

            $bar = $this->createProgressBar($total);
            $bar->start();

            $query->orderBy('id')->chunkById(200, function (Collection $workReports) use (
                &$adsCreated,
                &$requestsCreated,
                &$sqlMirrored,
                &$sqlMirrorFailures,
                &$skippedNoUser,
                &$skippedNoCompany,
                &$candidates,
                &$orderCostCache,
                &$dryPreviewRows,
                &$requestsCompletedFromExisting,
                &$notifiedDone,
                $defaultRecipients,
                $defaultServiceId,
                $testMode,
                $dryRun,
                $bar
            ) {
                foreach ($workReports as $workReport) {
                    $bar->advance();
                    $candidates++;

                    $userId = $workReport->user_id ?: User::query()->value('id');
                    if (!$userId) {
                        $skippedNoUser++;
                        continue;
                    }

                    if (!$workReport->company_id) {
                        $skippedNoCompany++;
                        continue;
                    }

                    // Regra do prazo: informado no dia D => vence em D+6 às 23:59:59
                    $dueAt = Carbon::parse($workReport->informed_at)
                        ->addDays(6)
                        ->endOfDay();

                    $recipientIds = $this->resolveRecipientsForNote(
                        noteId: (int) $workReport->note_id,
                        serviceId: $defaultServiceId,
                        defaultRecipients: $defaultRecipients
                    );

                    if ($recipientIds->isEmpty()) {
                        continue;
                    }

                    // --- DRY RUN: simula contagens e mostra amostras, mas não grava nada ---
                    if ($dryRun) {
                        $adsCreated++; // simulado
                        $requestsCreated += max(1, $recipientIds->count()); // simulado
                        $dryPreviewRows[] = [
                            'nota' => (string) ($workReport->note?->note ?? $workReport->note_id),
                            'informado_em' => optional($workReport->informed_at)->format('d/m/Y H:i:s'),
                            'venceu_em' => $dueAt->format('d/m/Y H:i:s'),
                            'destinatarios' => (string) $recipientIds->count(),
                        ];
                        continue;
                    }

                    $batchId = (string) Str::uuid();
                    $createdRequests = [];
                    $requestsToMirror = [];
                    $requestsToNotify = [];
                    $orderCostsByOrderId = $this->resolveOrderCostsForWorkReport($workReport->id, $orderCostCache);

                    DB::transaction(function () use (
                        $workReport,
                        $userId,
                        $dueAt,
                        $batchId,
                        $recipientIds,
                        &$adsCreated,
                        &$requestsCreated,
                        &$createdRequests,
                        &$requestsToMirror,
                        &$requestsToNotify,
                        &$requestsCompletedFromExisting,
                        $orderCostsByOrderId
                    ) {
                        if ($workReport->adsform()->exists()) {
                            return;
                        }

                        if (!empty($orderCostsByOrderId)) {
                            foreach ($orderCostsByOrderId as $orderId => $serviceCost) {
                                DB::table('orders')
                                    ->where('id', $orderId)
                                    ->update([
                                        'service_cost' => round((float) $serviceCost, 2),
                                        'updated_at' => now(),
                                    ]);
                            }
                        }

                        $latestRequestWithUrl = AdsRequest::query()
                            ->where('note_id', $workReport->note_id)
                            ->whereNotNull('url')
                            ->whereRaw("NULLIF(LTRIM(RTRIM(url)), '') IS NOT NULL")
                            ->latest('created_at')
                            ->first();

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
                            // Este campo deve ser preenchido apenas no envio manual da ADS
                            // pelo parceiro (fluxo ReceiveAdsfomrm).
                            'tacit_delivered_at' => null,
                        ]);

                        $adsCreated++;

                        // Corrige version: calcula uma vez e incrementa por destinatário
                        $version = (int) AdsRequest::query()
                            ->where('note_id', $workReport->note_id)
                            ->max('version');

                        $existingActive = AdsRequest::query()
                            ->where('note_id', $workReport->note_id)
                            ->where('company_id', $workReport->company_id)
                            ->whereIn('status', [
                                AdsRequestStatus::QUEUED->value,
                                AdsRequestStatus::IN_PROGRESS->value,
                                AdsRequestStatus::RETRY->value,
                            ])
                            ->lockForUpdate()
                            ->exists();

                        if ($existingActive && !$latestRequestWithUrl) {
                            return;
                        }

                        foreach ($recipientIds->filter()->unique()->values() as $recipientUserId) {
                            $version++;

                            if ($latestRequestWithUrl) {
                                $doneAt = $latestRequestWithUrl->completed_at ?? now();

                                $request = AdsRequest::query()->create([
                                    'requested_by' => $recipientUserId,
                                    'company_id' => $workReport->company_id,
                                    'note_id' => $workReport->note_id,
                                    'batch_id' => $batchId,
                                    'partner' => false,
                                    'completed' => true,
                                    'status' => AdsRequestStatus::DONE,
                                    'version' => $version,
                                    'description' => 'Solicitação automática concluída com reaproveitamento de ADS já disponível.',
                                    'url' => $latestRequestWithUrl->url,
                                    'completed_at' => $doneAt,
                                    'delivered_at' => null,
                                ]);

                                $createdRequests[] = $request;
                                $requestsToNotify[] = $request;
                                $requestsCompletedFromExisting++;
                                $requestsCreated++;
                                continue;
                            }

                            $request = AdsRequest::query()->create([
                                'requested_by' => $recipientUserId,
                                'company_id' => $workReport->company_id,
                                'note_id' => $workReport->note_id,
                                'batch_id' => $batchId,
                                'partner' => false,
                                'completed' => false,
                                'status' => AdsRequestStatus::QUEUED,
                                'version' => $version,
                                'description' => 'Solicitação automática gerada por ADS tácita.',
                            ]);

                            $createdRequests[] = $request;
                            $requestsToMirror[] = $request;
                            $requestsCreated++;
                        }
                    });

                    if (!$testMode) {
                        foreach ($requestsToMirror as $request) {
                            if ($this->mirrorToSqlServer($request, (string) $workReport->note?->note)) {
                                $sqlMirrored++;
                            } else {
                                $sqlMirrorFailures++;
                            }
                        }
                    }

                    foreach ($requestsToNotify as $request) {
                        if ($this->notifyDoneRequesterIfNeeded($request)) {
                            $notifiedDone++;
                        }
                    }
                }
            });

            $bar->finish();
            $this->newLine();

            if ($defaultRecipients->isEmpty() && !$defaultServiceId) {
                $this->warn('Nenhum destinatário padrão configurado e nenhum serviço padrão definido para roteamento por produção. Apenas ADS tácita seria criada.');
            }

            $this->info("Candidatos processados: {$candidates}");
            $this->info("ADS tácitas " . ($dryRun ? 'SIMULADAS' : 'criadas') . ": {$adsCreated}");
            $this->info("Solicitações ADS " . ($dryRun ? 'SIMULADAS' : 'criadas') . ": {$requestsCreated}");
            $this->info("Solicitações ADS concluídas com link existente: {$requestsCompletedFromExisting}");
            $this->info("Pulos (sem user): {$skippedNoUser}");
            $this->info("Pulos (sem company): {$skippedNoCompany}");

            if ($dryRun) {
                $this->warn('DRY RUN: espelhamento no SQL Server não foi executado.');
                if (!empty($dryPreviewRows)) {
                    $this->newLine();
                    $this->info('Lista simulada (nota, informado em, venceu em):');
                    $this->table(
                        ['Nota', 'Informado em', 'Venceu em', 'Destinatários'],
                        array_map(fn ($row) => [
                            $row['nota'],
                            $row['informado_em'],
                            $row['venceu_em'],
                            $row['destinatarios'],
                        ], $dryPreviewRows)
                    );
                }
            } else {
                if ($testMode) {
                    $this->warn('MODO TESTE ATIVO: espelhamento no SQL Server está desabilitado por configuração.');
                } else {
                    $this->info("Solicitações espelhadas no SQL Server: {$sqlMirrored}");
                    $this->info("Falhas de espelhamento no SQL Server: {$sqlMirrorFailures}");
                }
            }

            $this->info("Notificações de ADS concluída enviadas: {$notifiedDone}");

            // --- RegistroJson ---
            // Em dry run, ainda registra execução (útil pra auditoria), mas sem "criados" reais.
            // Se você preferir, pode setar 0 em dry.
            $log->setCreated($adsCreated);
            $log->setUpdated($requestsCreated);
            $log->save();

            return Command::SUCCESS;

        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            report($e);
            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @param array<string,float> $orderCostCache
     * @return array<int,float>
     */
    private function resolveOrderCostsForWorkReport(int $workReportId, array &$orderCostCache): array
    {
        $orders = DB::table('order_work_report as owr')
            ->join('orders as o', 'o.id', '=', 'owr.order_id')
            ->where('owr.work_report_id', $workReportId)
            ->where('o.canceled', false)
            ->where('o.statusSist', 'not like', 'CANC%')
            ->where('o.statusSist', 'not like', 'ENT%')
            ->where('o.statusSist', 'not like', 'ENC%')
            ->select('o.id', 'o.ordem')
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $orderNumbers = $orders->pluck('ordem')->filter()->unique()->values()->all();
        $missingOrders = array_values(array_diff($orderNumbers, array_keys($orderCostCache)));

        if (!empty($missingOrders)) {
            $loadedCosts = BaseCosts::query()
                ->whereIn('ordem', $missingOrders)
                ->select('ordem', DB::raw('SUM(qtdNecessaria * preco) as base_cost'))
                ->groupBy('ordem')
                ->pluck('base_cost', 'ordem');

            foreach ($missingOrders as $orderNumber) {
                $orderCostCache[$orderNumber] = round((float) ($loadedCosts[$orderNumber] ?? 0), 2);
            }
        }

        $costsByOrderId = [];
        foreach ($orders as $order) {
            $costsByOrderId[(int) $order->id] = (float) ($orderCostCache[$order->ordem] ?? 0);
        }

        return $costsByOrderId;
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
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function notifyDoneRequesterIfNeeded(AdsRequest $request): bool
    {
        $status = $request->status instanceof AdsRequestStatus ? $request->status->value : (string) $request->status;
        if ($status !== AdsRequestStatus::DONE->value || $request->delivered_at) {
            return false;
        }

        $user = $request->requestedBy()->first();
        if (!$user) {
            return false;
        }

        $noteNumber = $request->note()->value('note') ?? $request->note_id;
        $message = "A ADS da nota <strong>{$noteNumber}</strong> está disponível.";

        $user->notify(new SystemNotification(
            'ADS disponível',
            $message,
            $request->url ?: null,
            4,
            [
                'ads_request_id' => $request->id,
                'note_id' => $request->note_id,
            ]
        ));

        $request->timestamps = false;
        $request->forceFill([
            'delivered_at' => now(),
            'updated_at' => now(),
        ])->save();

        return true;
    }

    private function resolveRecipientsForNote(int $noteId, ?string $serviceId, \Illuminate\Support\Collection $defaultRecipients): \Illuminate\Support\Collection
    {
        if (!$serviceId) {
            return $defaultRecipients;
        }

        $assignedUserId = Production::query()
            ->where('note_id', $noteId)
            ->where('service_id', $serviceId)
            ->where('status', 2)
            ->where('completed', false)
            ->whereNotNull('user_id')
            ->orderByDesc('att_at')
            ->orderByDesc('id')
            ->value('user_id');

        if ($assignedUserId) {
            return collect([$assignedUserId]);
        }

        return $defaultRecipients;
    }
}
