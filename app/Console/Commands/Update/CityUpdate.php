<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\BaseOV;
use App\Models\{City, HistoricNote, Note};
use App\Models\Edp_depc\City as OriginCity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class Fixbase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Cities to LocalBase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('INIT UPDATE CITIES BD');
        $origin_count         = OriginCity::count();
        $progressBar    = new ProgressBar($this->output, $origin_count);

        if ($origin_count) {

            $progressBar->start();

            foreach (OriginCity::all() as $city) {
                City::updateOrCreate(
                    [
                        'rdMunicipio' => $city->rdMunicipio
                    ],
                    [
                        'gpm' => $city->gpm,
                        'cidade' => $city->cidade,
                        'municipio' => $city->municipio,
                        'respExpansao' => $city->respExpansao,
                        'respPreventiva' => $city->respPreventiva,
                        'cenCusto' => $city->cenCusto,
                        'baseConstrucao' => $city->baseConstrucao,
                        'centrlizador' => $city->centrlizador,
                        'centro' => $city->centro,
                        'regiao' => $city->regiao,
                        'regional' => $city->regional,
                        'codIbge' => $city->codIbge,
                        'centroHana' => $city->centroHana,
                    ]
                );

                $progressBar->advance();
            }

            $progressBar->finish();
        }

    }

}
