<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\{Bancoupdate, HistoricNote, Note};
use App\Models\Edp_depc\BaseOV as Edp_depcBaseOV;
use Carbon\Carbon;
use Illuminate\Console\Command;
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

            // Fonte válida (última situação + filtros de prazo). O registro de cada OV é sempre buscado aqui,
            // sem janela, para que uma divergência fora da janela também seja corrigida.
            $baseQuery = Edp_depcBaseOV::where('ultimoStatus', 1)
                ->when($this->option('prazos'), function ($q) {
                    $q->where('numStat', '<', 98)
                        ->where(function ($query) {
                            $query
                                ->whereNull('considerarPrazo')
                                ->orWhereRaw(
                                    "LOWER(LTRIM(RTRIM(considerarPrazo))) <> ?",
                                    ['expurgar']
                                );
                        });
                });

            // Seleção de OVs: no --full todas; senão, a janela do contador da origem (diasNoStatus)
            // somada às OVs cujo numStat/diasPVencimento divergem de nstats/days_left, em qualquer situação.
            $ovList = $this->option('full')
                ? (clone $baseQuery)->distinct()->orderBy('OV')->pluck('OV')
                : (clone $baseQuery)->where('diasNoStatus', '<=', $days)->distinct()->pluck('OV')
                    ->merge($this->divergentOvs($baseQuery, $chunkSize))
                    ->unique()
                    ->sort()
                    ->values();
            $ovList = $ovList->filter()->values();

            // A barra avança por OV distinta, não por linha da origem.
            $total = $ovList->count();
            $log->setTotal($total);

            $this->info("Starting BaseOV data transfer...(Source daysNoStatus <= {$days} + divergent nstats/days_left)");
            $this->info('');

            // ProgressBar com tempo restante
            $bar = $this->createProgressBar($total);
            $bar->setFormat("%current%/%max% [%tins%][I: %ins%/U: %upd%] [%bar%] %percent%% (ETA: %remaining%)");
            $bar->setMessage('start', 'message');
            $bar->start();

            foreach ($ovList->chunk($chunkSize) as $ovChunk) {
                $ovList = $ovChunk->values();

                // Uma OV pode aparecer em mais de uma linha na origem. A consulta
                // vem ordenada pela situação mais recente; se ela não trouxer
                // prazo, aproveitamos a ocorrência mais recente que o possua.
                $recordsByOv = (clone $baseQuery)
                    ->whereIn('OV', $ovList)
                    ->orderByDesc('dhStat')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('OV');

                $existingNotes = Note::whereIn('note', $ovList)->get()->keyBy('note');

                foreach ($ovList as $ov) {
                    $sourceRecords = $recordsByOv->get($ov, collect());
                    $record        = $sourceRecords->first();

                    if ($record && !$this->hasSourceValue($record->diasPVencimento)) {
                        $record = $sourceRecords->first(
                            fn ($candidate) => $this->hasSourceValue($candidate->diasPVencimento)
                        ) ?? $record;
                    }

                    if (!$record) {
                        $bar->advance();

                        continue;
                    }

                    $existing = $existingNotes->get($ov);

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

                    if (!$shouldUpdate) {
                        $bar->setMessage($count['tins'], 'tins');
                        $bar->setMessage($count['ins'], 'ins');
                        $bar->setMessage($count['upd'], 'upd');
                        $bar->advance();

                        continue;
                    }

                    if ($existing && $existing->nstats != $record->numStat) {
                        HistoricNote::create([
                            'note_id'  => $existing->id,
                            'old_date' => $existing->dt_status,
                            'old_stat' => $existing->nstats,
                            'new_date' => $record->dhStat,
                            'new_stat' => $record->numStat,
                        ]);
                    }

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
                        'lexp'          => $record->localExp ?? $existing?->lexp,
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
                        // Não substitui um valor existente quando a origem vier vazia.
                        $data = array_filter($data, fn ($value) => $this->hasSourceValue($value));
                        $existing->update($data);
                        $count['upd']++;
                    } else {
                        $model = Note::create(array_merge(['note' => $ov], $data));
                        $existingNotes->put($ov, $model);
                        $count['ins']++;
                    }

                    $bar->setMessage($count['tins'], 'tins');
                    $bar->setMessage($count['ins'], 'ins');
                    $bar->setMessage($count['upd'], 'upd');
                    $bar->advance();
                }

                $count['tins']++;
            }

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

            $this->info('Data transfer completed: ' . ($count['ins'] + $count['upd']) . ' records processed.');
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * OVs cujo numStat/diasPVencimento da origem diferem de nstats/days_left da Note,
     * independentemente da janela de dias.
     */
    private function divergentOvs($baseQuery, int $chunkSize)
    {
        $divergent = collect();

        $this->info('Checking divergent nstats/days_left across the whole source...');
        $scanBar = $this->createProgressBar((clone $baseQuery)->distinct()->count('OV'));
        $scanBar->setFormat('%current%/%max% [%bar%] %percent%% (ETA: %remaining%) divergent: %divergent%');
        $scanBar->setMessage('0', 'divergent');
        $scanBar->start();

        (clone $baseQuery)->select('OV')->distinct()->orderBy('OV')
            ->chunk($chunkSize, function ($ovRows) use ($baseQuery, &$divergent, $scanBar) {
                $ovs = $ovRows->pluck('OV')->filter()->unique()->values();

                $sourceByOv = (clone $baseQuery)
                    ->whereIn('OV', $ovs)
                    ->orderByDesc('dhStat')
                    ->orderByDesc('id')
                    ->get(['OV', 'numStat', 'diasPVencimento'])
                    ->groupBy('OV');

                $notes = Note::whereIn('note', $ovs)->get(['note', 'nstats', 'days_left'])->keyBy('note');

                foreach ($ovs as $ov) {
                    $note = $notes->get($ov);
                    $rows = $sourceByOv->get($ov);

                    if (!$note || !$rows) {
                        continue;
                    }

                    $latest   = $rows->first();
                    $withDays = $rows->first(fn ($row) => $this->hasSourceValue($row->diasPVencimento));

                    if ($this->valuesDiffer($note->nstats, $latest->numStat)
                        || ($withDays && $this->valuesDiffer($note->days_left, $withDays->diasPVencimento))) {
                        $divergent->push($ov);
                    }
                }

                $scanBar->setMessage((string) $divergent->count(), 'divergent');
                $scanBar->advance($ovs->count());
            });

        $scanBar->finish();
        $this->info('');

        return $divergent;
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
            $sourceDate      = Carbon::parse($source);
            $destinationDate = $this->hasSourceValue($destination)
                ? Carbon::parse($destination)
                : null;

            return $destinationDate === null || $sourceDate->isAfter($destinationDate);
        } catch (Throwable) {
            return false;
        }
    }
}
