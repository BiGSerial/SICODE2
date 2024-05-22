<?php

namespace App\Http\Livewire\Construction\Responser\Actions;

use App\Models\File;
use App\Models\Note;
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

    protected $listeners = [
        'getInfoPartnerViab',
        'confirm_response',
    ];

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

            return;
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

    public function confirm_response()
    {
        if ($this->decision === 'CONCORDAR') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->responser .= "\n\n >> EMPRESA PARCEIRA CONCORDA COM SEGUIMENTO PARA CONTRATAÇÃO. <<";


            if ($this->note->Viabilities->count()) {

                foreach ($this->note->Viabilities as $viability) {
                    DB::beginTransaction();

                    try {
                        // Atualize a viabilidade
                        $viability->update([
                            'approved' => true,
                            'rejected' => false,
                            'treplica' => true,
                            'completed' => $viability->hired ? true : false,
                            'completed_at' => $viability->hired ? date('Y-m-d H:i:s') : null,
                            'status' => $viability->hired ? 9 : 6,
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
                            'title'    => 'Contestação Aceita',
                            'html'      => 'Foi confirmado junto a contratante o parecer da viabilidade.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh_list');
                        $this->clean();

                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realizada..',
                            'timer'    => 5000,
                        ]);
                        $this->clean();

                    }
                }
            }
        }

        if ($this->decision === 'DISCORDAR') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->responser .= "\n\n >> EMPRESA PARCEIRA MANTÉM A REJEIÇÃO DA VIABILIDADE TÉCNICA APRESENTADA. <<";

            if ($this->note->Viabilities->count()) {
                foreach ($this->note->Viabilities as $viability) {
                    DB::beginTransaction();

                    try {
                        // Atualize a viabilidade
                        $viability->update([
                            'approved' => false,
                            'treplica' => true,
                            'status' => 4,
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
                            'title'    => 'Contestação Mantida',
                            'html'      => 'Foi confirmado junto a contratante o parecer da viabilidade.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh_list');
                        $this->clean();

                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realiazada..',
                            'timer'    => 5000,
                        ]);
                        $this->clean();
                    }
                }
            }

        }


    }

    public function isTextValid($text)
    {
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
        $containsLetter = preg_match('/[a-zA-Z]/', $text);
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


    public function render()
    {
        return view('livewire.construction.responser.actions.responserpartners');
    }
}
