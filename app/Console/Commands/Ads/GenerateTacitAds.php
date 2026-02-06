<?php

namespace App\Console\Commands\Ads;

use App\Models\Adsform;
use App\Models\User;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTacitAds extends Command
{
    protected $signature = 'ads:generate-tacit';

    protected $description = 'Cria ADS tácita automaticamente para WorkReports vencidos sem ADS.';

    public function handle(): int
    {
        $cutoff = now()->subDays(6)->startOfDay();

        $query = WorkReport::query()
            ->where('rejected', false)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('Adsform');

        $count = 0;

        $query->chunkById(200, function ($workReports) use (&$count) {
            foreach ($workReports as $workReport) {
                $userId = $workReport->user_id ?: User::query()->value('id');
                if (!$userId) {
                    $this->warn("WorkReport {$workReport->id} sem user_id e nenhum usuário disponível.");
                    continue;
                }

                $dueAt = Carbon::parse($workReport->created_at)->endOfDay()->addDays(6);

                DB::transaction(function () use ($workReport, $userId, $dueAt, &$count) {
                    if ($workReport->Adsform()->exists()) {
                        return;
                    }

                    Adsform::create([
                        'work_report_id' => $workReport->id,
                        'note_id' => $workReport->note_id,
                        'user_id' => $userId,
                        'name' => null,
                        'obs' => 'ADS tácita criada automaticamente pelo sistema.',
                        'contract' => null,
                        'center' => null,
                        'deposit' => null,
                        'amount' => 0.00,
                        'partial' => false,
                        'tacit' => true,
                        'tacit_due_at' => $dueAt,
                        'tacit_delivered_at' => null,
                    ]);

                    $count++;
                });
            }
        });

        $this->info("ADS tácitas criadas: {$count}");

        return Command::SUCCESS;
    }
}
