<?php

namespace App\Http\Livewire\Services\Desenho\Forms;

use App\Helpers\SelectOptions;
use App\Models\{File, Note, Notetimeline, Production, ProjectReviewCycle, ProjectReviewFinding, ProjectReviewMessage, Reclaim, User};
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;

class Analise extends Component
{
    use WithFileUploads;

    public $view_form = false; // Ou o valor inicial que desejar

    public $ninst;

    public $nmedidor;

    public $patrimonio;

    public $lat;

    public $lon;

    public $carga_ini;

    public $carga_fim;

    public $queda;

    public $queda_max;

    public $queda_cliente;

    public $vao;

    public $restriction;

    public $motivo;

    public $conclusion;

    public $info;

    public $info_save;

    public $carta;

    public $card;

    public $alimentador;

    public $comprador;

    public $matricula;

    public $area;

    public $endereco;

    public $documento;

    public $count = 0;

    public $municipio;

    public $reserva;

    public $service_type;

    public $limit_pause = 5000;

    public $production;

    public $note;

    public $analise;

    public $postes;

    public $postes_c;

    public $odi;

    public $odd;

    public $ods;

    public $cadastro;

    public $iproject;

    public $eo;

    public $cad;

    public $preresult;

    public $reviewOrders = [];
    public $projectReviewLastSnapshot = null;
    public $order_input_number = '';
    public $order_input_total = '';
    public $order_input_company = '';
    public $order_input_client = '';
    public ?array $pendingReviewOrderInsert = null;

    public $designer_note;
    public $riRequest = null;

    public $rejectedFindings = [];
    public string $selectedReviewPointFilter = '';

    public $reviewMessages = [];

    public $newContestationMessage;
    public bool $viewOnlyProjectReview = false;
    public bool $allowProjectReviewHistory = false;
    public string $modalContext = 'finish';
    public bool $hasProjectReviewCycles = false;


    // Files
    public $files = [];

    public $needFiles = false;

    public $hasFile = false;

    public $show_files = [];

    public $nota_divergente;

    protected $listeners = [
        'open_analise_draw' => 'openAnalise',
        'analise_clean'     => 'clean',
        'confirm_goFinish'  => 'goFinish',
        'clean' => 'clean',
        'hasFile',
        'savedFiles',
        'continue' => 'toContinue',
        'projectReviewMessageCreated' => '$refresh',
        'goToFinishFlow' => 'goToFinishFlow',
        'openFinishModalFromReview' => 'openFinishModalFromReview',
        'openFinishConfirmation' => 'openFinishConfirmation',

    ];

    public function mount(string $modalContext = 'finish'): void
    {
        $this->modalContext = $modalContext;
    }

