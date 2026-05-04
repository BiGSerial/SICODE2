<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Services\SqlLog\SyncProtestJobsToSqlServerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncProtestJobsToSqlServer extends Command
{
    use ShowsProgress;

    protected $signature = 'sqllog:sync-protest-jobs
        {--full : Sincroniza todos os registros}
        {--hours=2 : Quantidade de horas para modo incremental}
        {--from= : Data inicial no formato Y-m-d H:i:s}
        {--to= : Data final no formato Y-m-d H:i:s}
        {--chunk=60 : Tamanho do lote por envio}';

    protected $description = 'Sincroniza ProtestJob para SQL Server (protest_jobs_sync).';

    public function handle(SyncProtestJobsToSqlServerService $service): int
    {
        $full = (bool) $this->option('full');
        $hours = (int) $this->option('hours');
        $chunk = (int) $this->option('chunk');
        $fromInput = $this->option('from');
        $toInput = $this->option('to');

        if ($hours < 1) {
            $this->error('O parâmetro --hours deve ser >= 1.');
            return self::FAILURE;
        }

        if ($chunk < 1) {
            $this->error('O parâmetro --chunk deve ser >= 1.');
            return self::FAILURE;
        }

        $from = $this->parseDateOption('from', $fromInput);
        if ($fromInput && !$from) {
            return self::FAILURE;
        }

        $to = $this->parseDateOption('to', $toInput);
        if ($toInput && !$to) {
            return self::FAILURE;
        }

        if ($from && $to && $from->gt($to)) {
            $this->error('O parâmetro --from não pode ser maior que --to.');
            return self::FAILURE;
        }

        $this->line('Iniciando sincronização de ProtestJob para SQL Server...');

        $bar = null;

        $summary = $service->sync(
            full: $full,
            hours: $hours,
            from: $from,
            to: $to,
            chunk: $chunk,
            progress: function (string $event, array $data) use (&$bar): void {
                if ($event === 'init') {
                    $total = (int) ($data['total'] ?? 0);
                    if ($total <= 0) {
                        return;
                    }

                    $bar = $this->createProgressBar($total);
                    $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%');
                    $bar->setBarCharacter('█');
                    $bar->setEmptyBarCharacter('░');
                    $bar->setProgressCharacter('▓');
                    $bar->start();
                    return;
                }

                if ($event === 'advance' && $bar instanceof ProgressBar) {
                    $bar->advance((int) ($data['steps'] ?? 0));
                }
            }
        );

        if ($bar instanceof ProgressBar) {
            $bar->finish();
            $this->newLine(2);
        }

        $interval = $summary['mode'] === 'full'
            ? 'todos os registros'
            : (($summary['from'] ?? '-') . ' até ' . ($summary['to'] ?? 'agora'));

        $this->newLine();
        $this->info('Sincronização concluída.');
        $this->line('Modo: ' . $summary['mode']);
        $this->line('Intervalo: ' . $interval);
        $this->line('Chunk: ' . $summary['chunk']);
        $this->line('Quantidade lida: ' . $summary['read']);
        $this->line('Quantidade inserida: ' . $summary['inserted']);
        $this->line('Quantidade atualizada: ' . $summary['updated']);
        $this->line('Quantidade total sincronizada: ' . $summary['synced']);
        $this->line('Duração total (s): ' . $summary['duration_seconds']);
        $this->line('Metadados de tamanho carregados: ' . ($summary['column_limits_loaded'] ? 'sim' : 'não'));

        return self::SUCCESS;
    }

    private function parseDateOption(string $optionName, mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', (string) $value);
        } catch (\Throwable $e) {
            $this->error("Formato inválido em --{$optionName}. Use: Y-m-d H:i:s");
            return null;
        }
    }
}
