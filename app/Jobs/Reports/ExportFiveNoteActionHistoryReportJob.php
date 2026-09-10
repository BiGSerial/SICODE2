<?php

namespace App\Jobs\Reports;

use App\Exports\Reports\FiveNoteActionHistoryExport;
use App\Models\FiveNote;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportFiveNoteActionHistoryReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $filters;
    public string $userId;

    public $tries = 2;
    public $backoff = [30, 120];
    public int $timeout = 1200;

    public function __construct(array $filters, string $userId)
    {
        $this->onQueue('exports');
        $this->filters = $filters;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $filePath = null;

        try {
            $fiveNoteIdsQuery = $this->fiveNoteQuery()->select('five_notes.id');

            $query = TimelineEvent::query()
                ->select('timeline_events.*')
                ->join('five_notes as fn', 'fn.id', '=', 'timeline_events.five_note_id')
                ->leftJoin('notes as n', 'n.id', '=', 'timeline_events.note_id')
                ->whereIn('timeline_events.five_note_id', $fiveNoteIdsQuery)
                ->with([
                    'fiveNote.note:id,note',
                    'fiveNote.company:id,name',
                    'note:id,note',
                    'actor:id,name',
                    'owner:id,name',
                    'service:uuid,service',
                    'production:id',
                    'production.Analise:id,production_id,conclusion,info',
                ])
                ->orderByRaw('COALESCE(fn.note_d5, n.note, "") asc')
                ->orderBy('timeline_events.occurred_at')
                ->orderBy('timeline_events.id');

            $stamp = now()->format('YmdHis');
            $filePath = "exports/five_note_action_history_{$stamp}.xlsx";

            Storage::disk('local')->makeDirectory('exports');
            Excel::store(new FiveNoteActionHistoryExport($query), $filePath, 'local');

            if (!Storage::disk('local')->exists($filePath)) {
                throw new \RuntimeException('Arquivo nao foi gerado.');
            }

            if ($user) {
                $user->notify(new SystemNotification(
                    'Exportação - Histórico de Ações D5',
                    'Seu arquivo do histórico de ações D5 está pronto para download.',
                    Storage::url($filePath),
                    4,
                    []
                ));
            }
        } catch (Throwable $exception) {
            Log::error('ExportFiveNoteActionHistoryReportJob falhou', [
                'error_message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'filters' => $this->filters,
                'attempt' => $this->attempts(),
            ]);

            if ($filePath && Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($user = User::find($this->userId)) {
            $user->notify(new SystemNotification(
                'Erro ao gerar histórico de ações D5',
                "Ocorreu um erro ao gerar o arquivo.\n" . $exception->getMessage(),
                null,
                5,
                []
            ));
        }
    }

    protected function fiveNoteQuery(): Builder
    {
        $dispatchFrom = $this->asStartOfDay($this->filters['dispatch_from'] ?? null);
        $dispatchTo = $this->asEndOfDay($this->filters['dispatch_to'] ?? null);
        $companyId = isset($this->filters['company_id']) ? (string) $this->filters['company_id'] : '';
        $passiveMode = $this->filterScalar('passive_mode', 'both');
        $search = trim((string) ($this->filters['search'] ?? ''));
        $openOnly = $this->filterScalar('open_only', '0') === '1';
        $directTerms = $this->normalizeTerms($this->filters['direct_terms'] ?? []);

        if ($search !== '') {
            $directTerms[] = $search;
        }

        $directTerms = array_values(array_unique(array_filter($directTerms)));
        $hasDirectSearch = count($directTerms) > 0;

        $query = FiveNote::query();

        if ($hasDirectSearch) {
            return $query->where(function ($scope) use ($directTerms) {
                $scope->whereIn('note_d5', $directTerms)
                    ->orWhereHas('note', fn ($noteQuery) => $noteQuery->whereIn('note', $directTerms));
            });
        }

        if (!$openOnly) {
            $query->when($dispatchFrom, fn ($q) => $q->where('dispatch_at', '>=', $dispatchFrom))
                ->when($dispatchTo, fn ($q) => $q->where('dispatch_at', '<=', $dispatchTo));
        } else {
            $query->where('is_archived', false);
        }

        $query->when($companyId !== '', fn ($q) => $q->where('company_id', $companyId))
            ->when($passiveMode === 'passive', fn ($q) => $q->where('isPassive', true))
            ->when($passiveMode === 'meta', function ($q) {
                $q->where(function ($scope) {
                    $scope->whereNull('isPassive')->orWhere('isPassive', false);
                });
            });

        return $query;
    }

    protected function normalizeTerms($terms): array
    {
        if (!is_array($terms)) {
            $terms = preg_split('/[\s,;\n\r\t]+/', (string) $terms) ?: [];
        }

        return array_values(array_filter(array_map(function ($term) {
            return trim((string) $term);
        }, $terms), fn ($term) => $term !== ''));
    }

    protected function asStartOfDay(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date)->startOfDay() : null;
    }

    protected function asEndOfDay(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date)->endOfDay() : null;
    }

    protected function filterScalar(string $key, string $default = ''): string
    {
        $value = $this->filters[$key] ?? $default;

        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
