@php
    $partnerCan     = fn (string $permission) => \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), $permission);
    $canRequest     = $partnerCan('conclusion_reports.ads_requests');
    $canExport      = $partnerCan('conclusion_reports.export');
    $isActive       = $tab !== 'history';
    $tabs           = [
        'active'  => 'Em andamento (' . number_format($counts['active'], 0, ',', '.') . ')',
        'history' => 'Histórico (' . number_format($counts['history'], 0, ',', '.') . ')',
    ];
    $processable    = collect($previewItems)->where('can_process', true)->count();
    $blockedCount   = collect($previewItems)->where('can_process', false)->count();
    $hasFilters     = $this->hasListFilters();
    $hasHistoryCrit = $hasFilters || trim((string) $historySearch) !== '' || $historyStart || $historyEnd;
@endphp

{{-- SOLICITAÇÕES DE ADS DA PARCEIRA (lista de controle). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Regras de pedido, reaproveitamento de link e espelho no SQL Server: componente. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · ADS" title="Solicitações de ADS"
        subtitle="Peça os arquivos de ADS das suas obras, acompanhe o que está na fila e baixe o que já foi concluído." />

    @if (!$sqlSyncEnabled)
        <div class="alert alert-warning mb-3" role="status">
            Modo teste sem envio para SQL Server está habilitado. As solicitações serão registradas apenas localmente.
        </div>
    @endif

    <div class="alert alert-warning mb-3" role="note">
        <strong>Aviso importante</strong><br>
        Não serão enviados e-mails com as ADS. Antes de solicitar, verifique se o link já está disponível no SICODE.
        Na pré-análise, o sistema já faz essa verificação automaticamente.<br>
        <strong>Atenção:</strong> É obrigatório que o usuário responsável <strong>TENHA USUARIO EDP (email)</strong> para conseguir baixar os arquivos.
        Os arquivos não ficam hospedados no SICODE.<br>
        Se a solicitação estiver <strong>BLOQUEADA</strong> para o usuário, solicite a liberação.
    </div>

    {{-- Pedido: notas -> pré-análise -> processar --}}
    <section class="mp-panel" aria-label="Solicitação de pedidos ADS">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Solicitação de pedidos ADS</h2>
                <div class="mp-panel-sub">Informe as notas (separe por vírgula, espaço ou quebra de linha) e faça a pré-análise antes de processar.</div>
            </div>
        </div>
        <div class="mp-panel-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-9">
                    <label class="form-label fw-semibold text-uppercase small" for="adsNotesInput">Notas</label>
                    <textarea id="adsNotesInput" class="form-control" rows="3" wire:model.defer="notesInput" placeholder="Ex.: 4001800922, 4001800923"></textarea>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2">
                    @if ($canRequest)
                        <button type="button" class="btn btn-primary w-100" wire:click.prevent="analyzeNotes"
                            wire:loading.attr="disabled" wire:target="analyzeNotes,processRequests,confirmProcessRequests"
                            @disabled($isProcessingRequests)>
                            <i class="ri-search-line" aria-hidden="true"></i> Pré-analisar
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click.prevent="clearPreview">Limpar</button>
                </div>
            </div>
        </div>
    </section>

    @if (count($previewItems))
        <section class="mp-panel" aria-label="Pré-análise">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Pré-análise</h2>
                    <div class="mp-panel-sub">Aptos: {{ $processable }}@if ($blockedCount) · {{ $blockedCount }} nota(s) bloqueada(s): veja o motivo na coluna Detalhe @endif</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if ($canRequest)
                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="processRequests"
                            wire:loading.attr="disabled" wire:target="processRequests,confirmProcessRequests"
                            @disabled($processable === 0 || $isProcessingRequests)>
                            <span wire:loading.remove wire:target="processRequests,confirmProcessRequests"><i class="ri-check-line" aria-hidden="true"></i> Processar lista</span>
                            <span wire:loading wire:target="processRequests,confirmProcessRequests">Processando...</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-danger btn-sm ops-action" wire:click.prevent="removeSelectedPreview" @disabled(count($selectedPreviewItems ?? []) === 0)>Remover selecionados</button>
                    <button type="button" class="btn btn-outline-danger btn-sm ops-action" wire:click.prevent="removeAllPreview">Remover todos</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table mp-table align-middle mb-0">
                    <colgroup>
                        <col style="width: 44px">
                        <col style="width: 140px">
                        <col style="width: 150px">
                        <col>
                        <col style="width: 170px">
                        <col style="width: 160px">
                        <col style="width: 110px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><span class="visually-hidden">Selecionar</span></th>
                            <th>Nota</th>
                            <th>Situação</th>
                            <th>Detalhe</th>
                            <th>Baixar ADS</th>
                            <th>Solicitação anterior</th>
                            <th><span class="visually-hidden">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previewItems as $item)
                            <tr wire:key="preview_{{ $item['note_number'] }}">
                                <td><input type="checkbox" class="form-check-input" wire:model="selectedPreviewItems" value="{{ $item['note_number'] }}" aria-label="Selecionar a nota {{ $item['note_number'] }}"></td>
                                <td><x-ops.note :note="$item['note_number']" /></td>
                                <td><x-ops.badge :tone="$item['status_tone']" :label="$item['status_label']" /></td>
                                <td>
                                    @if (!$item['can_process'])
                                        <div class="fw-semibold"><i class="ri-error-warning-line" aria-hidden="true"></i> Bloqueado</div>
                                    @endif
                                    <div>{{ $item['message'] }}</div>
                                </td>
                                <td>
                                    @if (!empty($item['last_url']))
                                        <a href="{{ $item['last_url'] }}" class="btn btn-outline-primary btn-sm ops-action" target="_blank" rel="noopener"><i class="ri-download-2-line" aria-hidden="true"></i> Baixar ADS</a>
                                        @if (!empty($item['last_url_age']))
                                            <div class="ops-person-sub">{{ $item['last_url_age'] }}</div>
                                        @endif
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item['previous_request_id'])
                                        #{{ $item['previous_request_id'] }}
                                        @if ($item['previous_status'])
                                            <span class="ops-person-sub d-inline">({{ $item['previous_status'] }})</span>
                                        @endif
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm ops-action" wire:click.prevent="removePreview('{{ $item['note_number'] }}')">Remover</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Listas: duas abas, só a aberta é consultada --}}
    <section class="ops-toolbar" aria-label="Filtros das solicitações">
        <div class="ops-toolbar-row">
            <x-ops.segmented name="adsTab" label="Lista" wire:model="tab" :options="$tabs" />

            @if ($isActive)
                <x-ops.search wire:model.debounce.500ms="activeSearch" placeholder="Buscar notas (separe por vírgula ou espaço)…" label="Buscar nota em andamento" />
            @else
                <x-ops.search wire:model.debounce.500ms="historySearch" placeholder="Buscar notas (separe por vírgula ou espaço)…" label="Buscar nota no histórico" />
            @endif

            <span class="ops-toolbar-spacer"></span>

            @if (!$isActive && $hasHistoryCrit)
                <button type="button" class="ops-chips-clear" wire:click="clearHistoryFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @elseif ($isActive && trim((string) $activeSearch) !== '')
                <button type="button" class="ops-chips-clear" wire:click="$set('activeSearch', '')"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            @if ($companyChoices->count() > 1)
                <span class="ops-toolbar-label">Empresa</span>
                <select class="form-select form-select-sm w-auto" wire:model="partnerCompanyFilter" aria-label="Empresa da solicitação">
                    <option value="">Todas as empresas permitidas</option>
                    @foreach ($companyChoices as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            @endif

            @if (!$isActive)
                <span class="ops-toolbar-label">Período</span>
                <input type="date" class="form-control form-control-sm w-auto" wire:model="historyStart" aria-label="Criadas a partir de">
                <input type="date" class="form-control form-control-sm w-auto" wire:model="historyEnd" aria-label="Criadas até">

                @if (count($this->listFilterPayload()['defs']))
                    <span class="ops-toolbar-label">Filtros</span>
                    <x-ops.filters :payload="$this->listFilterPayload()" />
                @endif
            @endif
        </div>

        @if (!$isActive && $hasFilters)
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    @if ($isActive)
        <section class="mp-panel" aria-label="Solicitações em andamento">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Solicitações em andamento</h2>
                    <div class="mp-panel-sub">{{ number_format($activeRequests->total(), 0, ',', '.') }} {{ $activeRequests->total() === 1 ? 'solicitação' : 'solicitações' }}@if (trim((string) $activeSearch) !== '') com a busca atual @endif</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click.prevent="syncAllRequests" wire:loading.attr="disabled" wire:target="syncAllRequests"
                    @disabled($activeRequests->isEmpty() || !$sqlSyncEnabled)>
                    <i class="ri-refresh-line" aria-hidden="true"></i> Sincronizar todos
                </button>
            </div>

            <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="7" />

            <div wire:loading.delay.class="d-none">
                @if ($activeRequests->isEmpty())
                    <x-closure.empty-state icon="ri-inbox-line"
                        :title="trim((string) $activeSearch) !== '' ? 'Nenhuma solicitação com essa busca' : 'Nenhuma solicitação em andamento'"
                        :text="trim((string) $activeSearch) !== '' ? 'Ajuste ou limpe a busca para ver mais solicitações.' : 'Use a pré-análise acima para pedir uma ADS.'" />
                @else
                    <div class="table-responsive">
                        <table class="table mp-table align-middle mb-0">
                            <colgroup>
                                <col style="width: 140px">
                                <col style="width: 18%">
                                <col style="width: 150px">
                                <col>
                                <col style="width: 80px">
                                <col style="width: 150px">
                                <col style="width: 150px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Empresa</th>
                                    <th>Situação</th>
                                    <th>Descrição</th>
                                    <th>Versão</th>
                                    <th>SQL Server</th>
                                    <th>Criada em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activeRequests as $request)
                                    <tr wire:key="active_{{ $request->id }}">
                                        <td><x-ops.note :note="$request->note?->note ?? $request->note_id" /></td>
                                        <td>{{ $request->company?->name ?? '—' }}</td>
                                        <td><x-ops.badge :tone="$statusTones[$request->status?->value] ?? 'muted'" :label="$request->status?->label() ?? '—'" /></td>
                                        <td>{{ $request->description ?: '—' }}</td>
                                        <td>{{ $request->version }}</td>
                                        <td>
                                            @if (!$sqlSyncEnabled)
                                                <x-ops.badge tone="muted" label="Modo teste" hint="Sem envio ao SQL Server neste ambiente" />
                                            @else
                                                @php
                                                    $sqlStatus = $sqlStatusBySicodeId->get($request->id)?->status;
                                                    $isSynced  = $sqlStatus && $sqlStatus === $request->status?->value;
                                                @endphp
                                                @if (!$sqlStatus)
                                                    <x-ops.badge tone="amber" label="Não encontrado" hint="A solicitação ainda não está no SQL Server" />
                                                @elseif ($isSynced)
                                                    <x-ops.badge tone="green" label="Sincronizado" hint="O SQL Server tem a mesma situação" />
                                                @else
                                                    <x-ops.badge tone="red" label="Diferente" hint="O SQL Server tem outra situação: use Sincronizar todos" />
                                                @endif
                                            @endif
                                        </td>
                                        <td><x-ops.date :date="$request->created_at" :time="true" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-ops.pager :paginator="$activeRequests" :per-page="$perPage" :sizes="[25, 50, 100]" />
                @endif
            </div>
        </section>
    @else
        <section class="mp-panel" aria-label="Histórico de solicitações">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Histórico de solicitações</h2>
                    <div class="mp-panel-sub">{{ number_format($historyRequests->total(), 0, ',', '.') }} {{ $historyRequests->total() === 1 ? 'solicitação' : 'solicitações' }}@if ($hasHistoryCrit) com os filtros atuais @endif</div>
                </div>
                @if ($canExport)
                    <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click.prevent="exportHistory" @disabled($historyRequests->total() === 0)>
                        <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                    </button>
                @endif
            </div>

            <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

            <div wire:loading.delay.class="d-none">
                @if ($historyRequests->isEmpty())
                    <x-closure.empty-state icon="ri-history-line"
                        :title="$hasHistoryCrit ? 'Nenhuma solicitação com esses filtros' : 'Nenhuma solicitação encontrada'"
                        :text="$hasHistoryCrit ? 'Ajuste ou limpe os filtros para ver mais solicitações.' : 'As solicitações concluídas, canceladas ou com falha aparecem aqui.'" />
                @else
                    <div class="table-responsive">
                        <table class="table mp-table align-middle mb-0">
                            <colgroup>
                                <col style="width: 130px">
                                <col style="width: 16%">
                                <col style="width: 130px">
                                <col>
                                <col style="width: 140px">
                                <col style="width: 70px">
                                <col style="width: 130px">
                                <col style="width: 130px">
                                <col style="width: 130px">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Empresa</th>
                                    <th>Situação</th>
                                    <th>Descrição</th>
                                    <th>ADS</th>
                                    <th>Versão</th>
                                    <th>Criada em</th>
                                    <th>Concluída em</th>
                                    <th>Cancelada em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historyRequests as $request)
                                    <tr wire:key="history_{{ $request->id }}">
                                        <td><x-ops.note :note="$request->note?->note ?? $request->note_id" /></td>
                                        <td>{{ $request->company?->name ?? '—' }}</td>
                                        <td><x-ops.badge :tone="$statusTones[$request->status?->value] ?? 'muted'" :label="$request->status?->label() ?? '—'" /></td>
                                        <td>{{ $request->description ?: '—' }}</td>
                                        <td>
                                            @if ($request->url)
                                                <a href="{{ $request->url }}" class="btn btn-outline-primary btn-sm ops-action" target="_self"><i class="ri-download-2-line" aria-hidden="true"></i> Baixar ADS</a>
                                            @else
                                                <x-ops.badge sm tone="muted" label="Sem link" />
                                            @endif
                                        </td>
                                        <td>{{ $request->version }}</td>
                                        <td><x-ops.date :date="$request->created_at" :time="true" /></td>
                                        <td><x-ops.date :date="$request->completed_at" :time="true" /></td>
                                        <td><x-ops.date :date="$request->canceled_at" :time="true" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <x-ops.pager :paginator="$historyRequests" :per-page="$perPage" :sizes="[25, 50, 100]" />
                @endif
            </div>
        </section>
    @endif
</div>
