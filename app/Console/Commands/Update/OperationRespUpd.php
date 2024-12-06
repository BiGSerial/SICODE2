<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\BaseOperationResp;
use Illuminate\Console\Command;
use Carbon\Carbon;

class OperationRespUpd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:operation-resp-upd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $operation = BaseOperationResp::where("operacao", "0040")
        ->where('confFinal', '!=', 'X') // confFinal diferente de 'X'
        ->where('fimLancado', '>=', Carbon::now()->subDays(7)) // fimLancado nos últimos 7 dias
        ->count();

        dd($operation);
    }
}
