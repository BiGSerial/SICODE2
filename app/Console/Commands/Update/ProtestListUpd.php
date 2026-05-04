<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseProtest;
use App\Models\MedProtest;
use App\Models\Protest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProtestListUpd extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_protestList {--onlyMeda}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza a lista dos protestos no sistema SICODE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $log = null;

        try {
        $this->info('Iniciando atualização das Medidas de Protesto no sistema SICODE...');

        $log = new RegistroJson('upd_protest_list', $this->options());
        $count = ['ins' => 0, 'upd' => 0, 'tins' => 1, 'errors' => 0];

        $baseQuery = BaseProtest::query()->when(
            $this->option('onlyMeda'),
            fn ($query) => $query->where('statusSist', 'MEDA')
        )->orderBy('nota', 'ASC')->orderBy('numOrdenacao', 'ASC');

        $total = $baseQuery->count();
        $log->setTotal($total);

        $bar = $this->createProgressBar($total);
        $bar->setFormat(
            '<bg=blue;fg=white>UPDATE PROTEST MED LIST: %current%/%max% </>' .
            '<fg=white;options=bold> [%tins%][I: %ins%/U: %upd%] </>' .
            '<fg=green>[%bar%]</> <fg=white;options=bold> %percent%%</> ' .
            '<bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%'
        );

        $bar->setMessage('Iniciando...', 'message');
        $bar->start();

        $baseQuery->chunk(1000, function ($protests) use ($bar, &$count) {
            $notas = $protests->pluck('nota')->unique()->values();
            $existingProtests = Protest::whereIn('nota', $notas)->get()->keyBy('nota');

            $protestIds = $existingProtests->pluck('id');
            $existingMedProtests = MedProtest::whereIn('protest_id', $protestIds)
                ->get()
                ->keyBy(fn ($item) => $item->protest_id . '-' . $item->med_id);

            $upsertData = [];

            foreach ($protests as $record) {

                $protest = $existingProtests->get($record->nota);

                if (! $protest || is_null($record->numOrdenacao)) {
                    $count['errors']++;
                    $bar->advance();
                    continue;
                }

                $key = $protest->id . '-' . $record->numOrdenacao;
                $existing = $existingMedProtests->get($key);

                $data = [
                    'protest_id'         => $protest->id,
                    'med_id'             => $record->numOrdenacao,

                    'statusSist'         => $record->statusSist,
                    'statMedida'         => $record->statMedida,
                    'codMedida'          => $record->codMedida,
                    'txtCodCodificacao'  => $record->txtCodCodificacao,
                    'txtCodMedida'       => $record->txtCodMedida,
                    'dtCriacaoMedida'    => $record->dtCriacaoMedida,
                    'dtFimMedidaDesej'   => $record->dtFimMedidaDesej,
                    'dtFimMedida'        => $record->dtFimMedida,
                    'protest_type'       => $record->Rastreio,
                    'updated_at'         => now(),
                ];

                if (! $existing) {
                    $data['created_at'] = now();
                    $count['ins']++;
                } else {
                    $count['upd']++;
                }

                $upsertData[] = $data;

                // Atualiza visual do progress bar
                $bar->setMessage($count['tins'], 'tins');
                $bar->setMessage($count['ins'], 'ins');
                $bar->setMessage($count['upd'], 'upd');
                $bar->advance();
            }



            if (!empty($upsertData)) {

                MedProtest::upsert(
                    $upsertData,
                    ['protest_id', 'med_id'],
                    [
                        'statusSist',
                        'statMedida',
                        'codMedida',
                        'txtCodCodificacao',
                        'txtCodMedida',
                        'dtCriacaoMedida',
                        'dtFimMedidaDesej',
                        'dtFimMedida',
                        'updated_at',
                        'protest_type',
                    ]
                );
            }

            $count['tins']++;
        });

        $this->info("\nAtualização concluída com sucesso!");
        $log->setCreated($count['ins']);
        $log->setUpdated($count['upd']);
        if ($count['errors'] > 0) {
            $log->setErrorMessage("Erros durante processamento: {$count['errors']}");
        }
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
