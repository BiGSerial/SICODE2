<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseProtest;
use App\Models\Protest;
use Illuminate\Console\Command;
use Throwable;

class ProtestUpd extends Command
{
    use ShowsProgress;

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
        $log = null;

        try {
        $this->info('Iniciando atualização dos protestos no sistema SICODE...');

        $log = new RegistroJson('upd_protest', $this->options());
        $count = ['ins' => 0, 'upd' => 0, 'tins' => 1, 'errors' => 0];

        // Pega apenas o último registro por nota (maior dtCriacaoMedida)
        $baseQuery = BaseProtest::query()
            ->from('tbld_usr_baseReclamacoes as t')
            ->whereIn('id', function ($query) {
                $query->select('id')
                    ->from('tbld_usr_baseReclamacoes as sub')
                    ->whereColumn('sub.nota', 't.nota')
                    ->orderByDesc('sub.dtCriacaoMedida')
                    ->limit(1); // vira TOP 1 no SQL Server
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
                't.descricao',
            ]);

        $total = $baseQuery->count();
        $log->setTotal($total);

        $bar = $this->createProgressBar($total);
        $bar->setFormat(
            '<bg=blue;fg=white>UPDATE PROTEST LIST: %current%/%max% </>' .
            '<fg=white;options=bold> [T: %tins%][I: %ins%/U: %upd%] </>' .
            '<fg=green>[%bar%]</> <fg=white;options=bold> %percent%%</> ' .
            '<bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%'
        );

        $bar->setMessage('Starting', 'message');
        $bar->setMessage('0', 'tins');
        $bar->setMessage('0', 'ins');
        $bar->setMessage('0', 'upd');
        $bar->start();

        $baseQuery
            ->orderBy('id')
            ->chunk(2000, function ($protests) use ($bar, &$count) {
                // todas as notas do chunk
                $notas = $protests->pluck('nota')->unique()->values();

                // busca o que já existe no banco
                $existingNotes = Protest::whereIn('nota', $notas)->get()->keyBy('nota');

                $upsertData = [];

                foreach ($protests as $record) {
                    $existing = $existingNotes->get($record->nota);

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
                        'descricao'          => $record->descricao,

                        'updated_at'         => now(),
                        // preserva o created_at se já existir, senão usa agora
                        'created_at'         => $existing?->created_at ?? now(),
                    ];

                    // apenas pra estatística
                    if ($existing === null) {
                        $count['ins']++;
                    } else {
                        $count['upd']++;
                    }

                    $upsertData[] = $data;

                    $bar->setMessage((string) $count['tins'], 'tins');
                    $bar->setMessage((string) $count['ins'], 'ins');
                    $bar->setMessage((string) $count['upd'], 'upd');
                    $bar->advance();
                }

                if (!empty($upsertData)) {
                    Protest::upsert(
                        $upsertData,
                        ['nota'], // chave única
                        [
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
                            'descricao',
                            'updated_at',
                            'created_at',
                        ]
                    );
                }

                $count['tins']++;
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Atualização concluída com sucesso!');
        $this->info("Inseridos: {$count['ins']} | Atualizados: {$count['upd']}");
        $log->setCreated($count['ins']);
        $log->setUpdated($count['upd']);
        $log->save();

        return 0;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
