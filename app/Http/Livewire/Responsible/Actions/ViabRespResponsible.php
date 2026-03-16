<?php

namespace App\Http\Livewire\Responsible\Actions;

use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ViabRespResponsible extends Component
{
    public ?Viability $viability = null;
    public $decision;
    public $responser;
    public $serviceList;
    public $category;

    public $service;
    public $options;
    public $production;
    public $show = false;
    public $text;


    protected $listeners = [
        'getInfoResponse',
        'confirm_response',
        'd1c6b8f9b3a1d0a2e3f4b5c6d7e8f9a0' => 'confirm_response',
        'd41d8cd98f00b204e9800998ecf8427e' => 'confirm_deny',
    ];


    protected $messages = [
        'decision.required' => 'Por favor, selecione uma decisão.',
        'responser.required' => 'Por favor, forneça uma resposta.',
        'responser.min' => 'A resposta deve ter no mínimo 10 caracteres.',
        'options.required' => 'Por favor, selecione uma opção quando a decisão for Concordar.',
        'service.required' => 'Por favor, selecione um serviço quando a opção for Devolver.',
        'category.required' => 'Por favor, selecione uma categoria quando a opção for Devolver.',
    ];


    public function getInfoResponse(Viability $viability)
    {
        $this->viability = $viability;
        $this->serviceList = Service::where('canReturn', true)->orderBy('service')->get();

        if ($this->viability) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_resp_viability',
            ]);
        }
    }

    public function updatedService($uuid)
    {
        if ($uuid) {
            $this->production = Production::where('note_id', $this->viability->note_id)->where('service_id', $uuid)->get()->last();

            if (!$this->production) {
                $this->show = true;
                $this->text = '<h5 class="text-center">USUÁRIO NÃO ENCONTRADO</h5>
                                                                    <p>
                                                                        Não foi encontrado um usário para retono direto.
                                                                        Porém, o projeto será retornado para o POOL de
                                                                        atividades do serviço selecionado e
                                                                        um responsável pela atividade poderá direcionar
                                                                        a um usuário possível.
                                                                    </p>
                ';
            } else {
                $this->show = false;
            }

        } else {
            $this->production = null;
        }
    }

    public function updatedOptions($value)
    {
        if ($value !== 'DEVOLVER') {
            $this->production = null;
            $this->show = false;
        } else {
            $this->show = false;
            $this->text = '<h5 class="text-center">USUÁRIO NÃO ENCONTRADO</h5>
                                                                <p>
                                                                    Não foi encontrado um usário para retono direto.
                                                                    Porém, o projeto será retornado para o POOL de
                                                                    atividades do serviço selecionado e
                                                                    um responsável pela atividade poderá direcionar
                                                                    a um usuário possível.
                                                                </p>
            ';
        }

        if ($value === 'EXECUTADA') {
            $this->show = true;
            $this->text = '<h5 class="text-center">OBRA JA EXECUTADA</h5>
                                                                <p>
                                                                    O Sistema irá encerrar esta viabilidade como realizado.
                                                                </p>
                                                                <p>O Fluxo continuará nas etapas seguintes confome as situações oriundas do SAP.</p>
            ';
        } elseif ($value === 'LIBERAR') {
            $this->show = true;
            $this->text = '<h5 class="text-center">LIBERAR PARA CONTRATAÇÃO</h5>
                                                                <p>
                                                                    Liberar a viabilidade para continuar com a contratação. (Caso ainda não tenha sido contratada, caso contrário, so será encerrado a viabilidade normalmente.)
                                                                </p>



            ';
        } elseif ($value === 'RETORNAR') {
            $this->show = true;
            $this->text = '<h5 class="text-center">LIBERAR NOVA VIABILIDADE</h5>
                                                                <p>
                                                                    A Viabilidade será retornado para a parceira para realizar novamente a viabilidade técnica. A data de envio será alterada para este momento com novo prazo de até 21 dias para conclusão.
                                                                </p>

                                                                  ';
        }

    }



    public function toResponser()
    {
        $this->validate([
            'decision' => 'required',
            'responser' => 'required|min:10',
        ]);

        if ($this->decision === 'CONCORDAR') {



            $this->validate([
                'options' => 'required',
            ]);


            if ($this->options === 'DEVOLVER') {
                $this->validate([
                    'service' => 'required',
                    'category' => 'required',
                ]);
            }


            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'VIABILIDADE RESPOSTA',
                'msg'           => "Você diz <strong>{$this->decision}</strong> com(da) decisão. Deseja Continuar o Envio?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'd1c6b8f9b3a1d0a2e3f4b5c6d7e8f9a0',
                // 'chave'         => '',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
            ]);

            return;
        }



        if ($this->decision === 'DISCORDAR') {




            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'VIABILIDADE RESPOSTA',
                'msg'           => "Você diz <strong>{$this->decision}</strong> com(da) decisão. Deseja Continuar o Envio?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'd41d8cd98f00b204e9800998ecf8427e',
                // 'chave'         => '',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
            ]);

            return;
        }


    }


    public function confirm_response()
    {
        // dd('confirm_response'); //Removido para a versão final
        if ($this->decision === 'CONCORDAR') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->responser .= "\n\n >> O RESPONSÁVEL CONCORDA COM QUESTIONAMENTO. <<";

            if ($this->viability) {

                // Processo de DEVOLVER
                if ($this->options === 'DEVOLVER') {

                    DB::beginTransaction();
                    try {
                        $this->responser .= "\n\n >> O RESPONSÁVEL DEVOLVEU PARA ETAPA DE PROJETO. <<";

                        // Atualize a viabilidade
                        $this->viability->update([
                            'approved' => false,
                            'rejected' => true,
                            'treplica' => true,
                            'completed' => $this->viability->hired ? true : false,
                            'completed_at' => $this->viability->hired ? now() : null,
                            'status' => 10,
                        ]);

                        // Crie um novo comentário e associe-o à viabilidade
                        $this->viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->responser ?? null,
                            'dismissed' => false,
                            'granted' => true,

                        ]);

                        if ($this->service) {
                            if (Reclaim::hasActiveForService($this->viability->note_id, $this->service)) {
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

                            $production = Production::where('note_id', $this->viability->note_id)->where('service_id', $this->service)->latest()->first();


                            // Verifica se o usuário foi excluído
                            if (!$production || $production->User->trashed()) {

                                $reclaim = $this->viability->Reclaims()->create([
                                    'note_id' => $this->viability->note_id,
                                    'service_id' => $this->service,
                                    'category' => 'RESOLUÇAO DE VIABILIDADE',
                                ]);

                                if ($reclaim) {
                                    $reclaim->Comments()->create([
                                        'user_id' => auth()->user()->id,
                                        'message' => $this->responser ?? null,
                                        'dismissed' => false,
                                        'granted' => true,
                                    ]);
                                }

                            } else {
                                $pro = Production::create([
                                    'note_id' => $production->note_id,
                                    'service_id' => $this->service,
                                    'user_id' => $production->user_id,
                                    'company_id' => $production->company_id,
                                    'dispatch_by' => Auth()->user()->id,
                                    'dispatch_at' => now(),
                                    'att_by' => Auth()->user()->id,
                                    'att_at' => now(),
                                    'status' => 2,
                                    'd5' => true,
                                    'dt_note' => $production->dt_note,
                                    'status_note' => $production->Note->nstats,
                                    'centroTrab' => $production->centroTrab,
                                ]);

                                if ($pro) {
                                    $reclaim = $this->viability->Reclaims()->create([
                                        'note_id' => $production->note_id,
                                        'service_id' => $this->service,
                                        'production_id' => $pro->id,
                                        'category' => $this->category,
                                    ]);


                                    if ($reclaim) {
                                        $reclaim->Comments()->create([
                                            'user_id' => auth()->user()->id,
                                            'message' => $this->responser ?? null,
                                            'dismissed' => false,
                                            'granted' => true,
                                        ]);


                                        $this->viability->update([
                                            'status' => 11,
                                        ]);
                                    }
                                }


                            }
                        }
                        DB::commit();
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'CONTESTAÇÃO ACEITA',
                            'html'      => 'Foi confirmado junto a contratante o parecer da viabilidade. Obra devolvida para etapa de projeto.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh');
                        $this->clean();

                    } catch (\Throwable $th) {
                        DB::rollback();
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu um erro ao processar a devolução para a etapa de projeto. Nenhuma alteração foi realizada..<br><br>' . $th->getMessage(),

                        ]);
                        $this->clean();
                    }

                    return; // Importante: Retornar após processar DEVOLVER
                }

                // Processo de EXECUTADA
                if ($this->options === 'EXECUTADA') {

                    DB::beginTransaction();
                    try {
                        $this->responser .= "\n\n >> O RESPONSÁVEL INFORMA OBRA JÁ EXECUTADA. <<";
                        $this->viability->update([
                            'approved' => true,
                            'rejected' => false,
                            'treplica' => true,
                            'completed' => $this->viability->hired ? true : false,
                            'completed_at' => $this->viability->hired ? now() : null,
                            'status' => $this->viability->hired ? 9 : 6,
                        ]);

                        // Crie um novo comentário e associe-o à viabilidade
                        $this->viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->responser ?? null,
                            'dismissed' => false,
                            'granted' => true,

                        ]);

                        DB::commit();
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'OBRA EXECUTADA',
                            'html'      => 'Você Sinalizou que a obra foi executada.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh');
                        $this->clean();


                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu um erro ao processar a informação de obra executada. Nenhuma alteração foi realizada..<br><br>' . $th->getMessage(),

                        ]);
                        $this->clean();
                    }

                    return; // Importante: Retornar após processar EXECUTADA
                }

                // Processo de LIBERAR
                if ($this->options === 'LIBERAR') {
                    DB::beginTransaction();
                    try {
                        $this->responser .= "\n\n >> O RESPONSÁVEL LIBEROU PARA CONTRATAÇÂO. <<";
                        $this->viability->update([
                            'approved' => true,
                            'rejected' => false,
                            'treplica' => true,
                            'completed' => $this->viability->hired ? true : false,
                            'completed_at' => $this->viability->hired ? now() : null,
                            'status' => $this->viability->hired ? 9 : 6,
                        ]);

                        // Crie um novo comentário e associe-o à viabilidade
                        $this->viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->responser ?? null,
                            'dismissed' => false,
                            'granted' => true,

                        ]);

                        DB::commit();
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'OBRA LIBERADA',
                            'html'      => 'Você Liberou a obra com sucesso.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh');
                        $this->clean();

                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu um erro ao processar a liberação da obra. Nenhuma alteração foi realizada..<br><br>' . $th->getMessage(),

                        ]);
                        $this->clean();
                    }

                    return; // Importante: Retornar após processar LIBERAR
                }

                // Processo de RETORNAR
                if ($this->options === 'RETORNAR') {

                    try {
                        $this->responser .= "\n\n >> O RESPONSÁVEL RETORNOU PARA REFAZER A VIABILIDADE. <<";

                        $this->viability->update([
                            'approved' => false,
                            'rejected' => false,
                            'treplica' => false,
                            'replica' => false,
                            'sended_at' => now(),
                            'completed' => false,
                            'completed_at' => null,
                            'returned_at' => null,
                            'tacit' => false,
                            'tacit_at' => null,
                            'status' => 1,

                        ]);

                        $this->viability->Days()->delete();

                        // Crie um novo comentário e associe-o à viabilidade
                        $this->viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->responser ?? null,
                            'dismissed' => false,
                            'granted' => true,

                        ]);

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'Contestação Aceita',
                            'html'      => 'Foi confirmado junto a contratante o parecer da viabilidade.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('refresh');
                        $this->clean();

                    } catch (\Throwable $th) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu um erro ao processar o retorno para refazer a viabilidade. Nenhuma alteração foi realizada..<br><br>' . $th->getMessage(),

                        ]);
                        $this->clean();

                    }
                    return; // Importante: Retornar após processar RETORNAR
                }


            }
        }


    }

    public function confirm_deny()
    {


        if ($this->decision === 'DISCORDAR') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->responser .= "\n\n <br><br> >> RESPONSÁVEL DISCORDOU DO QUESTIONAMENTO. <<";


            if ($this->viability) {

                DB::beginTransaction();

                try {
                    // Atualize a viabilidade
                    $this->viability->update([
                        'approved' => false,
                        'rejected' => true,
                        'replica' => true,
                        // 'completed' => $this->viability->hired ? true : false,
                        // 'completed_at' => $this->viability->hired ? date('Y-m-d H:i:s') : null,
                        'status' => 5,
                    ]);

                    // Crie um novo comentário e associe-o à viabilidade
                    $this->viability->Comments()->create([
                        'user_id' => auth()->user()->id,
                        'message' => $this->responser ?? null,
                        'dismissed' => true,
                        'granted' => false,

                    ]);

                    DB::commit();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Contestação Rejeitada',
                        'html'      => 'Foi rejeitado com sucesso a contestação do parceiro.',
                        'timer'    => 5000,
                    ]);

                    $this->emitUp('refresh');
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
        $this->decision = null;
        $this->responser = null;
        $this->service = null;
        $this->options = null;
        $this->show = false;
        $this->category = null;
        $this->text = null;
    }

    public function render()
    {
        return view('livewire.responsible.actions.viab-resp-responsible');
    }
}
