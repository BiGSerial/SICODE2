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

                // Cancelamentos
                'CancellationRequests' => function ($q) {
                    $q->with([
                        'Orders:id,ordem',
                    ])->select([
                        'id','note_id','created_at'
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
                        'Engineer:id,name',
                        'Company:id,name',
                        'Form:id,viability_id,responsible',
                    ])->select([
                        'id','note_id','user_id','engineer_id','company_id',
                        'hired','tacit','hired_at','sended_at','returned_at'
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
                    ])
                    ->select([
                        'id','note_id','company_id','user_id','team','responsible','date','created_at',
                        'changes','rejected','informed_at'
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

        return redirect()->route('files.zip', [
            'ids'  => implode(',', $this->selectedFiles),
            'note' => $this->lists->note,
        ]);
    }

    public function render()
    {
        return view('livewire.reports.search', [
            'lists' => $this->lists,
        ]);
    }
}
