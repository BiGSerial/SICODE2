<div>
    @if ($viewForm && $process && $stage)
        @php $isBudget = $process->phase === \App\Enum\QualityStageType::BUDGET; @endphp
        @php $lastRejection = $rejections->first(); @endphp
        @php $lastForward = $forwards->last(); @endphp
        <div class="container-fluid py-2">
            <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong><i class="ri-shield-check-line"></i> Atividade da Qualidade</strong>
                    <span class="ms-2">{{ $process->phase->label() }} · rodada {{ $stage->round_number }}</span>
                </div>
                @if ($detailsOnly)
                    <span class="badge text-bg-secondary"><i class="ri-eye-line"></i> Somente leitura · nada foi iniciado</span>
                @else
                    <span class="badge text-bg-primary">Ao concluir, a rodada volta para o N1</span>
                @endif
            </div>

            @error('workflow')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header fw-bold">Contexto</div>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between"><span>Nota/OV</span><strong>{{ $process->Note?->note }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Empresa</span><strong>{{ $process->Company?->name }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>N1 responsável</span><strong>{{ $process->N1User?->name ?? '—' }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Despachado por</span><strong>{{ $stage->DispatchedBy?->name ?? '—' }}</strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Ciclo / rodada</span><strong>{{ $process->phase->short() }} · {{ $stage->round_number }}</strong></li>
                            <li class="list-group-item">
                                <span class="text-muted">Ação necessária</span><br>
                                <strong>
                                    @if ($lastRejection && $lastRejection->level->value === 'N2')
                                        Corrigir o que o N2 devolveu (encaminhado pelo N1) e reenviar ao N1.
                                    @elseif ($lastRejection)
                                        Corrigir os pontos rejeitados pelo N1 e reenviar.
                                    @elseif ($isBudget)
                                        Executar {{ $serviceName ?? 'o 2º ciclo' }}, informar as ordens do orçamento e enviar ao N1.
                                    @else
                                        Concluir {{ $serviceName ?? 'o 1º ciclo' }}: preencher o informe de encerramento, anexar os arquivos e enviar ao N1.
                                    @endif
                                </strong>
                            </li>
                        </ul>
                    </div>

                    @if ($lastRejection)
                        <div class="card border-danger shadow-sm mb-3">
                            <div class="card-header text-danger fw-bold">
                                Última devolução · {{ $lastRejection->level->label() }} · rodada {{ $lastRejection->round_number }}
                            </div>
                            <div class="card-body small">
                                <div class="text-muted mb-1">{{ $lastRejection->Author?->name }} · {{ $lastRejection->created_at?->format('d/m/Y H:i') }}</div>
                                @if ($lastRejection->observation)
                                    <p class="mb-2">{{ $lastRejection->observation }}</p>
                                @endif
                                <ul class="mb-0">
                                    @foreach ($lastRejection->Items as $item)
                                        <li>
                                            <strong>{{ $item->Category?->name }}</strong>{{ $item->Subcategory ? ' · ' . $item->Subcategory->name : '' }}
                                            @if ($item->observation)
                                                — {{ $item->observation }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if ($lastForward && $lastForward->observation)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold">Orientação do N1</div>
                            <div class="card-body small">{{ $lastForward->observation }}</div>
                        </div>
                    @endif

                    @if ($comments->isNotEmpty())
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold">Comentários</div>
                            <ul class="list-group list-group-flush small">
                                @foreach ($comments as $comment)
                                    <li class="list-group-item">
                                        <div class="text-muted">{{ $comment->Actor?->name }} ({{ $comment->actor_role }}) · {{ $comment->created_at?->format('d/m/Y H:i') }}</div>
                                        {{ $comment->observation }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($rejections->count() > 1)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold">Devoluções anteriores</div>
                            <ul class="list-group list-group-flush small">
                                @foreach ($rejections->skip(1) as $rejection)
                                    <li class="list-group-item">
                                        <strong>{{ $rejection->level->label() }} · {{ $rejection->type->short() }} · rodada {{ $rejection->round_number }}</strong>
                                        <div>{{ $rejection->Items->map(fn ($item) => $item->Category?->name . ($item->Subcategory ? ' / ' . $item->Subcategory->name : ''))->implode('; ') ?: ($rejection->observation ?: 'Sem detalhes.') }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="col-lg-7">
                    @if ($detailsOnly)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold"><i class="ri-file-list-3-line"></i> Dados da Nota/OV</div>
                            <div class="card-body">
                                <div class="fs-5 fw-bold mb-1">{{ $note?->note }}</div>
                                <div class="text-muted mb-3">{{ $note?->material ?: '—' }}</div>
                                <div class="row g-3 small">
                                    @foreach ([
                                        'Rubrica' => $note?->rubrica, 'Localização' => $note?->lexp, 'Município' => $note?->City?->rdMunicipio ?? $note?->nexp, 'Status da Nota' => $note?->nstats,
                                        'Grupo 1' => $note?->group1, 'Grupo 2' => $note?->group2, 'Cliente' => $note?->client, 'Pedido' => $note?->numPedido,
                                        'Data do status' => $note?->dt_status ? \Illuminate\Support\Carbon::parse($note->dt_status)->format('d/m/Y') : null, 'Dias restantes' => $note?->days_left,
                                    ] as $label => $value)
                                        <div class="col-6 col-md-4"><div class="text-muted text-uppercase" style="font-size: .68rem; letter-spacing: .05em;">{{ $label }}</div><div class="fw-semibold">{{ filled($value) ? $value : '—' }}</div></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold"><i class="ri-history-line"></i> Envios anteriores ({{ $history->count() }})</div>
                            <div class="card-body small">
                                @forelse ($history as $past)
                                    <div class="border rounded p-2 mb-2">
                                        <div class="d-flex justify-content-between"><strong>{{ $past->type->short() }} · rodada {{ $past->round_number }}</strong><span class="text-muted">{{ $past->completed_at?->format('d/m/Y H:i') }}</span></div>
                                        @if (!empty($past->submission_data['survey']))
                                            @php $sv = $past->submission_data['survey']; @endphp
                                            <div class="text-muted">Postes {{ $sv['postes'] }} · DOE {{ $sv['doe'] }} · Vegetação {{ $sv['ma'] }} · {{ $sv['conclusion'] }}@if (!empty($sv['cadastro'])) · Cadastro {{ $sv['postes_c'] }}@endif</div>
                                        @endif
                                        @foreach ($past->submission_data['orders'] ?? [] as $order)
                                            <span class="badge text-bg-light border">{{ $order['order_number'] }} · {{ number_format((float) $order['total_cost'], 2, ',', '.') }}</span>
                                        @endforeach
                                        @if ($past->observation)<div class="mt-1">{{ $past->observation }}</div>@endif
                                        @foreach ($past->Files as $stageFile)<div><i class="ri-attachment-2"></i> {{ $stageFile->File?->file_name }}</div>@endforeach
                                    </div>
                                @empty
                                    <div class="text-muted">Ainda não houve envios desta obra.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold"><i class="ri-folder-open-line"></i> Arquivos da Nota</div>
                            <div class="card-body small">
                                @forelse ($files as $file)
                                    <div class="d-flex justify-content-between border-bottom py-1"><span><i class="ri-file-line"></i> {{ $file['name'] }}</span><span class="text-muted">{{ $file['created_at'] }}</span></div>
                                @empty
                                    <div class="text-muted">Nenhum arquivo enviado ainda.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="d-flex justify-content-between gap-2">
                            <button type="button" class="btn btn-outline-secondary" wire:click="close">Fechar</button>
                            <button type="button" class="btn btn-primary btn-lg" wire:click="startFinalization"><i class="ri-play-circle-line"></i> Iniciar finalização</button>
                        </div>
                    @else
                    @if (!$isBudget)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-bold">Informe de encerramento{{ $serviceName ? ' · ' . $serviceName : '' }}</div>
                            <div class="card-body row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Postes</label>
                                    <input type="number" min="0" max="500" class="form-control" wire:model.defer="postes">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Depende de órgão externo</label>
                                    <select class="form-select" wire:model.defer="doe"><option value="">Selecione...</option><option value="SIM">SIM</option><option value="NAO">NÃO</option><option value="NAO SEI">NÃO SEI</option></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Interferência em vegetação</label>
                                    <select class="form-select" wire:model.defer="ma"><option value="">Selecione...</option><option value="SIM">SIM</option><option value="NAO">NÃO</option></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Conclusão</label>
                                    <select class="form-select" wire:model.defer="conclusion">
                                        <option value="">Selecione...</option>
                                        @foreach ($conclusions as $option)<option value="{{ $option->value }}">{{ $option->reason }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                                    <div class="form-check mb-0"><input class="form-check-input" type="checkbox" wire:model="cadastro" id="qCadastro"><label class="form-check-label" for="qCadastro">Cadastro</label></div>
                                    @if ($cadastro)
                                        <div style="max-width: 220px;"><label class="form-label mb-1 fw-semibold">Postes do cadastro</label><input type="number" min="0" max="500" class="form-control" wire:model.defer="postesC"></div>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Informações adicionais</label>
                                    <textarea class="form-control" rows="4" wire:model.defer="info"></textarea>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($isBudget)
                        <div class="card shadow-sm mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Ordens do orçamento</span>
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addOrder"><i class="ri-add-line"></i> Adicionar ordem</button>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted">Cada ordem tem 12 dígitos, com prefixo 170, 190, 150 ou 200 (200 quando a Nota inicia com dígito 3 ou maior).</p>
                                @foreach ($orders as $index => $order)
                                    <div class="row g-2 align-items-end mb-2" wire:key="quality-order-{{ $index }}">
                                        <div class="col-md-4">
                                            <label class="form-label small mb-0">Número da ordem</label>
                                            <input class="form-control form-control-sm" wire:model.defer="orders.{{ $index }}.order_number" maxlength="12">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Total</label>
                                            <input class="form-control form-control-sm" wire:model.defer="orders.{{ $index }}.total_cost" placeholder="0,00">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Empresa</label>
                                            <input class="form-control form-control-sm" wire:model.defer="orders.{{ $index }}.company_cost" placeholder="0,00">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Cliente</label>
                                            <input class="form-control form-control-sm" wire:model.defer="orders.{{ $index }}.client_cost" placeholder="0,00">
                                        </div>
                                        <div class="col-md-2 text-end">
                                            @if (count($orders) > 1)
                                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeOrder({{ $index }})"><i class="ri-delete-bin-line"></i></button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="card shadow-sm mb-3">
                        <div class="card-header fw-bold">Arquivos da atividade</div>
                        <div class="card-body">
                            <p class="small text-muted">Os arquivos anexados aqui são associados à atividade, como no encerramento normal. Marque os que compõem esta rodada; os enviados agora já vêm marcados.</p>
                            @forelse ($files as $file)
                                <label class="d-flex gap-2 align-items-center border-bottom py-2" wire:key="quality-file-{{ $file['id'] }}">
                                    <input type="checkbox" class="form-check-input mt-0" value="{{ $file['id'] }}" wire:model="selectedFileIds">
                                    <span>{{ $file['name'] }}</span>
                                    @if ($file['is_new'])
                                        <span class="badge text-bg-success">Nova</span>
                                    @endif
                                    <small class="text-muted ms-auto">{{ $file['created_at'] }}</small>
                                </label>
                            @empty
                                <div class="text-muted small">Nenhum arquivo enviado ainda.</div>
                            @endforelse
                            <div class="mt-3">
                                @livewire('files.manager.create-prod-files', ['production' => \App\Models\Production::find($productionId), 'needFiles' => false], key('quality-files-' . $productionId . '-' . $stage->id))
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-bold">Observações para o N1</label>
                    <textarea class="form-control mb-3" rows="4" wire:model.defer="observation" placeholder="Descreva o que foi feito ou corrigido nesta rodada."></textarea>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" wire:click="close">Cancelar</button>
                        <button type="button" class="btn btn-success" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,afterFilesSaved">
                            <i class="ri-send-plane-line"></i> Enviar ao N1
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
