<?php

namespace App\Console\Commands\SqlLog;

use App\Custom\Notestatus;
use App\Models\Production;
use App\Models\SicodeSql\Production as SicodeSqlProduction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class ProductionLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_production {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send LOG Production to SQL SERVER';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> Verifing Productions.... </>');

        $productions = Production::where('d5', false)->whereDate('updated_at', '>=', Carbon::now()->subDays($this->option('days')))->with('Note', 'User', 'Company', 'Service')->get();

        $progressBar = new ProgressBar($this->output, $productions->count());

        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');
        $progressBar->start();

        if ($productions->count()) {

            $progressBar->start();
            $this->info("<bg=blue;fg=white;options=bold> INFO </><fg=white;options=bold> WE HAS FOUNDED {$productions->count()} REGISTER ARENT IN PRODUCTION LOG");

            foreach ($productions as $production) {

                $check = SicodeSqlProduction::where('production_id', $production->id)->first();
                $msg   = '';

                if ($check) {

                    $check->update([
                        'production_id'    => $production->id,
                        'user'             => $production->user_id ? $production->User->name : 'Desconhecido',
                        'company'          => $production->Company->name,
                        'dispatch_by'      => $production->dispatch_by ? $production->load('Dispatcher.Employee.Contract.company')->Dispatcher->name : 'Desconhecido',
                        'company_dispatch' => $production->load('Dispatcher.Employee.Contract.company')->Dispatcher->Employee->Contract->company->name,
                        'att_by'           => $production->att_by ? $production->load('Att.Employee.Contract.company')->Att->name : 'Desconhecido',
                        'company_att'      => $production->att_by ? $production->load('Att.Employee.Contract.company')->Att->Employee->Contract->company->name : 'Desconhecido',
                        'service'          => $production->Service->service,
                        'note'             => $production->Note->note,
                        'status'           => Notestatus::status($production->status)->status,
                        'dispatch_at'      => $production->dispatch_at,
                        'att_at'           => $production->att_at,
                        'completed_at'     => $production->completed_at,
                        'confirmed_at'     => $production->confirmed_at,
                        'completed'        => $production->completed,
                        'confirmed'        => $production->confirmed,
                        'stopped'          => $production->stopped,
                        'note_status'      => $production->status_note,
                        'conclusion'       => $production->load('Analise')->Analise ? $production->load('Analise')->Analise->conclusion : '',
                        'mmgd'             => $production->mmgd,
                        'transfer'         => $production->transferred,
                        'input_manual'     => $production->manual,
                        'conf_manual'      => $production->conf_manual,
                        'reje_manual'      => $production->rejected,
                        'dhstats'          => $production->dt_note,
                        'type_note'        => $production->Note->type_note,
                        'eo'               => $production->eo,
                        'iproject'         => $production->iproject,
                        'cadastro'         => $production->cadastro,
                        'postes_u'         => $production->postes_u,
                        'postes_c'         => $production->postes_c,
                        'centroTrab'       => $production->Note->centerjob,
                        'noinconsistency'  => $production->noinconsistency,

                    ]);

                    // $this->info("<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> {$production->Note->note} </> HAS UPDATED");
                    $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> {$production->Note->note} </>";
                } else {

                    $check = SicodeSqlProduction::create([
                        'production_id'    => $production->id,
                        'user'             => $production->user_id ? $production->User->name : 'Desconhecido',
                        'company'          => $production->Company->name,
                        'dispatch_by'      => $production->dispatch_by ? $production->load('Dispatcher.Employee.Contract.company')->Dispatcher->name : 'Desconhecido',
                        'company_dispatch' => $production->load('Dispatcher.Employee.Contract.company')->Dispatcher->Employee->Contract->company->name,
                        'att_by'           => $production->att_by ? $production->load('Att.Employee.Contract.company')->Att->name : 'Desconhecido',
                        'company_att'      => $production->att_by ? $production->load('Att.Employee.Contract.company')->Att->Employee->Contract->company->name : 'Desconhecido',
                        'service'          => $production->Service->service,
                        'note'             => $production->Note->note,
                        'status'           => Notestatus::status($production->status)->status,
                        'dispatch_at'      => $production->dispatch_at,
                        'att_at'           => $production->att_at,
                        'completed_at'     => $production->completed_at,
                        'confirmed_at'     => $production->confirmed_at,
                        'completed'        => $production->completed,
                        'confirmed'        => $production->confirmed,
                        'stopped'          => $production->stopped,
                        'note_status'      => $production->status_note,
                        'conclusion'       => $production->load('Analise')->Analise ? $production->load('Analise')->Analise->conclusion : '',
                        'mmgd'             => $production->mmgd,
                        'transfer'         => $production->transferred,
                        'input_manual'     => $production->manual,
                        'conf_manual'      => $production->conf_manual,
                        'reje_manual'      => $production->rejected,
                        'dhstats'          => $production->dt_note,
                        'type_note'        => $production->Note->type_note,
                        'eo'               => $production->eo,
                        'iproject'         => $production->iproject,
                        'cadastro'         => $production->cadastro,
                        'postes_u'         => $production->postes_u,
                        'postes_c'         => $production->postes_c,
                        'centroTrab'       => $production->Note->centerjob,
                        'noinconsistency'  => $production->noinconsistency,
                    ]);

                    // $this->info("<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> {$production->Note->note} </> HAS UPDATED");
                    $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> {$production->Note->note} </>";
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
}
