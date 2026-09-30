<?php

namespace App\Http\Livewire\Quality;

use App\Models\{File, QualityProcess, QualityStageFile};
use App\Support\QualityUi;
use Livewire\Component;

/** Galeria de arquivos da obra: pré-visualização, filtros por tipo, busca e download individual ou em .zip. */
class ProcessFiles extends Component
{
    public int $processId;

    public string $kind = 'todos';

    public string $search = '';

    public int $limit = 24;

    public function mount(int $processId): void
    {
        $this->processId = $processId;
        $this->process();
    }

    private function process(): QualityProcess
    {
        $process = QualityProcess::findOrFail($this->processId);
        abort_unless(auth()->user()->can('view', $process), 403);

        return $process;
    }

    public function setKind(string $kind): void
    {
        $this->kind  = $kind;
        $this->limit = 24;
    }

    public function updatedSearch(): void
    {
        $this->limit = 24;
    }

    public function loadMore(): void
    {
        $this->limit += 24;
    }

    public function render()
    {
        $process = $this->process();
        $files   = File::query()->with('User')->where('note_id', $process->note_id)->orderByDesc('created_at')->orderByDesc('id')->get();

        // arquivos enviados em cada rodada (ciclo/rodada) para marcar na galeria
        $submitted = QualityStageFile::query()->with('Stage')->whereHas('Stage', fn ($s) => $s->where('quality_process_id', $process->id))->get()->keyBy('file_id');

        $items = $files->map(function (File $file) use ($submitted) {
            $kind  = QualityUi::fileKind($file->ext ?: pathinfo((string) $file->file_name, PATHINFO_EXTENSION));
            $stage = $submitted->get($file->id)?->Stage;

            return (object) [
                'file' => $file, 'kind' => $kind,
                'name' => $file->original_name ?: $file->file_name,
                'tag'  => $stage ? $stage->type->short() . ' · rodada ' . $stage->round_number : null,
            ];
        });

        $counts   = $items->groupBy(fn ($item) => $item->kind['key'])->map->count();
        $term     = mb_strtolower(trim($this->search));
        $filtered = $items->when($this->kind !== 'todos', fn ($c) => $c->where('kind.key', $this->kind))
            ->when($term !== '', fn ($c) => $c->filter(fn ($item) => str_contains(mb_strtolower($item->name), $term)));

        return view('livewire.quality.process-files', [
            'process' => $process,
            'items'   => $filtered->take($this->limit),
            'total'   => $filtered->count(),
            'all'     => $items->count(),
            'counts'  => $counts,
            'kinds'   => ['image' => 'Imagens', 'pdf' => 'PDF', 'cad' => 'CAD', 'sheet' => 'Planilhas', 'doc' => 'Documentos', 'other' => 'Outros'],
        ]);
    }
}
