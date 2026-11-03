<?php

namespace App\Http\Livewire\Services\Concerns;

use App\Custom\RuleBuilder;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Base das listas de Serviços "pegue a nota para você" (Incorporação, Análise (inc) e Cadastro): busca, filtro de Rubrica
 * (WithListFilters, na URL), atribuição da Nota/OV ao próprio usuário e o cabeçalho em cache curto.
 * Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md.
 *
 * O componente (que também usa `WithPagination`) só declara: colunas da busca, regra do filtro de Rubrica, se grava a linha do tempo
 * e como cada Nota vira linha (`rowFor`).
 */
trait IsActivityNoteList
{
    use WithListFilters;

    public $service;

    public $perPage = 100;

    public $search = '';

    public $note;

    public $last_update;

    /** Método (e não propriedade): o trait não redeclara `$listeners` do Livewire\Component. */
    protected function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'refresh_service'   => '$refresh',
            'getCopy'           => 'copy',
            'confirm_accompany' => 'add_to_accompany',
        ]);
    }

    /** Método (e não propriedade): o trait não redeclara `$queryString` do Livewire\Component. */
    protected function queryString(): array
    {
        return [
            'search'  => ['except' => '', 'as' => 'buscar'],
            'filters' => ['except' => [], 'as' => 'f'],
        ];
    }

    /** Colunas da Nota consultadas pela busca simples. */
    protected function searchColumns(): array
    {
        return ['note', 'material', 'numPedido'];
    }

    /** Rubrica marcada também traz as notas sem rubrica (regra da Análise (inc)). */
    protected function rubricaIncludesNull(): bool
    {
        return false;
    }

    /** Grava a linha do tempo da Nota ao assumir (o Cadastro nunca gravou). */
    protected function recordsTimeline(): bool
    {
        return true;
    }

    /** Uma Nota vira a linha da tela (estado, tons e textos já prontos; a view só desenha). */
    abstract protected function rowFor(Note $note): array;

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('ops-last-note-status-date', 60, fn () => Note::query()->max('dt_status'));
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return ['rubrica' => ['label' => 'Rubrica']];
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->where('nstats', $this->service->status)
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . $this->service->uuid;
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || filled($this->search);
    }

    /** "Limpar tudo": filtros e busca. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search = '';
        $this->afterListFiltersChanged();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Consulta ----------

    protected function listQuery(): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $this->service->Status);

        $search = trim((string) $this->search);
        $query->when($search !== '', function ($q) use ($search) {
            return $q->where(function ($query) use ($search) {
                foreach ($this->searchColumns() as $column) {
                    $query->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        });

        $rubricas = $this->filterValues('rubrica');
        $query->when($rubricas, function ($q) use ($rubricas) {
            return $q->where(function ($query) use ($rubricas) {
                $query->whereIn('rubrica', $rubricas);

                if ($this->rubricaIncludesNull()) {
                    $query->orWhereNull('rubrica');
                }
            });
        });

        return $query
            ->with('Productions.User')
            ->orderBy('pze_parecer', 'DESC')
            ->orderBy('dt_created');
    }

    public function getListsProperty()
    {
        return $this->listQuery()->paginate($this->perPage);
    }

    /** Dados comuns do render: página, linhas prontas e cabeçalho. */
    protected function listViewData(): array
    {
        $lists = $this->lists;

        return [
            'lists'     => $lists,
            'rows'      => $lists->getCollection()->map(fn (Note $note) => $this->rowFor($note))->values(),
            'updatedAt' => Cache::remember('ops-bancoupdate-exists', 60, fn () => Bancoupdate::query()->exists()) ? $this->last_update : null,
        ];
    }

    // ---------- Assumir a Nota/OV ----------

    public function to_accompany(Note $note)
    {
        $this->note = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Atribuir Tarefa',
            'msg'   => "
            Você deseja atribuir a NOTA/OV para você?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p><strong>NOTA/OV estará disponível em acompanhamento como
            sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',
        ]);
    }

    public function add_to_accompany()
    {
        $user = User::find(Auth()->User()->id);

        $check = Production::where('note_id', $this->note->id)->where(function ($q) {
            return $q->where('completed', false)
                ->orWhere('dt_note', $this->note->dt_status);
        })->with('User', 'Company', 'Service')->first();

        if ($check) {
            $name = $check->User?->name ?? ($check->Company ? "{$check->Company->name} (sem usuário atribuído)" : 'Desconhecido');

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO',
                'html'     => "<strong>{$this->note->note}</strong> foi ou está em Tratamento em {$check->Service->service} por <strong>{$name}</strong>",
            ]);

            return;
        }

        $production = Production::Create([
            'note_id'     => $this->note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $this->note->dt_status,
            'status_note' => $this->note->nstats,
            'dispatch_at' => date('Y-m-d H:i:s'),
            'att_at'      => date('Y-m-d H:i:s'),
            'status'      => 2,
            'dhstats'     => $this->note->dt_status,
        ]);

        if ($production) {
            if ($this->recordsTimeline()) {
                Notetimeline::Create([
                    'note_id'       => $this->note->id,
                    'service_id'    => $production->service_id,
                    'user_id'       => Auth()->User()->id,
                    'info'          => "Usuário {$user->name} atribuiu a Nota/OV.",
                    'status'        => 2,
                    'production_id' => $production->id,
                ]);
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => "{$this->note->note} foi atribuído a você com sucesso.",
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "Erro ao tentar atribuir {$this->note->note}.",
                'timer'    => 2500,
            ]);
        }
    }

    // ---------- Apresentação (regras de tom, nunca na view) ----------

    /** "Fulano S" (primeiro nome e inicial do último), como a coluna de ação sempre mostrou. */
    protected function shortAssigneeName(?Production $production): string
    {
        $name = $production?->User?->name;

        if (!$name) {
            return 'DESCONHECIDO';
        }

        $parts = explode(' ', $name);

        return $parts[0] . ' ' . substr(end($parts), 0, 1);
    }

    /** Situação do prazo do SAP (`pze_parecer`): selo pronto. */
    protected function parecerBadge(?string $parecer): array
    {
        return match ($parecer) {
            'Vencido'     => ['tone' => 'red', 'label' => 'Vencido', 'hint' => 'Parecer do SAP: prazo vencido.'],
            'Não vencido' => ['tone' => 'green', 'label' => 'Em prazo', 'hint' => 'Parecer do SAP: dentro do prazo.'],
            default       => ['tone' => 'muted', 'label' => 'Desconhecido', 'hint' => 'O SAP não informou o parecer do prazo.'],
        };
    }

    /** Prazo real: vencido (negativo) neutro; até 5 dias vermelho; 6 a 9 âmbar; 10 ou mais verde. */
    protected function prazoRealTone(?int $daysLeft): string
    {
        return match (true) {
            $daysLeft === null => 'muted',
            $daysLeft < 0      => 'muted',
            $daysLeft < 6      => 'red',
            $daysLeft < 10     => 'amber',
            default            => 'green',
        };
    }

    protected function prazoRealHint(?int $daysLeft): string
    {
        $base = 'Dias do prazo real, já sem os tempos em status não contabilizáveis. ';

        return $base . match (true) {
            $daysLeft === null => 'Sem prazo informado.',
            $daysLeft < 0      => 'Prazo vencido.',
            default            => $daysLeft . ($daysLeft === 1 ? ' dia' : ' dias') . ' para vencer',
        } . ' (neutro vencido, vermelho até 5 dias, âmbar de 6 a 9, verde a partir de 10).';
    }

    protected function createdAge(Note $note): ?int
    {
        return $note->dt_created ? (int) Carbon::now()->diffInDays(Carbon::parse($note->dt_created)->startOfDay()) : null;
    }
}
