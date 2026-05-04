<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Wpa;
use Illuminate\Console\Command;

class fixServiceWpa extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix-service-wpa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add service_id where is null in Wpa table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Wpa::whereNull('service_id')->count();

        if ($count > 0) {
            $this->info("{$count} Wpa(s) found without service_id");

            $progressBar = $this->createProgressBar($count);

            $progressBar->setFormat('%current%/%max% [upd: %upd% |np: %np% |err: %err%] [%bar%] %elapsed%/%estimated% | %memory%');

            $count = [];
            $count['upd'] = 0;
            $count['np'] = 0;
            $count['err'] = 0;

            $progressBar->start();

            Wpa::whereNull('service_id')->with('Production')->chunk(500, function ($wpas) use (&$count, &$progressBar) {

                foreach ($wpas as $wpa) {

                    if ($wpa->Production) {
                        $wpa->service_id = $wpa->Production->service_id;
                        $wpa->save();
                        $count['upd']++;
                    } else {
                        $count['np']++;
                    }

                    $progressBar->setMessage($count['upd'], 'upd');
                    $progressBar->setMessage($count['np'], 'np');
                    $progressBar->setMessage(0, 'err');
                    $progressBar->advance();
                }

            });

            $progressBar->finish();

            $this->info("\n {$count['upd']} Wpa(s) updated with service_id");

        } else {
            $this->info("\n No Wpa(s) found without service_id");
        }
    }
}
