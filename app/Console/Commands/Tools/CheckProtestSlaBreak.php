<?php

namespace App\Console\Commands\Tools;

use App\Models\ProtestJob;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckProtestSlaBreak extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:check_protest_jobs_sla {--window=7 : Janela de tolerância (minutos)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica os vencimentos de SLA de protestos e gera as devidas ocorrências.';


    private const MARK_1D   = 'minus_1d';
    private const MARK_1H   = 'minus_1h';
    private const MARK_30M  = 'minus_30m';
    private const MARK_BRCH = 'breached';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $window = (int) $this->option('window');
        $now    = CarbonImmutable::now();

        $this->info("Verificando SLAs ({$window}min window)…");

        // ========= PASSO A: AVISOS (somente futuros dentro de +1 dia) =========
        ProtestJob::query()
            ->open()
            ->withSla()
            ->whereBetween('sla_due_at', [$now, $now->addDay()]) // apenas futuros próximos
            ->chunkById(500, function ($jobs) use ($now, $window) {
                foreach ($jobs as $job) {
                    /** @var ProtestJob $job */
                    $due = $job->sla_due_at?->copy();
                    if (!$due) {
                        continue;
                    }

                    $minsToDue = $now->diffInMinutes($due, false); // sempre >= 0 aqui

                    $this->tryWarn($job, $minsToDue, 24 * 60, self::MARK_1D, 'SLA vence em 1 dia', 'Faltam ~24h para o prazo do job #'.$job->id);
                    $this->tryWarn($job, $minsToDue, 60, self::MARK_1H, 'SLA vence em 1 hora', 'Falta ~1h para o prazo do job #'.$job->id);
                    $this->tryWarn($job, $minsToDue, 30, self::MARK_30M, 'SLA vence em 30 min', 'Faltam ~30min para o prazo do job #'.$job->id);
                }
            });

        // ========= PASSO B: ESTOURO (todos que já venceram, sem limitar por dia) =========
        ProtestJob::query()
            ->open()
            ->withSla()
            ->whereNull('sla_breached_at')
            ->where('sla_due_at', '<', $now) // QUALQUER atrasado, independe de "dia"
            ->chunkById(500, function ($jobs) {
                foreach ($jobs as $job) {
                    /** @var ProtestJob $job */
                    // idempotente: só marca se ainda não marcado
                    $job->breachSla('Prazo ultrapassado');
                    $this->notifyBreached($job);
                    $this->line(" • job {$job->id}: SLA estourado (marcado)");
                }
            });

        $this->info('Concluído.');
        return self::SUCCESS;
    }

    /**
     * Dispara aviso próximo do marco ($targetMinutes) com tolerância $window.
     */
    private function tryWarn(ProtestJob $job, int $minsToDue, int $targetMinutes, string $code, string $title, string $info): void
    {
        $window = (int) $this->option('window');

        // Envia quando a diferença está dentro da janela (ex.: 60±7)
        // i.e., minsToDue ∈ [targetMinutes - window, targetMinutes]
        if ($minsToDue <= $targetMinutes && $minsToDue >= ($targetMinutes - $window)) {

            if (!$job->alreadyWarned($code)) {
                DB::transaction(function () use ($job, $code, $title, $info) {
                    // loga evento (idempotência por evento)
                    $job->logSlaWarning($code, [
                        'due_at' => optional($job->sla_due_at)?->toIso8601String(),
                    ]);

                    // notifica (ajuste para seu sistema de notificações)
                    $this->notify($job, $title, $info);
                });

                $this->line(" • job {$job->id}: aviso {$code} enviado");
            }
        }
    }

    private function notifyBreached(ProtestJob $job): void
    {
        $title = 'SLA estourado';
        $info  = 'O prazo do job #'.$job->id.' foi ultrapassado.';

        $this->notify($job, $title, $info, breached: true);
    }

    /**
     * Central de notificação: usa sua tabela de notificações (user_id, title, info, link, status, readed).
     * Ajuste o nome da tabela/Model conforme seu projeto.
     */
    private function notify(ProtestJob $job, string $title, string $info, bool $breached = false): void
    {
        // $link = route('protest.jobs.show', $job->id);

        $link = null; // ajuste conforme necessário

        // Defina quem deve ser notificado (owner, criador, gestor etc.)
        $targets = array_filter([
            $job->owner_id,
            $job->created_by,
        ]);

        $users = User::query()->whereIn('id', $targets)->get();

        foreach ($users as $user) {
            $user->notify(new SystemNotification(
                titulo: $title,
                mensagem: $info,
                link: $link,
                status: 8,
                extras: [
                    'job_id'     => $job->id,
                    'protest_id' => $job->protest_id,
                    'due_at'     => optional($job->sla_due_at)?->toIso8601String(),
                    'breached'   => $breached,
                    // você pode incluir também o "code" do marco quando for pré-aviso:
                    // 'code' => 'minus_1h' | 'minus_30m' | ...
                ],
            ));
        }
    }
}
