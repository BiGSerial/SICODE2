<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /*
        |--------------------------------------------------------------------------
        | Limpeza
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'exports:clear-old', 'exports-clear-old')
            ->dailyAt('00:00');


        /*
        |--------------------------------------------------------------------------
        | Checagens principais
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'sicode:chk_integridade', 'chk-integridade')
            ->cron('5 9-21 * * *');

        $this->scheduleCommand($schedule, 'sicode:wpas_log', 'wpas-log')
            ->cron('30 8-21 * * *');


        /*
        |--------------------------------------------------------------------------
        | Atualizações de base e operações
        |--------------------------------------------------------------------------
        |
        | Mantém a lógica antiga do cron com &&:
        | só executa o próximo comando se o anterior terminar com sucesso.
        |
        */

        $this->scheduleSequentialCommands($schedule, [
            'sicode:upd_baseOrder',
            'sicode:upd_baseOperation',
            'sicode:operation-resp-upd',
        ], 'sync-base-orders-operations')
            ->cron('30 5,8,10,12,14,16,20 * * *');

        $this->scheduleCommand($schedule, 'sicode:upd_baseov --prazos --full', 'upd-baseov-prazos-full')
            ->dailyAt('08:20');

        $this->scheduleCommand($schedule, 'sicode:upd_baseEP', 'upd-base-ep')
            ->cron('20 9-21 * * *');


        /*
        |--------------------------------------------------------------------------
        | Reclamações
        |--------------------------------------------------------------------------
        */

        $this->scheduleSequentialCommands($schedule, [
            'sicode:upd_protest',
            'sicode:upd_protestList',
        ], 'sync-protests')
            ->hourlyAt(10);

        $this->scheduleSequentialCommands($schedule, [
            'sicode:prune-protest-med --force',
            'sicode:check_protest_jobs_sla',
        ], 'prune-protest-and-check-sla')
            ->hourlyAt(15);

        $this->scheduleCommand($schedule, 'sicode:check_protest_jobs_sla', 'check-protest-jobs-sla')
            ->hourlyAt(1);

        $this->scheduleCommand($schedule, 'sicode:sync-log-protest-jobs', 'sync-log-protest-jobs')
            ->hourlyAt(20);


        /*
        |--------------------------------------------------------------------------
        | Atualizações de custos
        |--------------------------------------------------------------------------
        */

        $this->scheduleSequentialCommands($schedule, [
            'sicode:upd_costs_mot',
            'sicode:viab-values',
        ], 'sync-costs-and-viability-values')
            ->cron('10 10,13 * * *');


        /*
        |--------------------------------------------------------------------------
        | Logs horários
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'sicode:transfer_log', 'transfer-log')
            ->hourlyAt(2);

        $this->scheduleCommand($schedule, 'sicode:notestop_log', 'notestop-log')
            ->hourlyAt(3);

        $this->scheduleCommand($schedule, 'sicode:log_viability', 'log-viability')
            ->hourlyAt(4);

        $this->scheduleCommand($schedule, 'sicode:log_inform', 'log-inform')
            ->hourlyAt(5);

        $this->scheduleCommand($schedule, 'sicode:log_rejected_viab', 'log-rejected-viab')
            ->hourlyAt(6);

        $this->scheduleCommand($schedule, 'sicode:users-log', 'users-log')
            ->hourlyAt(7);

        $this->scheduleCommand($schedule, 'sicode:log_InformReturn', 'log-inform-return')
            ->hourlyAt(8);

        $this->scheduleCommand($schedule, 'sicode:reclaims', 'reclaims')
            ->hourlyAt(9);

        $this->scheduleCommand($schedule, 'sicode:informs-ads-log', 'informs-ads-log')
            ->hourlyAt(11);

        $this->scheduleCommand($schedule, 'sicode:log_externalEntities', 'log-external-entities')
            ->cron('15 */3 * * *');

        $this->scheduleCommand($schedule, 'sicode:log_informs_smc', 'log-informs-smc')
            ->hourlyAt(16);

        $this->scheduleCommand($schedule, 'sicode:sync-log-partials-informs --hours=2 --if-empty', 'sync-log-partials-informs')
            ->hourlyAt(17);


        /*
        |--------------------------------------------------------------------------
        | Produção
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'sicode:log_production', 'log-production')
            ->cron('*/10 8-21 * * *');


        /*
        |--------------------------------------------------------------------------
        | Contratação / Status de contratação
        |--------------------------------------------------------------------------
        */

        $this->scheduleSequentialCommands($schedule, [
            'sicode:log_hiring_status',
            'sicode:log_hired_status',
        ], 'log-hiring-and-hired-status')
            ->cron('12 5-23 * * *');

        $this->scheduleSequentialCommands($schedule, [
            'sicode:log_hiring_status',
            'sicode:log_hired_status --full',
        ], 'log-hiring-and-hired-status-night')
            ->dailyAt('01:12');


        /*
        |--------------------------------------------------------------------------
        | Confirmação tácita
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'sicode:check_tacit', 'check-tacit')
            ->hourlyAt(33);

        $this->scheduleCommand($schedule, 'sicode:tacitInApproval', 'tacit-in-approval')
            ->hourly();

        $this->scheduleCommand($schedule, 'sicode:tacitToApproval', 'tacit-to-approval')
            ->dailyAt('00:01');

        $this->scheduleCommand($schedule, 'ads:generate-tacit', 'ads-generate-tacit')
            ->dailyAt('00:05');


        /*
        |--------------------------------------------------------------------------
        | Correções
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'sicode:fix-operation-order', 'fix-operation-order')
            ->cron('0 10,17 * * *');


        /*
        |--------------------------------------------------------------------------
        | Sincronismo ADS
        |--------------------------------------------------------------------------
        */

        $this->scheduleCommand($schedule, 'sicode:sync_ads_requests', 'sync-ads-requests')
            ->everyThirtyMinutes();


        /*
        |--------------------------------------------------------------------------
        | Comandos comentados no cron antigo
        |--------------------------------------------------------------------------
        */

        // $this->scheduleCommand($schedule, 'sicode:fix-prazos', 'fix-prazos')
        //     ->dailyAt('07:00');

        // $this->scheduleCommand($schedule, 'sicode:fix-prazos --full', 'fix-prazos-full')
        //     ->dailyAt('12:05');
    }

    /**
     * Agenda um comando artisan simples.
     */
    protected function scheduleCommand(Schedule $schedule, string $command, string $logName)
    {
        return $schedule->command($command)
            ->name($this->scheduleDisplayName($logName))
            ->withoutOverlapping(180)
            ->appendOutputTo(storage_path("logs/scheduler/{$logName}.log"));
    }

    /**
     * Agenda vários comandos artisan em sequência usando a mesma lógica do && no cron.
     *
     * Se um comando falhar, os próximos não executam.
     */
    protected function scheduleSequentialCommands(Schedule $schedule, array $commands, string $logName)
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');

        $chain = collect($commands)
            ->map(fn (string $command) => "{$php} {$artisan} {$command}")
            ->implode(' && ');

        return $schedule->exec($chain)
            ->name($this->scheduleDisplayName($logName))
            ->withoutOverlapping(180)
            ->appendOutputTo(storage_path("logs/scheduler/{$logName}.log"));
    }

    /**
     * Nome amigável usado no schedule:list e no monitor web.
     */
    protected function scheduleDisplayName(string $logName): string
    {
        return [
            'exports-clear-old' => 'Limpar Exports',
            'chk-integridade' => 'Base OV',
            'wpas-log' => 'Log de WPAs',
            'sync-base-orders-operations' => 'Base Ordens e Operações',
            'upd-baseov-prazos-full' => 'Prazo Base OV',
            'upd-base-ep' => 'Base EP',
            'sync-protests' => 'Sincronizar reclamações',
            'prune-protest-and-check-sla' => 'Limpar MED e SLA',
            'check-protest-jobs-sla' => 'Checar SLA reclamações',
            'sync-log-protest-jobs' => 'Log reclamações SQL',
            'sync-costs-and-viability-values' => 'Custos e valores viab.',
            'transfer-log' => 'Log transferências',
            'notestop-log' => 'Log notas paradas',
            'log-viability' => 'Log viabilidades',
            'log-inform' => 'Log Informes Final',
            'log-rejected-viab' => 'Log viab. rejeitadas',
            'users-log' => 'Log usuários',
            'log-inform-return' => 'Log Info. Rejeitados.',
            'reclaims' => 'Log Retorno Interno',
            'informs-ads-log' => 'Log ADS Informes',
            'log-external-entities' => 'Log entidades externas',
            'log-informs-smc' => 'Log informes SMC',
            'sync-log-partials-informs' => 'Log Informe parciais',
            'log-production' => 'Log produção',
            'log-hiring-and-hired-status' => 'Log contratação',
            'log-hiring-and-hired-status-night' => 'Log contratação full',
            'check-tacit' => 'Checar tácita',
            'tacit-in-approval' => 'Tácita em aprovação',
            'tacit-to-approval' => 'Tácita para aprovação',
            'ads-generate-tacit' => 'Gerar ADS tácita',
            'fix-operation-order' => 'Corrigir operação pedido',
            'sync-ads-requests' => 'Sincronizar ADS',
        ][$logName] ?? str($logName)->replace('-', ' ')->headline()->toString();
    }

    /**
     * Timezone usado pelo scheduler.
     */
    protected function scheduleTimezone(): ?string
    {
        return 'America/Sao_Paulo';
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
