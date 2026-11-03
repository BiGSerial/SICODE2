<?php

namespace App\Http\Livewire\Dispatchs\Survey;

use App\Models\{Bancoupdate, Company, Note, Production, Service, User, Wpa};
use App\Services\Dispatch\{DispatchException, DispatchWorkflowService};
use App\Services\Survey\SurveyListRowPresenter;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\{Component, WithPagination};

class PartnerMain extends Component
{
    use \App\Traits\QueuesPartnerExports;
    use Concerns\HasSurveyListFilters;
    use Concerns\LoadsLegalNoteLinks;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $search_user;

    public $note;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    public $company_l;

    public $company_s;

    public $user_l;

    public $user_s;

    public $type;

    public $additionalData = [];

    public $additionalDataUpd = [];

    public $notes;

    public $note_type = '';

    public $branco = false;

    //Variáveis para DDs
    public $enter_dd;

    public $existDD;

    public $key;

    public $municipio_edit;

    //Botão de exibição de nao atribuído
    public $not_assigned = false;

    public bool $bulkSearchAnyStatus = false;

    protected $listeners = [
        'refresh_dispatch'  => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'confirm_dispatch'  => 'confirmed_att',
        'confirm_mass_dd'   => 'confirmed_mass_dd',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'note_type'    => ['except' => '', 'as' => 'tipo'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    /** Parceira também lê os Informes e Parciais da nota (sub-linhas da coluna Nota). */
    protected function listRelations(): array
    {
        return [
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)->with(['User:id,name', 'Company:id,name']),
            'Wpas',
            'WorkForms:id,note_id',
            'Partials:id,note_id',
        ];
    }

    public function view_edit($key)
    {
        $this->key = $key;
    }

    public function hide_edit()
    {
        $this->key = "";
    }

    public function municipio_update(Note $note)
    {
        if (trim($this->municipio_edit)) {
            if ($note->update(['lexp' => mb_strtoupper(trim($this->municipio_edit))])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Informação Alterada',
                    'timer'    => 2500,
                ]);

                $this->municipio_edit = "";
                $this->hide_edit();
                $this->emit('refresh_dispatch');
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma informação inserida.',
                'timer'    => 2500,
            ]);
            $this->municipio_edit = "";
            $this->hide_edit();
            $this->emit('refresh_dispatch');
        }
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('survey-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    public function export_excel()
    {
        $this->queuePartnerExport("main");
    }

    public function updatedCompanyS()
    {
        $this->user_s = '';
        $this->loadDispatchUsers();
    }

    public function dispatchCompanyChanged($companyId): void
    {
        $this->company_s = $companyId;
        $this->updatedCompanyS();
    }

    public function loadDispatchCompanies(): void
    {
        if (Auth()->User()?->usesContractDispatch() && $this->notes && $this->notes->count()) {
            $companyIds = Production::whereIn('note_id', $this->notes->pluck('id'))
                ->where('service_id', $this->service->uuid)
                ->whereIn('company_id', SicodeRules::visibleCompanyIdsFor(Auth()->User()))
                ->whereNull('user_id')
                ->where('completed', false)
                ->where('confirmed', false)
                ->distinct()
                ->pluck('company_id');

            $this->company_l = Company::whereIn('id', $companyIds)
                ->orderBy('name', 'ASC')
                ->get();

            return;
        }

        $this->company_l = Company::whereHas('toUsers', function ($query) {
            $query->whereRelation('ToServices', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where('service', true);
            });
        })
            ->when(Auth()->User()?->usesContractDispatch(), function ($q) {
                $companyIds = SicodeRules::visibleCompanyIdsFor(Auth()->User());

                return count($companyIds)
                    ? $q->whereIn('id', $companyIds)
                    : $q->whereRaw('0 = 1');
            })
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function loadDispatchUsers(): void
    {
        $this->user_s = '';

        if (!$this->company_s) {
            $this->user_l = collect();

            return;
        }

        $this->user_l = User::whereRelation('ToServices', function ($q) {
            $q->where('service_id', $this->service->uuid)
                ->where('service', true);
        })
            ->where(function ($q) {
                $q->where('company_id', $this->company_s)
                    ->orWhere('company_id', $this->company_s)
                    ->orWhereRelation('Companies', 'companies.id', $this->company_s);
            })
            ->when($this->search_user, function ($q) {
                return $q->where('name', 'like', '%' . $this->search_user . '%');
            })
            ->select('id', 'name')
            ->orderBy('name', 'ASC')
            ->get();
    }

    private function preselectContractDispatchCompany(): void
    {
        if (!Auth()->User()?->usesContractDispatch() || !$this->notes || !$this->notes->count()) {
            return;
        }

        $companyIds = $this->notes
            ->map(fn ($note) => SicodeRules::openCompanyStackProductionFor($note, Auth()->User(), $this->service->uuid)?->company_id)
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($companyIds->count() === 1) {
            $this->company_s = $companyIds->first();
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function get_single_note($note)
    {
        $this->selected = [$note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {

        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', array_values($this->selected));
    }

    public function confirm_att()
    {
        if (!$this->company_s) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma empresa foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->type === "2") {

            if (!$this->user_s) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nenhum usuário foi selecionado para despacho individual!',
                    'timer'    => 2500,
                ]);

                return;
            }

            $para = User::find($this->user_s)->name . " da " . (Company::find($this->company_s))->name;

        } else {
            $para = (Company::find($this->company_s))->name;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Despachar',
            'msg'           => "Você está prestes a Despachar {$this->notes->count()} nota(s) para {$para}",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Despache!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => "confirm_dispatch",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

        ]);
    }

    public function add_dd()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma entrada para atribuição!',
                'timer'    => 5000,
            ]);

            return;
        }

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {

            foreach ($linhas as $linha) {

                if ($linha) {

                    $coluna = explode("\t", $linha);

                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $index = $this->notes->search(function ($note) use ($coluna) {
                            return $note->note == $coluna[0];
                        });

                        if ($index !== false) {
                            $this->additionalData[$index] = $coluna[1];
                        }
                    }

                }
            }

        }
    }

    public function confirmed_att()
    {
        if (!in_array((string) $this->type, ['1', '2'], true)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione o tipo de despacho.',
                'timer'    => 2500,
            ]);

            return;
        }

        try {
            $workflow   = app(DispatchWorkflowService::class);
            $company    = Company::findOrFail($this->company_s);
            $targetUser = (string) $this->type === '2' ? User::findOrFail($this->user_s) : null;
            $actor      = Auth()->User();

            DB::transaction(function () use ($workflow, $company, $targetUser, $actor) {
                foreach ($this->notes as $key => $note) {
                    $dd = $this->additionalData[$key] ?? null;

                    if ($targetUser) {
                        $workflow->dispatchToUser($note, $this->service, $company, $targetUser, $actor, $dd);
                    } else {
                        $workflow->dispatchToCompanyStack($note, $this->service, $company, $actor, $dd);
                    }
                }
            });
        } catch (DispatchException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => $e->getMessage(),
                'timer'    => 6000,
            ]);

            return;
        } catch (\Throwable $e) {
            report($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao despachar as Notas/OVs.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notas Despachadas com sucesso!',
            'timer'    => 2500,
        ]);

        $this->closeall();
        $this->emit('refresh_dispatch');
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->company_s = "";
        $this->selected  = [];
        $this->user_s    = "";
        $this->user_l    = collect();
        // $this->type = "";
        $this->additionalData      = [];
        $this->multiSearch         = [];
        $this->bulkSearchAnyStatus = false;
        $this->advanceSearch       = "";
        $this->search              = "";

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {

        $this->company_s   = "";
        $this->enter_dd    = "";
        $this->user_s      = "";
        $this->search_user = "";
        $this->user_l      = collect();
        // $this->type = "";
        $this->additionalData      = [];
        $this->multiSearch         = [];
        $this->bulkSearchAnyStatus = false;
        $this->advanceSearch       = "";
        $this->search              = "";
    }

    public function buscarMulti()
    {

        $this->gotoPage(1);

        if ($this->advanceSearch) {

            $this->search = "";

            $this->multiSearch = preg_split('/[\s,;]+/', $this->advanceSearch, -1, PREG_SPLIT_NO_EMPTY);
            $this->multiSearch = array_values(array_unique(array_map('trim', $this->multiSearch)));
        }

        if (count($this->multiSearch)) {
            $this->advanceSearch = "";
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    /**
     * Atribuição de DD em Mmassa.
     *
     * Aqui foi montando uma arquitetura para que seja possível associar uma nota a uma DD, na tabela
     * WPAs. Assim sendo, quando carregar as DD para atribuição em massa, o mesmo ja seja inserido nas
     * correspondentes.
     */

    public function mass_modal()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma entrada para atribuição.',
                'timer'    => 5000,
            ]);

            return;
        }

        $additionalData    = [];
        $additionalDataUpd = [];
        $seenDds           = [];
        $unchanged         = 0;
        $moved             = 0;

        foreach (preg_split('/\r\n|\r|\n/', trim($this->enter_dd)) as $lineNumber => $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                continue;
            }

            $coluna = preg_split('/[\s;,]+/', $linha, -1, PREG_SPLIT_NO_EMPTY);

            if (count($coluna) !== 2) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Linha ' . ($lineNumber + 1) . ' inválida.',
                    'html'     => 'Informe exatamente dois valores por linha: <strong>Nota/OV</strong> e <strong>DD</strong>.',
                ]);

                return;
            }

            [$noteNumber, $ddNumber] = array_map('trim', $coluna);

            if (!preg_match('/^[0-9]+$/', $noteNumber) || !preg_match('/^[0-9]+$/', $ddNumber)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Linha ' . ($lineNumber + 1) . ' contém caracteres inválidos.',
                    'html'     => 'Nota/OV e DD devem conter somente números.',
                ]);

                return;
            }

            if (isset($seenDds[$ddNumber])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'DD duplicada no lote.',
                    'html'     => "A DD <strong>{$ddNumber}</strong> apareceu mais de uma vez. Revise o lote antes de continuar.",
                ]);

                return;
            }
            $seenDds[$ddNumber] = $noteNumber;

            $note = Note::where('note', $noteNumber)->first();

            if (!$note) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Nota/OV não encontrada.',
                    'html'     => "A Nota/OV <strong>{$noteNumber}</strong> da linha " . ($lineNumber + 1) . ' não foi encontrada.',
                ]);

                return;
            }

            $existingByDd = Wpa::where('dd', $ddNumber)->with('Note', 'Production.User')->first();

            if ($existingByDd && (string) $existingByDd->note_id !== (string) $note->id) {
                $additionalDataUpd[] = [
                    'id'            => $existingByDd->id,
                    'note_id'       => $note->id,
                    'service_id'    => $this->service->uuid,
                    'production_id' => null,
                    'dd'            => $ddNumber,
                ];
                $moved++;

                continue;
            }

            if ($existingByDd) {
                $unchanged++;

                continue;
            }

            $existingForNote = Wpa::where('note_id', $note->id)->whereNull('production_id')->first();

            if ($existingForNote) {
                $additionalDataUpd[] = [
                    'id'            => $existingForNote->id,
                    'note_id'       => $note->id,
                    'service_id'    => $this->service->uuid,
                    'production_id' => null,
                    'dd'            => $ddNumber,
                ];
            } else {
                $additionalData[] = [
                    'note_id'    => $note->id,
                    'service_id' => $this->service->uuid,
                    'dd'         => $ddNumber,
                ];
            }

        }

        if (!count($additionalData) && !count($additionalDataUpd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'Nenhuma alteração necessária.',
                'html'     => $unchanged
                    ? "{$unchanged} vínculo(s) informado(s) já existiam para a própria Nota/OV."
                    : 'Nenhuma associação nova foi identificada.',
            ]);

            return;
        }

        $count = count($additionalData) + count($additionalDataUpd);

        $this->additionalData    = $additionalData;
        $this->additionalDataUpd = $additionalDataUpd;

        $summary = "Você está prestes a associar {$count} Nota(s)/OV(s) e DD(s).";

        if ($moved) {
            $summary .= " {$moved} DD(s) serão movidas de outra Nota/OV para a Nota/OV informada.";
        }

        if ($unchanged) {
            $summary .= " {$unchanged} vínculo(s) já existiam e serão mantidos.";
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmar Associação de DD?',
            'msg'           => $summary,
            'icon'          => 'info',
            'btnOktxt'      => 'Sim, associar',
            'btnCanceltxt'  => 'Não, cancelar',
            'action'        => "confirm_mass_dd",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/OV foi associada.',

        ]);
    }

    public function confirmed_mass_dd()
    {

        // if ($count = count($this->additionalData)) {
        //     $error = 0;
        //     foreach ($this->additionalData as $wpa) {
        //         if (!Wpa::Create($wpa)) {
        //             $error++;
        //         }
        //     }
        // }

        // if ($count = count($this->additionalDataUpd)) {
        //     $error = 0;
        //     foreach ($this->additionalDataUpd as $wpa) {
        //         if (!Wpa::Update($wpa)) {
        //             $error++;
        //         }
        //     }
        // }

        // if (!$error) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'success',
        //         'title' => 'Notas DDs associadas com sucesso',
        //         'timer' => 2500,
        //     ]);

        //     $this->closeall();
        // } else {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon' => 'error',
        //         'title' => "OOPS!, Ocorreram {$error} de {$count} ao associar as DD às Notas.",
        //         'timer' => 8000,
        //     ]);
        // }

        $count = count($this->additionalData);
        $error = 0;

        foreach ($this->additionalData as $wpa) {
            if (!Wpa::create($wpa)) {
                $error++;
            }
        }

        $countUpd = count($this->additionalDataUpd);
        $errorUpd = 0;

        foreach ($this->additionalDataUpd as $wpa) {
            $idToUpdate = $wpa['id'];
            unset($wpa['id']);

            if (!Wpa::where('id', $idToUpdate)->update($wpa)) {
                $errorUpd++;
            }
        }

        $totalErrors = $error + $errorUpd;
        $totalCount  = $count + $countUpd;

        if (!$totalErrors) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas DDs associadas com sucesso',
                'timer'    => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "OOPS!, Ocorreram {$totalErrors} de {$totalCount} ao associar as DD às Notas.",
                'timer'    => 8000,
            ]);
        }

    }

    public function render()
    {
        $this->dispatchStateMemo = [];

        $lists = $this->paginateLists();
        $this->recomputeSelectAllFor($lists->getCollection());

        $presenter = app(SurveyListRowPresenter::class);
        $rows      = $lists->getCollection()->map(fn (Note $note) => $presenter->present($note, $this->rowDispatchState($note), $this->service->uuid, true));

        return view('livewire.dispatchs.levantamento.partner_main', [
            'lists'        => $lists,
            'rows'         => $rows,
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('id')->all()),
            'update'       => Cache::remember('survey-dispatch:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
        ]);
    }
}
