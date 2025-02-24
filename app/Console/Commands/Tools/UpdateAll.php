<?php

namespace App\Console\Commands\tools;

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
        $this->call('sicode:chk_integridade');
        $this->call('sicode:upd_baseEP');

        $this->call('sicode:upd_baseOrder');
        $this->call('sicode:upd_baseOperation');
        $this->call('sicode:operation-resp-upd');

        $this->call('sicode:upd_costs_mot');

    }
}
