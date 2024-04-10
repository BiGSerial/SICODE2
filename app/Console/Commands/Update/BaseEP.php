<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\{BaseEP as Edp_depcBaseEP, Gpm};
use App\Models\Note;
use Carbon\Carbon;
use DateTime;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class BaseEP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseEP {--full} {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Table Notes with BaseEP Sql info';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $DaysAgo   = Carbon::now()->subDays($this->option('days'));
        $chunkSize = 500;

        if ($this->option('full')) {
            $chunkSize = 1000;
        }

        $count = ['upd' => 0, 'ins' => 0, 'tins' => 1, 'errors' => 0];

        $totalRecords = Edp_depcBaseEP::count();

        // $totalRecords = Edp_depcBaseOV::where('ultimoStatus', 1)->count();
        $progressBar = new ProgressBar($this->output, $totalRecords);

        $progressBar->setFormat('<bg=blue;fg=white>UPDATE BaseEP: %current%/%max% </><fg=white;options=bold> [%tins%][I: %ins%/U: %upd%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%');
        $progressBar->setMessage('Inserting in bulk');
        $progressBar->start($totalRecords);

        // Edp_depcBaseOV::where('ultimoStatus', 1)->chunk($chunkSize, function ($records) use ($progressBar, &$count) {
        Edp_depcBaseEP::chunk($chunkSize, function ($records) use ($progressBar, &$count) {

            $historic = null;

            $notes = Note::whereIn('note', $records->pluck('nota'))->get();

            foreach ($records as $record) {

                $existingRecord = $notes->where('note', $record->nota)->first();

                if ($existingRecord) {
                    $atualizar = true;

                    if ($this->option('full')) {
                        $atualizar = true;
                    }

                    if ($atualizar &&
                    $existingRecord->created_by === $record->criadoPor &&
                    $existingRecord->dt_status === $record->dtCriacao &&
                    $existingRecord->user === $record->notificador &&
                    $existingRecord->numPedido === $record->descricao &&
                    $existingRecord->pze === $record->PzE &&
                    $existingRecord->num_material === $record->conjunto &&
                    $existingRecord->material === $record->denomConjunto &&
                    $existingRecord->nstats === $record->statusUsuario &&
                    $existingRecord->status === $record->status
                    ) {

                    } else {

                        try {
                            $city = Gpm::where('gpm', $record->grpPlan)->first();

                            $chk = $existingRecord->update([
                                'created_by' => $record->criadoPor,
                                'dt_created' => "{$record->dtNota} 0:00:00",
                                'dt_status'  => $record->dtNota,
                                'user'       => $record->notificador,
                                // 'value' => $record->valorLiq,
                                // 'currency' => $record->moeda,
                                // 'eq_venda' => $record->eqVenda,
                                'numPedido' => $record->descricao,
                                // 'client' => $record->emissorOV,
                                // 'group1' => $record->grpCliente1,
                                // 'group2' => $record->grpCliente2,
                                // 'group3' => $record->grpCliente3,
                                // 'group4' => $record->grpCliente4,
                                // 'group5' => $record->grpCliente5,
                                'pze'          => $record->PzE,
                                'num_material' => $record->conjunto,
                                'material'     => $record->denomConjunto,
                                'nexp'         => $city ? $city->rdMunicipio : null,
                                'lexp'         => $city ? $city->cidade : null,
                                // 'pep' => $record->PEP,
                                'nstats' => $record->statusUsuario,
                                'status' => $record->status,
                                // 'days' => $record->dias,
                                // 'transaction' => $record->transicao,
                                // 'validar_prazo' => $record->considerarPrazo,
                                'rubrica' => $record->rubrica,
                                // 'pze_tratado' => $record->PzETratado,
                                // 'days_stat' => $record->diasNoStatus,
                                // 'pze_parecer' => $record->parecerPrazo,
                                // 'days_left' => $record->diasPVencimento,
                                'centerjob' => $record->cenTrabResp,
                                'type_note' => 1,
                                'mesalization' => $record->mensalizacao,
                            ]);

                            if ($chk) {
                                $count['upd']++;
                            }

                        } catch (\Throwable $th) {
                            dd($th->getMessage());
                        }

                    }

                } elseif (!$existingRecord) {

                    try {

                        $city = Gpm::where('gpm', $record->grpPlan)->first();

                        $chk = Note::create([
                            'note'       => $record->nota,
                            'created_by' => $record->criadoPor,
                            'dt_created' => "{$record->dtNota} 0:00:00",
                            'dt_status'  => $record->dtNota,
                            'user'       => $record->notificador,
                            // 'value' => $record->valorLiq,
                            // 'currency' => $record->moeda,
                            // 'eq_venda' => $record->eqVenda,
                            'numPedido' => $record->descricao,
                            // 'client' => $record->emissorOV,
                            // 'group1' => $record->grpCliente1,
                            // 'group2' => $record->grpCliente2,
                            // 'group3' => $record->grpCliente3,
                            // 'group4' => $record->grpCliente4,
                            // 'group5' => $record->grpCliente5,
                            'pze'          => $record->PzE,
                            'num_material' => $record->conjunto,
                            'material'     => $record->denomConjunto,
                            'nexp'         => $city ? $city->rdMunicipio : null,
                            'lexp'         => $city ? $city->cidade : null,
                            // 'pep' => $record->PEP,
                            'nstats' => $record->statusUsuario,
                            'status' => $record->status,
                            // 'days' => $record->dias,
                            // 'transaction' => $record->transicao,
                            // 'validar_prazo' => $record->considerarPrazo,
                            'rubrica' => $record->rubrica,
                            // 'pze_tratado' => $record->PzETratado,
                            // 'days_stat' => $record->diasNoStatus,
                            // 'pze_parecer' => $record->parecerPrazo,
                            // 'days_left' => $record->diasPVencimento,
                            'centerjob' => $record->cenTrabResp,
                            'type_note' => 1,
                            'mesalization' => $record->mensalizacao,
                        ]);

                        if ($chk) {
                            $count['ins']++;
                        }

                    } catch (\Throwable $th) {
                        dd($th->getMessage());
                    }
                }

                $progressBar->setMessage($count['tins'], 'tins');
                $progressBar->setMessage($count['upd'], 'upd');
                $progressBar->setMessage($count['ins'], 'ins');
                $progressBar->setMessage('Charging to memory');
                $progressBar->advance();
            }

            $count['tins']++;
        });


        // Muda Status de todas as notas que não são mais trazidas atualiza
        $limiteTempo = Carbon::now()->subDays(1);

        $cancelNotes = Note::where('type_note', 1)->where('updated_at', '<', $limiteTempo)->update(['centerjob' => 'LIMBO', 'nstats' => 99]);
        $this->info('NOTAS CANCELADAS: '.$cancelNotes);







        $filePath = base_path('registroUpdate.json');

        if (!file_exists($filePath)) {

            $registroUpdate[] = [
                'tarefa'     => 'BaseEP',
                'options'    => $this->option(),
                'total'      => $totalRecords,
                'updated'    => $count['upd'],
                'created'    => $count['ins'],
                'notupdated' => '',
                'erros'      => $count['errors'],
                'date'       => date('Y-m-d H:i:s'),
            ];

        } else {

            $registroUpdate = json_decode(file_get_contents($filePath), true);

            $registroUpdate[] = [
                'tarefa'     => 'BaseEP',
                'options'    => $this->option(),
                'total'      => $totalRecords,
                'updated'    => $count['upd'],
                'created'    => $count['ins'],
                'notupdated' => '',
                'erros'      => $count['errors'],
                'date'       => date('Y-m-d H:i:s'),
            ];

        }

        $registroUpdate = array_filter($registroUpdate, function ($item) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $item['date']);

            return $date && $date->diff(new DateTime())->days <= 15;
        });

        file_put_contents($filePath, json_encode($registroUpdate));

        // Registra atualizações
        // Bancoupdate::Create([
        //     'last_update' => date('Y-m-d H:i:s'),
        //     'error' => $count['errors'],
        //     'inserts' => $count['ins'],
        //     'updates' => $count['upd']
        // ]);

        // Bancoupdate::whereDate('created_at', '<', Carbon::now()->subDays(30))->delete();

        $progressBar->finish();
        $this->info('Data transfer completed.');

        return 0;
    }
}
