<?php

namespace App\Http\Livewire\Services\Oexterno\Actions;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;

use Livewire\Component;
use ZipArchive;

class Protocols extends Component
{
    public ?Note $note = null;
    public $selType;
    public $selAgency;
    public $protocol = [];
    public $comment = [];
    public $selectedFiles = [];

    protected $listeners = [
        'openProtocol',
        'cleanAll'
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
        $this->cleanAll();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'SALVO COM SUCESSO',

            'timer'    => 2500,
        ]);
    }

    public function cleanAll()
    {
        $this->emitUp('refresh_list');
        $this->selType = '';
        $this->selAgency = '';
        $this->protocol = [];
        $this->comment = [];
    }

    public function downloadFile(File $file)
    {
        if ($file) {

            if (Storage::fileExists($file->path)) {
                return Storage::download($file->path, explode('.', $file->file_name)[0] . "." . $file->ext);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }
    }

    public function zipFiles()
    {
        if (!count($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM ARQUIVO SELECIONADO',
                'timer'    => 5000,
            ]);

            return;
        }

        if (count($this->selectedFiles)) {


            $files = File::WhereIn('id', $this->selectedFiles)->get();


            if ($files) {
                $zipFile = 'Arquivos-' . $this->note->note . "-" . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString(explode('.', $file->file_name)[0] . '.' . $file->ext, $content);
                }

                $zip->close();

                $this->selectedFiles = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        }
    }


    public function render()
    {
        return view('livewire.services.oexterno.actions.protocols');
    }
}
