<?php

namespace App\Console\Commands\System;

use Illuminate\Console\Command;

class version extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Return SICODE Version Info';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appverData = json_decode(file_get_contents(base_path('appver.json')));

        if ($appverData && isset($appverData->appver)) {
            $date = date('d/m/Y', strtotime($appverData->historic[0]->date));

            $art = "
            _____  _____   _____   ____   _____   ______
           / ____||_   _| / ____| / __ \ |  __ \ |  ____|
          | (___    | |  | |     | |  | || |  | || |__
           \___ \   | |  | |     | |  | || |  | ||  __|
           ____) | _| |_ | |____ | |__| || |__| || |____
          |_____/ |_____| \_____| \____/ |_____/ |______|TM
          Ver: {$appverData->appver}
          Date: {$date}
           ";

            $this->info($art);
        } else {
            $this->error('Version file Not found.');
        }
    }
}
