<?php

namespace App\Http\Livewire\Quality;

use App\Enum\QualityEventType;
use App\Models\QualityProcess;
use Livewire\Component;

/** Histórico do processo: agrupado por dia, filtrável e paginado ("carregar mais"), com atualização por polling. */
class ProcessTimeline extends Component
{
    public int $processId;

    public string $filter = 'todos';

    public int $limit = 12;

    public function mount(int $processId): void
    {
        $this->processId = $processId;
        $this->process();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['todos', 'decisoes', 'fluxo'], true) ? $filter : 'todos';
        $this->limit  = 12;
    }

    public function loadMore(): void
    {
        $this->limit += 12;
    }

    private function process(): QualityProcess
    {
        $process = QualityProcess::findOrFail($this->processId);
        abort_unless(auth()->user()->can('view', $process), 403);

        return $process;
    }

    public function render()
    {
        $process = $this->process();
        $seesN2  = auth()->user()->can('quality.n2');

        $query = $process->Events()->reorder()->with(['Actor', 'Target'])
            ->where('type', '!=', QualityEventType::COMMENT_ADDED->value)
            ->when(!$seesN2, fn ($q) => $q->where('type', 'not like', 'SAP_%')->where('type', '!=', QualityEventType::PRODUCTION_CLOSED->value))
            ->when($this->filter !== 'todos', fn ($q) => $q->whereIn('type', collect(QualityEventType::cases())->filter(fn ($type) => $type->group() === $this->filter)->map->value->all()))
            ->orderByDesc('created_at')->orderByDesc('id');

        $total  = (clone $query)->count();
        $events = $query->limit($this->limit)->get();

        return view('livewire.quality.process-timeline', [
            'days'    => $events->groupBy(fn ($event) => $event->created_at->format('Y-m-d')),
            'total'   => $total,
            'shown'   => $events->count(),
            'process' => $process,
        ]);
    }
}
