<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\{Bancoupdate, HistoricNote, Note};
use App\Models\Edp_depc\BaseOV;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class FixPrazos extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix-prazos {--full} {--chk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tries fix days left general days notes';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        system('clear');

        $log = null;

        try {
            if ($this->option('chk')) {
                $log = new RegistroJson('fix_prazos_chk', $this->options());
                $count = $this->chk_prazos($log);
            } else {
                $log = new RegistroJson('fix_prazos', $this->options());
                $count = $this->update_base($log);

                Bancoupdate::create([
                    'last_update' => now(),
                    'error'       => $log->getErrors(),
                    'inserts'     => $count['ins'],
                    'updates'     => $count['upd'],
                    'info'        => 'Fix-Prazos',
                ]);

                Bancoupdate::whereDate('created_at', '<', now()->subDays(30))->delete();
            }

            $log->setTotal($count['total']);
            $log->setCreated($count['ins'] ?? 0);
            $log->setUpdated($count['upd'] ?? 0);
            $log->setNoteUpdated($count['ne'] ?? 0);
            $log->save();

            $this->info('');
            $this->info('<bg=green;fg=white> DONE </> Fix-Prazos finalizado.');
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->fail($e->getMessage());
            }

            throw $e;
        }

    }

    public function update_base(RegistroJson $log): array
    {
        $baseQuery = BaseOV::where('ultimoStatus', 1)->where('numStat', '<', 98);
        $total = (clone $baseQuery)->count();
        $log->setTotal($total);

        if (!$total) {
            $this->info('<bg=red;fg=yellow> FAIL </> <fg=yellow;options=underscore;options=bold> NO REGISTER ARE OUTDATED! </>');

            return ['total' => 0, 'ins' => 0, 'upd' => 0, 'err' => 0, 'ne' => 0, 'dif' => 0];
        }

        $progressBar = $this->createProgressBar($total);

        $progressBar->setFormat('<bg=blue;fg=white>%current%/%max% </> [%tins%][E: %err% / I: %ins% / U: %upd% / NE: %ne% / D: %dif%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        $count = ['total' => $total, 'ins' => 0, 'upd' => 0, 'err' => 0, 'ne' => 0, 'dif' => 0, 'tins' => 0];
        $now = now();
        $updateColumns = $this->noteUpdateColumns();

        $baseQuery->orderBy('id')->chunkById(1000, function ($origens) use ($progressBar, &$count, $now, $updateColumns, $log) {
            $count['tins']++;
            $chunkTotal = $origens->count();
            $origens = $origens->unique('OV')->values();

            $ovList = $origens->pluck('OV')->filter()->unique()->values();
            $existingNotes = Note::where('type_note', 2)->whereIn('note', $ovList)->get()->keyBy('note');

            $insertRows = [];
            $updateRows = [];
            $historicRows = [];

            foreach ($origens as $origem) {
                $existing = $existingNotes->get($origem->OV);
                $data = $this->baseOvToNoteData($origem, $existing?->lexp);

                if (!$existing) {
                    $insertRows[] = array_merge(['note' => $origem->OV], $data, [
                        'type_note'  => 2,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    continue;
                }

                if (!$this->option('full') && !$this->shouldUpdateExisting($existing, $origem)) {
                    $count['ne']++;
                    continue;
                }

                if ((int) $existing->nstats !== (int) $origem->numStat) {
                    $count['dif']++;
                    $historicRows[] = [
                        'note_id'    => $existing->id,
                        'old_date'   => $existing->dt_status,
                        'old_stat'   => $existing->nstats,
                        'new_date'   => $origem->dhStat,
                        'new_stat'   => $origem->numStat,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $updateRows[] = array_merge(['id' => $existing->id], $data, [
                    'type_note'  => 2,
                    'updated_at' => $now,
                ]);
            }

            try {
                DB::transaction(function () use ($insertRows, $updateRows, $historicRows, $updateColumns, &$count) {
                    if ($insertRows) {
                        DB::table('notes')->insert($insertRows);
                        $count['ins'] += count($insertRows);
                    }

                    if ($updateRows) {
                        DB::table('notes')->upsert($updateRows, ['id'], $updateColumns);
                        $count['upd'] += count($updateRows);
                    }

                    if ($historicRows) {
                        DB::table((new HistoricNote())->getTable())->insert($historicRows);
                    }
                });
            } catch (Throwable $e) {
                $count['err']++;
                $log->setErrorMessage($e->getMessage());
                $this->error("Erro durante a atualização: {$e->getMessage()}");
            }

            $progressBar->setMessage($count['tins'], 'tins');
            $progressBar->setMessage($count['err'], 'err');
            $progressBar->setMessage($count['ins'], 'ins');
            $progressBar->setMessage($count['upd'], 'upd');
            $progressBar->setMessage($count['ne'], 'ne');
            $progressBar->setMessage($count['dif'], 'dif');
            $progressBar->advance($chunkTotal);
        });

        $progressBar->finish();

        return $count;
    }

    public function chk_prazos(?RegistroJson $log = null): array
    {
        $notes = Note::where('type_note', 2);
        $total = (clone $notes)->count();

        if ($log) {
            $log->setTotal($total);
        }

        $progressBar = $this->createProgressBar($total);

        $progressBar->setFormat('<bg=blue;fg=white>%current%/%max% </> [%tins%][E: %err% / NE: %ne%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s%');

        $count = ['total' => $total, 'ins' => 0, 'upd' => 0, 'err' => 0, 'ne' => 0, 'tins' => 0];

        $progressBar->start();

        $notes->orderBy('id')->chunkById(1000, function ($destinos) use (&$count, $progressBar) {
            $count['tins']++;
            $origens = BaseOV::where('ultimoStatus', 1)->whereIn('OV', $destinos->pluck('note'))->get()->keyBy('OV');

            foreach ($destinos as $chk) {
                $origem = $origens->get($chk->note);

                if (
                    $origem
                    && $chk->days_left == $origem->diasPVencimento
                    && $chk->pze_parecer == $origem->parecerPrazo
                ) {
                    $count['ne']++;
                } else {
                    $count['err']++;
                }

                $progressBar->setMessage($count['tins'], 'tins');
                $progressBar->setMessage($count['err'], 'err');
                $progressBar->setMessage($count['ne'], 'ne');
                $progressBar->advance();
            }
        });

        $progressBar->finish();

        return $count;
    }

    private function shouldUpdateExisting(Note $note, BaseOV $baseOv): bool
    {
        if ($note->type_note != 2 || $note->nstats >= 98) {
            return false;
        }

        return $note->updated_at === null
            || Carbon::parse($note->updated_at)->lt(Carbon::today()->subHours(2))
            || (int) $note->nstats !== (int) $baseOv->numStat
            || !$this->sameDateTime($note->dt_status, $baseOv->dhStat)
            || (string) $note->days_left !== (string) $baseOv->diasPVencimento
            || (string) $note->pze_parecer !== (string) $baseOv->parecerPrazo;
    }

    private function sameDateTime($left, $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return Carbon::parse($left)->equalTo(Carbon::parse($right));
    }

    private function baseOvToNoteData(BaseOV $baseOv, ?string $currentLexp = null): array
    {
        return [
            'created_by'    => $baseOv->criadoPor,
            'dt_created'    => "{$baseOv->dtCriacao} {$baseOv->hrCriacao}",
            'dt_status'     => $baseOv->dhStat,
            'user'          => $baseOv->usuario,
            'value'         => $baseOv->valorLiq,
            'currency'      => $baseOv->moeda,
            'eq_venda'      => $baseOv->eqVenda,
            'numPedido'     => $baseOv->numPedido,
            'client'        => $baseOv->emissorOV,
            'group1'        => $baseOv->grpCliente1,
            'group2'        => $baseOv->grpCliente2,
            'group3'        => $baseOv->grpCliente3,
            'group4'        => $baseOv->grpCliente4,
            'group5'        => $baseOv->grpCliente5,
            'pze'           => $baseOv->PzE,
            'num_material'  => $baseOv->numMaterial,
            'material'      => $baseOv->material,
            'nexp'          => $baseOv->numExp,
            'lexp'          => $baseOv->localExp ?? $currentLexp,
            'pep'           => $baseOv->PEP,
            'nstats'        => $baseOv->numStat,
            'status'        => $baseOv->status,
            'days'          => $baseOv->dias,
            'transaction'   => $baseOv->transicao,
            'validar_prazo' => $baseOv->considerarPrazo,
            'rubrica'       => $baseOv->rubrica,
            'pze_tratado'   => $baseOv->PzETratado,
            'days_stat'     => $baseOv->diasNoStatus,
            'pze_parecer'   => $baseOv->parecerPrazo,
            'days_left'     => $baseOv->diasPVencimento,
        ];
    }

    private function noteUpdateColumns(): array
    {
        return [
            'created_by',
            'dt_created',
            'dt_status',
            'user',
            'value',
            'currency',
            'eq_venda',
            'numPedido',
            'client',
            'group1',
            'group2',
            'group3',
            'group4',
            'group5',
            'pze',
            'num_material',
            'material',
            'nexp',
            'lexp',
            'pep',
            'nstats',
            'status',
            'days',
            'transaction',
            'validar_prazo',
            'rubrica',
            'pze_tratado',
            'days_stat',
            'pze_parecer',
            'days_left',
            'type_note',
            'updated_at',
        ];
    }
}
