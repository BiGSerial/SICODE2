<?php

namespace App\Http\Livewire\Reports;

use App\Models\Edp_depc\BaseOV;
use App\Models\File;
use App\Models\Note;
use Livewire\Component;

class Search extends Component
{
    public $search = '';
    public $selectedFiles = [];
    public $historico = null;
    public $openServiceId = null;
    public bool $hasProtestOverview = false;

    /** @var \App\Models\Note|null */
    public $lists = null;

    protected $queryString = [
        'search' => ['except' => '', 'as' => 's'],
    ];

    protected $listeners = [
        'update_list'   => '$refresh',
        'setOpenService',
    ];

    public function setOpenService($serviceId)
    {
        $this->openServiceId = $serviceId;
    }

    /**
     * Busca a Nota/OV com tudo que a view usa (exceto HISTÓRICO, que é sob demanda)
     */
    public function findNote()
    {
        $term = trim($this->search);

        $this->lists = Note::query()
            ->where(function ($q) use ($term) {
                $q->where('note', $term)
                  ->orWhereHas('Orders', fn ($qq) => $qq->where('ordem', $term))
                  ->orWhereHas('FiveNote', fn ($qq) => $qq->where('note_d5', $term));
            })
            ->with([
                // D5
                'FiveNote:id,note_id,note_d5,visible_partner,is_completed,is_payed,is_archived,is_supervisioned,completed_at',

                // Arquivos
                'Files:id,note_id,service_id,file_name,ext,path,created_at',
                'Files.Service:id,service',

                // Ordens + Operações
                'Orders:id,note_id,ordem,statusSist',
                'Orders.Operations:id,order_id,operacao,descOperacao,status,cenTrab,inicioPlanejado,fimPlanejado,inicioReal,fimReal',

                // Protestos
                'Protests:id,nota,tipoNota',

                // Cancelamentos
                'CancellationRequests' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                    ])->select([
                        'id','note_id','scope','status','closed_at','created_at'
                    ]);
                },

                // Projeto (Productions)
                'Productions' => function ($q) {
                    $q->where('rejected', false)
                      ->with([
                          'Service:id,uuid,service',
                          'User:id,name,email',
                          'Company:id,name',
                      ])
                      ->select([
                          'id','note_id','service_id','user_id','company_id',
                          'status','status_note','dispatch_at','att_at','completed_at',
                          'stopped','manual','confirmed','d5','dfive','partial'
                      ]);
                },

