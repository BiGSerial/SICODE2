<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Production;
use App\Models\SicodeSql\Production as SicodeSqlProduction;
use Illuminate\Console\Command;

class ExpurgoSqlProduction extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:expurgo_sql_prod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expurgo Sql production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sql_prod_count = SicodeSqlProduction::where('confirmed', false)->count();

        $progress = $this->createProgressBar($sql_prod_count);

        $progress->start();

        $count = 0;

        SicodeSqlProduction::where('confirmed', false)->chunk(1000, function ($sqlprods) use (&$progress, &$count) {
            $idsSqlProd = $sqlprods->pluck('production_id')->toArray();

            if (!empty($idsSqlProd)) {
                $existingIds = Production::whereIn('id', $idsSqlProd)->pluck('id')->toArray();

                $idsToDelete = array_diff($idsSqlProd, $existingIds);

                if (!empty($idsToDelete)) {
                    SicodeSqlProduction::whereIn('production_id', $idsToDelete)->delete();
                    print_r($idsToDelete);
                    $count += count($idsToDelete);
                }

            }

            $progress->advance();
        });

        $progress->finish();

        $this->info('Total Expurgado: ' . $count);

    }
}
