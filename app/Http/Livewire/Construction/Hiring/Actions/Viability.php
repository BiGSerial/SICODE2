<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Models\Viability as ModelsViability;
use Exception;
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

    public $hiringAll = false;
    public $retainAll = false;

    protected $listeners = [
        'getNotes',
        'closeAll',
        'd1b5f6f9e5e1c1e8e4e8e5e8e5e8e5e8' => 'goViability',
    ];

    protected $rules = [
        'company_id' => 'required',
        'responsible_id' => 'required',
        'toViabilities.*.temp_files.files.*' => 'file|max:10240|mimes:xlsx,xls,ods,ots,doc,docx,odt,ott,pdf,jpg,jpeg,png,webp,gif,bmp,tiff',
    ];

    protected $messages = [
        'company_id.required' => 'Selecione a empresa',
        'responsible_id.required' => 'Selecione o responsável',
        'toViabilities.*.temp_files.files.*.file' => 'O arquivo deve ser um documento',
        'toViabilities.*.temp_files.files.*.max' => 'O arquivo deve ter no máximo 10MB',
        'toViabilities.*.temp_files.files.*.mimes' => 'O arquivo deve ser um dos seguintes tipos: xlsx,xls,ods,ots,doc,docx,odt,ott,pdf,jpg,jpeg,png,webp,gif,bmp,tiff',
    ];

    public function mount()
    {
        $this->companies = Company::WhereRelation('contracts', 'construction', true)->WhereRelation('contracts', 'service', false)->Select('id', 'name')->orderBy('name')->get();

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


        $this->responsibles = User::whereHas('companies', function ($query) use ($company_id) {
            $query->where('companies.id', trim($company_id));
        })
        ->where('responsible', true)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();


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

    public function selectAllHiring($value)
    {
        if (count($this->toViabilities) > 0 && $value) {
            foreach ($this->toViabilities as $key => $toViability) {
                $this->toViabilities[$key]['contratar'] = true;
            }

        } else {
            foreach ($this->toViabilities as $key => $toViability) {
                $this->toViabilities[$key]['contratar'] = false;
            }
        }
    }

    public function selectAllRetain($value)
    {
        if (count($this->toViabilities) > 0 && $value) {
            foreach ($this->toViabilities as $key => $toViability) {
                $this->toViabilities[$key]['reter'] = true;
            }

        } else {
            foreach ($this->toViabilities as $key => $toViability) {
                $this->toViabilities[$key]['reter'] = false;
            }
        }
    }

    public function checkAllHiringSelected()
    {

        if (count($this->toViabilities) <= 0) {
            return false;
        }

        foreach ($this->toViabilities as $toViability) {
            if (!$toViability['contratar']) {
                return false;
            }
        }

        return true;
    }

    public function checkAllRetainSelected()
    {
        if (count($this->toViabilities) <= 0) {
            return false;
        }

        foreach ($this->toViabilities as $toViability) {
            if (!$toViability['reter']) {
                return false;
            }
        }

        return true;
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

        $count = count($this->toViabilities);

        $company = Company::find($this->company_id)->name;
        $user = User::find($this->responsible_id)->name;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => "ENVIAR VIABILIDADE",
            'msg'           => "
                <p>Deseja enviar <span class='fw-bold'>{$count}</span> obra(s) para <span class='fw-bold'>{$company}</span>?</p>
                <div class='card'>
                    <div class='card-body text-left'>
                        <p class='fw-bold'>Responsável:<span class='fw-normal'> {$user}</span></p>
                    </div>
                </div>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'd1b5f6f9e5e1c1e8e4e8e5e8e5e8e5e8',
            'confirm'       => 'Sim envie',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Ordem foi Enviada!',

        ]);

        return;

    }

    public function removeViability($key)
    {
        if (isset($this->toViabilities[$key])) {
            unset($this->toViabilities[$key]);
        }

        if (count($this->toViabilities) <= 0) {
            $this->closeAll();
        }
    }


    public function goViability()
    {
        $note = '';

        DB::beginTransaction();

        try {
            foreach ($this->toViabilities as $toViability) {

                $viability = ModelsViability::create([
                    'note_id'    => $toViability['note']['id'],
                    'user_id'    => Auth()->User()->id,
                    'company_id' => $this->company_id,
                    'engineer_id' => $this->responsible_id,
                    'sended_at' => $toViability['reter'] ? null : date('Y-m-d H:i:s'),
                    'visible_partner' => $toViability['reter'] ? true : false,
                    'hired'  => $toViability['contratar'] ? true : false,
                    'hired_at' => $toViability['contratar'] ? date('Y-m-d H:i:s') : null,
                    'status' => $toViability['reter'] ? 16 : 1,
                    ]);




                $orders = Order::where('statusSist', 'NOT LIKE', 'ENC%')
                                ->where('statusSist', 'NOT LIKE', 'ENT%')
                                ->where('note_id', $toViability['note']['id'])
                                ->get();

                if ($orders->count()) {
                    $sum = 0.0;
                    foreach ($orders as $order) {
                        $sum += $order->moaberto;
                        $viability->orders()->syncWithoutDetaching([$order->id]);
                    }

                    $viability->value = $sum > 0.0 ? $sum : null;
                    $viability->save();

                } else {
                    throw new Exception("Um ou mais registros não possue(m) Ordem(ns) válida(s) para contratação", 1);
                }

                if (isset($toViability['temp_files']['files']) && count($toViability['temp_files']['files']) > 0) {

                    $note = Note::find($toViability['note']['id']);

                    if ($note->exists()) {
                        foreach ($toViability['temp_files']['files'] as $index => $file) {
                            $new_name = 'PROJETO_DESE_'.$note->note.'_F'.str_pad($index + 1, 2, '0', STR_PAD_LEFT).'-'.str_pad(count($toViability['temp_files']['files']), 2, '0', STR_PAD_LEFT);
                            $rev = File::where('file_name', 'like', $new_name."%")->count();

                            $caminho = $file->store('/arquivos/PROJETO');

                            if (Storage::exists($caminho)) {

                                $createdFile = File::create([
                                    'note_id' => $note->id,
                                    'user_id' => Auth()->User()->id,
                                    'service_id' => null,
                                    'file_name' => $new_name."_Rev-".$rev,
                                    'original_name' => $file->getClientOriginalName(),
                                    'path' => $caminho,
                                    'ext' => $file->extension(),
                                    'suspicious' => 0,
                                    'noexists' => false,
                                ]);

                                if ($createdFile) {
                                    // Mantem rastreabilidade do anexo pela origem da viabilidade
                                    $viability->files()->syncWithoutDetaching([$createdFile->id]);
                                }
                            } else {
                                throw new Exception("Um ou mais arquivos não foram salvos corretamente", 1);
                            }
                        }
                    }
                }

            }

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'A Viabilidade foi enviada com sucesso.',
                'timer'    => 5000,
            ]);

            $this->closeAll();

        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Ocorreu um erro ao tentar enviar a viabilidade, tente novamente.<br><br>'.$th->getMessage(),
                // 'timer'    => 10000,
            ]);
        }



    }

    public function closeAll()
    {
        $this->toViabilities = [];
        $this->notes = null;
        $this->company_id = null;
        $this->responsible_id = null;
        $this->resetErrorBag();
        $this->resetValidation();

        $this->emitUp('closeAll');
        $this->dispatchBrowserEvent('hideModal');
    }



    public function render()
    {
        return view('livewire.construction.hiring.actions.viability');
    }
}
