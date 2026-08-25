<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Reclaim;
use App\Models\ScheduleExecutionLog;
use App\Models\SicodeSql\ReclaimLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Reclaims extends Command
{
    use ShowsProgress;

    private const CHUNK_SIZE = 100;
    private const FALLBACK_HOURS = 2;
    private const WATERMARK_LOOKBACK_MINUTES = 5;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:reclaims {--full} {--hours=} {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia para Log os reclaims de retrabalho para o SQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $date = $this->syncSince();
        $totalSteps = Reclaim::when($date, function ($query) use ($date) {
            return $query->where('updated_at', '>=', $date);
        })->count();



        // Configure o ProgressBar
        $bar = $this->createProgressBar($totalSteps);

        // Estilo customizado para uma aparência moderna e elegante
        // $bar->setFormatDefinition('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');

        // Defina o formato customizado
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message%');

        //  Caracteres para um visual mais limpo (opcional, mas recomendado)
        $bar->setBarCharacter('<fg=green>█</>'); // Barra preenchida
        $bar->setEmptyBarCharacter('<fg=red>░</>'); // Barra vazia
        $bar->setProgressCharacter('<fg=green>█</>'); // Caractere de progresso (pode ser o mesmo da barra preenchida)

        // Adicione informações úteis
        $bar->setMessage('Iniciando...'); // Mensagem inicial


        $bar->start();

        Reclaim::when($date, function ($query) use ($date) {
            return $query->where('updated_at', '>=', $date);
        })->with([
            'Production.User.Company',
            'Production.Company',
            'Note',
            'Approvals.User.Company',
            'Viabilities.User.Company',
            'Waiting.User.Company',
            'Service',
        ])->chunkById(self::CHUNK_SIZE, function ($reclaims) use (&$bar) {

            $rows = [];
            $message = '';
            foreach ($reclaims as $reclaim) {

                $origem['origem'] = 'Desconhecida';
                $work = null;

                if ($reclaim->Approvals->isNotEmpty()) {
                    $origem['origem'] = 'Analise Projeto';
                    $work = $reclaim->Approvals->first();
                }

                if ($reclaim->Viabilities->isNotEmpty()) {
                    $origem['origem'] = 'Viabilidade';
                    $work = $reclaim->Viabilities->first();
                }

                if ($reclaim->Waiting) {
                    $origem['origem'] = 'Contratação';
                    $work = $reclaim->Waiting;
                }

                $rows[] = [
                    'reclaim_id' => $reclaim->id,
                    'note' => $reclaim->Note->note,
                    'origin' => $origem['origem'],
                    'service' => $reclaim->Service->service,
                    'category' => $reclaim->category,
                    'emissor' => $work?->User?->name ?? 'Desconhecido',
                    'company_emissor' => $work?->User?->Company?->name ?? 'Desconhecido',
                    'received_at' => $reclaim->created_at,
                    'att_at' => $reclaim->Production ? $reclaim->Production->att_at : '',
                    'completed_at' => $reclaim->completed_at,
                    'user' => $reclaim->Production?->User?->name ?? 'Desconhecido',
                    'company_user' => $reclaim->Production?->Company?->name ?? 'Desconhecido',
                ];

                $message = $reclaim->Note->note." - ".$reclaim->Service->service;
            }

            if ($rows) {
                ReclaimLog::upsert(
                    $rows,
                    ['reclaim_id'],
                    [
                        'note',
                        'origin',
                        'service',
                        'category',
                        'emissor',
                        'company_emissor',
                        'received_at',
                        'att_at',
                        'completed_at',
                        'user',
                        'company_user',
                    ]
                );

                $bar->setMessage($message);
                $bar->advance(count($rows));
            }
        });

        // Mensagem de finalização
        $bar->setMessage('<info>Concluído!</info>'); // Use um estilo para destacar

        $bar->finish();

        // Adicione uma nova linha após a barra de progresso
        $this->output->writeln(''); // Garante que a saída seguinte não fique na mesma linha da barra
    }

    private function syncSince(): ?string
    {
        if ($this->option('full')) {
            return null;
        }

        if ($this->option('days')) {
            return now()->subDays((int) $this->option('days'))->startOfDay()->format('Y-m-d H:i:s');
        }

        if ($this->option('hours')) {
            return now()->subHours(max(1, (int) $this->option('hours')))->format('Y-m-d H:i:s');
        }

        return $this->lastSuccessfulScheduleStart()
            ?? now()->subHours(self::FALLBACK_HOURS)->format('Y-m-d H:i:s');
    }

    private function lastSuccessfulScheduleStart(): ?string
    {
        if (!Schema::hasTable('schedule_execution_logs')) {
            return null;
        }

        $lastRun = ScheduleExecutionLog::query()
            ->where('command', 'like', '%sicode:reclaims%')
            ->where('status', ScheduleExecutionLog::STATUS_DONE)
            ->where(function ($query) {
                $query->where('exit_code', 0)
                    ->orWhereNull('exit_code');
            })
            ->whereNotNull('started_at')
            ->orderByDesc('started_at')
            ->first(['started_at']);

        return $lastRun?->started_at
            ? $lastRun->started_at->copy()->subMinutes(self::WATERMARK_LOOKBACK_MINUTES)->format('Y-m-d H:i:s')
            : null;
    }
}
