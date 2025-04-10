<?php

namespace App\Http\Livewire\Partner\Actions;

use App\Models\Note;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Responserviab extends Component
{
    public ?Viability $viability = null;
    public $decision;
    public $responser;

    protected $listeners = [
        'getInfoResponse',
        'confirm_response',
        'savedFiles',
        'continue',
    ];


    public function getInfoResponse(Viability $viability)
    {
        $this->viability = $viability;

        if ($this->viability) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_resp_viability',
            ]);
        }
    }

    public function continue()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->viability = null;
        $this->emitUp('refresh_list');
    }

    public function savedFiles()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->viability = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Arquivos Salvos com Sucesso!',
            'timer'    => 5000,
        ]);
        $this->emitUp('refresh_list');
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


            if ($this->viability) {

                DB::beginTransaction();

                try {
                    // Atualize a viabilidade
                    $this->viability->update([
                        'approved' => true,
                        'rejected' => false,
                        'treplica' => true,
                        'completed' => $this->viability->hired ? true : false,
                        'completed_at' => $this->viability->hired ? date('Y-m-d H:i:s') : null,
                        'status' => $this->viability->hired ? 9 : 6,
                    ]);

                    // Crie um novo comentário e associe-o à viabilidade
                    $this->viability->Comments()->create([
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

        if ($this->decision === 'DISCORDAR') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->responser .= "\n\n >> EMPRESA PARCEIRA MANTÉM A REJEIÇÃO DA VIABILIDADE TÉCNICA APRESENTADA. <<";

            if ($this->Viabilities) {
                DB::beginTransaction();

                try {
                    // Atualize a viabilidade
                    $this->viability->update([
                        'approved' => false,
                        'treplica' => true,
                        'status' => 4,
                    ]);

                    // Crie um novo comentário e associe-o à viabilidade
                    $this->viability->Comments()->create([
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
            'qwerqwer',
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



    public function clean()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->viability = null;
    }

    public function render()
    {
        return view('livewire.partner.actions.responserviab');
    }
}
