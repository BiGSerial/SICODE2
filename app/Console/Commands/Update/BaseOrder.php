<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseOrder as Edp_depcBaseOrder, City};
use App\Models\Note;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class BaseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Base order from SQLSERVER.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalRecords = Edp_depcBaseOrder::count();
        $cities       = City::get();

        $log = new RegistroJson('upd_baseOrder', $this->option());
        $log->setTotal($totalRecords);

        $progressBar = new ProgressBar($this->output, $totalRecords);

        $progressBar->setFormat('<bg=blue;fg=white>UPDATE ORDERS: %current%/%max% </><fg=white;options=bold> [%cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%');
        // $progressBar->setFormat('%current%/%max% [%cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');

        $progressBar->start();

        $chunkSize = 5000;

        $count['upd']   = 0;
        $count['ctd']   = 0;
        $count['tloop'] = round($totalRecords / $chunkSize, 0);
        $count['cloop'] = 0;
        $count['nf']    = 0;

        Edp_depcBaseOrder::chunk($chunkSize, function ($origins) use (&$progressBar, &$count, $cities, &$log) {

            $originNotes = $origins->pluck('ovNota')->unique()->map(function ($item) {
                return (string) intval($item);
            });

            $notes = Note::whereIn('note', $originNotes)->get();

            if (count($originNotes) > $notes->count()) {
                $this->info('<bg=yellow;fg=black> INFO </> <fg=white;options=bold> DESTINY`s BASE HAVE NOTES/OV NOT FOUNDED...</>');
            }

            $count['cloop']++;

            foreach ($origins as $origin) {

                $note = $notes->where('note', (string) intval($origin->ovNota))->first();

                if (!$note) {

                    $note = Note::whereRelation('Orders', 'ordem', $origin->ordem)->first();

                    if (!$note) {
                        // Nota não encontrada, cria-se um uma nova numeração caso o tamanho da numeração seja menor que 4 digitos.
                        $count['nf']++;

                        if (strlen((string) intval($origin->ovNota)) > 4) {
                            $rangeNum = 90000000;
                            $newNote  = 'C' . (string) ($rangeNum + Note::Where('note', 'like', 'C9%')->count());
                        } else {
                            $newNote = (string) intval($origin->ovNota);
                        }

                        if (!$note = Note::where('note', $newNote)->first()) {
                            $note             = new Note();
                            $note->note       = $newNote;
                            $note->created_by = 'SICODE';
                            $note->numPedido  = $origin->descricao;
                            $note->centerjob  = $origin->cenTrab;
                            $note->nexp       = $cities->where('gpm', $origin->gpm)->first() ? $cities->where('gpm', $origin->gpm)->first()->rdMunicipio : null;
                            $note->lexp       = $cities->where('gpm', $origin->gpm)->first() ? $cities->where('gpm', $origin->gpm)->first()->municipio : null;
                            $note->nstats     = '101';
                            $note->save();
                        }

                    }
                }

                if ($note) {

                    try {
                        $order = $note->orders()->updateOrCreate(
                            ['ordem' => $origin->ordem],
                            [
                                'descricao'     => $origin->descricao,
                                'locInstalacao' => $origin->locInstalacao,
                                'cenPlan'       => $origin->cenPlan,
                                'prioridade'    => $origin->prioridade,
                                'statusSist'    => $origin->statusSist,
                                'statusUser'    => $origin->statusUser,
                                'cenTrab'       => $origin->cenTrab,
                                'gpm'           => $origin->gpm,
                                'custPlanejado' => $origin->custPlanejado,
                                'custRealizado' => $origin->custRealizado,
                                'modifPor'      => $origin->modifPor,
                                'pep'           => $origin->pep,
                                'conjunto'      => $origin->conjunto,
                                'denConjunto'   => $origin->denConjunto,
                                'dtEntrada'     => $origin->dtEntrada,
                            ]
                        );

                        if ($order->wasRecentlyCreated) {

                            $count['ctd']++;

                        } else {

                            $count['upd']++;

                        }
                    } catch (\Throwable $th) {
                        $log->setErrorMessage($th->getMessage());
                    }


                } else {
                    $count['nf']++;
                }

                $progressBar->setMessage($count['nf'], 'nf');
                $progressBar->setMessage($count['cloop'], 'cloop');
                $progressBar->setMessage($count['tloop'], 'tloop');
                $progressBar->setMessage($count['upd'], 'upd');
                $progressBar->setMessage($count['ctd'], 'ctd');
                $progressBar->advance();
            }

            unset($origins);
            unset($originNotes);

        });

        $log->setCreated($count['ctd']);
        $log->setUpdated($count['upd']);
        $log->setNoteUpdated($count['nf']);
        $log->save();

        unset($count);

        $progressBar->finish();
    }
}
