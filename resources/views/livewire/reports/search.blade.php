<div>
    {{-- Loading --}}
    <x-show-loading />

    {{-- BUSCAR NOTA/OV --}}
    <div class="card border-0 shadow">
        <h4 class="card-header edp-bg-sprucegreen-70 text-edp-verde">BUSCAR NOTA/OV</h4>
        <div class="card-body">
            <div class="row align-items-end g-2">
                <div class="col-md-3">
                    <label for="searchInput" class="form-label">Buscar</label>
                    <input id="searchInput" class="form-control" type="text" placeholder="Informe a Nota/OV"
                        wire:model.defer="search" wire:keydown.enter="findNote">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" wire:click.prevent="findNote">Buscar</button>
                </div>
            </div>
        </div>
    </div>

    @if ($lists)
        {{-- DADOS DA NOTA --}}
        <div class="card border-0 mt-4 shadow edp-bg-sprucegreen-70 edp-text-verde-dark">
            <div class="card-header edp-bg-sprucegreen-100 edp-text-verde-dark d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h4 class="mb-0">
                    NOTA/OV: <strong class="text-uppercase">{{ $lists->note }}</strong>
                </h4>
                @if ($hasProtestOverview)
                    <a href="{{ route('protests.common.note', ['note' => $lists->id]) }}" target="_blank" class="btn btn-outline-light btn-sm">
                        <i class="ri-external-link-line me-1"></i>
                        Detalhes do Protesto
                    </a>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- COLUNA ESQUERDA --}}
                    <div class="col-md-7">
                        <dl class="row ms-2">
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">RUBRICA</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->rubrica }}</dd>

                            @if ($lists->type_note == 2)
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 1</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group1 }}</dd>
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 2</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group2 }}</dd>
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 4</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group4 }}</dd>
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">GRUPO 5</dt>
                                <dd class="col-sm-8 text-white text-uppercase">{{ $lists->group5 }}</dd>
                            @endif

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">DESCRIÇÃO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->numPedido }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">MUNICÍPIO</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->lexp }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">MMGD</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->mmgd ? 'SIM' : 'NÃO' }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">STATUS ATUAL</dt>
                            <dd class="col-sm-8 fw-bold text-white text-uppercase">{{ $lists->nstats }}</dd>

                            @if ($lists->type_note == 1)
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">CENTRO DE TRABALHO</dt>
                                <dd class="col-sm-8 fw-bold text-white text-uppercase">{{ $lists->centerjob }}</dd>
                            @endif

                            @php $lastDate = (new \App\Helpers\DaysLeft($lists))->getLastDate(); @endphp
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">PRAZO OBRA</dt>
                            <dd class="col-sm-8 fw-bold text-warning text-uppercase">{{ $lastDate }}</dd>

                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">CRITICIDADE</dt>
                            <dd class="col-sm-8 text-white text-uppercase">{{ $lists->txpriority ?: '---' }}</dd>

                            {{-- D5 --}}
                            <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">NOTA D5</dt>
                            <dd class="col-sm-8 text-white text-uppercase">
                                @if ($lists->FiveNote)
                                    <span class="fw-bold" style="cursor:pointer"
                                        wire:click.prevent="$emitTo('components.five-note.view-d5', 'getInfoResponse', {{ $lists->FiveNote->id }})">
                                        {{ $lists->FiveNote?->note_d5 ?? 'A GERAR D5' }}
                                        @if ($lists->FiveNote?->visible_partner && $lists->FiveNote?->is_completed)
                                            <small>( {{ $lists->FiveNote?->completed_at?->format('d/m/Y H:i') }}
                                                )</small>
                                        @endif
                                        <i class="ri-eye-line ms-1 text-primary"></i>
                                    </span>
                                @else
                                    ---
                                @endif
                            </dd>

                            @if ($lists->FiveNote)
                                @php
                                    $status = '';
                                    $color = '';
                                    if ($lists->FiveNote?->is_payed) {
                                        if ($lists->FiveNote?->is_archived) {
                                            $status = 'Finalizada';
                                            $color = 'text-bg-success';
                                        } elseif ($lists->FiveNote?->is_supervisioned) {
                                            $status = 'Aguardando Liberação Pagamento';
                                            $color = 'text-bg-danger';
                                        } elseif ($lists->FiveNote?->is_completed) {
                                            $status = 'Aguardando Fiscalização';
                                            $color = 'text-bg-danger';
                                        } elseif ($lists->FiveNote?->visible_partner) {
                                            $status = 'Aguardando Conclusão Parceira';
                                            $color = 'text-bg-primary';
                                        }
                                    } else {
                                        $status = 'Aguardando Despacho Pagamento';
                                        $color = 'text-bg-primary';
                                    }
                                @endphp
                                <dt class="col-sm-4 edp-bg-sprucegreen-100 mb-1">STATUS NOTA D5</dt>
                                <dd class="col-sm-8 text-white text-uppercase">
                                    <span class="badge {{ $color }}">{{ $status }}</span>
                                </dd>
                            @endif
                        </dl>

                        {{-- ORDENS (já vêm com Operations) --}}
                        @if ($lists->Orders->count())
                            @foreach ($lists->Orders as $order)
                                @php
                                    $orderCancellation = $lists->CancellationRequests
                                        ->filter(fn($req) => $req->Orders->contains('id', $order->id))
                                        ->sortByDesc('created_at')
                                        ->first();
                                @endphp
                                <div class="card border-0 shadow mb-3">
                                    <div class="card-header edp-bg-sprucegreen-100 text-white d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-edp-verde">ORDEM:</span>
                                            {{ $order->ordem }}
                                            ({{ $order->statusSist ? explode(' ', $order->statusSist)[0] : '' }})
                                        </div>
                                        @if ($orderCancellation)
                                            <a class="btn btn-sm btn-outline-light"
                                                href="{{ route('cancellations.show', ['request' => $orderCancellation->id]) }}"
                                                target="_blank" rel="noopener">
                                                <i class="ri-file-search-line me-1"></i> Cancelamento
                                            </a>
                                        @endif
                                    </div>

                                    @php
                                        $closed = \Illuminate\Support\Str::startsWith($order->statusSist ?? '', [
                                            'ENTE',
                                            'ENCE',
                                        ]);
                                    @endphp

                                    @if ($closed || !$order->Operations->count())
                                        <div class="card-body text-bg-secondary text-center">
                                            @if ($closed)
                                                @if (\Illuminate\Support\Str::startsWith($order->statusSist ?? '', 'ENCE'))
                                                    <h5>ORDEM ENCERRADA</h5>
                                                @else
                                                    <h5>ORDEM ENCERRADA TÉCNICAMENTE</h5>
                                                @endif
                                            @else
                                                <h5>SEM OPERAÇÕES PARA ESSA ORDEM</h5>
                                            @endif
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Operação</th>
                                                        <th>Descrição</th>
                                                        <th>Status</th>
                                                        <th>CenTrab</th>
                                                        <th>IniPlan</th>
                                                        <th>FimPlan</th>
                                                        <th>IniReal</th>
                                                        <th>FimReal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($order->Operations->sortBy('operacao') as $op)
                                                        <tr>
                                                            <td>{{ $op->operacao }}</td>
                                                            <td>{{ $op->descOperacao }}</td>
                                                            <td>{{ $op->status ? explode(' ', $op->status)[0] : '' }}
                                                            </td>
                                                            <td>{{ $op->cenTrab }}</td>
                                                            <td>{{ $op->inicioPlanejado ? \Carbon\Carbon::parse($op->inicioPlanejado)->format('d/m/Y') : '-' }}
                                                            </td>
                                                            <td>{{ $op->fimPlanejado ? \Carbon\Carbon::parse($op->fimPlanejado)->format('d/m/Y') : '-' }}
                                                            </td>
                                                            <td>{{ $op->inicioReal ? \Carbon\Carbon::parse($op->inicioReal)->format('d/m/Y') : '-' }}
                                                            </td>
                                                            <td>{{ $op->fimReal ? \Carbon\Carbon::parse($op->fimReal)->format('d/m/Y') : '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- COLUNA DIREITA --}}
                    <div class="col-md-5">
                        {{-- REGISTRO SICODE --}}
                        <div class="card border-0 mb-3 shadow">
                            <h5 class="card-header edp-bg-sprucegreen-100 text-edp-verde">REGISTRO SICODE</h5>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-6 fw-bold">ENTRADA NO SICODE</dt>
                                    <dd class="col-6">
                                        {{ \Carbon\Carbon::parse($lists->created_at)->format('d/m/Y H:i:s') }}</dd>
                                    <dt class="col-6 fw-bold">ÚLTIMA ATUALIZAÇÃO</dt>
                                    <dd class="col-6">
                                        {{ \Carbon\Carbon::parse($lists->updated_at)->format('d/m/Y H:i:s') }}</dd>
                                </dl>
                            </div>
                        </div>

                        {{-- ARQUIVOS (download/zip via HTTP) --}}
                        <div class="card edp-bg-sprucegreen-50 border-0 mb-3 shadow">
                            <div
                                class="card-header edp-bg-sprucegreen-100 text-edp-verde d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">ARQUIVOS</h5>
                                <div>
                                    @can('admin')
                                        <button class="btn btn-sm btn-primary"
                                            wire:click.prevent="$emitTo('files.manager.createfiles','createFile',{{ $lists->id }})">
                                            <i class="ri-chat-new-fill fs-5 align-middle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" wire:click="$emit('update_list')">
                                            <i class="ri-refresh-line fs-5"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>

                            @if ($lists->Files->count())
                                @php
                                    $grouped = $lists->Files
                                        ->sortBy('file_name')
                                        ->groupBy(fn($f) => $f->Service->service ?? 'Outros');
                                    $services = $grouped
                                        ->keys()
                                        ->filter(fn($k) => $k !== 'Outros')
                                        ->sort()
                                        ->values()
                                        ->all();
                                    if ($grouped->has('Outros')) {
                                        $services[] = 'Outros';
                                    }
                                @endphp

                                <div class="accordion" id="filesByServiceAccordion">
                                    @foreach ($services as $service)
                                        @php
                                            $files = $grouped[$service];
                                            $slug = \Illuminate\Support\Str::slug($service);
                                        @endphp

                                        <div class="accordion-item border-secondary"
                                            wire:key="service-{{ $slug }}">
                                            <h2 class="accordion-header" id="heading{{ $slug }}">
                                                <button
                                                    class="accordion-button edp-bg-sprucegreen-20 text-white {{ $openServiceId !== $slug ? 'collapsed' : '' }}"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#collapse{{ $slug }}"
                                                    aria-expanded="{{ $openServiceId === $slug }}"
                                                    aria-controls="collapse{{ $slug }}">
                                                    {{ $service }}
                                                </button>
                                            </h2>

                                            <div id="collapse{{ $slug }}"
                                                class="accordion-collapse collapse {{ $openServiceId === $slug ? 'show' : '' }}"
                                                aria-labelledby="heading{{ $slug }}"
                                                data-bs-parent="#filesByServiceAccordion" x-data
                                                x-init="$el.addEventListener('shown.bs.collapse', () => Livewire.emit('setOpenService', '{{ $slug }}'))">

                                                <div class="accordion-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-striped table-hover mb-0">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th class="text-center">
                                                                        <input
                                                                            class="form-check-input border border-1 border-secondary"
                                                                            type="checkbox"
                                                                            wire:click="toggleGroup('{{ $slug }}')">
                                                                    </th>
                                                                    <th class="text-center">Arquivo</th>
                                                                    <th class="text-center">Data</th>
                                                                    <th class="text-center">Tam</th>
                                                                    <th></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($files as $file)
                                                                    @php $exists = \Storage::exists($file->path); @endphp
                                                                    <tr
                                                                        wire:key="file-{{ $file->id }}-{{ $lists->note }}">
                                                                        <td class="text-center align-middle">
                                                                            <input
                                                                                class="form-check-input border border-1 border-secondary"
                                                                                type="checkbox"
                                                                                value="{{ $file->id }}"
                                                                                wire:model.defer="selectedFiles">
                                                                        </td>
                                                                        <td class="text-start align-middle">
                                                                            <i
                                                                                class="{{ \App\Helpers\FileIcon::getIcon($file->ext)->icon }} me-1"></i>
                                                                            <a class="text-dark"
                                                                                href="{{ route('files.download', $file->id) }}">
                                                                                {{ $file->file_name }}
                                                                            </a>
                                                                        </td>
                                                                        <td class="text-center align-middle">
                                                                            {{ $file->created_at->format('d/m/Y H:i:s') }}
                                                                        </td>
                                                                        <td class="text-center align-middle">
                                                                            {{ $exists ? number_format(\Storage::size($file->path) / 1024, 0) . ' KB' : '---' }}
                                                                        </td>
                                                                        <td class="text-center align-middle">
                                                                            @can('admin')
                                                                                <i class="ri-pencil-fill text-primary fs-5"
                                                                                    style="cursor:pointer;"
                                                                                    wire:click.prevent="$emitTo('files.manager.fileedit','editFile',{{ $file->id }})"></i>
                                                                                <i class="ri-delete-bin-2-line text-danger fs-5"
                                                                                    style="cursor:pointer;"
                                                                                    wire:click.prevent="$emitTo('files.manager.fileedit','deleteFile',{{ $file->id }})"></i>
                                                                            @endcan
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <div class="p-2 text-end">
                                                        <button class="btn btn-sm btn-primary"
                                                            wire:click.prevent="zipFiles">
                                                            <i class="bx bxs-cloud-download"></i> Baixar Selecionados
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="card-body">
                                    <h6 class="text-center text-muted">SEM ARQUIVOS</h6>
                                </div>
                            @endif
                        </div>

                        {{-- STATUS HISTÓRICO (único sob demanda; outro banco) --}}
                        <div class="card border-0 shadow">
                            <div
                                class="card-header py-1 edp-bg-sprucegreen-100 edp-text-verde-dark d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 edp-text-verde-dark">STATUS HISTÓRICO</h5>
                                <button class="btn btn-sm btn-primary" wire:click="loadHistorico">Carregar</button>
                            </div>
                            @if ($historico && $historico->count())
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped table-hover mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-center">Data</th>
                                                <th class="text-center">Nstats</th>
                                                <th class="text-center">Desc</th>
                                                <th class="text-center">Usuário</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($historico as $hist)
                                                <tr class="{{ $hist->ultimoStatus ? 'table-primary' : '' }}">
                                                    <td class="text-center">
                                                        {{ date('d/m/Y H:i:s', strtotime($hist->dhStat)) }}</td>
                                                    <td class="text-center fw-bold">{{ $hist->numStat }}</td>
                                                    <td class="text-center">{{ $hist->status }}</td>
                                                    <td class="text-center">{{ $hist->transicaoUsuario }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="card-body">
                                    <h6 class="text-center text-muted">SEM HISTÓRICO CARREGADO</h6>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PROJETO --}}
        @if ($lists->Productions->count())
            <div class="card border-0 mt-3 shadow">
                <h5 class="card-header edp-bg-sprucegreen-100 text-edp-verde">PROJETO</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Serviço</th>
                                <th>Status</th>
                                <th>Usuário</th>
                                <th>Empresa</th>
                                <th>Status</th>
                                <th>Data Despacho</th>
                                <th>Data Atribuído</th>
                                <th>Data Conclusão</th>
                                <th>Parado</th>
                                <th>Conclusão</th>
                                <th>Ent Manual</th>
                                <th>Conf Prod</th>
                                <th>#</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists->Productions as $p)
                                <tr wire:key="prod-{{ $p->id }}">
                                    <td>
                                        @if ($p->d5)
                                            <span class="badge text-bg-info">RI</span>
                                        @endif
                                        @if ($p->dfive)
                                            <span class="badge text-bg-primary">D5</span>
                                        @endif
                                        @if ($p->partial)
                                            <span class="badge text-bg-warning">P</span>
                                        @endif
                                    </td>
                                    <td>{{ $p->Service?->service ?? 'Desconhecido' }}</td>
                                    <td>{{ $p->status_note }}</td>
                                    <td>
                                        @if ($p->User?->email)
                                            <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                style="cursor:pointer"
                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $p->User?->email }}', '_blank')"></i>
                                        @endif
                                        {{ $p->User?->name ?? 'Desconhecido' }}
                                    </td>
                                    <td>{{ $p->Company?->name ?? 'Desconhecido' }}</td>
                                    <td>
                                        <span class="badge {{ \App\Custom\Notestatus::status($p->status)->colorbg }}"
                                            style="cursor:pointer;"
                                            wire:click.prevent="$emitTo('components.status.show-status','showStatus', {{ $p->id }}, {{ $p->status }})">
                                            {{ \App\Custom\Notestatus::status($p->status)->status }}
                                        </span>
                                    </td>
                                    <td>{{ $p->dispatch_at ? date('d/m/Y H:i:s', strtotime($p->dispatch_at)) : '-' }}
                                    </td>
                                    <td>{{ $p->att_at ? date('d/m/Y H:i:s', strtotime($p->att_at)) : '-' }}
                                    </td>
                                    <td>{{ $p->completed_at ? date('d/m/Y H:i:s', strtotime($p->completed_at)) : '-' }}
                                    </td>
                                    <td>{{ \Carbon\CarbonInterval::seconds((int) $p->stopped)->cascade()->forHumans(['short' => true]) }}
                                    </td>
                                    <td>
                                        @livewire('components.historic.analises', ['production_id' => $p->id], key('hist-' . $p->id))
                                    </td>
                                    <td>{{ $p->manual ? 'SIM' : 'NÃO' }}</td>
                                    <td>{{ $p->confirmed ? 'SIM' : 'NÃO' }}</td>
                                    <td class="text-center">
                                        @livewire('production.actions.geralreattribute', ['production' => $p, 'chave' => hash('sha512', $p->id)], key('reatt-' . $p->id))
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card border-0 mt-3 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">
                        SEM INFORMAÇÃO DE ATIVIDADES EM PROJETOS NA NOTA/OV
                    </h6>
                </div>
            </div>
        @endif

        {{-- CONTRATAÇÃO --}}
        @if ($lists->Viabilities->count())
            <div class="card border-0 mt-3 shadow">
                <h5 class="card-header edp-bg-sprucegreen-100 text-edp-verde">CONTRATAÇÃO</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Ordem</th>
                                <th>Contratante</th>
                                <th>Contratado</th>
                                <th>Tácitamente</th>
                                <th>Dt Contratação</th>
                                <th>Dt Envio</th>
                                <th>Dt Retorno</th>
                                <th>Responsável</th>
                                <th>Empreiteira</th>
                                <th>Resp Informe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists->Viabilities as $v)
                                <tr>
                                    <td></td>
                                    <td class="align-middle">
                                        @foreach ($v->Orders as $o)
                                            @php $op = $o->Operations->where('operacao','0010')->first(); @endphp
                                            <p
                                                class="my-0 {{ $op && !\Illuminate\Support\Str::startsWith($op->status, 'CONF') && $v?->hired_at?->lt(now()->subHours(24)) ? 'text-danger' : '' }}">
                                                {{ $o->ordem }}
                                                @if ($op && !\Illuminate\Support\Str::startsWith($op->status, 'CONF'))
                                                    <i class="ri-alert-line"></i>
                                                @endif
                                            </p>
                                        @endforeach
                                    </td>
                                    <td class="align-middle">
                                        @if ($v->User?->email)
                                            <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                style="cursor:pointer"
                                                onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $v->User?->email }}', '_blank')"></i>
                                        @endif
                                        {{ $v->User?->name }}
                                    </td>
                                    <td class="align-middle">{{ $v->hired ? 'SIM' : 'NÃO' }}</td>
                                    <td class="align-middle">{{ $v->tacit ? 'SIM' : 'NÃO' }}</td>
                                    <td class="align-middle">
                                        {{ $v->hired_at ? date('d/m/Y H:i:s', strtotime($v->hired_at)) : '---' }}
                                    </td>
                                    <td class="align-middle">
                                        {{ $v->sended_at ? date('d/m/Y H:i:s', strtotime($v->sended_at)) : '---' }}
                                    </td>
                                    <td class="align-middle">
                                        {{ $v->returned_at ? date('d/m/Y H:i:s', strtotime($v->returned_at)) : '---' }}
                                    </td>
                                    <td class="align-middle">{{ $v->Engineer->name }}</td>
                                    <td class="align-middle">{{ $v->Company->name }}</td>
                                    <td class="align-middle">{{ $v->Form->responsible ?? '---' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card border-0 mt-3 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">
                        SEM INFORMAÇÃO DE CONTRATAÇÃO NA NOTA/OV
                    </h6>
                </div>
            </div>
        @endif

        {{-- INFORMES DE OBRA (Parciais, Ramal, Work) --}}
        @if ($lists->WorkForm || $lists->RamalForm || $lists->Partials->isNotEmpty())
            <div class="card border-0 mt-3 shadow">
                <h5 class="card-header edp-bg-sprucegreen-100 text-edp-verde">INFORMES DE OBRA</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Ordens</th>
                                <th class="text-center">Empresa</th>
                                <th class="text-center">Equipamentos</th>
                                <th class="text-center">Alteração</th>
                                <th class="text-center">Equipe WPA</th>
                                <th class="text-center">Responsável</th>
                                <th class="text-center">Conclusão Informada</th>
                                <th class="text-center">Primeira Entrega</th>
                                <th class="text-center">Rejeições</th>
                                <th class="text-center">Última Devolução</th>
                                <th class="text-center">Status Atual</th>
                                <th class="text-center">Entregue Em</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Parciais --}}
                            @if ($lists->Partials->count())
                                @foreach ($lists->Partials as $partial)
                                    <tr wire:key="partial-{{ $partial->id }}"
                                        wire:dblclick="$emitTo('partner.show.show-partial-info','show_form',{{ $partial->id }})">
                                        <td class="text-center text-bg-warning align-middle">PARCIAL</td>
                                        <td class="text-center align-middle">
                                            @foreach ($partial->Orders as $o)
                                                <p class="my-0">{{ $o->ordem }}</p>
                                            @endforeach
                                        </td>
                                        <td class="text-center align-middle">{{ $partial->Company->name }}</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            {{ $partial->responsible ?? 'Desconhecido' }}
                                        </td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            {{ $partial->created_at ? date('d/m/Y', strtotime($partial->created_at)) : 'Desconhecido' }}
                                        </td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">---</td>
                                        <td class="text-center align-middle">
                                            @if ($partial->deny)
                                                <span class="badge bg-warning">REJEITADO</span>
                                            @elseif($partial->allow && !$partial->supervision)
                                                <span class="badge bg-warning">EM FISCALIZAÇÃO</span>
                                            @elseif($partial->allow && $partial->supervision && !$partial->payment)
                                                <span class="badge bg-warning">EM PAGAMENTO</span>
                                            @elseif($partial->allow && $partial->complete)
                                                <span class="badge bg-warning">PAGO</span>
                                            @else
                                                <span class="badge bg-secondary text-white">DESCONHECIDO</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            {{ $partial->created_at ? date('d/m/Y', strtotime($partial->created_at)) : 'Desconhecido' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                            {{-- RAMAL --}}
                            @if ($lists->RamalForm)
                                <tr wire:key="ramal-{{ $lists->RamalForm?->id }}"
                                    wire:dblclick="$emitTo('btzero.view.compare-form','showCompareForm',{{ $lists->id }})">
                                    <td class="text-center text-bg-success align-middle">SMC</td>
                                    <td class="text-center align-middle">
                                        @foreach ($lists->RamalForm?->Orders as $o)
                                            <p class="my-0">{{ $o->ordem }}</p>
                                        @endforeach
                                    </td>
                                    <td class="text-center align-middle">{{ $lists->RamalForm?->Company->name }}</td>
                                    <td class="text-center align-middle">
                                        {!! $lists->RamalForm?->BtzeroEquipment->count()
                                            ? "<span class='badge bg-dark text-white'>" . $lists->RamalForm?->BtzeroEquipment->count() . '</span>'
                                            : '' !!}
                                    </td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->User->name ?? 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">---</td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->created_at ? date('d/m/Y', strtotime($lists->RamalForm?->created_at)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($lists->RamalForm?->ReturnRamal?->count())
                                            <span class="badge bg-warning fw-bold" style="cursor:pointer;"
                                                wire:click.prevent="$emitTo('components.workform.view-reason-return', 'workReturnViews', {{ $lists->id }})">
                                                {{ $lists->RamalForm?->ReturnRamal?->count() }}
                                            </span>
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->ReturnRamal?->last()?->created_at
                                            ? date('d/m/Y', strtotime($lists->RamalForm?->ReturnRamal?->last()?->created_at))
                                            : '---' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <span
                                            class="badge {{ $lists->RamalForm?->rejected ? 'bg-warning text-wrap' : 'bg-primary text-wrap' }}">
                                            {{ $lists->RamalForm?->rejected ? 'Informe em Revisão' : 'Normal' }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->RamalForm?->created_at ? date('d/m/Y', strtotime($lists->RamalForm?->created_at)) : 'Desconhecido' }}
                                    </td>
                                </tr>
                            @endif

                            {{-- WORK FORM --}}
                            @if ($lists->WorkForm)
                                <tr wire:key="work-{{ $lists->WorkForm->id }}"
                                    wire:dblclick="$emitTo('partner.show.show-work-form','show_form',{{ $lists->WorkForm->id }})">
                                    <td class="text-center bg-primary text-white align-middle">FINAL</td>
                                    <td class="text-center align-middle">
                                        @foreach ($lists->WorkForm->Orders as $o)
                                            <p class="my-0">{{ $o->ordem }}</p>
                                        @endforeach
                                    </td>
                                    <td class="text-center align-middle">{{ $lists->WorkForm->Company->name }}</td>
                                    <td class="text-center align-middle">
                                        {!! $lists->WorkForm->Equipment->count()
                                            ? "<span class='badge bg-dark text-white'>" . $lists->WorkForm?->Equipment?->count() . '</span>'
                                            : '' !!}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->changes ? 'SIM' : 'NÃO' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->team ?? 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->responsible ?? 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->date ? date('d/m/Y', strtotime($lists->WorkForm?->date)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm->created_at ? date('d/m/Y', strtotime($lists->WorkForm?->created_at)) : 'Desconhecido' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($lists->WorkForm->Returnwork->count())
                                            <span class="badge bg-warning fw-bold" style="cursor:pointer;"
                                                wire:click.prevent="$emitTo('components.workform.view-reason-return', 'workReturnViews', {{ $lists->WorkForm->id }})">
                                                {{ $lists->WorkForm?->Returnwork?->count() }}
                                            </span>
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm?->Returnwork?->last()?->created_at
                                            ? date('d/m/Y', strtotime($lists->WorkForm?->Returnwork?->last()?->created_at))
                                            : '---' }}
                                    </td>
                                    <td class="text-center align-middle">
                                        <span
                                            class="badge {{ $lists->WorkForm?->rejected ? 'bg-warning text-wrap' : 'bg-primary text-wrap' }}">
                                            {{ $lists->WorkForm->rejected ? 'Informe em Revisão' : 'Normal' }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $lists->WorkForm?->informed_at ? date('d/m/Y', strtotime($lists->WorkForm?->informed_at)) : 'Desconhecido' }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="card border-0 mt-3 shadow">
                <div class="card-body">
                    <h6 class="text-center text-muted">SEM INFORME DE OBRA</h6>
                </div>
            </div>
        @endif
    @else
        <div class="card border-0 mt-4 shadow">
            <div class="card-body">
                <h6 class="text-center text-muted">NADA PARA EXIBIR</h6>
            </div>
        </div>
    @endif

    {{-- Modals --}}
    @livewire('partner.show.show-work-form', key('FormModalShow'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('files.manager.fileedit', key('file-edit'))
    @livewire('files.manager.createfiles', key('create-files'))
    @livewire('btzero.view.compare-form', key('compare_form'))
    @livewire('partner.show.show-partial-info', key('partial_info'))
    @livewire('components.workform.view-reason-return', key('WorkReturnsReason'))
    @livewire('components.ramalform.view-reason-return', key('RamalReturnsReason'))
    @livewire('components.five-note.view-d5', key('view_d5'))
</div>
