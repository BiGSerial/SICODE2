<?php

namespace App\Http\Livewire\Services\Reverse\Accompany;

use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Models\{Note, Notetimeline, Production, Service, User};
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use BuildsLegalNoteTags;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $advanceSearch;

    public $multiSearch = [];

    public $rubrica_s = [];

    public $rubrica_l;

    public $limit_pause = 1000;

    public $analise;

    public $user_l;

    public $user_s;

    public $user_search;

    public $production;

    public $note;

    public $selectAll = false;

    public $selected = [];

    public $bulkConclusion;

    public $bulkMmgd;

    public $bulkIs45;

    public $bulkInfo;

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
        'confirm_finish_mass' => 'finishBulk',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function goTransferProd($prod_id)
    {
        $this->emit('transfer_production', $prod_id);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function checkOpen()
    {

        $check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first();

        if ($check) {

            $this->emit('open_analise_analise', ['productionId' => $check->id, 'noteId' => $check->note_id]);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                        adequada.
                    </p>
                ",
            ]);

        }

    }

    public function go_to_analise()
    {
        $this->emit('open_analise_analise', $this->analise);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_form',
        ]);
    }

    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        if ($this->limit_pause === Production::Where('status', 4)->Where('service_id', $this->service->uuid)->Where('user_id', Auth()->User()->id)->count() && (Production::find($production))->status != 4) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você ja atingiu o limite de pausa neste serviço, ao iniciar esta nota, você não poderá colocar esta NOTA/OV em espera. \n Tem certeza que deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
            $this->emit('open_analise_analise', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    public function filter_save()
    {
        $this->rubrica_s = array_values(array_filter($this->rubrica_s));
        $this->resetPage();
        $this->resetSelection();
    }

    public function visualizar()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function filter_clean()
    {
        $this->rubrica_s = [];
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function buscarMulti()
    {
        $this->multiSearch = collect(preg_split('/[\s,;\r\n\t]+/', (string) $this->advanceSearch))
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();

        $this->resetPage();
        $this->resetSelection();
        $this->dispatchBrowserEvent('hideModal');
    }

    public function clearMultiSearch()
    {
        $this->advanceSearch = null;
        $this->multiSearch = [];
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedUserS()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function setSelectAll()
    {
        $idsToKeep = $this->lists->pluck('id')->toArray();

        if ($this->selectAll) {
            foreach ($idsToKeep as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $idsToKeep)) {
                    $newSelected[] = $id;
                }
            }

            $this->selected = $newSelected;
        }
    }

    public function checkAllSelect($items)
    {
        $items = $items->pluck('id')->toArray();
        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
    }

    public function confirmBulkClose()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SELECIONE REGISTROS',
                'html'     => 'Selecione ao menos uma nota para encerrar em massa.',
            ]);

            return;
        }

        if (!$this->bulkConclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CONCLUSÃO NÃO DEFINIDA',
                'html'     => 'Informe a conclusão que será aplicada aos registros selecionados.',
            ]);

            return;
        }

        if (!$this->bulkMmgd) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORMAÇÃO OBRIGATÓRIA',
                'html'     => 'Obrigatório informar MMGD para encerrar em massa.',
            ]);

            return;
        }

        $count = count($this->selected);

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'ENCERRAMENTO EM MASSA',
            'msg'           => "Você está prestes encerrar <strong>{$count}</strong> registro(s).<br>
                Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.
                Uma vez encerrado, essa operação nao poderá ser desfeita.
                <h4 class='text-center mt-3'>DESEJA CONTINUAR?</h4>",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_finish_mass',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Ação Cancelada.',
        ]);
    }

    public function finishBulk()
    {
        $mmgd = $this->bulkMmgd === 'SIM';

        $productions = Production::whereIn('id', $this->selected)
            ->where('service_id', $this->service->uuid)
            ->where('completed', false)
            ->when($this->user_s, function ($query) {
                return $query->where('user_id', $this->user_s);
            }, function ($query) {
                return $query->where('user_id', Auth()->user()->id);
            })
            ->with('Note', 'Analise')
            ->get();

        if (!$productions->count()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM REGISTRO',
                'html'     => 'Nenhuma nota válida foi encontrada para encerrar.',
            ]);

            return;
        }

        DB::beginTransaction();

        try {
            $user = Auth()->user()->name;
            $totalSelected = $productions->count();
            $bulkSignature = "encerrado em massa - de um total de {$totalSelected} registros";

            foreach ($productions as $production) {
                $analise = $production->Analise()->first();

                if (!$analise) {
                    $analise = $production->Analise()->create();
                }

                $infoMessage = trim((string) $this->bulkInfo);
                $infoMessage = $infoMessage
                    ? "{$infoMessage} | {$bulkSignature}"
                    : $bulkSignature;

                $analise->update([
                    'conclusion' => $this->bulkConclusion,
                    'info'       => $infoMessage,
                ]);

                $production->update([
                    'status'       => 5,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'completed'    => true,
                    'confirmed'    => false,
                    'mmgd'         => $mmgd,
                ]);

                if ($production->Note) {
                    $production->Note->update([
                        'mmgd' => $mmgd,
                        'is45' => $this->bulkIs45,
                    ]);
                }

                Notetimeline::create([
                    'note_id'      => $production->note_id,
                    'service_id'   => $production->service_id,
                    'user_id'      => Auth()->user()->id,
                    'info'         => "Usuário {$user} encerrou a Nota/OV.",
                    'status'       => 5,
                    'productionId' => $production->id,
                ]);
            }

            DB::commit();

            $this->selected = [];
            $this->selectAll = false;
            $this->bulkConclusion = null;
            $this->bulkMmgd = null;
            $this->bulkIs45 = null;
            $this->bulkInfo = null;

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Encerramento concluído',
                'html'     => 'Os registros selecionados foram encerrados com sucesso.',
                'timer'    => 5000,
            ]);

            $this->dispatchBrowserEvent('hideModal');
            $this->emit('refresh_accomany');
        } catch (\Throwable $throwable) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao encerrar em massa',
                'html'     => "Ocorreu um erro ao tentar encerrar os registros. <br>
                    <p class='text-bg-light mt-2 p-2'>
                        Por favor, tente novamente mais tarde ou entre em contato com o suporte.
                    </p>",
            ]);
        }
    }

    public function getListsProperty()
    {
        $this->user_l = User::when($this->user_search, function ($q) {
            return $q->where('name', 'like', '%' . $this->user_search . '%');
        })->orderBy('name')->get();

        return Production::Where('productions.service_id', $this->service->uuid)
            ->join('notes as n', 'productions.note_id', '=', 'n.id')
            ->when($this->user_s, function ($q) {
                return $q->where('productions.user_id', $this->user_s);
            }, function ($q) {
                return $q->where('productions.user_id', Auth()->user()->id);
            })
            ->where('productions.completed', false)
            ->when(count($this->multiSearch), function ($q) {
                return $q->where(function ($query) {
                    $query->whereIn('n.note', $this->multiSearch)
                        ->orWhereIn('n.numPedido', $this->multiSearch);
                });
            })
            ->when($this->search, function ($q, $s) {
                return $q->where(function ($query) use ($s) {
                    $query->where('n.note', 'like', '%' . $s . '%')
                      ->orWhere('n.material', 'like', '%' . $s . '%');
                });
            })
            ->when(count($this->rubrica_s), function ($q) {
                return $q->whereIn('n.rubrica', $this->rubrica_s);
            })
            ->select('productions.*')
            ->orderBy('n.type_note', 'desc')
            ->orderBy('n.days_left', 'asc')
            ->orderBy('productions.id', 'asc')
            ->with('note', 'user')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $this->rubrica_l = Note::query()
            ->select('rubrica')
            ->where('nstats', $this->service->status)
            ->whereNotNull('rubrica')
            ->where('rubrica', '<>', '')
            ->distinct()
            ->orderBy('rubrica')
            ->get();
        $lists = $this->lists;

        return view('livewire.services.reverse.accompany.main', [
            'lists' => $lists,
            'legalTagsByNoteId' => $this->buildLegalTagsByNoteIds(collect($lists->items())->pluck('note_id')->all()),
        ]);
    }
}
