<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;

class UpdateAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza todos os registros de todas as tabelas';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->refreshScreen();
        $this->info('Atualizando todas as tabelas');
        $this->info('Atualizando BaseOV');
        $this->call('sicode:chk_integridade');

        $this->refreshScreen();
        $this->info('Atualizando BaseEP');
        $this->call('sicode:upd_baseEP');

        $this->refreshScreen();
        $this->info('Atualizando Base Order');
        $this->call('sicode:upd_baseOrder');

        $this->refreshScreen();
        $this->info('Atualizando Base Operation');
        $this->call('sicode:upd_baseOperation');

        $this->refreshScreen();
        $this->info('Atualizando Base Operation Responsáveis');
        $this->call('sicode:operation-resp-upd');

        $this->refreshScreen();
        $this->info('Atualizando Base de Custos de Mão de Obra Terceirizada');
        $this->call('sicode:upd_costs_mot');

    }

    private function refreshScreen()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Se for Windows
            system('cls');
        } else {
            // Se for Unix (Linux, macOS)
            system('clear');
        }
    }
}
