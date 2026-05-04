<?php

namespace App\Console\Commands\fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Order;
use App\Models\Viability;
use Illuminate\Console\Command;

class ViabilityValues extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:viab-values';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Value MOA in Viability table';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('Starting the command...');

        try {
            $total = Viability::where('value', '<', 1.0)->orWhereNull('value')->count();


            if ($total > 0) {
                $this->progressStart($total);
                Viability::where('value', '<', 1.0)->orWhereNull('value')->chunk(500, function ($viabilities) {
                    foreach ($viabilities as $viability) {

                        $moas = Order::where('note_id', $viability->note_id)->get();


                        $soma = 0.0;

                        if (!$moas->isEmpty()) {
                            foreach ($moas as $moa) {
                                if ($moa->moaberto > 0.0) {
                                    $this->info($viability->note->note . " - " . $moa->moaberto);
                                    $soma += $moa->moaberto;
                                }
                            }
                        }


                        if ($soma > 0.0) {
                            $this->info($viability->note->note . " - " . $soma);
                            $viability->value = $soma;
                            $viability->save();
                        }

                        $this->progressAdvance();
                    }
                });
                $this->progressFinish();
            }


            $this->info('Command completed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }


    }
}
