@php
    $statusTone  = ['PENDING' => 'amber', 'BLOCKED' => 'red', 'RELEASED' => 'green', 'SKIPPED' => 'muted'];
    $statusLabel = ['PENDING' => 'Pendente', 'BLOCKED' => 'Bloqueado', 'RELEASED' => 'Liberado', 'SKIPPED' => 'Dispensado'];
    $statusHint  = [
        'PENDING'  => 'Aguardando a confirmação do bloqueio na data do inventário.',
        'BLOCKED'  => 'Depósito bloqueado: as Ordens dos centros de trabalho vinculados estão em Depósito Bloqueado.',
        'RELEASED' => 'Depósito liberado: as Ordens voltaram ao status anterior.',
        'SKIPPED'  => 'Data dispensada (por exemplo, inventário adiado). Pode ser reaberta.',
    ];
    $statusFilter   = $filters['status'] ?? [];
    $loadingTargets = 'search,setDue,gotoPage,nextPage,previousPage,clearFilters,removeAnyFilter,applyListFilters,removeListFilter,clearListFilters,selectFiltered';
    $pageIds        = $entries->pluck('id')->map(fn ($i) => (string) $i)->all();
    $allOnPage      = $entries->count() > 0 && collect($pageIds)->diff($selected)->isEmpty();
    $bulkMeta       = [
        'block'   => ['Confirmar bloqueio', 'ri-lock-line', 'PENDING', 'Bloqueia, de uma vez, as Ordens dos centros de trabalho cadastrados.'],
        'skip'    => ['Dispensar datas', 'ri-forbid-line', 'PENDING', 'Não bloqueia nestas datas (inventário adiado). Dá para reabrir.'],
        'release' => ['Liberar depósitos', 'ri-lock-unlock-line', 'BLOCKED', 'As Ordens voltam ao status anterior ao bloqueio.'],
        'reopen'  => ['Reabrir datas', 'ri-restart-line', 'SKIPPED', 'Volta a data dispensada para Pendente.'],
    ];
@endphp