    public function openAnalise($data)
    {
        $isViewOnlyRequest = (bool) ($data['viewOnlyProjectReview'] ?? false);
        if ($this->modalContext === 'review' && !$isViewOnlyRequest) {
            return;
        }

        if ($this->modalContext === 'finish' && $isViewOnlyRequest) {
            return;
        }

        $this->clean();
        $this->clean_form();

        $productionId = $data['productionId'];
        $noteId       = $data['noteId'];
        $this->viewOnlyProjectReview = $isViewOnlyRequest;
        $this->allowProjectReviewHistory = (bool) ($data['allowProjectReviewHistory'] ?? false);

        $this->production = Production::withCount('ProjectReviewCycles')
            ->with('Note')
            ->find($productionId);
        $this->note = $this->production?->Note ?: Note::find($noteId);
        $this->hasProjectReviewCycles = ((int) ($this->production->project_review_cycles_count ?? 0)) > 0;

        if ($isViewOnlyRequest && !$this->canOpenProjectReviewReadonly()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'VISUALIZAÇÃO INDISPONÍVEL',
                'html'     => 'A atividade não está em um status válido para visualização da Análise de Projeto.',
                'timer'    => 3200,
            ]);
            return;
        }

        $this->loadRiRequestContext();
        $this->loadProjectReviewDraft();

        // Amarra a produção à análise: sempre resolve pelo vínculo da produção.
        // Se não existir, cria apenas uma vez e reutiliza.
        $this->analise = $this->production->Analise()->firstOrCreate([]);
        $analysisAlreadyExists = (bool) $this->analise->wasRecentlyCreated === false;

        if ($analysisAlreadyExists) {

            $this->conclusion = $this->analise->conclusion;
            $this->info       = $this->analise->info;
            $this->postes     = ($this->analise->postes && $this->analise->postes > 0) ? $this->analise->postes : (($this->production->postes_u && $this->production->postes_u > 0) ? $this->production->postes_u : $this->note->postes);
            $this->odi        = $this->production->odi;
            $this->odd        = $this->production->odd;
            $this->ods        = $this->production->ods;
            $this->cadastro   = $this->production->cadastro;
            $this->iproject   = $this->production->iproject;
            $this->eo         = $this->production->eo;
            $this->cad         = $this->production->cad;
            $this->postes_c   = $this->production->postes_c;
            $this->preresult  = $this->analise->preresult;

        } else {
            $this->clean_form();
            $this->postes = $this->note->postes;
        }

        if ($this->production && $this->note) {

            $time = 0;

            $isProjectReviewReturnStatus = $this->isProjectReviewTracked();

            if (!(bool) $this->production->completed && !$isProjectReviewReturnStatus) {
                if ($this->production->status === 4) {
                    $hist = Notetimeline::where('note_id', $this->production->note_id)->Where('service_id', $this->production->service_id)->where('status', 4)->orderBy('created_at', 'DESC')->first();

                    if ($hist) {
                        $time = (Carbon::parse($hist->created_at))->diffInSeconds(Carbon::now());
                        $hist->update(['return_stop' => date('Y-m-d H:i:s')]);
                    }
                }
                // Coloca nota em andamento
                $update = $this->production->update([
                    'status'  => 3,
                    'stopped' => $this->production->stopped + $time,
                ]);

                if ($update && $this->production->status !== 3) {
                    // Registra Movimento Nota
                    $user = Auth()->User()->name;

                    Notetimeline::Create([
                        'note_id'      => $this->note->id,
                        'service_id'   => $this->production->service_id,
                        'user_id'      => Auth()->User()->id,
                        'info'         => "Usuário {$user} iniciou a Nota/OV.",
                        'status'       => 3,
                        'productionId' => $this->production->id,
                    ]);
                }
            }

            if ($this->production->d5 && blank($this->conclusion)) {

                if ($this->production->Reclaim?->category && ($this->production->Reclaim?->category != 'LIBERAR EO')) {
                    $this->conclusion = $this->production->Reclaim->category;
                    $this->needFiles = true;
                    $this->updatedConclusion();
                } else {
                    $this->conclusion = 'RESOLUÇÃO INTERNA';

                    $this->updatedConclusion();
                }
            }

            $this->view_form = true;
        }
    }

    private function loadProjectReviewDraft(): void
    {
        if (!$this->production) {
            return;
        }

        $latestCycle = $this->production->ProjectReviewCycles()
            ->with([
                'Orders',
            ])
            ->latest('round_number')
            ->first();

        if ($latestCycle) {
            $this->designer_note = $latestCycle->designer_note;

            if ($latestCycle->Orders->count()) {
                $this->reviewOrders = $latestCycle->Orders->map(function ($order) {
                    return [
                        'order_number' => $order->order_number,
                        'total_cost' => number_format((float) $order->total_cost, 2, ',', '.'),
                        'company_cost' => number_format((float) $order->company_cost, 2, ',', '.'),
                        'client_cost' => number_format((float) $order->client_cost, 2, ',', '.'),
                        'locked' => true,
                    ];
                })->toArray();
            }

            if (in_array((int) $this->production->status, [
                Production::STATUS_IN_PROJECT_REVIEW,
                Production::STATUS_REJECTED_PROJECT_REVIEW,
                Production::STATUS_RELEASED_TO_FINISH,
            ], true)) {
                $latestCycle->loadMissing([
                    'Findings.Subcategory.Category',
                    'Findings.Item',
                    'Messages.User',
                ]);

                $findings = $latestCycle->Findings->values();

                $this->rejectedFindings = $this->mapRejectedFindingsForView($findings);
                $this->refreshReviewMessages();
            }
        }

        if (!count($this->reviewOrders)) {
            $this->reviewOrders = [];
        }

        $this->projectReviewLastSnapshot = $this->buildProjectReviewSnapshot();
    }

    private function loadRiRequestContext(): void
    {
        $this->riRequest = null;

        if (!$this->production || !$this->production->d5) {
            return;
        }

        $reclaim = $this->production->Reclaim()
            ->with(['Subcategory.Category', 'Comments.User'])
            ->first();

        if (!$reclaim) {
            $reclaim = Reclaim::query()
                ->with(['Subcategory.Category', 'Comments.User'])
                ->where('note_id', $this->production->note_id)
                ->where('service_id', $this->production->service_id)
                ->latest('id')
                ->first();
        }

        if (!$reclaim) {
            return;
        }

        $requestComment = $reclaim->Comments
            ->sortBy('created_at')
            ->first();

        $this->riRequest = [
            'category' => $reclaim->category ?: '---',
            'subcategory' => optional($reclaim->Subcategory)->subcategory ?: optional($reclaim->Subcategory)->name ?: '---',
            'subcategory_group' => optional(optional($reclaim->Subcategory)->Category)->category ?: optional(optional($reclaim->Subcategory)->Category)->name ?: '---',
            'message' => $requestComment?->message ?: null,
            'requested_by' => optional($requestComment?->User)->name ?: null,
            'requested_at' => $requestComment?->created_at ? date('d/m/Y H:i', strtotime($requestComment->created_at)) : null,
        ];
    }

    public function getRequiresProjectReviewProperty(): bool
    {
        if (!$this->production) {
            return false;
        }

        return !$this->production->partial && !$this->production->d5 && !$this->production->dfive;
    }

    public function getShouldSendToProjectReviewProperty(): bool
    {
        if (!$this->requiresProjectReview) {
            return false;
        }

        if ($this->isSapReleaseFinalizeFlow) {
            return false;
        }

        if ($this->isConclusionDirectCloseWithoutProjectReview()) {
            return false;
        }

        if (!$this->preResultRequiresProjectReview()) {
            return false;
        }

        return true;
    }

    public function getIsSapReleaseFinalizeFlowProperty(): bool
    {
        return (int) ($this->production->status ?? 0) === Production::STATUS_RELEASED_TO_FINISH;
    }

    private function isConclusionDirectCloseWithoutProjectReview(): bool
    {
        $conclusion = mb_strtoupper(trim((string) $this->conclusion));
        return in_array($conclusion, [
            'RETORNADO LEVANTAMENTO',
            'ARQUIVADO',
            'DEPENDE DE ORGAO EXTERNO',
        ], true);
    }

    private function preResultRequiresProjectReview(): bool
    {
        $preResult = $this->normalizePreResult((string) $this->preresult);

        // Sem seleção explícita, mantém o comportamento conservador (envia para análise).
        if ($preResult === '') {
            return true;
        }

        return in_array($preResult, [
            'NORMAL',
            'REVALIDACAO',
        ], true);
    }

    private function normalizePreResult(string $value): string
    {
        $value = mb_strtoupper(trim($value));
        $replacements = [
            'Á' => 'A',
            'À' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'É' => 'E',
            'Ê' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ú' => 'U',
            'Ç' => 'C',
        ];

        return strtr($value, $replacements);
    }

    // OPERAÇÕES COM ARQUIVOS
    public function updatedFiles()
    {

        try {
            $this->validate([
                'files.*' => 'mimes:pdf,jpeg,jpg,png,webp,gif,tiff,bmp,dwg,dxf,dwf,doc,docx,xls,xlsx,ppt,pptx'
            ]);
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TIPO DE ARQUIVO NÃO PERMITIDO',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">Somente são aceitos arquivos: <span class="fw-bold">.pdf, .jpeg, .jpg, .png, .webp, .gif, .tiff, .bmp, .dwg, .dxf, .dwf, .doc, .docx, .xls, .xlsx, .ppt, .pptx</span> </div></div>',

            ]);

            return;
        }

        if (count($this->files)) {

            $this->show_files = [];

            foreach ($this->files as $index => $file) {

                $skip_file = false;

                if (!$skip_file) {


                    if (strpos(explode('.', $file->getClientOriginalName())[0], $this->production->Note->note) !== false) {
                        $this->nota_divergente = false;
                    } else {
                        $this->nota_divergente = true;
                    }

                    $this->show_files[$index] = [
                        'id'       => $index,
                        'note_id'  => '',
                        'name'     => explode('.', $file->getClientOriginalName())[0],
                        'old_name' => explode('.', $file->getClientOriginalName())[0],
                        'ext'      => $file->getClientOriginalExtension(),
                        'chk'      => false,
                    ];
                }
            }

        }
    }

    public function delete_file($id)
    {
        if (isset($this->show_files[$id])) {
            unset($this->files[$id]);
            unset($this->show_files[$id]);
        }

        if (!count($this->show_files)) {
            $this->reset('files');

        }

        $this->updatedFiles();
    }



    //     public function updatedConclusion($value)
    //     {

    //         $this->save_info();

    //         $text = "";

    //         if ($this->service_type == "ER") {
    //             $text = "
    // __________________________________________________

    // Comprador: {$this->comprador};
    // Matrícula: {$this->matricula} - em conformidade com o INCRA apresentado;
    // Área total: {$this->area} ha;
    // Localização do imóvel: {$this->endereco} - em conformidade com a informada no pedido;

    // Documento Apresentado: {$this->documento}

    // *****
    // Documentação válida para dar continuidade ao levantamento de campo;
    // Necessário informar a universalização no croqui/SAP para definição do custo;
    // *****

    // Instalação vizinha: {$this->nmedidor}
    // Coordenada: Lat {$this->lat} / Lon {$this->lon}
    // Alim: {$this->alimentador}
    // Tel.:

    // __________________________________________________
    //         ";
    //         }

    //         if ($this->service_type == "RR") {
    //             $text = "
    // __________________________________________________

    // Comprador: {$this->comprador};
    // Segue para levantamento de campo;

    // ****
    // Providenciar:
    // Croqui, fotos, GPS, parecer técnico e análise de risco, se necessário.
    // ****

    // Documentação válida para dar continuidade ao levantamento de campo.
    // Necessário informar a universalização no croqui/SAP para definição do custo.

    // Instalação: {$this->ninst};
    // Coordenada: Lat {$this->lat} / Lon {$this->lon};
    // Alim: {$this->alimentador};
    // Tel.:
    // __________________________________________________
    // ";
    //         }

    //         $this->info = $text;

    //     }

    public function updatedPreresult()
    {
        if ($this->isRejectedProjectReviewResubmission()) {
            $this->preresult = (string) ($this->analise->preresult ?? $this->preresult);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'FINALIDADE BLOQUEADA',
                'html'     => 'Em retorno da Análise de Projeto, a finalidade não pode ser alterada.',
                'timer'    => 3200,
            ]);
            return;
        }

        $this->postes     = ($this->analise?->postes && $this->analise?->postes > 0) ? $this->analise->postes : (($this->production->postes_u && $this->production->postes_u > 0) ? $this->production->postes_u : $this->note->postes);
        $this->updatedConclusion();
    }

    public function updatedEo(): void
    {
        $this->updatedConclusion();
    }

    public function updatedIproject(): void
    {
        $this->updatedConclusion();
    }

    public function updatedCad(): void
    {
        $this->updatedConclusion();
    }

    public function updatedCadastro(): void
    {
        $this->updatedConclusion();
    }

    public function updatedPostes(): void
    {
        $this->updatedConclusion();
    }

    public function updatedPostesC(): void
    {
        $this->updatedConclusion();
    }

    public function updatedConclusion()
    {

        if ($this->preresult !== 'NORMAL' && $this->preresult !== 'REVALIDACAO') {
            $this->iproject = $this->eo = $this->cadastro = false;
            $this->postes   = 1;
        }

        // if ($this->conclusion === 'ARQUIVADO' || $this->conclusion === 'RETORNADO LEVANTAMENTO') {
        //     $this->iproject = $this->eo = $this->cadastro = false;
        //     $this->postes   = 1;
        //     $this->odi      = '';
        //     $this->odd      = '';
        //     $this->ods      = '';
        // }


        $this->info = '';

        if (trim($this->postes) != '') {
            $this->info .= 'POSTES - ' . $this->postes . "\n";
        }

        if ($this->eo || $this->iproject || $this->cadastro) {
            $this->info .= "-------------------- \n";

            if ($this->eo) {
                $this->info .= "EO \n";
            }

            if ($this->iproject) {
                $this->info .= "iProject \n";
            }

            if ($this->cad) {
                $this->info .= "AutoCad \n";
            }

            if ($this->cadastro) {
                $this->info .= "Acerto Cadastro: \n";
                $this->info .= 'POSTES: ' . $this->postes_c . "\n";
            }


        }

        if ($this->production->d5) {
            $this->info .= "\n";
            $this->info .= "Resolução Interna (RI): \n";
            $this->info .= $this->conclusion ."\n";
        }

        $this->info .= "-------------------- \n";
        $this->info .= Auth()->User()->Registration . ' - ' . Auth()->User()->name . "\n";
        $this->info .= date('d/m/Y') . "\n";

    }

    public function save_info()
    {
        $chk = $this->analise->update([

            'conclusion' => $this->conclusion,
            'info'       => $this->info,
            'preresult'  => $this->preresult,
        ]);
    }

    public function addOrderToList(): void
    {
        $this->validate([
            'order_input_number' => 'required|string|max:100',
        ], [
            'order_input_number.required' => 'Informe o número da ordem.',
        ]);

        $total = $this->normalizeBrNumber($this->order_input_total);
        $company = $this->normalizeBrNumber($this->order_input_company);
        $client = $this->normalizeBrNumber($this->order_input_client);

        [$total, $company, $client] = $this->autofillCostTuple($total, $company, $client);

        $this->order_input_total = is_null($total) ? '' : number_format($total, 2, ',', '.');
        $this->order_input_company = is_null($company) ? '' : number_format($company, 2, ',', '.');
        $this->order_input_client = is_null($client) ? '' : number_format($client, 2, ',', '.');

        if (is_null($total) || $total < 0) {
            $this->addError('order_input_total', 'Informe um custo total válido.');
            return;
        }

        if (is_null($company) || $company < 0) {
            $this->addError('order_input_company', 'Informe um custo empresa válido.');
            return;
        }

        if (is_null($client) || $client < 0) {
            $this->addError('order_input_client', 'Informe um custo cliente válido.');
            return;
        }

        $newNumber = trim((string) $this->order_input_number);
        if ($this->hasMultipleNumericValues($newNumber)) {
            $this->addError('order_input_number', 'Informe somente uma ordem por campo (não use dois números separados por espaço, vírgula, ponto e vírgula etc.).');
            return;
        }

        $orderNumberError = $this->projectReviewOrderNumberError($newNumber);
        if (!is_null($orderNumberError)) {
            $this->addError('order_input_number', $orderNumberError);
            if (str_contains($orderNumberError, 'prefixo')) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'PREFIXO INVÁLIDO PARA A NOTA/OV',
                    'html'     => $orderNumberError,
                    'timer'    => 3500,
                ]);
            }
            return;
        }

        $exists = collect($this->reviewOrders)->contains(function ($row) use ($newNumber) {
            return trim((string) ($row['order_number'] ?? '')) === $newNumber;
        });

        if ($this->isRejectedProjectReviewResubmission() && $this->hasLockedOrderNumber($newNumber)) {
            $this->addError('order_input_number', 'Esta ordem já existe no retorno. A correção deve ser feita ajustando os valores da ordem já exibida.');
            return;
        }

        if ($exists) {
            $this->addError('order_input_number', 'Número de ordem já adicionado nesta submissão.');
            return;
        }

        if ($this->isRejectedProjectReviewResubmission()) {
            $prefix = $this->extractOrderPrefix($newNumber);
            if ($prefix !== '' && $this->hasLockedOrderWithPrefix($prefix)) {
                $this->pendingReviewOrderInsert = [
                    'order_number' => $newNumber,
                    'total_cost' => $total,
                    'company_cost' => $company,
                    'client_cost' => $client,
                ];

                $this->dispatchBrowserEvent('confirmProjectReviewNewOrderPrefix', [
                    'componentId' => $this->id,
                    'orderNumber' => $newNumber,
                    'prefix' => $prefix,
                ]);
                return;
            }
        }

        $this->appendReviewOrderRow($newNumber, $total, $company, $client);
    }

    public function confirmAddOrderAfterPrefixCheck(): void
    {
        if (!is_array($this->pendingReviewOrderInsert)) {
            return;
        }

        $payload = $this->pendingReviewOrderInsert;
        $this->pendingReviewOrderInsert = null;

        $orderNumber = trim((string) ($payload['order_number'] ?? ''));
        $total = isset($payload['total_cost']) ? (float) $payload['total_cost'] : null;
        $company = isset($payload['company_cost']) ? (float) $payload['company_cost'] : null;
        $client = isset($payload['client_cost']) ? (float) $payload['client_cost'] : null;

        if ($orderNumber === '' || is_null($total) || is_null($company) || is_null($client)) {
            return;
        }

        $exists = collect($this->reviewOrders)->contains(function ($row) use ($orderNumber) {
            return trim((string) ($row['order_number'] ?? '')) === $orderNumber;
        });
        if ($exists) {
            $this->addError('order_input_number', 'Número de ordem já adicionado nesta submissão.');
            return;
        }

        $this->appendReviewOrderRow($orderNumber, $total, $company, $client);
    }

    private function appendReviewOrderRow(string $orderNumber, float $total, float $company, float $client): void
    {
        $this->reviewOrders[] = [
            'order_number' => $orderNumber,
            'total_cost' => number_format($total, 2, ',', '.'),
            'company_cost' => number_format($company, 2, ',', '.'),
            'client_cost' => number_format($client, 2, ',', '.'),
            'locked' => false,
        ];

        $this->order_input_number = '';
        $this->order_input_total = '';
        $this->order_input_company = '';
        $this->order_input_client = '';
        $this->pendingReviewOrderInsert = null;
    }

    public function removeReviewOrder(int $index): void
    {
        if ($this->isRejectedProjectReviewResubmission() && !empty($this->reviewOrders[$index]['locked'])) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Ordem existente não pode ser removida',
                'html' => 'Em reprovação, ordens já enviadas só podem ter valores ajustados.',
                'timer' => 2800,
            ]);
            return;
        }

        if (isset($this->reviewOrders[$index])) {
            unset($this->reviewOrders[$index]);
            $this->reviewOrders = array_values($this->reviewOrders);
        }
    }

    public function addContestationMessage(): void
    {
        if (
            !$this->production
            || !$this->isProjectReviewTracked()
        ) {
            return;
        }

        $message = trim((string) $this->newContestationMessage);
        if ($message === '') {
            return;
        }

        $latestCycle = $this->production->ProjectReviewCycles()->latest('round_number')->first();
        if (!$latestCycle) {
            return;
        }

        ProjectReviewMessage::create([
            'production_id' => $this->production->id,
            'cycle_id' => $latestCycle->id,
            'user_id' => auth()->id(),
            'message' => $message,
        ]);

        $recipientIds = collect()
            ->push((int) ($latestCycle->decided_by ?? 0))
            ->merge(
                $latestCycle->Messages()
                    ->where('user_id', '!=', auth()->id())
                    ->pluck('user_id')
            )
            ->filter(fn ($id) => (int) $id > 0)
            ->unique()
            ->values();

        if ($recipientIds->isNotEmpty()) {
            $recipients = User::whereIn('id', $recipientIds)->get();

            foreach ($recipients as $recipient) {
                $recipient->notify(new SystemNotification(
                    titulo: 'Novo comentário na Análise de Projeto',
                    mensagem: 'Novo comentário do desenhista na nota <strong>' . ($this->production->Note->note ?? '-') . '</strong>.',
                    link: $this->buildProjectReviewChatLinkForRecipient($recipient),
                    status: 2,
                    extras: []
                ));
            }
        }

        $this->newContestationMessage = '';
        $this->refreshReviewMessages();
    }

    private function refreshReviewMessages(): void
    {
        if (!$this->production) {
            $this->reviewMessages = [];
            return;
        }

        $this->reviewMessages = ProjectReviewMessage::query()
            ->with('User')
            ->where('production_id', $this->production->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->all();
    }

    public function goToFinishFlow(): void
    {
        $this->viewOnlyProjectReview = false;

        $this->dispatchBrowserEvent('projectReviewGoToFinish');
    }

    public function openFinishModalFromReview(): void
    {
        if (!$this->production) {
            return;
        }

        $this->viewOnlyProjectReview = false;
        $this->dispatchBrowserEvent('switchToFinishModal');
    }

    public function openFinishConfirmation(): void
    {
        if (!$this->production) {
            return;
        }

        $this->to_finish($this->production);
    }

    private function validateProjectReviewPayload(): void
    {
        $normalizedOrders = collect($this->reviewOrders)->map(function ($row) {
            $total = $this->normalizeBrNumber($row['total_cost'] ?? null);
            $company = $this->normalizeBrNumber($row['company_cost'] ?? null);
            $client = $this->normalizeBrNumber($row['client_cost'] ?? null);
            [$total, $company, $client] = $this->autofillCostTuple($total, $company, $client);

            return [
                'order_number' => trim((string) ($row['order_number'] ?? '')),
                'total_cost' => $total,
                'company_cost' => $company,
                'client_cost' => $client,
            ];
        })->all();

        $this->reviewOrders = collect($normalizedOrders)->map(function ($row) {
            return [
                'order_number' => $row['order_number'],
                'total_cost' => is_null($row['total_cost']) ? '' : number_format((float) $row['total_cost'], 2, '.', ''),
                'company_cost' => is_null($row['company_cost']) ? '' : number_format((float) $row['company_cost'], 2, '.', ''),
                'client_cost' => is_null($row['client_cost']) ? '' : number_format((float) $row['client_cost'], 2, '.', ''),
            ];
        })->all();

        $this->validate([
            'reviewOrders' => 'required|array|min:1',
            'reviewOrders.*.order_number' => 'required|string|max:100',
            'reviewOrders.*.total_cost' => 'required|numeric|min:0',
            'reviewOrders.*.company_cost' => 'required|numeric|min:0',
            'reviewOrders.*.client_cost' => 'required|numeric|min:0',
        ], [
            'reviewOrders.required' => 'Adicione pelo menos uma ordem.',
            'reviewOrders.*.order_number.required' => 'Informe o número da ordem.',
        ]);

        $orderNumbers = [];
        foreach ($this->reviewOrders as $index => $row) {
            $number = trim((string) ($row['order_number'] ?? ''));
            if ($number === '') {
                continue;
            }

            if ($this->hasMultipleNumericValues($number)) {
                $this->addError("reviewOrders.{$index}.order_number", 'Informe somente uma ordem por campo.');
                continue;
            }

            $orderNumberError = $this->projectReviewOrderNumberError($number);
            if (!is_null($orderNumberError)) {
                $this->addError("reviewOrders.{$index}.order_number", $orderNumberError);
                continue;
            }

            if (in_array($number, $orderNumbers, true)) {
                $this->addError("reviewOrders.{$index}.order_number", 'Número de ordem duplicado nesta submissão.');
            }
            $orderNumbers[] = $number;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw ValidationException::withMessages($this->getErrorBag()->toArray());
        }
    }

    private function normalizeBrNumber($value): ?float
    {
        if (is_null($value)) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(' ', '', $raw);

        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        if (!is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    private function hasMultipleNumericValues(?string $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        preg_match_all('/\d+/', $value, $matches);
        return count($matches[0] ?? []) > 1;
    }

    private function hasAllowedProjectReviewOrderPrefix(?string $orderNumber): bool
    {
        $number = preg_replace('/\D+/', '', (string) $orderNumber);
        if ($number === '' || strlen($number) < 3) {
            return false;
        }

        $prefix = substr($number, 0, 3);
        if ($this->noteRequiresPrefix200()) {
            return $prefix === '200';
        }

        return in_array($prefix, ['170', '190', '150', '200'], true);
    }

    private function isValidProjectReviewOrderNumber(?string $orderNumber): bool
    {
        return is_null($this->projectReviewOrderNumberError($orderNumber));
    }

    private function projectReviewOrderNumberError(?string $orderNumber): ?string
    {
        $value = trim((string) $orderNumber);
        if ($value === '') {
            return 'Informe o número da ordem.';
        }

        if (!preg_match('/^\d+$/', $value)) {
            return 'Número da ordem inválido: use apenas números.';
        }

        $len = strlen($value);
        if ($len !== 12) {
            return 'Número da ordem inválido: informe exatamente 12 dígitos.';
        }

        if (!$this->hasAllowedProjectReviewOrderPrefix($value)) {
            if ($this->noteRequiresPrefix200()) {
                return 'Número da ordem inválido: para esta Nota/OV o prefixo deve iniciar com 200.';
            }
            return 'Número da ordem inválido: o prefixo deve iniciar com 170, 190, 150 ou 200.';
        }

        return null;
    }

    private function noteRequiresPrefix200(): bool
    {
        $noteValue = (string) ($this->note->note ?? '');
        $digits = preg_replace('/\D+/', '', $noteValue);
        if ($digits === '') {
            return false;
        }

        $first = (int) substr($digits, 0, 1);
        return $first >= 3;
    }

    private function extractOrderPrefix(string $orderNumber): string
    {
        $digits = preg_replace('/\D+/', '', $orderNumber);
        return strlen($digits) >= 3 ? substr($digits, 0, 3) : '';
    }

    private function hasLockedOrderWithPrefix(string $prefix): bool
    {
        return collect($this->reviewOrders)->contains(function ($row) use ($prefix) {
            if (empty($row['locked'])) {
                return false;
            }

            $orderNumber = trim((string) ($row['order_number'] ?? ''));
            return $this->extractOrderPrefix($orderNumber) === $prefix;
        });
    }

    private function hasLockedOrderNumber(string $orderNumber): bool
    {
        return collect($this->reviewOrders)->contains(function ($row) use ($orderNumber) {
            if (empty($row['locked'])) {
                return false;
            }

            return trim((string) ($row['order_number'] ?? '')) === $orderNumber;
        });
    }

    private function autofillCostTuple(?float $total, ?float $company, ?float $client): array
    {
        if (!is_null($total) && !is_null($company) && is_null($client)) {
            $client = round($total - $company, 2);
        }

        if (!is_null($total) && !is_null($client) && is_null($company)) {
            $company = round($total - $client, 2);
        }

        if (!is_null($total) && !is_null($company) && !is_null($client)) {
            $company = min($company, $total);
            $client = round($total - $company, 2);
        }

        if (is_null($total) && !is_null($company) && !is_null($client)) {
            $total = round($company + $client, 2);
        }

        if (!is_null($company) && $company < 0) {
            $company = null;
        }
        if (!is_null($client) && $client < 0) {
            $client = null;
        }
        if (!is_null($total) && $total < 0) {
            $total = null;
        }

        return [$total, $company, $client];
    }

    private function estimateProportionalityFromOrders(array $orders): float
    {
        $sumCompany = 0.0;
        $sumClient = 0.0;

        foreach ($orders as $row) {
            $company = $row['company_cost'] ?? null;
            $client = $row['client_cost'] ?? null;

            if (is_null($company) || is_null($client)) {
                continue;
            }

            $sumCompany += (float) $company;
            $sumClient += (float) $client;
        }

        $base = $sumCompany + $sumClient;
        if ($base <= 0) {
            // Default contratual: 100% cliente e 0% empresa.
            return 0.0;
        }

        $pct = round(($sumCompany / $base) * 100, 2);
        return max(0, min(100, $pct));
    }

    private function estimateClientSharePercentFromOrders(): float
    {
        $normalizedOrders = collect($this->reviewOrders)->map(function ($row) {
            return [
                'company_cost' => $this->normalizeBrNumber($row['company_cost'] ?? null),
                'client_cost' => $this->normalizeBrNumber($row['client_cost'] ?? null),
            ];
        })->all();

        $companyPct = $this->estimateProportionalityFromOrders($normalizedOrders);
        return max(0, min(100, round(100 - $companyPct, 2)));
    }

    private function isRejectedProjectReviewResubmission(): bool
    {
        return $this->requiresProjectReview
            && (int) ($this->production->status ?? 0) === Production::STATUS_REJECTED_PROJECT_REVIEW
            && $this->isProjectReviewTracked();
    }

    private function buildProjectReviewSnapshot(): array
    {
        $orders = collect($this->reviewOrders)
            ->map(function ($row) {
                return [
                    'order_number' => trim((string) ($row['order_number'] ?? '')),
                    'total_cost' => $this->normalizeBrNumber($row['total_cost'] ?? null),
                    'company_cost' => $this->normalizeBrNumber($row['company_cost'] ?? null),
                    'client_cost' => $this->normalizeBrNumber($row['client_cost'] ?? null),
                ];
            })
            ->sortBy('order_number')
            ->values()
            ->all();

        return [
            'orders' => $orders,
        ];
    }

    private function hasProjectReviewPayloadChanges(): bool
    {
        if (!$this->isRejectedProjectReviewResubmission()) {
            return true;
        }

        return $this->buildProjectReviewSnapshot() !== (array) $this->projectReviewLastSnapshot;
    }

    public function to_pause()
    {
        if ((bool) ($this->production->completed ?? false)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'PAUSA BLOQUEADA',
                'html'     => 'Atividades já concluídas não podem ser pausadas.',
                'timer'    => 2800,
            ]);
            return;
        }

        $this->save_info();

        $this->count = Production::Where('status', 4)->Where('service_id', $this->production->service_id)->Where('user_id', Auth()->User()->id)->count();

        if ($this->count === $this->limit_pause) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'LIMITE ATINGIDO',
                'html'     => "Você atingiu o limite máximo de pausas. Não é possível interromper esta nota. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                                   adequada.
                    </p>
                ",
            ]);

            return;
        }

        $this->emit('stop_note', ['productionId' => $this->production->id, 'noteId' => $this->production->note_id, 'limit' => $this->limit_pause]);

        $this->postes = null;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'pause_note',
        ]);
    }

    public function cancel(Production $production)
    {
        $this->emitTo('files.manager.create-prod-files', 'cleanFiles');
        $production->update([

        ]);
    }

    // Interação com o componante Livewire Files/Filesservice
    public function hasFile($value)
    {
        $this->hasFile = $value;
    }

    public function to_finish(Production $production)
    {
        $this->save_info();
        $this->production = $production;
        $this->note       = Note::find($this->production->note_id);
        $isSapReleaseFinalizeFlow = $this->isSapReleaseFinalizeFlow;

        if (
            (int) $this->production->status === Production::STATUS_IN_PROJECT_REVIEW
            && $this->isProjectReviewTracked()
        ) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ENCERRAMENTO BLOQUEADO',
                'html'     => 'A atividade está em Análise de Projeto. Aguarde o retorno do analista para encerrar.',
                'timer'    => 3800,
            ]);
            return;
        }




        // if ($this->postes == '') {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'warning',
        //         'title'    => 'QUANTIDADE DE POSTES',
        //         'html'     => 'Você não informou a quantidade de postes levantados.
        //         ',
        //     ]);

        //     return;
        // }

        if (!$isSapReleaseFinalizeFlow && !$this->conclusion) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CONCLUSÃO NÃO DEFINIDA',
                'html'     => 'Você não definiu uma conclusão para a nota/ov em questão. Gentileza concluir a análise da mesma.
                ',
            ]);

            return;
        }

        if (!$isSapReleaseFinalizeFlow && $this->shouldSendToProjectReview) {
            if (!is_array($this->reviewOrders) || !count($this->reviewOrders)) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'ORDENS OBRIGATÓRIAS',
                    'html'     => 'Para esta conclusão, é obrigatório informar ao menos uma ordem para análise.',
                ]);
                return;
            }

            if (!$this->hasFile && !$this->isRejectedProjectReviewResubmission()) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'ARQUIVO DE PROJETO OBRIGATÓRIO',
                    'html'     => 'Para pré-resultado <strong>NORMAL</strong> ou <strong>REVALIDAÇÃO</strong>, é obrigatório anexar o arquivo do projeto antes do envio para análise.',
                ]);
                return;
            }
        }

        if ($this->shouldSendToProjectReview) {
            try {
                $this->validateProjectReviewPayload();
            } catch (ValidationException $e) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'DADOS DA ANÁLISE INCOMPLETOS',
                    'html'     => collect($e->errors())->flatten()->take(3)->implode('<br>'),
                ]);
                return;
            }
        }



        if (
            !$isSapReleaseFinalizeFlow
            && !$this->hasFile
            && SelectOptions::verifyNeedFilesReclaims($this->conclusion)
        ) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ARQUIVOS OBRIGATÓRIO',
                'html'     => '<div class="card"><div class="card-body"><p class="text-start">Para o tipo de RI (Resolução Interna) definido pelo solicitante, é obrigatório inserir o PDF do PROJETO em "ADICIONAR PROJETO".
                </p><p class="text-start">Caso a solicitação tenha sido "ALTERAR PROJETO", lembre-se de adicionar apenas o PDF mais RECENTE e todas as FOLHAS desse projeto no mesmo UPLOAD se aplicável.
                </p></div></div>',
            ]);

            return;
        }



        if ($isSapReleaseFinalizeFlow) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'FINALIZAÇÃO NO SAP',
                'msg'   => "Você está prestes a finalizar <strong>{$this->note->note}</strong> após liberação da Análise de Projeto.
                    <div class='card'>
                        <div class='card-body'>
                            Este fluxo não altera as datas da produção.
                            <h4 class='text-center'>DESEJA CONTINUAR?</h4>
                        </div>
                    </div>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Finalizar',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_goFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',
            ]);
        } elseif ($this->shouldSendToProjectReview) {
            $clientSharePct = $this->estimateClientSharePercentFromOrders();
            $highClientShareWarning = $clientSharePct > 90
                ? "<div class='alert alert-warning mt-2 mb-0'>
                        Custo cliente em <strong>{$clientSharePct}%</strong>. Aguarde a aprovação do projeto antes de alterar no SAP.
                   </div>"
                : '';

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENVIO PARA ANÁLISE DE PROJETO',
                'msg'   => "Você está prestes enviar <strong>{$this->note->note}</strong> para análise de projeto.
                    <div class='card'>
                        <div class='card-body'>
                            Após o envio, a nota ficará em <strong>Em Análise</strong> até decisão do analista.
                            {$highClientShareWarning}
                            <h4 class='text-center'>DESEJA CONTINUAR?</h4>
                        </div>
                    </div>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_goFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'ENCERRAMENTO DE SERVIÇO',
                'msg'   => "Você está prestes encerrar <strong>{$this->note->note}</strong>.
                    <div class='card'>
                        <div class='card-body'>
                            Ao encerrar, entendemos que você seguiu todos os procedimentos em relação as transações no SAP.
                            <h4 class='text-center'>DESEJA CONTINUAR?</h4>
                        </div>
                    </div>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_goFinish',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',
            ]);
        }
    }

    public function goFinish()
    {
        // Existem duas instâncias desse componente (finish/review).
        // Apenas o contexto de encerramento deve efetivar o envio.
        if ($this->modalContext !== 'finish') {
            return;
        }

        $productionId = $this->production->id ?? $this->analise->production_id ?? null;
        if (!$productionId) {
            // Evita alertas espúrios quando o evento chega fora do fluxo ativo.
            if (!$this->view_form) {
                return;
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ATIVIDADE NÃO IDENTIFICADA',
                'html'     => 'Não foi possível identificar a atividade para envio. Reabra o formulário e tente novamente.',
            ]);
            return;
        }

        $this->production = Production::find($productionId);
        if (!$this->production) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ATIVIDADE NÃO ENCONTRADA',
                'html'     => 'A atividade selecionada não está mais disponível. Atualize a tela e tente novamente.',
            ]);
            return;
        }

        $this->note = Note::find($this->production->note_id);
        if (!$this->note) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NOTA NÃO ENCONTRADA',
                'html'     => 'A nota vinculada à atividade não foi encontrada. Atualize a tela e tente novamente.',
            ]);
            return;
        }

        if (
            (int) $this->production->status === Production::STATUS_IN_PROJECT_REVIEW
            && $this->isProjectReviewTracked()
        ) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ENCERRAMENTO BLOQUEADO',
                'html'     => 'A atividade está em Análise de Projeto. Aguarde o retorno do analista para encerrar.',
                'timer'    => 3800,
            ]);
            return;
        }

        try {
            DB::beginTransaction();
            $cycle = null;
            $isSapReleaseFinalizeFlow = $this->isSapReleaseFinalizeFlow;
            $sendToProjectReview = $this->shouldSendToProjectReview;
            $completedAtReference = (bool) ($this->production->completed ?? false)
                ? ($this->production->completed_at ?? now())
                : now();
            if (!$isSapReleaseFinalizeFlow && $sendToProjectReview) {
                $this->validateProjectReviewPayload();

                $nextRound = ((int) $this->production->ProjectReviewCycles()->max('round_number')) + 1;

                $cycle = ProjectReviewCycle::create([
                    'production_id' => $this->production->id,
                    'round_number' => $nextRound,
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                    'designer_note' => null,
                    'decision' => 'PENDING',
                ]);

                foreach (array_values($this->reviewOrders) as $index => $row) {
                    $cycle->Orders()->create([
                        'order_number' => trim((string) $row['order_number']),
                        'total_cost' => (float) $row['total_cost'],
                        'company_cost' => (float) $row['company_cost'],
                        'client_cost' => (float) $row['client_cost'],
                        'sort_order' => $index,
                    ]);
                }
            }

            if ($isSapReleaseFinalizeFlow) {
                $chk = $this->production->update([
                    'status' => 5,
                    'completed' => true,
                    'completed_at' => $completedAtReference,
                    'confirmed' => false,
                    'priority' => false,
                    'status_note' => ($this->note->nstats != $this->production->status_note) ? $this->note->nstats : $this->production->status_note,
                ]);
            } else {
                $chk = $this->production->update([
                    'status'       => $sendToProjectReview ? Production::STATUS_IN_PROJECT_REVIEW : 5,
                    'completed_at' => $completedAtReference,
                    'postes_p'     => (int) $this->postes,
                    'postes_u'     => $this->postes ? (int) $this->postes : 0,
                    'cadastro'     => $this->cadastro ? true : false,
                    'iproject'     => $this->iproject ? true : false,
                    'eo'           => $this->eo ? true : false,
                    'cad'          => $this->cad ? true : false,
                    'postes_c'     => $this->postes_c ? (int) $this->postes_c : 0,
                    'completed'    => true,
                    'confirmed'    => false,
                    'priority'     => false,
                    'status_note'  => ($this->note->nstats != $this->production->status_note) ? $this->note->nstats : $this->production->status_note,
                ]);
            }

            if ($chk) {
                $user = Auth()->User()->name;

                Notetimeline::Create([
                    'note_id'    => $this->note->id,
                    'service_id' => $this->production->service_id,
                    'user_id'    => Auth()->User()->id,
                    'production_id' => $this->production->id,
                    'info'       => $isSapReleaseFinalizeFlow
                        ? "Usuário {$user} finalizou a Nota/OV no SAP após liberação da Análise de Projeto."
                        : ($sendToProjectReview
                            ? "Usuário {$user} enviou a Nota/OV para Análise de Projeto (rodada {$cycle->round_number})."
                            : "Usuário {$user} encerrou a Nota/OV."),
                    'status'     => $isSapReleaseFinalizeFlow
                        ? 5
                        : ($sendToProjectReview ? Production::STATUS_IN_PROJECT_REVIEW : 5),
                ]);

                //Encerrar RI Caso existir
                if ($this->production->d5) {
                    $d5 = Reclaim::where('production_id', $this->production->id)->first();

                    if ($d5) {
                        $d5->update([
                            'completed' => true,
                            'completed_at' => date('Y-m-d H:i:s'),
                        ]);

                        $d5->Viabilities()->update(['status' => 13]);
                    }
                }



                // if (count($this->show_files)) {

                //     foreach ($this->show_files as $temp_file) {

                //         $caminho = '';

                //         if (isset($this->files[$temp_file['id']])) {

                //             $caminho = $this->files[$temp_file['id']]->store('/arquivos/projeto');

                //             if ($caminho) {

                //                 $this->production->Files()->create([
                //                     'note_id'   => $this->production->note_id,
                //                     'user_id'   => Auth()->User()->id,
                //                     'service_id'   => $this->production->service_id,
                //                     'file_name' => $temp_file['name'],
                //                     'path'      => $caminho,
                //                     'ext'       => $temp_file['ext'],
                //                 ]);

                //             }

                //         }

                //     }
                // }
                DB::commit();
            } else {
                throw new \RuntimeException('Não foi possível atualizar a atividade.');
            }
        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            report($th);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'NÃO ENVIADO',
                'html'     => 'Não conseguimos enviar a atividade para análise. Revise os dados e tente novamente.',
            ]);

            return;
        }

        try {
            $this->emitTo('files.manager.create-prod-files', 'saveFiles');
        } catch (\Throwable $th) {
            report($th);
            $this->toContinue();
        }
    }

    public function toContinue()
    {
        $this->clean();
        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_accomany');
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => $this->isSapReleaseFinalizeFlow
                ? 'FINALIZADO NO SAP'
                : ($this->shouldSendToProjectReview ? 'ENVIADO PARA ANÁLISE' : 'ENCERRADO COM SUCESSO'),
            'html'     => $this->isSapReleaseFinalizeFlow
                ? 'Nota/OV finalizada no SAP com sucesso.'
                : ($this->shouldSendToProjectReview
                    ? 'Nota/OV enviada para Análise de Projeto com sucesso.'
                    : 'Nota/OV encerrada com sucesso.'),
            'timer'   => 2500,
        ]);

    }

    public function savedFiles()
    {
        $this->clean();
        $this->emitTo('files.manager.create-prod-files', 'cleanFiles');
        $this->dispatchBrowserEvent('hideModal');
        $this->emit('refresh_accomany');
    }

    public function clean()
    {
        $this->production  = null;
        $this->note        = null;
        $this->motivo      = null;
        $this->info        = null;
        $this->restriction = null;
        $this->card        = null;
        $this->view_form   = false;
        $this->postes        = null;
        $this->postes_c      = null;
        $this->reviewOrders = [];
        $this->order_input_number = '';
        $this->order_input_total = '';
        $this->order_input_company = '';
        $this->order_input_client = '';
        $this->projectReviewLastSnapshot = null;
        $this->designer_note = null;
        $this->riRequest = null;
        $this->rejectedFindings = [];
        $this->reviewMessages = [];
        $this->newContestationMessage = null;
        $this->selectedReviewPointFilter = '';
        $this->viewOnlyProjectReview = false;
        $this->allowProjectReviewHistory = false;
        $this->hasProjectReviewCycles = false;


    }

    public function clean_form()
    {
        $this->ninst         = '';
        $this->nmedidor      = '';
        $this->patrimonio    = '';
        $this->lat           = '';
        $this->lon           = '';
        $this->carga_ini     = '';
        $this->carga_fim     = '';
        $this->queda         = '';
        $this->queda_max     = '';
        $this->queda_cliente = '';
        $this->vao           = '';
        $this->restriction   = '';
        $this->motivo        = '';
        $this->conclusion    = '';
        $this->info          = '';
        $this->card          = '';
        $this->alimentador   = '';
        $this->comprador     = '';
        $this->matricula     = '';
        $this->area          = '';
        $this->endereco      = '';
        $this->postes        = null;
        $this->postes_c      = null;
        $this->odi           = '';
        $this->odd           = '';
        $this->ods           = '';
        $this->cadastro      = false;
        $this->iproject      = false;
        $this->eo            = false;
        $this->cad           = false;
        $this->reviewOrders = [];
        $this->order_input_number = '';
        $this->order_input_total = '';
        $this->order_input_company = '';
        $this->order_input_client = '';
        $this->projectReviewLastSnapshot = null;
        $this->designer_note = '';
        $this->riRequest = null;
        $this->rejectedFindings = [];
        $this->reviewMessages = [];
        $this->newContestationMessage = '';
        $this->selectedReviewPointFilter = '';
        $this->viewOnlyProjectReview = false;
        $this->allowProjectReviewHistory = false;
        $this->hasProjectReviewCycles = false;

    }

    public function render()
    {
        return view('livewire.services.desenho.forms.analise');
    }

    private function canOpenProjectReviewReadonly(): bool
    {
        if (!$this->production) {
            return false;
        }

        if (!in_array((int) $this->production->status, [
            Production::STATUS_IN_PROJECT_REVIEW,
            Production::STATUS_REJECTED_PROJECT_REVIEW,
            Production::STATUS_RELEASED_TO_FINISH,
        ], true)) {
            return $this->allowProjectReviewHistory && $this->hasProjectReviewCycles;
        }

        return $this->hasProjectReviewCycles;
    }

    private function isProjectReviewTracked(): bool
    {
        if (!$this->production) {
            return false;
        }

        if (!in_array((int) $this->production->status, [
            Production::STATUS_IN_PROJECT_REVIEW,
            Production::STATUS_REJECTED_PROJECT_REVIEW,
            Production::STATUS_RELEASED_TO_FINISH,
        ], true)) {
            return false;
        }

        return $this->hasProjectReviewCycles;
    }

    private function buildProjectReviewChatLinkForRecipient(User $recipient): string
    {
        if (!$this->production) {
            return $recipient->can('analyst')
                ? route('project_review.list')
                : route('home');
        }

        $targetProduction = $this->resolveRecipientProductionForUserArea($recipient) ?: $this->production;
        $isOwnerRecipient = (string) $targetProduction->user_id === (string) $recipient->id;

        if ($isOwnerRecipient) {
            return route('services.production', [
                'service' => $targetProduction->service_id,
                'prod' => $targetProduction->id,
                'open_project_review' => 1,
                'production' => $targetProduction->id,
                'note' => $targetProduction->note_id,
                'focus' => 'chat',
            ]);
        }

        if ($recipient->can('analyst')) {
            return route('project_review.list', [
                'production' => $this->production->id,
                'focus' => 'chat',
            ]);
        }

        return route('services.main', [
            'service' => $this->production->service_id,
        ]);
    }

    private function resolveRecipientProductionForUserArea(User $recipient): ?Production
    {
        if (!$this->production) {
            return null;
        }

        if ((string) $recipient->id === (string) $this->production->user_id) {
            return $this->production;
        }

        $recipientProduction = Production::query()
            ->where('note_id', $this->production->note_id)
            ->where('user_id', $recipient->id)
            ->whereHas('Service', function ($q) {
                $q->where(function ($serviceQuery) {
                    $serviceQuery->where('folder', 'desenho')
                        ->orWhereRaw('LOWER(service) like ?', ['%desenho%']);
                });
            })
            ->latest('id')
            ->first();

        return $recipientProduction ?: $this->production;
    }

    private function mapRejectedFindingsForView(Collection $findings): array
    {
        return $findings->map(function ($finding) {
            $pointLabel = trim((string) ($finding->point_label ?? ''));
            if ($pointLabel === '') {
                $pointLabel = 'Sem ponto';
            }

            return [
                'id' => (int) $finding->id,
                'point_label' => $pointLabel,
                'category_name' => optional(optional($finding->Subcategory)->Category)->name ?: 'Sem categoria',
                'subcategory_name' => optional($finding->Subcategory)->name ?: 'Sem subcategoria',
                'item_id' => $finding->item_id ? (int) $finding->item_id : null,
                'item_name' => optional($finding->Item)->name ?: 'Estrutura sem item',
                'action_type' => $finding->action_type,
                'quantity' => $finding->quantity,
                'origin' => $finding->origin,
                'note' => $finding->note,
            ];
        })->values()->all();
    }

    public function getAvailableReviewPointsProperty(): array
    {
        return collect($this->rejectedFindings)
            ->map(fn ($row) => trim((string) data_get($row, 'point_label', '')))
            ->filter(fn ($label) => $label !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function getFilteredRejectedFindingsProperty(): array
    {
        $filter = trim((string) $this->selectedReviewPointFilter);
        if ($filter === '') {
            return $this->rejectedFindings;
        }

        return collect($this->rejectedFindings)
            ->filter(fn ($row) => (string) data_get($row, 'point_label', '') === $filter)
            ->values()
            ->all();
    }

    public function downloadFile(int $fileId)
    {
        if (!$this->production) {
            return null;
        }

        $file = File::query()
            ->where('id', $fileId)
            ->where('note_id', $this->production->note_id)
            ->first();

        if (!$file) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo não encontrado',
                'html' => 'O arquivo selecionado não está disponível para esta nota.',
                'timer' => 2600,
            ]);
            return null;
        }

        if (!$file->path || !Storage::exists($file->path)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Arquivo indisponível',
                'html' => 'Não foi possível localizar o arquivo no storage. Atualize a lista e tente novamente.',
                'timer' => 3200,
            ]);
            return null;
        }

        $downloadName = $file->original_name ?: ($file->file_name . ($file->ext ? '.' . $file->ext : ''));
        try {
            return Storage::download($file->path, $downloadName);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Erro ao baixar arquivo',
                'html' => 'O arquivo não pôde ser lido no storage.',
                'timer' => 3200,
            ]);
            return null;
        }
    }
}
