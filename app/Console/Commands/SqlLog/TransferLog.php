<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\Notestatus;
use App\Models\Prodtransfer;
use App\Models\SicodeSql\TransferProdLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TransferLog extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:transfer_log {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charger to SQLServer Transfered Notes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT IS PRODUCTION SERVER, ABORTING PROPAGATION LOG</>');
        }
        
        $transfers = Prodtransfer::whereDate('updated_at', '>=', Carbon::now()->subDays($this->option('days')))
            ->with([
                'Production.Note',
                'From.Employee.Contract.company',
                'To.Employee.Contract.company',
                'Service',
            ])
            ->get();

        $progressBar = $this->createProgressBar($transfers->count());

        $progressBar->setFormat('<bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');

        if ($transfers->count()) {
            $progressBar->start();

            foreach ($transfers as $transfer) {
                $check = TransferProdLog::where('prodtrans_id', $transfer->id)->first();
                $payload = $this->payload($transfer);
                $msg   = '';

                if ($check) {
                    $check->update($payload);

                    $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> { $check->note } </>";
                } else {
                    $check = TransferProdLog::create($payload);

                    $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> { $check->note } </>";
                }

                $progressBar->setMessage($msg);
                $progressBar->advance();
            }

            $progressBar->finish();

        } else {
            $this->info('<bg=green;fg=white;options=bold> DONE </><fg=yellow;options=bold> NO REGISTERS FOUNDED');
        }

        $this->info('<bg=green;fg=white> DONE </>');
    }

    private function payload(Prodtransfer $transfer): array
    {
        $production = $transfer->Production;

        return [
            'prodtrans_id'  => $transfer->id,
            'production_id' => $transfer->production_id,
            'note'          => $production?->Note?->note ?? '',
            'service'       => $transfer->Service?->service ?? '',
            'from'          => $transfer->From?->name ?? '',
            'company_from'  => $transfer->From?->Employee?->Contract?->company?->name ?? '',
            'to'            => $transfer->To?->name ?? '',
            'company_to'    => $transfer->To?->Employee?->Contract?->company?->name ?? '',
            'info'          => trim((string) $transfer->info),
            'status'        => Notestatus::status($transfer->status)->status,
            'note_status'   => $production?->status_note ?? '',
        ];
    }
}