{{-- Calendário de depósitos: lista operacional (docs/PADRAO_LISTAS_OPERACIONAIS.md). Hero → cartões-filtro → ferramentas → painel → barra de seleção. --}}
<div class="closure-dash my-panel ops-list">

    <x-ops.hero eyebrow="Gestão do ciclo · Encerramento" title="Calendário de depósitos" subtitle="Inventário da gestão de ativos: na data de cada depósito, confirme o bloqueio e as Ordens dos centros de trabalho vinculados passam a Depósito Bloqueado.">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="openImport"><i class="ri-upload-2-line" aria-hidden="true"></i> Importar calendário</button>
        </div>
    </x-ops.hero>

    <div class="ops-stats">
        <x-ops.stat wire:key="dstat-today" wire:click="setDue('today')" tone="red" icon="ri-calendar-event-line" label="Vencem hoje" :value="number_format($kpis['today'], 0, ',', '.')" :active="$due === 'today'" title="Pendentes com a data de hoje: confirme o bloqueio hoje" />
        <x-ops.stat wire:key="dstat-week" wire:click="setDue('week')" tone="blue" icon="ri-calendar-2-line" label="Próximos 7 dias" :value="number_format($kpis['week'], 0, ',', '.')" :active="$due === 'week'" title="Agenda da semana" />
        <x-ops.stat wire:key="dstat-overdue" wire:click="setDue('overdue')" tone="amber" icon="ri-alarm-warning-line" label="Atrasados" :value="number_format($kpis['overdue'], 0, ',', '.')" :active="$due === 'overdue'" title="A data passou e o bloqueio não foi confirmado" />
        <x-ops.stat wire:key="dstat-active" tone="teal" icon="ri-lock-line" label="Bloqueios ativos" :value="number_format($kpis['active'], 0, ',', '.')" disabled title="Bloqueios por inventário em vigor agora" />
    </div>

    @if (!$hasEntries)
        <section class="mp-panel">
            <div class="mp-panel-body">
                <x-closure.empty-state title="Nenhum calendário carregado" text="Carregue a planilha de inventário entregue pela gestão de ativos (colunas Centro, Depósito e Data inicial das contagens). O sistema identifica as datas e os depósitos sozinho." icon="ri-calendar-line" />
                <div class="text-center mt-2"><button type="button" class="btn btn-primary btn-sm btn-dash" wire:click="openImport"><i class="ri-upload-2-line" aria-hidden="true"></i> Importar calendário</button></div>
            </div>
        </section>
    @else
        <section class="ops-toolbar" aria-label="Filtros da lista">
            <div class="ops-toolbar-row">
                <x-ops.search wire:model.debounce.500ms="search" placeholder="Depósito, denominação, cidade ou centro" label="Buscar depósito" />
                <span class="ops-toolbar-spacer"></span>
                @if ($hasCriteria)
                    <button type="button" class="ops-chips-clear" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
                @endif
            </div>
            <div class="ops-toolbar-row">
                <span class="ops-toolbar-label">Filtros</span>
                <x-ops.filters :payload="$this->listFilterPayload()" />
            </div>
            @if ($hasCriteria)
                <div class="ops-toolbar-row">
                    <x-ops.chips :chips="array_merge($criteriaChips, $this->listFilterChips())" remove-action="removeAnyFilter" clear-action="clearFilters" />
                </div>
            @endif
        </section>

        @if ($bulkResult)
            <div class="ops-notice {{ $bulkResult['refused'] ? 'ops-notice--warn' : '' }}" role="status">
                <i class="{{ $bulkResult['refused'] ? 'ri-error-warning-line' : 'ri-checkbox-circle-line' }}" aria-hidden="true"></i>
                <div>
                    <strong>{{ $bulkResult['done'] }} depósito(s) processado(s)</strong>@if ($bulkResult['orders']) · {{ $bulkResult['orders'] }} Ordem(ns) {{ $bulkResult['action'] === 'release' ? 'liberadas' : 'bloqueadas' }}@endif
                    @if ($bulkResult['ignored'])<div class="small">{{ $bulkResult['ignored'] }} selecionado(s) não estavam no status exigido e foram ignorados.</div>@endif
                    @if ($bulkResult['refused'])
                        <div class="small fw-semibold mt-1">{{ count($bulkResult['refused']) }} recusado(s):</div>
                        <ul class="small mb-0 ps-3">@foreach ($bulkResult['refused'] as $r)<li>{{ $r }}</li>@endforeach</ul>
                    @endif
                </div>
                <button type="button" class="btn-close" aria-label="Fechar" wire:click="dismissBulkResult"></button>
            </div>
        @endif

        <section class="mp-panel" aria-label="Datas de bloqueio">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Datas de bloqueio</h2>
                    <div class="mp-panel-sub">{{ number_format($entries->total(), 0, ',', '.') }} {{ $entries->total() === 1 ? 'depósito' : 'depósitos' }}, ordenados pela data inicial das contagens.</div>
                </div>
                <div class="ops-panel-actions">
                    <span class="mp-pill" wire:loading.delay wire:target="{{ $loadingTargets }}">Atualizando…</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="selectPage({{ json_encode($pageIds) }})">{{ $allOnPage ? 'Limpar página' : 'Selecionar página' }}</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="selectFiltered">Selecionar todos do filtro ({{ number_format(min($entries->total(), 500), 0, ',', '.') }})</button>
                </div>
            </div>

            <x-ops.skeleton wire:loading.delay.class.remove="d-none" wire:target="{{ $loadingTargets }}" class="d-none" :rows="min(10, max(4, $entries->count()))" :cols="7" />

            @if ($entries->isEmpty())
                <div class="mp-panel-body">
                    <x-closure.empty-state :title="$hasCriteria ? 'Nenhum depósito para os filtros escolhidos' : 'Nenhum depósito no calendário'" :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais depósitos.' : 'Importe a planilha do inventário.'" icon="ri-calendar-check-line" />
                    @if ($hasCriteria)
                        <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearFilters">Limpar filtros</button></div>
                    @endif
                </div>
            @else
                <div class="ops-table-wrap" wire:loading.delay.class="d-none" wire:target="{{ $loadingTargets }}">
                    <table class="table ops-table">
                        <colgroup>
                            <col style="width: 38px"><col style="width: 120px"><col style="width: 220px"><col style="width: 190px"><col style="width: 250px"><col style="width: 130px"><col style="width: 130px"><col style="width: 200px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-check-input" aria-label="Selecionar a página" wire:click="selectPage({{ json_encode($pageIds) }})" @checked($allOnPage)></th>
                                <th class="is-left">Data</th>
                                <th class="is-left">Depósito</th>
                                <th class="is-left">Cidade / Serviço</th>
                                <th class="is-left">Empresa e centro de trabalho</th>
                                <th title="Ordens em tratativa nesses centros de trabalho">Ordens afetadas</th>
                                <th>Status</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                @php
                                    $link      = $links[$entry->id];
                                    $days      = (int) $today->diffInDays($entry->scheduled_date, false);
                                    $isPending = $entry->status === 'PENDING';
                                    $noCenter  = $link['rows']->isEmpty();
                                    $dateTone  = !$isPending ? 'muted' : ($days < 0 ? 'red' : ($days === 0 ? 'amber' : 'muted'));
                                    $dateSub   = $days === 0 ? 'hoje' : ($days > 0 ? 'em ' . $days . ' dia(s)' : 'há ' . abs($days) . ' dia(s)');
                                    $companies = $link['rows']->groupBy('company_id');
                                @endphp
                                <tr wire:key="dep-{{ $entry->id }}" @class(['is-selected' => in_array((string) $entry->id, $selected, true)])>
                                    <td><input type="checkbox" class="form-check-input" value="{{ $entry->id }}" wire:model="selected" aria-label="Selecionar depósito {{ $entry->deposit }}"></td>
                                    <td class="is-left"><x-ops.date :date="$entry->scheduled_date" :tone="$dateTone" :sub="$dateSub" :hint="$isPending ? ($days < 0 ? 'A data passou e o bloqueio não foi confirmado.' : ($days === 0 ? 'É hoje: confirme o bloqueio.' : 'Data do inventário.')) : 'Data do inventário.'" /></td>
                                    <td class="is-left">
                                        <div class="ops-note">{{ $entry->deposit }} <span class="ops-sub d-inline">centro {{ $entry->center }}</span></div>
                                        <span class="ops-sub text-truncate" title="{{ $entry->denomination }}">{{ $entry->denomination ?: '—' }}</span>
                                    </td>
                                    <td class="is-left">
                                        <div class="fw-semibold">{{ $entry->city ?: '—' }}</div>
                                        <span class="ops-sub">{{ $entry->service_type ?: '—' }}</span>
                                    </td>
                                    <td class="is-left">
                                        @if ($noCenter)
                                            <x-ops.badge tone="red" icon="ri-error-warning-line" label="Sem centro de trabalho cadastrado" hint="O bloqueio precisa de ao menos um centro de trabalho ligado a este depósito. Cadastre em Administração > Empresa > Centros e depósitos." />
                                        @else
                                            <div class="ops-cell-stack">
                                                @foreach ($companies as $companyRows)
                                                    <div class="d-flex flex-wrap align-items-center gap-1">
                                                        <span class="small fw-semibold">{{ $companyRows->first()->Company?->display_name ?? '—' }}</span>
                                                        @foreach ($companyRows->pluck('centerjob')->unique() as $code)<x-ops.badge sm mono tone="teal" :label="$code" hint="Centro de trabalho (cenTrab)" />@endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($entry->status === 'BLOCKED' && $entry->Signal)
                                            <strong>{{ $entry->Signal->affected_count }}</strong> <span class="ops-sub d-inline">bloqueada(s)</span>
                                        @elseif ($link['affected'] !== null)
                                            <strong>{{ $link['affected'] }}</strong> <span class="ops-sub d-inline">em tratativa</span>
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="ops-cell-stack is-center">
                                            <x-ops.badge :tone="$statusTone[$entry->status] ?? 'muted'" :label="$statusLabel[$entry->status] ?? $entry->status" :hint="$statusHint[$entry->status] ?? null" />
                                            @if ($entry->status === 'SKIPPED' && $entry->skip_reason)<span class="ops-sub" title="{{ $entry->skip_reason }}">{{ \Illuminate\Support\Str::limit($entry->skip_reason, 28) }}</span>@endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ops-buttons">
                                            @if ($isPending)
                                                <button type="button" class="btn btn-sm ops-action {{ $days <= 0 && !$noCenter ? 'btn-danger' : 'btn-outline-secondary' }}" wire:click="startConfirm({{ $entry->id }})" @if ($noCenter) title="Sem centro de trabalho cadastrado: abra para ver como resolver" @endif><i class="ri-lock-line" aria-hidden="true"></i> Confirmar bloqueio</button>
                                                <div class="dropdown ops-row-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ops-menu-btn" data-bs-toggle="dropdown" data-closure-fixed-dropdown aria-expanded="false" aria-label="Mais ações do depósito {{ $entry->deposit }}"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><button type="button" class="dropdown-item" wire:click="startSkip({{ $entry->id }})"><i class="ri-forbid-line me-2"></i>Dispensar esta data</button></li>
                                                    </ul>
                                                </div>
                                            @elseif ($entry->status === 'BLOCKED')
                                                <button type="button" class="btn btn-sm btn-success ops-action" wire:click="startRelease({{ $entry->id }})"><i class="ri-lock-unlock-line" aria-hidden="true"></i> Liberar depósito</button>
                                            @elseif ($entry->status === 'SKIPPED')
                                                <button type="button" class="btn btn-sm btn-outline-secondary ops-action" wire:click="reopen({{ $entry->id }})"><i class="ri-restart-line" aria-hidden="true"></i> Reabrir</button>
                                            @else
                                                <span class="ops-sub">{{ $entry->Signal?->released_at?->format('d/m/Y H:i') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-ops.pager :paginator="$entries" />
        </section>

        @if (count($selected))
            <div class="ops-selection" id="deposit-bulk">
                <div class="ops-selection-count"><strong>{{ count($selected) }}</strong> {{ count($selected) === 1 ? 'selecionado' : 'selecionados' }}
                    <small class="d-block opacity-75">@foreach ($selectedCounts as $st => $total){{ $total }} {{ strtolower($statusLabel[$st] ?? $st) }}(s){{ !$loop->last ? ' · ' : '' }}@endforeach</small>
                </div>
                <div class="ops-selection-actions">
                    <div class="dropup">
                        <button type="button" class="btn btn-light btn-sm btn-dash dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="ri-stack-line" aria-hidden="true"></i> Ações em massa</button>
                        <ul class="dropdown-menu ops-bulk-menu">
                            @foreach ($bulkMeta as $act => [$label, $icon, $needs, $hint])
                                @php $n = $selectedCounts[$needs] ?? 0; @endphp
                                <li><button type="button" class="dropdown-item" wire:click="startBulk('{{ $act }}')" @disabled($n === 0)><i class="{{ $icon }}" aria-hidden="true"></i><span>{{ $label }} ({{ $n }})<small>{{ $n === 0 ? 'Nenhum selecionado está no status exigido (' . strtolower($statusLabel[$needs]) . ').' : $hint }}</small></span></button></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="ops-selection-clear" wire:click="clearSelection" aria-label="Limpar seleção"><i class="ri-close-line"></i></button>
            </div>
        @endif
    @endif

    {{-- Importar --}}
    @if ($importModal)
        <x-ops.modal title="Importar calendário de depósitos" eyebrow="Planilha de inventário da gestão de ativos (.xlsx)" close="closeImport" size="modal-lg">
            <p class="ops-modal-note">Colunas usadas: <strong>Centro</strong>, <strong>Depósito</strong> e <strong>Data inicial das contagens</strong> (obrigatórias), mais Cidade, Denominação, Tipo de serviço, Mês e Responsável. Importar de novo atualiza as datas ainda pendentes e não mexe em depósitos já bloqueados, liberados ou dispensados.</p>
            <label for="calendar-file" class="form-label text-uppercase small fw-bold">Planilha</label>
            <input id="calendar-file" type="file" class="form-control @error('calendarFile') is-invalid @enderror" wire:model="calendarFile" accept=".xlsx,.xls">
            @error('calendarFile') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div wire:loading wire:target="calendarFile" class="small text-muted mt-2"><span class="spinner-border spinner-border-sm"></span> Carregando arquivo…</div>

            @if ($importResult)
                <div class="ops-modal-card mt-3">
                    <div class="ops-modal-card-head tone-{{ count($importResult['errors']) ? 'red' : 'green' }}"><i class="ri-file-list-3-line" aria-hidden="true"></i> Resultado</div>
                    <div class="ops-modal-card-body">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <x-ops.badge tone="green" :label="$importResult['created'] . ' nova(s)'" />
                            <x-ops.badge tone="blue" :label="$importResult['updated'] . ' atualizada(s)'" />
                            <x-ops.badge tone="muted" :label="$importResult['kept'] . ' mantida(s)'" />
                            <x-ops.badge :tone="count($importResult['errors']) ? 'red' : 'muted'" :label="count($importResult['errors']) . ' erro(s)'" />
                        </div>
                        @if ($importResult['errors'])
                            <ul class="small text-danger mb-0 ps-3">@foreach (array_slice($importResult['errors'], 0, 30) as $error)<li>{{ $error }}</li>@endforeach</ul>
                        @endif
                    </div>
                </div>
            @endif
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="closeImport">Fechar</button>
                <button type="button" class="btn btn-primary" wire:click="import" wire:loading.attr="disabled" wire:target="import,calendarFile"><i class="ri-upload-2-line" aria-hidden="true"></i> Importar</button>
            </x-slot>
        </x-ops.modal>
    @endif

    {{-- Confirmar bloqueio: se não há centro de trabalho cadastrado, o modal explica o motivo e como resolver (não há botão morto). --}}
    @if ($confirming && $confirmInfo)
        @php $noCenters = $confirmInfo['rows']->isEmpty(); @endphp
        <x-ops.modal :title="'Confirmar bloqueio do depósito ' . $confirming->deposit" :eyebrow="$confirming->denomination . ' · contagem em ' . $confirming->scheduled_date->format('d/m/Y')" close="cancelConfirm" size="modal-lg">
            <div class="ops-modal-card mb-3">
                <div class="ops-modal-card-head tone-{{ $noCenters ? 'red' : 'teal' }}"><i class="ri-building-line" aria-hidden="true"></i> Empresa e centro de trabalho cadastrados</div>
                <div class="ops-modal-card-body">
                    <div class="small text-muted">Centro SAP {{ $confirming->center }} · {{ $confirming->city ?: '—' }}</div>
                    @forelse ($confirmInfo['rows']->groupBy('company_id') as $rows)
                        <div class="mt-1">{{ $rows->first()->Company?->display_name }} — @foreach ($rows->pluck('centerjob')->unique() as $code)<x-ops.badge sm mono tone="teal" :label="$code" />@endforeach</div>
                    @empty
                        <div class="mt-1 text-danger fw-semibold">Nenhum centro de trabalho cadastrado para o depósito {{ $confirming->deposit }}.</div>
                    @endforelse
                </div>
            </div>

            @if ($noCenters)
                <div class="ops-warning">
                    <strong>Por que não dá para confirmar:</strong> o bloqueio age sobre as Ordens dos centros de trabalho ligados ao depósito, e este depósito ainda não tem nenhum cadastrado
                    (a data estar em dia não basta). Cadastre o centro de trabalho em <strong>Administração › Empresa › Centros e depósitos</strong>, informando o depósito <strong>{{ $confirming->deposit }}</strong> (centro SAP {{ $confirming->center }}), e volte aqui.
                    @if ($canRegister)
                        <a href="{{ route('admin.company.list') }}" class="btn btn-sm btn-outline-dark ms-1" target="_blank" rel="noopener"><i class="ri-external-link-line" aria-hidden="true"></i> Abrir cadastro de empresas</a>
                    @else
                        Peça a um administrador para fazer o cadastro.
                    @endif
                </div>
            @else
                <p class="ops-modal-note"><strong>{{ $confirmInfo['affected'] }}</strong> Ordem(ns) em tratativa desses centros de trabalho passam para <strong>Depósito Bloqueado</strong> agora. Cada uma volta ao status anterior quando o depósito for liberado.</p>
            @endif

            @if ($confirmInfo['active'])
                <div class="ops-warning mb-3">Já existe um bloqueio ativo para este depósito. Libere-o antes de confirmar de novo.</div>
            @endif

            <label for="confirm-reason" class="form-label text-uppercase small fw-bold">Motivo <span class="text-danger">*</span></label>
            <textarea id="confirm-reason" class="form-control @error('confirmReason') is-invalid @enderror" rows="3" wire:model.defer="confirmReason" maxlength="1000" @disabled($noCenters)></textarea>
            @error('confirmReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelConfirm">Cancelar</button>
                <button type="button" class="btn btn-danger" wire:click="confirmBlock" wire:loading.attr="disabled" wire:target="confirmBlock" @disabled($noCenters || $confirmInfo['active']) @if ($noCenters) title="Cadastre o centro de trabalho do depósito antes de bloquear." @endif><i class="ri-lock-line" aria-hidden="true"></i> Bloquear depósito</button>
            </x-slot>
        </x-ops.modal>
    @endif

    {{-- Ação em massa --}}
    @if ($bulkAction)
        @php [$bTitle, $bIcon, $bNeeds, $bHint] = $bulkMeta[$bulkAction]; @endphp
        <x-ops.modal :title="$bTitle . ' em massa'" :eyebrow="$bulkCount . ' depósito(s) elegíveis na seleção'" close="cancelBulk">
            <p class="ops-modal-note">{{ $bHint }} @if ($bulkAction === 'block')Depósitos sem centro de trabalho ou já bloqueados são recusados e listados ao final.@endif Só os selecionados no status exigido ({{ strtolower($statusLabel[$bNeeds]) }}) são processados.</p>
            @if (in_array($bulkAction, ['block', 'release', 'skip'], true))
                <label for="bulk-reason" class="form-label text-uppercase small fw-bold">{{ $bulkAction === 'skip' ? 'Motivo (opcional)' : 'Motivo' }} @if ($bulkAction !== 'skip')<span class="text-danger">*</span>@endif</label>
                <textarea id="bulk-reason" class="form-control @error('bulkReason') is-invalid @enderror" rows="3" wire:model.defer="bulkReason" maxlength="1000"></textarea>
                @error('bulkReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @endif
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelBulk">Cancelar</button>
                <button type="button" class="btn btn-primary" wire:click="confirmBulk" wire:loading.attr="disabled" wire:target="confirmBulk" @disabled($bulkCount === 0)><i class="{{ $bIcon }}" aria-hidden="true"></i> {{ $bTitle }} ({{ $bulkCount }})</button>
            </x-slot>
        </x-ops.modal>
    @endif

    {{-- Dispensar --}}
    @if ($skipping)
        <x-ops.modal title="Dispensar esta data" :eyebrow="'Depósito ' . $skipping->deposit . ' · ' . $skipping->scheduled_date->format('d/m/Y')" close="cancelSkip">
            <p class="ops-modal-note">O depósito não será bloqueado nesta data (por exemplo, inventário adiado). Você pode reabrir depois.</p>
            <label for="skip-reason" class="form-label text-uppercase small fw-bold">Motivo (opcional)</label>
            <textarea id="skip-reason" class="form-control" rows="3" wire:model.defer="skipReason" maxlength="500"></textarea>
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelSkip">Cancelar</button>
                <button type="button" class="btn btn-primary" wire:click="confirmSkip"><i class="ri-forbid-line" aria-hidden="true"></i> Dispensar</button>
            </x-slot>
        </x-ops.modal>
    @endif

    {{-- Liberar --}}
    @if ($releasing)
        <x-ops.modal :title="'Liberar depósito ' . $releasing->deposit" :eyebrow="$releasing->denomination" close="cancelRelease">
            <p class="ops-modal-note">As <strong>{{ $releasing->Signal?->affected_count ?? 0 }}</strong> Ordem(ns) bloqueadas voltam, de uma vez, ao status que tinham antes do bloqueio.</p>
            <label for="rel-note" class="form-label text-uppercase small fw-bold">Observação (opcional)</label>
            <textarea id="rel-note" class="form-control" rows="3" wire:model.defer="releaseNote" maxlength="1000" placeholder="Ex.: inventário concluído."></textarea>
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="cancelRelease">Cancelar</button>
                <button type="button" class="btn btn-primary" wire:click="confirmRelease" wire:loading.attr="disabled" wire:target="confirmRelease"><i class="ri-lock-unlock-line" aria-hidden="true"></i> Liberar todas</button>
            </x-slot>
        </x-ops.modal>
    @endif
</div>
