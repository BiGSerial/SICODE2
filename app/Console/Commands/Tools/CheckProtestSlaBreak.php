<?php

namespace App\Console\Commands\Tools;

use App\Models\ProtestJob;
use Illuminate\Console\Command;

class CheckProtestSlaBreak extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:sla-chk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica os vencimentos de SLA de protestos e gera as devidas ocorrências.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $protestsLast24Hour = ProtestJob::Open()
            ->whereBetween('sla_due_at', [
                now()->startOfHour(),
                now()->addHours(24)->startOfHour()
            ])
            ->get();
    }
}
