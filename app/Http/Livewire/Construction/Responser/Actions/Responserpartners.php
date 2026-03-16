<?php

namespace App\Http\Livewire\Construction\Responser\Actions;

use App\Models\File;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use ZipArchive;

class Responserpartners extends Component
{
    public ?Note $note = null;
    public $selectedFiles = [];
    public $decision;
    public $responser;
    public $services;
    public $service;
    public $production;
    public $category;

    protected $listeners = [
        'getInfoPartnerViab',
        'confirm_response' => 'confirm_responser',
        'closeAll',
    ];

    public function mount()
    {
        $this->services = Service::where('canReturn', true)->orderBy('service')->get();
    }

    public function updatedService($service)
    {
        if ($service) {
            $this->production = Production::where('service_id', $service)->where('note_id', $this->note->id)->where('completed', true)->get();
        } else {
            $this->production = null;
        }

        if ($this->production) {
            $this->production = $this->production->last();
        }
    }

    public function getInfoPartnerViab(Note $note)
    {
        $this->note = $note;

        if ($this->note) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'responserPartners',
            ]);
        }
    }

    public function toResponser()
    {


        if ($this->note->Viabilities->where('completed', false)->last()->treplica) {


            $this->decision = 'CONCORDAR';
        }



        if (!trim($this->responser) ||  !$this->decision) {



            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Informar Decisão e Texto são obrigatórios',
                'timer'    => 2500,
            ]);

            return;

        } elseif (strlen(trim($this->responser)) < 10) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Um breve resumo é obrigatório.',
                'timer'    => 2500,
            ]);

            return;
        }

        if ($this->isTextValid($this->responser)) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'VIABILIDADE RESPOSTA',
                'msg'           => "Você diz <strong>{$this->decision}</strong> com(da) decisão. Deseja Continuar o Envio?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_response',
                // 'chave'         => '',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
            ]);



        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ERRO DE TEXTO.',
                'html'    => 'Um texto válido é obrigatório para entendimento entre as partes. Gentileza corrigir o texto e tentar novamente.',
                'timer'    => 5000,
            ]);

            return;
        }

    }

    public function confirm_responser()
    {


        if ($this->decision === 'CONCORDAR') {



            DB::beginTransaction();

            try {

                if (!$this->category) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Informar a Categoria é obrigatório',
                        'timer'    => 2500,
                    ]);

                    return;
                }

                // Acrescenta decisão da Empreiteira a mensagem postada.
                // $this->responser .= "\n\n >> EMPRESA CONTRATANTE CONCORDA COM O RESULTADO DA VIABILIDADE FEITO PELA PARECEIRA. <<";

                $targetServiceId = $this->production ? $this->production->service_id : $this->service;
                if ($targetServiceId && Reclaim::hasActiveForService($this->note->id, $targetServiceId)) {
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

                if ($this->production) {

                    // dd($this->production);

                    $production = Production::Create([
                        'note_id' => $this->production->note_id,
                        'service_id' => $this->production->service_id,
                        'company_id' => $this->production->company_id,
                        'user_id' => $this->production->user_id,
                        'att_by' => Auth()->User()->id,
                        'dispatch_by' => Auth()->User()->id,
                        'dispatch_at' => date('Y-m-d H:i:s'),
                        'att_at' => date('Y-m-d H:i:s'),
                        'dt_note' => $this->note->dt_status,
                        'status_note' => $this->note->nstats,
                        'status' => 2,
                        'd5' => true,
                    ]);

                    if ($production) {

                        $return = Reclaim::create([
                            'note_id' => $production->note_id,
                            'service_id' => $production->service_id,
                            'production_id' => $production->id,
                            'category' => $this->category,
                        ]);

                        $return->Comments()->create([
                            'user_id' => Auth()->User()->id,
                            'message' => $this->responser
                        ]);

                        if ($return && $this->note->Viabilities->where('completed', false)->count()) {


                            foreach ($this->note->Viabilities->where('completed', false) as $viab) {
                                // dd($viab);
                                $viab->update([
                                    'status' => 12,
                                    'engineer' => true,
                                    'engineer_at' => date('Y-m-d H:i:s'),
                                ]);

                                $viab->Reclaims()->attach($return->id);
                                $viab->Comments()->create([
                                    'user_id' => auth()->user()->id,
                                    'message' => '>>> RRESPONSÁVEL INFORMOU CONFORMIDADE COM A VIABILIDADE <<<',

                                ]);


                            }

                        } else {
                            DB::rollback();

                            $this->dispatchBrowserEvent('swal', [
                                'position' => 'center',
                                'icon'     => 'warning',
                                'title'    => 'Ocorreu um erro individual. tente novamente.',
                                'timer'    => 8000,
                            ]);

                            return;
                        }

                        DB::commit();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'ACEITA CONTESTAÇÃO',
                            'html'      => 'O Resultado da Viabilidade foi Acesita com sucesso, e obra despachada com sucesso.',
                            'timer'    => 5000,
                        ]);


                        $this->emitTo('construction.responser.main', 'refresh_main');
                        $this->closeAll();
                    }
                } else {

                    try {

                        $return = Reclaim::create([
                            'note_id' => $this->note->id,
                            'service_id' => $this->service,
                            'category' => $this->category,
                        ]);

                        $return->Comments()->create([
                            'user_id' => Auth()->User()->id,
                            'message' => $this->responser
                        ]);

                        if ($return && $this->note->Viabilities->where('completed', false)->count()) {


                            foreach ($this->note->Viabilities->where('completed', false) as $viab) {
                                // dd($viab);
                                $viab->update([
                                    'status' => 12,
                                    'engineer' => true,
                                    'engineer_at' => date('Y-m-d H:i:s'),
                                ]);

                                $viab->Reclaims()->attach($return->id);
                                $viab->Comments()->create([
                                    'user_id' => auth()->user()->id,
                                    'message' => '>>> RRESPONSÁVEL INFORMOU CONFORMIDADE COM A VIABILIDADE <<<',

                                ]);


                            }

                        } else {
                            DB::rollback();

                            $this->dispatchBrowserEvent('swal', [
                                'position' => 'center',
                                'icon'     => 'warning',
                                'title'    => 'Ocorreu um erro individual. tente novamente.',
                                'timer'    => 8000,
                            ]);

                            return;
                        }

                        DB::commit();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'ACEITA CONTESTAÇÃO',
                            'html'      => 'O Resultado da Viabilidade foi Acesita com sucesso, e obra despachada com sucesso.',
                            'timer'    => 5000,
                        ]);


                        $this->emitTo('construction.responser.main', 'refresh_main');
                        $this->closeAll();

                    } catch (\Throwable $th) {

                        DB::rollback();

                        dd($th->getMessage());

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'warning',
                            'title'    => 'Ocorreu um erro individual. tente novamente.',
                            'timer'    => 8000,
                        ]);

                        return;
                    }
                }



            } catch (\Throwable $th) {
                DB::rollback();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'Ocorreu uma Falha e Nao conseguimos registrar.',
                    'timer'    => 8000,
                ]);




                return;
            }
        }

        if ($this->decision === 'DISCORDAR') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            // $this->responser .= "\n\n >> EMPRESA PARCEIRA MANTÉM A REJEIÇÃO DA VIABILIDADE TÉCNICA APRESENTADA. <<";



            if ($this->note->Viabilities->where('completed', false)->count()) {

                foreach ($this->note->Viabilities->where('completed', false) as $viability) {

                    DB::beginTransaction();

                    try {
                        // Atualize a viabilidade
                        $viability->update([
                            'approved' => false,
                            'replica' => true,
                            'status' => 5,
                        ]);

                        // Crie um novo comentário e associe-o à viabilidade
                        $viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->responser ?? null,

                        ]);

                        DB::commit();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'CONTESTAÇÃO AO PARCEIRO',
                            'html'      => 'Foi enviado sua contestação ao parceiro',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh_main');
                        $this->closeAll();

                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realiazada..',
                            'timer'    => 5000,
                        ]);

                        // dd($th->getMessage());

                    }
                }
            }

        }


    }

    public function isTextValid($text)
    {
        // Converte o texto para minúsculas para garantir que a verificação não seja case sensitive
        $text = strtolower($text);

        // Verificação de comprimento mínimo
        if (strlen($text) < 10) {
            return false;
        }

        // Verificação de caracteres repetidos
        $uniqueChars = count(array_unique(str_split($text)));
        if ($uniqueChars <= 2) {
            return false;
        }

        // Verificação de variação de caracteres
        $containsLetter = preg_match('/[a-z]/', $text); // Apenas letras minúsculas, pois o texto já foi convertido para minúsculas
        $containsDigit = preg_match('/[0-9]/', $text);
        if (!$containsLetter && !$containsDigit) {
            return false;
        }

        // Verificação de padrões comuns inadequados
        $commonPatterns = [
            '1234567890', 'abcdefghij',
            '9876543210', '0987654321',
            "qwer", "rewq",
            "wert", "trew",
            "erty", "ytre",
            "rtyu", "uytr",
            "tyui", "iuyt",
            "yuio", "oiuy",
            "uiop", "poiu",
            "asdf", "fdsa",
            "sdfg", "gfds",
            "dfgh", "hgfd",
            "fghj", "jhgf",
            "ghjk", "kjhg",
            "hjkl", "lkjh",
            "jklç", "çlkj",
            "zxcv", "vcxz",
            "xcvb", "bvcx",
            "cvbn", "nbvc",
            "vbnm", "mnbv"
        ];

        foreach ($commonPatterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }


    public function downloadFile(File $file)
    {
        if ($file) {

            if (Storage::fileExists($file->path)) {
                return Storage::download($file->path, explode('.', $file->file_name)[0].".".$file->ext);
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
        if(!count($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM ARQUIVO SELECIONADO',
                'timer'    => 5000,
            ]);

            return;
        }

        if(count($this->selectedFiles)) {


            $files = File::WhereIn('id', $this->selectedFiles)->get();


            if ($files) {
                $zipFile = 'Arquivos-'.$this->note->note."-" . hash('crc32', time()) . '.zip';
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




    public function closeAll()
    {

        $this->dispatchBrowserEvent('hideModal');
        $this->emitTo('construction.responser.main', 'refresh_main');

        $this->note = null;
        $this->selectedFiles = [];
        $this->decision = "";
        $this->responser = "";
        $this->service = "";
        $this->production = null;

    }


    public function render()
    {
        return view('livewire.construction.responser.actions.responserpartners');
    }
}
