<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\User;
use App\Models\Viability as ModelsViability;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Viability extends Component
{
    use WithFileUploads;

    public $notes;
    public $companies;
    public $company_id;
    public $responsible_id;

    public $responsibles;
    public $toViabilities = [];

    protected $listeners = [
        'getNotes',
        'closeAll'
    ];

    protected $rules = [
        'company_id' => 'required',
        'responsible_id' => 'required',
        'toViabilities.*.temp_files.files.*' => 'file|max:10240|mimes:xlsx,xls,ods,ots,doc,docx,odt,ott,pdf,jpg,jpeg,png,gif,bmp,tiff',
    ];

    protected $messages = [
        'company_id.required' => 'Selecione a empresa',
        'responsible_id.required' => 'Selecione o responsável',
        'toViabilities.*.temp_files.files.*.file' => 'O arquivo deve ser um documento',
        'toViabilities.*.temp_files.files.*.max' => 'O arquivo deve ter no máximo 10MB',
        'toViabilities.*.temp_files.files.*.mimes' => 'O arquivo deve ser um dos seguintes tipos: xlsx,xls,ods,ots,doc,docx,odt,ott,pdf,jpg,jpeg,png,gif,bmp,tiff',
    ];

    public function mount()
    {
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->Select('id', 'name')->orderBy('name')->get();

    }

    public function getNotes($notes_id)
    {
        $this->notes = Note::whereIn('id', $notes_id)
                ->with([
                'files' => function ($q) {
                    $q->where('file_name', 'like', 'PROJETO%');
                },
                'orders' => function ($q) {
                    $q->where('statusSist', 'not like', 'ENC%')
                      ->where('statusSist', 'not like', 'ENT%');
                }
                ])
                ->get();

        if ($this->notes->count()) {
            $this->mountViabilities($this->notes);
            $this->dispatchBrowserEvent('showModal', [
                'id' => "modal_viability",
            ]);
        }
    }

    public function updatedCompanyId($company_id)
    {
        $this->responsibles = User::whereRelation('Companies', 'id', $company_id)->where('responsible', true)->select('id', 'name')->orderBy('name')->get();
    }


    private function mountViabilities($notes)
    {
        foreach ($notes as $note) {
            $this->toViabilities[$note->id] = [
                'company_id' => $this->company_id,
                'responsible_id' => $this->responsible_id,
                'contratar' => false,
                'reter' => false,
                'note' => $note,
                'temp_files' => [],
            ];
        }
    }

    public function toViability()
    {

        $validate = $this->validate();


        if (!$validate) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'TEM PROBLEMA DE VALIDADE.',
                'timer'    => 10000,
            ]);
        }


        if (count($this->toViabilities) <= 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Ocorreu algum erro interno onde não foi possível recuperar a lista, tente novamente.',
                'timer'    => 10000,
            ]);

            return;
        }

        dd($this->toViabilities);

    }

    public function closeAll()
    {
        $this->toViabilities = [];
        $this->notes = null;
        $this->company_id = null;
        $this->responsible_id = null;
        $this->resetErrorBag();
        $this->resetValidation();
    }



    public function render()
    {
        return view('livewire.construction.hiring.actions.viability');
    }
}