                // Contratação (Viabilities)
                'Viabilities' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                        'Orders.Operations:id,order_id,operacao,status',
                        'User:id,name,email',
                        'Engineer:id,name,email',
                        'Company:id,name',
                        'Form:id,viability_id,user_id,reason,description,changes,responsible,rejected,approved,historic,created_at,updated_at',
                        'Form.User:id,name,email',
                        'Form.Files:id,file_name,original_name,path,ext,user_id,created_at',
                        'Form.Files.User:id,name',
                        'Files:id,file_name,original_name,path,ext,user_id,created_at',
                        'Files.User:id,name',
                    ])->select([
                        'id','note_id','user_id','engineer_id','company_id',
                        'hired','tacit','hired_at','sended_at','returned_at',
                        'approved','rejected','completed','canceled',
                        'engineer','engineer_at','completed_at','status',
                        'init_at','tacit_at','visible_partner','value',
                    ]);
                },

                // Informes (Work / Ramal / Parciais)
                'WorkForm' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                        'Company:id,name',

                        // CORRETO: equipamentos referenciam work_report_id
                        'Equipment:id,work_report_id',

                        // CORRETO: devoluções também usam work_report_id
                        'Returnwork:id,work_report_id,created_at',
                        'Adsform:id,work_report_id,tacit,tacit_due_at,tacit_delivered_at,created_at',
                    ])
                    ->select([
                        'id','note_id','company_id','user_id','team','responsible','date','created_at',
                        'changes','rejected','informed_at','canceled','canceled_at','canceled_by',
                        'acceptance_name','acceptance_accepted','acceptance_at','acceptance_meta'
                    ]);
                },

                'WorkFormAny' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                        'Company:id,name',
                        'Equipment:id,work_report_id',
                        'Returnwork:id,work_report_id,created_at',
                        'Adsform:id,work_report_id,tacit,tacit_due_at,tacit_delivered_at,created_at',
                    ])
                    ->select([
                        'id','note_id','company_id','user_id','team','responsible','date','created_at',
                        'changes','rejected','informed_at','canceled','canceled_at','canceled_by',
                        'acceptance_name','acceptance_accepted','acceptance_at','acceptance_meta'
                    ]);
                },

                'RamalForm' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                        'Company:id,name',
                        'User:id,name',

                        // usual em RamalReport:
                        'BtzeroEquipment:id,ramal_report_id',

                        'ReturnRamal:id,ramal_report_id,created_at',
                    ])->select([
                        'id','note_id','company_id','user_id','created_at','rejected'
                    ]);
                },

                'Partials' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                        'Company:id,name',
                    ])->select([
                        'id','note_id','company_id','responsible','deny','allow','supervision','payment','complete','created_at'
                    ]);
                },
            ])
            ->first();

        // reset de estados voláteis
        $this->hasProtestOverview = $this->lists
            ? $this->lists->Protests()->exists()
            : false;
        $this->historico     = null;   // só carrega se clicarem
        $this->openServiceId = null;
        $this->selectedFiles = [];
    }

    /**
     * HISTÓRICO (outro banco) — sob demanda
     */
    public function loadHistorico()
    {
        if (!$this->lists) {
            return;
        }

        $this->historico = BaseOV::where('OV', trim($this->lists->note))
            ->orderBy('dhStat', 'DESC')
            ->get();
    }

    /**
     * Checkbox do cabeçalho (selecionar/deselecionar grupo inteiro)
     */
    public function toggleGroup(string $slug)
    {
        if (!$this->lists) {
            return;
        }

        $files = $this->lists->Files->filter(function ($f) use ($slug) {
            $service = $f->Service->service ?? 'Outros';
            return \Illuminate\Support\Str::slug($service) === $slug;
        });

        $allSelected = collect($this->selectedFiles)->intersect($files->pluck('id'))->count() === $files->count();

        if ($allSelected) {
            $this->selectedFiles = array_values(array_diff($this->selectedFiles, $files->pluck('id')->all()));
        } else {
            $this->selectedFiles = array_values(array_unique(array_merge($this->selectedFiles, $files->pluck('id')->all())));
        }
    }

    /**
     * Download único via HTTP (evita gargalos Livewire)
     */
    public function downloadFile(File $file)
    {
        if (!$file) {
            return;
        }
        return redirect()->route('files.download', ['file' => $file->id]);
    }

    public function updatedSelectedFiles($value)
    {
        if (!$this->lists) {
            $this->selectedFiles = [];
            return;
        }

        $ids = collect(is_array($value) ? $value : $this->selectedFiles)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->selectedFiles = [];
            return;
        }

        $validIds = File::query()
            ->where('note_id', (int) $this->lists->id)
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->selectedFiles = $validIds;
    }

    /**
     * Download ZIP via HTTP
     */
    public function zipFiles()
    {
        if (!$this->lists || !count($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM ARQUIVO SELECIONADO',
                'timer'    => 5000,
            ]);
            return;
        }

        $selectedIds = collect($this->selectedFiles)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SELEÇÃO INVÁLIDA OU EXPIRADA',
                'text'     => 'Atualize a lista de arquivos e selecione novamente.',
                'timer'    => 5000,
            ]);
            return;
        }

        $selectedIds = File::query()
            ->where('note_id', (int) $this->lists->id)
            ->whereIn('id', $selectedIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->selectedFiles = $selectedIds;

        if (empty($selectedIds)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SELEÇÃO INVÁLIDA OU EXPIRADA',
                'text'     => 'Atualize a lista de arquivos e selecione novamente.',
                'timer'    => 5000,
            ]);
            return;
        }

        return redirect()->route('files.zip', [
            'ids'  => implode(',', $selectedIds),
            'note' => $this->lists->note,
            'note_id' => (int) $this->lists->id,
        ]);
    }

    public function render()
    {
        return view('livewire.reports.search', [
            'lists' => $this->lists,
        ]);
    }
}
