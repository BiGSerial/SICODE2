<?php

namespace App\Console\Commands\fix;

use App\Models\Order;
use App\Models\Viability;
use Illuminate\Console\Command;

class ViabilityValues extends Command
{
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $this->info('Starting the command...');

        try {
            $total = Viability::count();
            $this->output->progressStart($total);

            Viability::chunk(100, function ($viabilities) {
                foreach ($viabilities as $viability) {
                    $viability->update([
                    'value' => Order::where('note_id', $viability->note_id)->sum('moaberto')
                    ]);
                    $this->output->progressAdvance();
                }
            });

            $this->output->progressFinish();
            $this->info('Command completed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
        }


    }
}
