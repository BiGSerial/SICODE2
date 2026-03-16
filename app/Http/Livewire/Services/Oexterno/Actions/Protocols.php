<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\Category;
use App\Models\File;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use ZipArchive;

class Protocols extends Component
{
    public ?Note $note = null;
    public $encerrar;
    public $selType;
    public $selAgency;
    public $protocol = [];
    public $comment = [];
    public $selectedFiles = [];

    public $categories;
    public $subcategory_id;
    public $category_id;
    public $service_id;
    public $services;
    public $reason;
    public $theService;

    public $production;

    protected $listeners = [
        'openProtocol',
        'cleanAll',
        'confirm_finish',
        'confirm_return',
    ];

    public function openProtocol(Note $note)
    {

        $this->note = $note;

        if ($this->note) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_protocols',
            ]);
        }
    }

    public function mount()
    {
        $this->categories = Category::orderBy('name')->get();
        $this->services = Service::where('canReturn', true)->orderBy('service')->get();

        $this->theService = Service::where('uuid', request()->route()->parameter('service'))->first();


    }

    public function updatedServiceId()
    {
        $this->production = Production::where('service_id', $this->service_id)
                                    ->where('completed', true)
                                    ->orderBy('completed_at', 'DESC')
                                    ->first();



    }

    public function save()
    {
        if (!$this->note->External) {

            if (!$this->selAgency) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Entidade Externa Obrigatória',
                    'html'      => 'NENHUMA ENTIDADE PROTOCOLAR FOI SELECIONADA.',
                    'timer'    => 5000,
                ]);

                return;
            }

            $check = $this->note->External()->updateOrCreate(
                ['note_id' => $this->note->id],
                [
                    'user_id' => Auth()->User()->id,
                    'entidade' => $this->selAgency,
                    'status' => 1,
                    'completed' => false,
                ]
            );

            if ($check) {

                $this->note = $this->note->fresh();
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'FALHA',
                    'html'     => 'Não conseguimos salvar as informações.',
                    'timer'    => 5000,
                ]);

                return;
            }
        }

        if ($this->note->External->completed) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'PROTOCOLO ENCERRADO',
                'html'     => 'Essa obra foi definida como CONCLUIDA na fase PROTOCOLAR. Não é possivel alterar as informações',
                'timer'    => 5000,
            ]);

            return;
        }


        // dd($this->protocol, $this->comment);

        if (!empty($this->protocol)) {
            if (!trim($this->protocol['protocol'])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'PROTOCOLO OBRIGATÓRIO',
                    'html'     => 'É NECESSÁRIO O NUMERO DE PROTOCOLO PARA SALVAR ESSA OPÇÃO',
                    'timer'    => 5000,
                ]);

                return;
            }

            $protocol = $this->note->External->Protocols()->updateOrCreate(
                [
                    'external_id' => $this->note->External->id,
                    'protocol' => trim($this->protocol['protocol'])
                ],
                [
                    'description' => trim($this->protocol['description']),
                ]
            );

            if (!$protocol) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'PROTOCOLO',
                    'html'     => 'Não foi Possível salvar o protocolo',
                    'timer'    => 5000,
                ]);

                return;
            }
        }

        if (!empty($this->comment)) {
            if (!trim($this->comment['comment'])) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'COMENTÁRIO OBRIGATÓRIO',
                    'html'     => 'SEJA CLARO E OBJETIVO NO COMENTÁRIO SOBRE A SITUAÇÃO OBSERVADA NA PROTOCOLAÇÃO. SUGERIMOS SEMPRE INSERIR QUANDO VOCÊ TENHA VERIFICADO A SITUAÇÃO E O QUE FOI DEFINIDO.',
                    'timer'    => 5000,
                ]);

                return;
            }

            $protocol = $this->note->External->Comments()->Create(
                [
                    'user_id' => Auth()->User()->id,
                    'comment' => trim($this->comment['comment']),
                    'title' => trim($this->comment['title']),
                ]
            );

            if (!$protocol) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'PROTOCOLO',
                    'html'     => 'Não foi Possível salvar o protocolo',
                    'timer'    => 5000,
                ]);

                return;
            }
        }


        $this->note = $this->note->fresh();


        $this->emitTo('files.manager.create-serv-files', 'saveFiles');


        if ($this->encerrar) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => "Encerrar Protocolo para {$this->note->note}?",
                'msg'           => "Você selecionou encerrar os protocolos para esta Nota/OV. Ao prosseguir, não será mais possível adicionar novos comentários ou protocolos. Tenha ajustado o Motivo em Comentários para Encerrado com o seu parecer final.<br><br> Deseja realmente encerra esta NOTA/OV?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Encerrar!',
                'btnCanceltxt'  => 'Não, Cancelar',
                'action'        => 'confirm_finish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma NOTA/OV foi encerrada!',

            ]);

            return;
        } else {

            $this->cleanAll();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SALVO COM SUCESSO',

                'timer'    => 2500,
            ]);
        }
    }

    public function confirm_finish()
    {


        $protocol = $this->note->External->Comments()->Create(
            [
                'user_id' => User::first()->id,
                'comment' => ">> Protocolo encerrado por: " . Auth()->User()->name . " << (System)",
                'title' => "ENCERRADO",
            ]
        );

        $protocol = $this->note->External()->update([
            'completed' => true
        ]);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ENCERRADO COM SUCESSO',

            'timer'    => 2500,
        ]);

        $this->note = $this->note->fresh();
        $this->cleanAll();
    }

    public function cleanAll()
    {

        $this->emitUp('refresh_list');
        $this->selType = '';
        $this->selAgency = '';
        $this->protocol = [];
        $this->comment = [];
        $this->encerrar = '';
        $this->service_id = '';
        $this->subcategory_id = '';
        $this->category_id = '';
        $this->reason = '';

        $this->emitTo('files.manager.create-serv-files', 'cleanFiles');


    }


    // public function downloadFile(File $file)
    // {
    //     if ($file) {

    //         if (Storage::fileExists($file->path)) {
    //             return Storage::download($file->path, explode('.', $file->file_name)[0] . "." . $file->ext);
    //         } else {
    //             $this->dispatchBrowserEvent('swal', [
    //                 'position' => 'center',
    //                 'icon'     => 'error',
    //                 'title'    => 'ARQUIVO INEXISTENTE!',
    //                 'timer'    => 5000,
    //             ]);

    //             return;
    //         }
    //     }
    // }

    // public function zipFiles()
    // {
    //     if (!count($this->selectedFiles)) {
    //         $this->dispatchBrowserEvent('swal', [
    //             'position' => 'center',
    //             'icon'     => 'warning',
    //             'title'    => 'NENHUM ARQUIVO SELECIONADO',
    //             'timer'    => 5000,
    //         ]);

    //         return;
    //     }

    //     if (count($this->selectedFiles)) {


    //         $files = File::WhereIn('id', $this->selectedFiles)->get();


    //         if ($files) {
    //             $zipFile = 'Arquivos-' . $this->note->note . "-" . hash('crc32', time()) . '.zip';
    //             $zip     = new ZipArchive();
    //             $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    //             foreach ($files as $file) {
    //                 $content = Storage::get($file->path);
    //                 $zip->addFromString(explode('.', $file->file_name)[0] . '.' . $file->ext, $content);
    //             }

    //             $zip->close();

    //             $this->selectedFiles = [];

    //             return response()->download($zipFile)->deleteFileAfterSend(true);
    //         }
    //     }
    // }

    public function getSubcategoriesProperty()
    {
        return Subcategory::where('category_id', $this->category_id)
            ->orderBy('name')
            ->get();
    }


    public function returnReclaims()
    {

        if (!$this->service_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'O Serviço de Retorno é Obrigatório',
                'timer'    => 5000,
            ]);

            return;
        }

        if (!$this->subcategory_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Motivo de Retorno Obrigatório',
                'timer'    => 5000,
            ]);

            return;
        }


        $this->dispatchBrowserEvent('alertar', [
            'title'         => "Retornar Internamente {$this->note->note}?",
            'msg'           => "Você está preste a retornar a Nota/OV {$this->note->note} para o serviço de {$this->services->where('uuid', $this->service_id)->first()->service}.<br><br> Deseja realmente retornar esta NOTA/OV?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Retornar!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'confirm_return',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma NOTA/OV foi retornada!',

        ]);

        return;
    }

    public function confirm_return()
    {
        if (!$this->note->External) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'PROTOCOLO NÃO ENCONTRADO',
                'html'     => 'Não foi possível retornar a Nota/OV. O Protocolo não foi encontrado.',
                'timer'    => 5000,
            ]);

            return;
        }

        DB::beginTransaction();

        try {
            if (Reclaim::hasActiveForService($this->note->id, $this->service_id)) {
                DB::rollBack();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'RECLAIM JÁ EM ANDAMENTO',
                    'html'     => 'Já existe retorno interno ativo para esta obra e serviço.',
                    'timer'    => 5000,
                ]);

                return;
            }

            $reclaim = Reclaim::create([
                'user_id' => Auth()->User()->id,
                'service_id' => $this->service_id,
                'note_id' => $this->note->id,
                'category' => $this->subcategories->where('id', $this->subcategory_id)->first()->category->name,
                'subcategory_id' => $this->subcategory_id,
            ]);

            $reclaim->Comments()->Create(
                [
                    'user_id' => Auth()->User()->id,
                    'message' => $this->reason,
                ]
            );


            $this->note->External->Reclaims()->attach($reclaim->id);

            if ($reclaim && $this->production) {

                $production = Production::create([
                    'user_id' => $this->production->user_id,
                    'service_id' => $this->service_id,
                    'note_id' => $this->note->id,
                    'company_id' => $this->production->user->company_id,
                    'dispatch_by' => auth()->user()->id,
                    'att_by' => auth()->user()->id,
                    'dispatch_at' => now(),
                    'att_at' => now(),
                    'completed' => false,
                    'status' => 2,
                    'dt_note' => $this->note->dt_status,
                ]);

                if ($production) {
                    $reclaim->production_id = $production->id;
                    $reclaim->save();
                }
            }



        } catch (\Throwable $th) {

            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO RETORNAR',
                'html'     => 'Msg: ' . $th->getMessage(),

            ]);
            return;
        }

        DB::commit();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'RETORNADO COM SUCESSO',
            // 'html'     => 'Acompanhe .',
            'timer'    => 2500,
        ]);

        $this->cleanAll();
        $this->dispatchBrowserEvent('hideModal');

        return;

    }



    public function render()
    {
        return view('livewire.services.oexterno.actions.protocols', [
            'subcategories' => $this->subcategories,
        ]);
    }
}
