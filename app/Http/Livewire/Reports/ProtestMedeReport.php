<?php

namespace App\Http\Livewire\Reports;

use App\Enum\ProtestType;
use App\Jobs\Protests\ExportDispatcherMeasuresJob;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProtestMedeReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?string $dt_in = null;
    public ?string $dt_out = null;
    public ?string $userId = null;
    public array $protestTypes = [];
    public int $perPage = 25;

    public $usersOptions = [];
    public array $protestTypeOptions = [];

    public function mount(): void
    {
        $this->dt_in = now()->startOfMonth()->toDateString();
        $this->dt_out = now()->toDateString();

        $this->usersOptions = User::whereIn('id', function ($q) {
            $q->select('created_by')->from('protest_jobs')->whereNotNull('created_by');
        })
            ->orWhereIn('id', function ($q) {
                $q->select('owner_id')->from('protest_jobs')->whereNotNull('owner_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->protestTypeOptions = collect(ProtestType::cases())->map(function (ProtestType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
            ];
        })->values()->all();
    }

    public function clearFilters(): void
    {
        $this->dt_in = now()->startOfMonth()->toDateString();
        $this->dt_out = now()->toDateString();
        $this->userId = null;
        $this->protestTypes = [];
        $this->perPage = 25;
        $this->resetPage();
    }

    public function exportReport(): void
    {
        $start = $this->dt_in ? now()->parse($this->dt_in)->startOfDay() : now()->startOfMonth();
        $end = $this->dt_out ? now()->parse($this->dt_out)->endOfDay() : now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        ExportDispatcherMeasuresJob::dispatch([
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'userId' => $this->userId,
            'protestTypes' => $this->getSelectedProtestTypes(),
        ], (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>Seu relatório de reclamação está sendo gerado.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver pronto.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    protected function getSelectedProtestTypes(): array
    {
        return collect($this->protestTypes)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    private function range(): array
    {
        $start = $this->dt_in ? now()->parse($this->dt_in)->startOfDay() : now()->startOfMonth();
        $end = $this->dt_out ? now()->parse($this->dt_out)->endOfDay() : now()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function baseQuery(Carbon $start, Carbon $end)
    {
        $firstJobs = DB::table('protest_jobs')
            ->selectRaw('med_protest_id, MIN(id) as job_id')
            ->whereNotNull('created_by')
            ->groupBy('med_protest_id');

        return DB::table('med_protests')
            ->leftJoinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
            ->leftJoin('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id')
            ->leftJoin('users as dispatcher', 'dispatcher.id', '=', 'first_job.created_by')
            ->leftJoin('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->where('protests.tipoNota', 'NA')
                        ->whereBetween('protests.dtConclusaoDesej', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->where(function ($tipo) {
                        $tipo->where('protests.tipoNota', '!=', 'NA')
                            ->orWhereNull('protests.tipoNota');
                    })->whereBetween('med_protests.dtFimMedidaDesej', [$start, $end]);
                });
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('med_protests as mp2')
                    ->whereColumn('mp2.protest_id', 'med_protests.protest_id')
                    ->where('mp2.statusSist', 'MEDA');
            })
            ->when(!empty($this->getSelectedProtestTypes()), fn ($q) => $q->whereIn('med_protests.protest_type', $this->getSelectedProtestTypes()))
            ->when($this->userId, fn ($q) => $q->where('first_job.created_by', $this->userId))
            ->select([
                'med_protests.med_id',
                'med_protests.statusSist',
                'med_protests.statMedida',
                'med_protests.dtCriacaoMedida',
                'med_protests.dtFimMedidaDesej',
                'med_protests.dtFimMedida',
                'protests.nota as protest_nota',
                'protests.tipoNota as protest_tipo_nota',
                'protests.dtAberturaNota as protest_dt_abertura_nota',
                'protests.dtConclusaoDesej as protest_dt_conclusao_desej',
                'protests.statUsuar as protest_stat_usuar',
                'dispatcher.name as dispatcher_name',
            ])
            ->orderByDesc('med_protests.dtFimMedidaDesej');
    }

    public function render()
    {
        [$start, $end] = $this->range();
        $rows = $this->baseQuery($start, $end)->paginate($this->perPage);

        $rows->setCollection($rows->getCollection()->map(function ($row) {
            $dueBase = ((string) $row->protest_tipo_nota === 'NA')
                ? $row->protest_dt_conclusao_desej
                : $row->dtFimMedidaDesej;

            $isOnTime = false;
            if ($row->dtFimMedida && $dueBase) {
                $isOnTime = Carbon::parse($row->dtFimMedida)->toDateString() <= Carbon::parse($dueBase)->toDateString();
            }

            $row->due_base_fmt = $dueBase
                ? Carbon::parse($dueBase)->format('d/m/Y H:i')
                : '---';
            $row->protest_dt_abertura_nota_fmt = $row->protest_dt_abertura_nota
                ? Carbon::parse($row->protest_dt_abertura_nota)->format('d/m/Y H:i')
                : '---';
            $row->dt_criacao_medida_fmt = $row->dtCriacaoMedida
                ? Carbon::parse($row->dtCriacaoMedida)->format('d/m/Y H:i')
                : '---';
            $row->dt_fim_medida_fmt = $row->dtFimMedida
                ? Carbon::parse($row->dtFimMedida)->format('d/m/Y H:i')
                : '---';
            $row->due_base = $dueBase;
            $row->is_on_time = $isOnTime;
            return $row;
        }));

        return view('livewire.reports.protest-mede-report', [
            'rows' => $rows,
        ]);
    }
}
