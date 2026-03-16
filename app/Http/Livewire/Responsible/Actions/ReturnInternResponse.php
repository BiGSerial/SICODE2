<?php

namespace App\Http\Livewire\Responsible\Actions;

use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\Viability;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnInternResponse extends Component
{
    public ?Viability $viability = null;
    public $decision;
    public $responser;
    public $serviceList;
    public $category;
    public $destination;

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
        'decision.required' => 'Por favor, é preciso definir uma decisão.',
        'category.required' => 'Por favor, selecione um motivo para devolução.',
        'destination.required' => 'Por favor, selecione uma opção em Destinação.',
        'service.required' => 'Por favor, selecione um serviço quando a opção for Devolver.',
    ];



    public function getInfoResponse(Viability $viability)
    {
        $this->justClean();

        $this->viability = $viability;
        $this->serviceList = Service::where('canReturn', true)->orderBy('service')->get();

        if ($this->viability) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_resp_viability',
            ]);
        }
    }

    public function updatedDecision()
    {
        $this->destination = null;
        $this->category = null;
        $this->service = null;

        $this->resetErrorBag();
    }

    public function updateDestination()
    {

        $this->category = null;
        $this->service = null;

        $this->resetErrorBag();
    }

    public function updatedService($uuid)
    {
        $this->resetErrorBag();

        $this->category = null;

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

        ]);

        if ($this->decision) {
            $this->validate([
                'destination' => 'required',

            ]);
        }
        # code...

        if ($this->destination === 'DEVOLVER') {
            $this->validate([
                'category' => 'required',
                'service' => 'required',
            ]);
        }


        if ($this->destination === 'RETORNAR' || $this->destination === 'DEVOLVER') {

            $this->validate([
                'responser' => 'required',
            ]);
        }



        $text = "Você está decidindo, <strong>";
        $text .= $this->destination. "  </strong> ";

        if ($this->destination === 'DEVOLVER') {
            $service = Service::where('uuid', $this->service)->first()->service;
            $text .= "o serviço para <strong> $service </strong> ";
            $text .= "pelo motivo de <strong>{$this->category}</strong> ";
        }



        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'AO RETORNO INTERNO',
            'msg'           => "Você informou <strong>{$this->decision}</strong> o retorno da Atividade. <br><br> {$text} <br><br> Deseja Continuar o Envio?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'd1c6b8f9b3a1d0a2e3f4b5c6d7e8f9a0',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
        ]);

        return;




        // if ($this->decision === 'DISCORDAR') {



        //     $this->dispatchBrowserEvent('alertar', [
        //         'title'         => 'VIABILIDADE RESPOSTA',
        //         'msg'           => "Você diz <strong>{$this->decision}</strong> com(da) decisão. Deseja Continuar o Envio?",
        //         'icon'          => 'question',
        //         'btnOktxt'      => 'Sim, Continue!',
        //         'btnCanceltxt'  => 'Não, Cancele',
        //         'action'        => 'd41d8cd98f00b204e9800998ecf8427e',
        //         // 'chave'         => '',
        //         'cancel_titulo' => 'Cancelado!',
        //         'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
        //     ]);

        //     return;
        // }

    }



    public function confirm_response()
    {
        if ($this->decision === 'APROVADO') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->responser .= "\n\n >> O RESPONSÁVEL APROVOU A RESOLUÇÃO. <<";



            if ($this->viability) {

                DB::beginTransaction();

                try {


                    if ($this->destination === 'LIBERAR') {

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
                    }


                    if ($this->destination === 'RETORNAR_VIAB') {

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
                    }

                    DB::commit();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'RESOLUÇÃO APROVADA',
                        'html'      => 'A Resolução foi aprovada com sucesso!',
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
                        'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realizada.. <br><br> ' . $th->getMessage(),

                    ]);
                    $this->clean();

                }
            }

            return;
        }

        if ($this->decision === 'REPROVADO') {

            if ($this->destination === 'DEVOLVER') {

                DB::beginTransaction();

                try {
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

                        $production = Production::where('note_id', $this->viability->note_id)->where('service_id', $this->service)->get()->last();
                        // Verifica se o usuário foi excluído
                        if (!$production || $production->User->trashed()) {

                            $reclaim = $this->viability->Reclaims()->create([
                                'note_id' => $this->viability->note_id,
                                'service_id' => $this->service,
                                'category' => $this->category,
                            ]);

                            if ($reclaim) {
                                $reclaim->Comments()->create([
                                    'user_id' => auth()->user()->id,
                                    'message' => $this->responser ?? null,
                                    'dismissed' => false,
                                    'granted' => true,
                                ]);
                            }

                        } elseif ($production) {
                            $pro = Production::create([
                                'note_id' => $production->note_id,
                                'service_id' => $this->service,
                                'user_id' => $production->user_id,
                                'company_id' => isset(Auth()->user()->Company->id) ? Auth()->user()->Company->id : Auth()->user()->Employee->Contract->company->id,
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


                            DB::commit();

                            $this->dispatchBrowserEvent('swal', [
                                'position' => 'center',
                                'icon'     => 'success',
                                'title'    => 'ENVIADO COM SUCESSO',
                                'html'      => 'A Obra foi devolvida com sucesso!',
                                'timer'    => 5000,

                            ]);

                            $this->emitUp('refresh');
                            $this->clean();

                        }
                    }
                } catch (\Throwable $th) {

                    DB::rollback();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'danger',
                        'title'    => 'Erro',
                        'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realizada.. <br><br> ' . $th->getMessage(),

                    ]);
                    return;
                }
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'danger',
                    'title'    => 'Erro',
                    'html'      => 'Ocorreu algum problema, nao foi possível indentificar a destinação. Tente novamente. <br><br> ',
                    'timer'    => 5000,
                ]);
            }

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'danger',
            'title'    => 'Erro',
            'html'      => 'O Sistema não conseguiu determinar a decisão sobre essa atividade. Por favor, tente novamente.',
            'timer'    => 5000,

        ]);

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

    public function justClean()
    {
        $this->decision = '';
        $this->responser = '';
        $this->service = '';
        $this->options = '';
        $this->show = false;
        $this->text = '';
        $this->category = '';
        $this->destination = '';
        $this->production = '';
    }

    public function clean()
    {

        $this->viability = null;
        $this->decision = '';
        $this->responser = '';
        $this->service = '';
        $this->options = '';
        $this->show = false;
        $this->text = '';
        $this->category = '';
        $this->destination = '';
        $this->production = '';

        $this->dispatchBrowserEvent('hideModal');

    }

    public function render()
    {
        return view('livewire.responsible.actions.return-intern-response');
    }
}
