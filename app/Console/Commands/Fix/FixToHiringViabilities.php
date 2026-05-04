<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\HiringWaiting;
use App\Models\SicodeSql\HiringStatus;
use App\Models\Viability;
use Illuminate\Console\Command;

class FixToHiringViabilities extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix-to-hiring';

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
        $this->info('Fixing hiring viabilities...');

        $viabilities = Viability::where('completed', false)
            ->orWhere('rejected', true)
            ->with('Note')
            ->get();

        $progressBar = $this->createProgressBar(count($viabilities));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        foreach (array_chunk($viabilities->toArray(), 500) as $chunk) {
            foreach ($chunk as $viability) {
                if (!HiringStatus::where('note_id', $viability['note_id'])->exists()) {
                    HiringStatus::create([
                    'note_id' => $viability['note_id'],
                    'note' => $viability['note']['note'],
                    'dt_status' => $viability['note']['note'],
                    ]);
                }
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info('Fixing Hiring Waiting...');

        $hiringWaitings = HiringWaiting::where('complete', false)
            ->with('Note')
            ->get();

        $progressBar = $this->createProgressBar(count($hiringWaitings));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        foreach (array_chunk($hiringWaitings->toArray(), 500) as $chunk) {
            foreach ($chunk as $waiting) {
                if (!HiringStatus::where('note_id', $waiting['note_id'])->exists()) {
                    HiringStatus::create([
                    'note_id' => $waiting['note_id'],
                    'note' => $waiting['note']['note'],
                    'dt_status' => $waiting['note']['note'],
                    ]);
                }
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info('Hiring waiting fixed successfully.');
    }
}
