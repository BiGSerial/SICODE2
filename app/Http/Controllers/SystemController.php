<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class SystemController extends Controller
{
    public function commands()
    {
        return view('system.commands');
    }

    public function execute(Request $request)
    {
        // Valida e sanitiza o comando
        $command = $request->input('command');

        if (empty($command)) {
            return response()->json(['error' => 'Comando não fornecido'], 400);
        }

        $allowedCommands = [
            // Comandos básicos do Laravel
            'migrate',
            'cache:clear',
            'config:cache',
            'route:cache',
            'queue:restart',
            'schedule:run',
            'optimize',
            'view:clear',

            // Comandos do SICODE
            'sicode:admin_pass',
            'sicode:check_tacit',
            'sicode:chk_integridade',
            'sicode:confirm-manual',
            'sicode:confirm_prod',
            'sicode:expurgo_sql_prod',
            'sicode:files-check',
            'sicode:fix-operation-order',
            'sicode:fix-prazos',
            'sicode:fix_destinyBase',
            'sicode:log_InformReturn',
            'sicode:log_inform',
            'sicode:log_production',
            'sicode:log_rejected_viab',
            'sicode:log_viability',
            'sicode:notestop_log',
            'sicode:operation-resp-upd',
            'sicode:rem_viab_duplicate',
            'sicode:transfer_log',
            'sicode:up_version',
            'sicode:upd_baseEP',
            'sicode:upd_baseOperation',
            'sicode:upd_baseOrder',
            'sicode:upd_baseov',
            'sicode:upd_baseov_lote',
            'sicode:upd_cities',
            'sicode:upd_costs_mot',
            'sicode:upd_wpa',
            'sicode:users-log',
            'sicode:version',
            'sicode:viab-values',
            'sicode:wpas_log',
        ];

        // Extrai o comando base e valida se é permitido
        $parsedCommand = explode(' ', $command)[0];
        if (!in_array($parsedCommand, $allowedCommands)) {
            return response()->json([
                'error' => 'Comando não permitido',
                'message' => "O comando '{$parsedCommand}' não está autorizado para execução."
            ], 403);
        }

        $path = base_path();

        $process = Process::path($path)->start('php artisan ' .$command);

        $pid = $process->id();

        if ($process->running()) {
            return response()->json([
                'status' => 'started',
                'pid' => $pid,
                'output' => $process->output(),
                'message' =>  $process->output(),
                'error' => null
            ]);
        } else {
            return response()->json([
                'status' => 'completed',
                'pid' => $pid,
                'output' => $process->output(),
                'message' => 'Erro no processo.',
                'error' => $process->errorOutput()
            ]);
        }


    }

    public function checkStatus($pid)
    {
        // Verifica se o processo está rodando no Linux/Unix
        if (PHP_OS_FAMILY === 'Linux') {
            $tasklistProcess = Process::run('ps -p ' . $pid);

            if ($tasklistProcess->successful() && str_contains($tasklistProcess->output(), $pid)) {
                return response()->json([
                    'status' => 'running',
                    'pid' => $pid,
                    'message' => 'Em execução...',
                    'details' => $tasklistProcess->output()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'pid' => $pid,
                    'message' => 'Nao encontramos execução...',
                    'details' => $tasklistProcess->output()
                ]);
            }
        }

        // Verifica se o processo está rodando no Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $tasklistProcess = Process::run('tasklist /FI "PID eq ' . $pid . '"');

            if ($tasklistProcess->successful() && str_contains($tasklistProcess->output(), $pid)) {
                return response()->json([
                    'status' => 'running',
                    'pid' => $pid,
                    'message' => 'Em execução...',
                    'details' => $tasklistProcess->output()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'pid' => $pid,
                    'message' => 'Nao encontramos execução...',
                    'details' => $tasklistProcess->output()
                ]);
            }
        }

        // Para outros sistemas operacionais
        return response()->json([
            'status' => 'unknown',
            'message' => 'Status check não suportado neste sistema operacional',
            'details' => null
        ]);
    }


}
