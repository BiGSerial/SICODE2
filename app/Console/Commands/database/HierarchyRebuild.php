<?php

namespace App\Console\Commands\database;

use Illuminate\Console\Command;
use App\Services\HierarchyService;

class HierarchyRebuild extends Command
{
    protected $signature = 'hierarchy:rebuild
                            {--fresh : Trunca e reconstrói toda a user_closure}
                            {--dry   : Simula (não grava mudanças)}';

    protected $description = 'Reconstrói a hierarquia (user_closure) a partir de users.manager_id';

    public function handle(HierarchyService $service): int
    {
        $fresh = (bool) $this->option('fresh');
        $dry   = (bool) $this->option('dry');

        if (!$fresh && !$dry) {
            // comportamento padrão: rebuild completo
            $fresh = true;
        }

        if ($dry) {
            $this->info('>> DRY-RUN: simulando rebuild (nenhuma mudança persistida).');
        } else {
            $this->info('>> Iniciando rebuild da user_closure...');
        }

        try {
            if ($fresh) {
                $service->rebuildAll($dry);
            } else {
                // caminho alternativo (não usado aqui): garantir reflexivas apenas
                $service->ensureSelfRows();
            }

            $this->info($dry
                ? '>> DRY-RUN finalizado.'
                : '>> Rebuild concluído com sucesso.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Erro no rebuild: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }
    }
}
