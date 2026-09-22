<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseOV as Edp_depcBaseOV;
use App\Models\{Bancoupdate, HistoricNote, Note};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BaseOV extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseov {--full} {--prazos} {--force} {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Table Notes with BaseOV SQL info';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $log = null;

        try {
        // Banner com fundo azul e texto branco
        $this->line('<options=bold;fg=white;bg=blue> BaseOV </>');

        $chunkSize = 500;

        $log   = new RegistroJson('upd_baseOV', $this->options());
        $count = ['ins' => 0, 'upd' => 0, 'tins' => 1, 'errors' => 0];

        $days = max(0, (int) $this->option('days'));

        // O recorte operacional deve ser feito pelo contador da origem
        // (diasNoStatus / days_stat), e não por dhStat. Assim detectamos
        // alterações de days_left dentro da janela sem varrer toda a BaseOV.
        $baseQuery = Edp_depcBaseOV::where('ultimoStatus', 1)
            ->when(!$this->option('full'), fn ($q) => $q->where('diasNoStatus', '<=', $days))
            ->when($this->option('prazos'), fn ($q) => $q->where('numStat', '<', 98));

        $total = $baseQuery->count();
        $log->setTotal($total);

        $this->info("Starting BaseOV data transfer...(Source daysNoStatus <= {$days})");
        $this->info('');

        // ProgressBar com tempo restante
        $bar = $this->createProgressBar($total);
        $bar->setFormat("%current%/%max% [%tins%][I: %ins%/U: %upd%] [%bar%] %percent%% (ETA: %remaining%)");
        $bar->setMessage('start', 'message');
        $bar->start();

        // Process in chunks by ID for consistency
        $baseQuery->orderBy('id')->chunkById($chunkSize, function ($records) use ($bar, &$count) {
            // Unique OV list in this chunk
            $ovList = $records->pluck('OV')->unique()->values();
            // Fetch existing notes keyed by 'note'
            $existingNotes = Note::whereIn('note', $ovList)->get()->keyBy('note');

            foreach ($ovList as $ov) {
                $record   = $records->firstWhere('OV', $ov);
                $existing = $existingNotes->get($ov);

                // Determine if should update or create
                $nstatsDiverged = $existing
                    && $this->hasSourceValue($record->numStat)
                    && $this->valuesDiffer($existing->nstats, $record->numStat);
                $daysLeftDiverged = $existing
                    && $this->hasSourceValue($record->diasPVencimento)
                    && $this->valuesDiffer($existing->days_left, $record->diasPVencimento);

                $statusDateIsNewer = $existing
                    && $this->sourceDateIsNewer($record->dhStat, $existing->dt_status);

                $shouldUpdate = is_null($existing)
                    || $statusDateIsNewer
                    || $nstatsDiverged
                    || $daysLeftDiverged
                    || $this->option('full')
                    || $this->option('force');



                if (! $shouldUpdate) {
                    $bar->setMessage($count['tins'], 'tins');
                    $bar->setMessage($count['ins'], 'ins');
                    $bar->setMessage($count['upd'], 'upd');
                    $bar->advance();
                    continue;
                }




                // Create historic entry if status changed
                if ($existing && $existing->nstats != $record->numStat) {
                    HistoricNote::create([
                        'note_id'  => $existing->id,
                        'old_date' => $existing->dt_status,
                        'old_stat' => $existing->nstats,
                        'new_date' => $record->dhStat,
                        'new_stat' => $record->numStat,
                    ]);
                }

                // Update or create note
                $data = [
                    'created_by'    => $record->criadoPor,
                    'dt_created'    => "$record->dtCriacao $record->hrCriacao",
                    'dt_status'     => $record->dhStat,
                    'user'          => $record->usuario,
                    'value'         => $record->valorLiq,
                    'currency'      => $record->moeda,
                    'eq_venda'      => $record->eqVenda,
                    'numPedido'     => $record->numPedido,
                    'client'        => $record->emissorOV,
                    'group1'        => $record->grpCliente1,
                    'group2'        => $record->grpCliente2,
                    'group3'        => $record->grpCliente3,
                    'group4'        => $record->grpCliente4,
                    'group5'        => $record->grpCliente5,
                    'pze'           => $record->PzE,
                    'num_material'  => $record->numMaterial,
                    'material'      => $record->material,
                    'nexp'          => $record->numExp,
                    'lexp'          => $record->localExp ?? $existing->lexp,
                    'pep'           => $record->PEP,
                    'nstats'        => $record->numStat,
                    'status'        => $record->status,
                    'days'          => $record->dias,
                    'transaction'   => $record->transicao,
                    'validar_prazo' => $record->considerarPrazo,
                    'rubrica'       => $record->rubrica,
                    'pze_tratado'   => $record->PzETratado,
                    'days_stat'     => $record->diasNoStatus,
                    'pze_parecer'   => $record->parecerPrazo,
                    'days_left'     => $record->diasPVencimento,
                    'type_note'     => 2,
                ];

                if ($existing) {
                    // Nunca apaga valor existente quando a origem vier vazia.
                    $data = array_filter($data, fn ($value) => $this->hasSourceValue($value));
                    $existing->update($data);
                    $count['upd']++;
                } else {
                    $model = Note::create(array_merge(['note' => $ov], $data));
                    $existingNotes->put($ov, $model);
                    $count['ins']++;
                }

                // Advance progress
                $bar->setMessage($count['tins'], 'tins');
                $bar->setMessage($count['ins'], 'ins');
                $bar->setMessage($count['upd'], 'upd');
                $bar->advance();
            }

            $count['tins']++;
        });

        // Save log and summary
        $bar->finish();
        $log->setCreated($count['ins']);
        $log->setUpdated($count['upd']);
        $log->save();

        Bancoupdate::create([
            'last_update' => now(),
            'error'       => $log->getErrors(),
            'inserts'     => $count['ins'],
            'updates'     => $count['upd'],
        ]);

        Bancoupdate::whereDate('created_at', '<', now()->subDays(30))->delete();

        $this->info('Data transfer completed: '.($count['ins'] + $count['upd']).' records processed.');
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            throw $e;
        }
    }

    private function hasSourceValue($value): bool
    {
        return $value !== null
            && $value !== ''
            && !(is_string($value) && trim($value) === '');
    }

    private function valuesDiffer($destination, $source): bool
    {
        if (!$this->hasSourceValue($source)) {
            return false;
        }

        if (is_numeric($destination) && is_numeric($source)) {
            return abs((float) $destination - (float) $source) > 0.000001;
        }

        return trim((string) $destination) !== trim((string) $source);
    }

    private function sourceDateIsNewer($source, $destination): bool
    {
        if (!$this->hasSourceValue($source)) {
            return false;
        }

        try {
            $sourceDate = Carbon::parse($source);
            $destinationDate = $this->hasSourceValue($destination)
                ? Carbon::parse($destination)
                : null;

            return $destinationDate === null || $sourceDate->isAfter($destinationDate);
        } catch (Throwable) {
            return false;
        }
    }
}
