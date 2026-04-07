<?php

namespace App\Jobs\Construction;

use App\Exports\Viability\HistoricReport;
use App\Models\Note;
use App\Models\User;
use App\Models\Viability;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExportHistHiringJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var array<string,mixed> */
    public array $params;
    public string $userId;

    public $tries   = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200; // 20 min

    public function __construct(array $params, string $userId)
    {
        $this->onQueue('exports');
        $this->params = $params;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $query = Viability::query()->where('hired', true);

            $dateBy = $this->params['dateBy'] ?? 'sended_at';
            $dateIn = $this->params['date_in'] ?? null;
            $dateOut = $this->params['date_out'] ?? null;

            if ($dateBy && ($dateIn || $dateOut)) {
                if ($dateIn && !$dateOut) {
                    $query->whereDate($dateBy, '>=', $dateIn);
                }

                if (!$dateIn && $dateOut) {
                    $query->whereDate($dateBy, '<=', $dateOut);
                }

                if ($dateIn && $dateOut) {
                    $query->whereBetween($dateBy, [$dateIn, $dateOut]);
                }
            }

            if (!empty($this->params['hasNoHired'])) {
                $query->whereHas('Note.Orders', function ($o) {
                    $o->whereRaw("LTRIM(statusSist) NOT LIKE 'ENT%'")
                        ->whereRaw("LTRIM(statusSist) NOT LIKE 'ENC%'")
                        ->whereRaw("LTRIM(statusSist) NOT LIKE 'CANCE%'")
                        ->whereHas('Operations', function ($op) {
                            $op->where('operacao', '0010')
                                ->where('status', 'NOT LIKE', 'CONF%');
                        });
                });
            }

            $multipleSearch = $this->params['multipleSearch'] ?? [];
            if (!empty($multipleSearch)) {
                $query->whereRelation('Note', function ($q) use ($multipleSearch) {
                    $q->whereIn('note', $multipleSearch)
                        ->orWhereHas('orders', function ($q) use ($multipleSearch) {
                            $q->whereIn('ordem', $multipleSearch);
                        });
                });
            }

            $search = $this->params['search'] ?? null;
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereRelation('Note', 'note', 'like', '%' . $search . '%')
                        ->orWhereRelation('Note.Orders', 'ordem', 'like', '%' . $search . '%');
                });
            }

            $filters = $this->params['filter'] ?? [];
            if (!empty($filters['rubrica'])) {
                $query->whereRelation('Note', function ($q) use ($filters) {
                    $q->whereIn('rubrica', $filters['rubrica']);
                });
            }

            if (!empty($filters['city'])) {
                $query->whereIn('lexp', $filters['city']);
            }

            $data = $query
                ->with([
                    'Company',
                    'Justification',
                    'Note.Orders' => function ($q) {
                        $q->where(function ($w) {
                            $w->whereRaw("LTRIM(statusSist) NOT LIKE 'ENT%'")
                                ->whereRaw("LTRIM(statusSist) NOT LIKE 'ENC%'")
                                ->whereRaw("LTRIM(statusSist) NOT LIKE 'CANCE%'");
                        });
                    },
                    'Note.Orders.Operations',
                ])
                ->orderBy($dateBy, 'DESC')
                ->orderBy(
                    Note::select('note')
                        ->whereColumn('notes.id', 'viabilities.note_id'),
                    'ASC'
                )
                ->get();

            $stamp = now()->format('YmdHis');
            $filePath = "exports/{$stamp}_hist_hiring.xlsx";

            Storage::disk('local')->makeDirectory('exports');

            (new HistoricReport($data))->store($filePath, 'local');

            if ($user && Storage::disk('local')->exists($filePath)) {
                $user->notify(new SystemNotification(
                    'Exportacao concluida!',
                    'Historico de viabilidades pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            } else {
                throw new \RuntimeException('Arquivo nao foi gerado no disco esperado.');
            }
        } catch (Throwable $e) {
            Log::error('ExportHistHiringJob falhou', [
                'user_id' => $this->userId,
                'params'  => $this->params,
                'attempt' => $this->attempts(),
                'error'   => $e->getMessage(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('ExportHistHiringJob FAILED', [
            'user_id' => $this->userId,
            'error'   => $exception->getMessage(),
        ]);

        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Exportacao falhou',
                'A geracao do relatorio de historico falhou apos novas tentativas.',
                null,
                5,
                []
            ));
        }
    }
}
