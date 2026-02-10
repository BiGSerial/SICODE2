<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseEP, BaseOV};
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class ConfirmProd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:confirm_prod {--days=1 : Number of days to check for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Status Change Note with Production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $log = null;

        try {
            $expirationDays = (int) $this->option('days');
            $manualDays = 5;
            $now = Carbon::now();
            $nowFormatted = $now->toDateTimeString();

            $this->info('CHECKING PRODS COMPLETEDS FROM BASE ... ');

            $productions = Production::query()
                ->where('completed', true)
                ->where('noinconsistency', false)
                ->where('confirmed', false)
                ->with(['Note', 'Service', 'User'])
                ->get();

            $log = new RegistroJson('confirm_prod', $this->option());
            $log->setTotal($productions->count());

            if ($productions->isEmpty()) {
                $log->setUpdated(0);
                $log->save();

                return self::SUCCESS;
            }

            $this->info('INITIALIZING UPDATE... ' . $productions->count());

            $ovProductions = $productions->filter(fn ($p) => optional($p->Note)->type_note == 2)->values();
            $noteProductions = $productions->filter(fn ($p) => optional($p->Note)->type_note == 1)->values();

            $ovNotes = $ovProductions->pluck('Note.note')->filter()->unique()->values();
            $noteNumbers = $noteProductions->pluck('Note.note')->filter()->unique()->values();

            $baseOvByOv = BaseOV::query()
                ->select(['OV', 'transicao', 'dhStat'])
                ->whereIn('OV', $ovNotes)
                ->orderBy('dhStat', 'DESC')
                ->get()
                ->groupBy('OV');

            $baseEpByNote = BaseEP::query()
                ->whereIn('nota', $noteNumbers)
                ->get()
                ->keyBy('nota');

            $confirmByExpiration = [];
            $manualConfirm = [];
            $noInconsistency = [];
            $confirmByMatch = [];

            foreach ($productions as $production) {
                $completedAt = $production->completed_at ? Carbon::parse($production->completed_at) : null;

                if ($completedAt) {
                    $daysFromCompletion = $completedAt->diffInDays($now);

                    if ($daysFromCompletion >= $expirationDays) {
                        $confirmByExpiration[] = $production->id;

                        if (optional($production->User)->bypassprod) {
                            $noInconsistency[] = $production->id;
                        }

                        continue;
                    }

                    if ($daysFromCompletion >= $manualDays) {
                        $manualConfirm[] = $production->id;
                    }
                }

                if (optional($production->User)->bypassprod) {
                    $noInconsistency[] = $production->id;
                    $confirmByMatch[] = $production->id;
                    continue;
                }

                $note = optional($production->Note)->note;
                $typeNote = optional($production->Note)->type_note;

                if (!$note || !$typeNote) {
                    continue;
                }

                if ((int) $typeNote === 2) {
                    $registers = $baseOvByOv->get($note, collect());

                    if ($registers->isEmpty()) {
                        continue;
                    }

                    $transitionA = trim((string) $production->status_note);
                    $transitionB = trim((string) optional($production->Service)->status);
                    $completedAtCarbon = Carbon::parse($production->completed_at);

                    $matchFound = $registers->contains(function ($row) use ($transitionA, $transitionB, $completedAtCarbon) {
                        $transition = (string) $row->transicao;
                        $validTransition = str_starts_with($transition, $transitionA . ' para')
                            || str_starts_with($transition, $transitionB . ' para');

                        if (!$validTransition) {
                            return false;
                        }

                        $dhStat = Carbon::parse($row->dhStat);
                        return $completedAtCarbon->diffInDays($dhStat) <= 2;
                    });

                    if ($matchFound) {
                        $noInconsistency[] = $production->id;
                        $confirmByMatch[] = $production->id;
                    }
                }

                if ((int) $typeNote === 1) {
                    $verificar = $baseEpByNote->get($note);

                    if (
                        ($verificar && ($verificar->statusUsuario && $production->status_note))
                        || (($verificar && $production->centroTrab) && ($production->centroTrab != $verificar->cenTrabResp))
                    ) {
                        $noInconsistency[] = $production->id;
                        $confirmByMatch[] = $production->id;
                    }
                }
            }

            $noInconsistency = array_values(array_unique($noInconsistency));
            $confirmByExpiration = array_values(array_unique($confirmByExpiration));
            $confirmByMatch = array_values(array_unique($confirmByMatch));
            $manualConfirm = array_values(array_unique($manualConfirm));

            if ($manualConfirm) {
                Production::whereIn('id', $manualConfirm)->update(['conf_manual' => true]);
            }

            if ($noInconsistency) {
                Production::whereIn('id', $noInconsistency)->update(['noinconsistency' => true]);
            }

            $confirmIds = array_values(array_unique(array_merge($confirmByExpiration, $confirmByMatch)));
            if ($confirmIds) {
                Production::whereIn('id', $confirmIds)->update([
                    'confirmed' => true,
                    'confirmed_at' => $nowFormatted,
                ]);
            }

            $updatedCount = count($manualConfirm) + count($noInconsistency) + count($confirmIds);

            $this->info('FINISHED CHECK... ' . Production::where('completed', true)->where('confirmed', false)->count());

            $log->setUpdated($updatedCount);
            $log->save();

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
