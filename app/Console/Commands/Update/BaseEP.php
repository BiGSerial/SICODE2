<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseEP as Edp_depcBaseEP;
use App\Models\Edp_depc\Gpm;
use App\Models\Note;
use Carbon\Carbon;
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
    protected $description = 'Update Table Notes with BaseEP SQL info';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysAgo = Carbon::now()->subDays($this->option('days'));
        $chunkSize = $this->option('full') ? 1000 : 500;



        $log = new RegistroJson('upd_baseEP', $this->options());
        $count = ['ins' => 0, 'upd' => 0, 'tins' => 1, 'errors' => 0];

        // Base query, optionally limiting by date
        $baseQuery = Edp_depcBaseEP::query();


        $total = $baseQuery->count();
        $log->setTotal($total);

        $bar = new ProgressBar($this->output, $total);
        $bar->setFormat(
            '<bg=blue;fg=white>UPDATE BaseEP: %current%/%max% </>' .
            '<fg=white;options=bold> [%tins%][I: %ins%/U: %upd%] </>' .
            '<fg=green>[%bar%]</> <fg=white;options=bold> %percent%%</> ' .
            '<bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%'
        );

        $this->info("Starting BaseOV data transfer...(Using updating of {$this->option('days')} days ago)");
        $this->info("");
        $bar->setMessage('Starting', 'message');
        $bar->start();

        // Process in ID-based chunks
        $baseQuery->orderBy('id')->chunkById($chunkSize, function ($records) use ($bar, &$count) {
            $notas = $records->pluck('nota')->unique()->values();
            $existingNotes = Note::whereIn('note', $notas)->get()->keyBy('note');

            foreach ($notas as $nota) {
                $record   = $records->firstWhere('nota', $nota);
                $existing = $existingNotes->get($nota);

                // Verifica se é necessário atualizar ou criar
                $modified = is_null($existing)
                    || $this->option('full')
                    || $existing->created_by   !== $record->criadoPor
                    || Carbon::parse($existing->dt_created)->toDateString() !== Carbon::parse($record->dtNota)->toDateString()
                    || $existing->user         !== $record->notificador
                    || $existing->numPedido    !== $record->descricao
                    || $existing->pze           != ($record->PzE ?: null)
                    || $existing->num_material !== ($record->conjunto ?: null)
                    || $existing->material     !== ($record->denomConjunto ?: null)
                    || $existing->nstats       != $record->statusUsuario
                    || $existing->status       != $record->status
                    || $existing->centerjob    !== $record->cenTrabResp;

                if (! $modified) {
                    $bar->advance();
                    continue;
                }

                // Busca dados de cidade
                $city = Gpm::firstWhere('gpm', $record->grpPlan);

                // Prepara payload
                $data = [
                    'created_by'   => $record->criadoPor,
                    'dt_created'   => "{$record->dtNota} 00:00:00",
                    'dt_status'    => $existing ? now() : ($existing->dt_status ?? now()),
                    'user'         => $record->notificador,
                    'numPedido'    => $record->descricao,
                    'pze'          => $record->PzE !== '' ? $record->PzE : null,
                    'num_material' => $record->conjunto !== '' ? $record->conjunto : null,
                    'material'     => $record->denomConjunto !== '' ? $record->denomConjunto : null,
                    'nexp'         => $city->rdMunicipio ?? null,
                    'lexp'         => $city->cidade ?? null,
                    'nstats'       => $record->statusUsuario,
                    'status'       => $record->status,
                    'rubrica'      => $record->rubrica,
                    'centerjob'    => $record->cenTrabResp,
                    'type_note'    => 1,
                    'mesalization' => $record->mensalizacao,
                    'txpriority'   => $record->txtPrioridade,
                ];

                if ($existing) {
                    $existing->update($data);
                    $count['upd']++;
                } else {
                    $model = Note::create(array_merge(['note' => $nota], $data));
                    $existingNotes->put($nota, $model);
                    $count['ins']++;
                }

                $bar->setMessage($count['tins'], 'tins');
                $bar->setMessage($count['ins'], 'ins');
                $bar->setMessage($count['upd'], 'upd');
                $bar->advance();
            }

            $count['tins']++;
        });

        // Marca notas antigas como canceladas
        $stale = Carbon::now()->subDay();
        $cancelCount = Note::where('type_note', 1)
            ->where('updated_at', '<', $stale)
            ->update(['nstats' => 99]);

        $this->info("NOTAS CANCELADAS: {$cancelCount}");

        // Finaliza log
        $bar->finish();
        $log->setCreated($count['ins']);
        $log->setUpdated($count['upd']);
        $log->save();

        $this->info('Data transfer completed: ' . ($count['ins'] + $count['upd']) . ' processed.');

        return 0;
    }
}
