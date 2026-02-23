<?php

namespace App\Console\Commands\Ads;

use App\Custom\RegistroJson;
use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\AdsRequestDefaultUser;
use App\Models\Adsform;
use App\Models\Edp_depc\BaseCosts;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class GenerateTacitAds extends Command
{
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

            // Regra: 6 dias corridos; às 00:00 do 7º dia já está vencido
            $startAt = Carbon::parse('2026-02-01 00:00:00');
            $cutoff  = now()->subDays(6)->startOfDay(); // tudo criado até aqui já está vencido na virada

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
                ->whereHas('note.orders', function ($orderQuery) {
                    $orderQuery->where('statusSist', 'like', 'ABER%')
                        ->orWhere('statusSist', 'like', 'LIB%');
                })
                ->whereDoesntHave('adsform');

            $adsCreated = 0;
            $requestsCreated = 0;
            $sqlMirrored = 0;
            $sqlMirrorFailures = 0;
            $skippedNoUser = 0;
            $skippedNoCompany = 0;
            $candidates = 0;
            $orderCostCache = [];

            $total = (clone $query)->count();
            $this->info("WorkReports elegíveis: {$total}");

            $bar = new ProgressBar($this->output, $total);
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
                $defaultRecipients,
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

                    // Regra do prazo: 00:00 do 7º dia (criado no dia D => vence D+6 às 00:00)
                    $dueAt = Carbon::parse($workReport->created_at)
                        ->startOfDay()
                        ->addDays(6);

                    // --- DRY RUN: simula contagens e mostra amostras, mas não grava nada ---
                    if ($dryRun) {
                        $adsCreated++; // simulado
                        $requestsCreated += $defaultRecipients->count(); // simulado
                        // espelhamento não roda em dry
                        // Exibe algumas amostras pra conferir se o filtro tá correto
                        if ($candidates <= 10) {
                            $this->line("SIMULADO WR#{$workReport->id} note_id={$workReport->note_id} created_at={$workReport->created_at} due_at={$dueAt}");
                        }
                        continue;
                    }

                    $batchId = (string) Str::uuid();
                    $createdRequests = [];
                    $orderCostsByOrderId = $this->resolveOrderCostsForWorkReport($workReport->id, $orderCostCache);

                    DB::transaction(function () use (
                        $workReport,
                        $userId,
                        $dueAt,
                        $batchId,
                        $defaultRecipients,
                        &$adsCreated,
                        &$requestsCreated,
                        &$createdRequests,
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

                        // Corrige version: calcula uma vez e incrementa por destinatário
                        $version = (int) AdsRequest::query()
                            ->where('note_id', $workReport->note_id)
                            ->max('version');

                        foreach ($defaultRecipients as $recipientUserId) {
                            $version++;

                            $request = AdsRequest::query()->create([
                                'requested_by' => $recipientUserId,
                                'company_id' => $workReport->company_id,
                                'note_id' => $workReport->note_id,
                                'batch_id' => $batchId,
                                'partner' => true,
                                'completed' => false,
                                'status' => AdsRequestStatus::QUEUED,
                                'version' => $version,
                                'description' => 'Solicitação automática gerada por ADS tácita.',
                            ]);

                            $createdRequests[] = $request;
                            $requestsCreated++;
                        }
                    });

                    if (!$testMode) {
                        foreach ($createdRequests as $request) {
                            if ($this->mirrorToSqlServer($request, (string) $workReport->note?->note)) {
                                $sqlMirrored++;
                            } else {
                                $sqlMirrorFailures++;
                            }
                        }
                    }
                }
            });

            $bar->finish();
            $this->newLine();

            if ($defaultRecipients->isEmpty()) {
                $this->warn('Nenhum destinatário padrão configurado em ADS automática. Apenas ADS tácita seria criada.');
            }

            $this->info("Candidatos processados: {$candidates}");
            $this->info("ADS tácitas " . ($dryRun ? 'SIMULADAS' : 'criadas') . ": {$adsCreated}");
            $this->info("Solicitações ADS " . ($dryRun ? 'SIMULADAS' : 'criadas') . ": {$requestsCreated}");
            $this->info("Pulos (sem user): {$skippedNoUser}");
            $this->info("Pulos (sem company): {$skippedNoCompany}");

            if ($dryRun) {
                $this->warn('DRY RUN: espelhamento no SQL Server não foi executado.');
            } else {
                if ($testMode) {
                    $this->warn('MODO TESTE ATIVO: espelhamento no SQL Server está desabilitado por configuração.');
                } else {
                    $this->info("Solicitações espelhadas no SQL Server: {$sqlMirrored}");
                    $this->info("Falhas de espelhamento no SQL Server: {$sqlMirrorFailures}");
                }
            }

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
}
