<?php

namespace App\Http\Livewire\Services\Analises_pre\Accompany;

use App\Helpers\TextFormatter;
use App\Models\{Note, Notetimeline, Production, Service, User};
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $limit_pause = 50;

    public $analise;

    public $user_l;

    public $user_s;

    public $user_search;

    public $production;

    public $note;

    public $advanceSearch;

    public $multiSearch = [];

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

    public function visualizar()
    {

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

            $this->emit('open_analise_preanalise', ['productionId' => $check->id, 'noteId' => $check->note_id]);

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
        $this->emit('open_analise_preanalise', $this->analise);
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
            $this->emit('open_analise_preanalise', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }

    }

    public function filter_save()
    {
        // session()->put('filtro', $this->rubrica_s);
        // if (!session()->isStarted()) { session()->start(); }
        // $_SESSION['filtro'] = $this->rubrica_s;
        $this->emit('refresh_service');

    }

    public function filter_clean()
    {
        $this->rubrica_s = [];
        $this->search = null;
        $this->advanceSearch = null;
        $this->multiSearch = [];

        // if (!session()->isStarted()) { session()->start(); }
        // if (isset($_SESSION['filtro'])) {
        //     unset($_SESSION['filtro']);
        // }

        $this->emit('refresh_service');
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch = [];
        }
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
        $mmgd = false;

        if ($this->bulkMmgd === 'SIM') {
            $mmgd = true;
        }

        $productions = Production::whereIn('id', $this->selected)
            ->where('service_id', $this->service->uuid)
            ->where('completed', false)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
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
            $user = Auth()->User()->name;
            $totalSelected = $productions->count();
            $bulkSignature = "encerrado em massa - de um total de {$totalSelected} registros";

            foreach ($productions as $production) {
                $analise = $production->Analise()->first();

                if (!$analise) {
                    $analise = $production->Analise()->create();
                }

                $infoMessage = trim((string) $this->bulkInfo);
                if ($infoMessage) {
                    $infoMessage .= " | {$bulkSignature}";
                } else {
                    $infoMessage = $bulkSignature;
                }

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

                Notetimeline::Create([
                    'note_id'      => $production->note_id,
                    'service_id'   => $production->service_id,
                    'user_id'      => Auth()->User()->id,
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
        } catch (\Throwable $th) {
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

        return Production::Where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', false)
            ->when($this->multiSearch, function ($q) {
                return $q->whereRelation('Note', function ($sq) {
                    $sq->whereIn('note', $this->multiSearch)
                        ->orWhereIn('numPedido', $this->multiSearch);
                });
            })
            ->when($this->search, function ($q, $s) {
                return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                    ->orwhereRelation('Note', 'material', 'like', '%' . $s . '%');
            })
            ->with(['Note' => function ($query) {
                $query->orderBy('dt_status', 'asc');
            }])
            ->paginate($this->perPage);
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.analises_pre.accompany.main', [
            'lists' => $this->lists,
        ]);
    }
}
