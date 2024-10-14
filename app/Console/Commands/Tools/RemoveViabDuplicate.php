<?php

namespace App\Console\Commands\Tools;

use App\Models\Viability;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveViabDuplicate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:rem_viab_duplicate';

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

        Viability::chunk(500, function ($viabs) {

            foreach ($viabs as $viab) {
                $viab->update([
                    'note_id' => $viab->Order->note_id,
                ]);


                $viability = Viability::where('note_id', $viab->Order->note_id)->orderBy('id', 'asc')->first();

                if ($viability) {
                    $viability->Orders()->syncWithoutDetaching([$viab->Order->id]);
                }
            }

        });

        $duplicates = Viability::select('note_id', DB::raw('MIN(id) as min_id'), DB::raw('GROUP_CONCAT(id) as ids'))
                ->groupBy('note_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();


        $idsToDelete = [];

        foreach ($duplicates as $duplicate) {
            $ids = explode(',', $duplicate->ids);
            // Remove o min_id da lista de ids a serem excluídos
            $idsToDelete = array_merge($idsToDelete, array_diff($ids, [$duplicate->min_id]));
        }


        Viability::whereIn('id', $idsToDelete)->delete();

    }
}
