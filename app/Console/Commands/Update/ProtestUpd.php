<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseProtest;
use App\Models\Protest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class ProtestUpd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_protest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza os protestos no sistema SICODE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando atualização dos protestos no sistema SICODE...');

        $log = new RegistroJson('upd_protest', $this->options());
        $count = ['ins' => 0, 'upd' => 0, 'tins' => 1, 'errors' => 0];


        $baseQuery = BaseProtest::query()->from('tbld_usr_baseReclamacoes as t')
    ->whereIn('id', function ($query) {
        $query->select('id')
              ->from('tbld_usr_baseReclamacoes as sub')
              ->whereColumn('sub.nota', 't.nota')
              ->orderByDesc('sub.dtCriacaoMedida')
              ->limit(1);  // no SQL Server vira TOP 1
    })
    ->select([
        't.id',
        't.nota',
        't.tipoNota',
        't.codecodf',
        't.txtGrpCodificacao',
        't.statUsuar',
        't.cidade',
        't.cenPlan',
        't.dtAberturaNota',
        't.dtConclusaoDesej',
        't.descCausa',
        't.descSubCausa',
    ]);



        $total = $baseQuery->count();
        $log->setTotal($total);

        $bar = new ProgressBar($this->output, $total);
        $bar->setFormat(
            '<bg=blue;fg=white>UPDATE PROTEST LIST: %current%/%max% </>' .
            '<fg=white;options=bold> [%tins%][I: %ins%/U: %upd%] </>' .
            '<fg=green>[%bar%]</> <fg=white;options=bold> %percent%%</> ' .
            '<bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%'
        );

        $bar->setMessage('Starting', 'message');
        $bar->start();

        $baseQuery->orderBy('id')
            ->chunk(2000, function ($protests) use ($bar, &$count) {
                $notas = $protests->pluck('nota')->unique()->values();
                $existingNotes = Protest::whereIn('nota', $notas)->get()->keyBy('nota');

                $upsertData = [];

                foreach ($notas as $nota) {
                    $record   = $protests->firstWhere('nota', $nota);
                    $existiting = $existingNotes->get($nota);

                    $modified = is_null($existiting)
                        || $existiting->tipoNota !== $record->tipoNota
                        || $existiting->txtGrpCodificacao !== $record->txtGrpCodificacao
                        || $existiting->cenPlan !== $record->cenPlan
                        || $existiting->descSubCausa !== $record->descSubCausa
                        || $existiting->descCausa !== $record->descCausa
                        || $existiting->statUsuar !== $record->statUsuar;

                    if (!$modified) {
                        $bar->setMessage($count['tins'], 'tins');
                        $bar->setMessage($count['ins'], 'ins');
                        $bar->setMessage($count['upd'], 'upd');
                        $bar->advance();
                        continue;
                    }

                    $data = [
                         'nota'               => $record->nota,
                        'tipoNota'           => $record->tipoNota,
                        'codecodf'           => $record->codecodf,
                        'txtGrpCodificacao'  => $record->txtGrpCodificacao,
                        'dtAberturaNota'     => $record->dtAberturaNota,
                        'dtConclusaoDesej'   => $record->dtConclusaoDesej,
                        'cenPlan'            => $record->cenPlan,
                        'cidade'             => $record->cidade,
                        'statUsuar'          => $record->statUsuar,
                        'descCausa'          => $record->descCausa,
                        'descSubCausa'       => $record->descSubCausa,
                        'updated_at'         => now(),
                        'created_at'         => is_null($existiting) ? now() : $existiting->created_at,
                    ];

                    if (is_null($existiting)) {
                        $count['ins']++;

                    } else {
                        $count['upd']++;
                    }

                    $upsertData[] = $data;

                    $bar->setMessage($count['tins'], 'tins');
                    $bar->setMessage($count['ins'], 'ins');
                    $bar->setMessage($count['upd'], 'upd');
                    $bar->advance();
                }



                if (!empty($upsertData)) {
                    Protest::upsert($upsertData, ['nota'], [
                       'tipoNota',
                        'codecodf',
                        'txtGrpCodificacao',
                        'dtAberturaNota',
                        'dtConclusaoDesej',
                        'cenPlan',
                        'cidade',
                        'statUsuar',
                        'descCausa',
                        'descSubCausa',
                        'updated_at',
                        'created_at'
                    ]);
                }


                $count['tins']++;

            });



        $this->info('Atualização concluída com sucesso!');
        // Retorne 0 para indicar sucesso
        return 0;
    }
}
