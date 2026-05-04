<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\{City};
use App\Models\Edp_depc\City as OriginCity;
use Illuminate\Console\Command;
use Throwable;

class CityUpdate extends Command
{
    use ShowsProgress;

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
        $log = null;

        try {
        $this->info('INIT UPDATE CITIES BD');
        $origin_count         = OriginCity::count();
        $log = new RegistroJson('upd_cities', $this->options(), $origin_count);
        $progressBar    = $this->createProgressBar($origin_count);
        $updated = 0;
        $created = 0;

        if ($origin_count) {

            $progressBar->start();

            foreach (OriginCity::all() as $city) {
                $chk = City::updateOrCreate(
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
                if ($chk->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
        }
        $log->setCreated($created);
        $log->setUpdated($updated);
        $log->save();
        return self::SUCCESS;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }

    }

}
