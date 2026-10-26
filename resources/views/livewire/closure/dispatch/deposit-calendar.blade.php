@php
    $monthNames = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
    $statusMeta = [
        'PENDING'  => ['Pendente', '#fef3c7', '#b45309'],
        'BLOCKED'  => ['Bloqueado', '#fee2e2', '#b91c1c'],
        'RELEASED' => ['Liberado', '#dcfce7', '#15803d'],
        'SKIPPED'  => ['Dispensado', '#f1f5f9', '#475569'],
    ];
    $kpiCards = [
        ['today', 'Vencem hoje', $kpis['today'], 'ri-calendar-event-line', '#c0392b', 'confirmar o bloqueio hoje'],
        ['week', 'Próximos 7 dias', $kpis['week'], 'ri-calendar-2-line', '#263cc8', 'agenda da semana'],
        ['overdue', 'Atrasados', $kpis['overdue'], 'ri-alarm-warning-line', '#b45309', 'data passou, sem confirmação'],
        ['active', 'Bloqueios ativos', $kpis['active'], 'ri-lock-line', '#155f67', 'por inventário'],
    ];
    $chips = [];
    if (trim($search) !== '') { $chips[] = ['search', 'Busca: ' . trim($search)]; }
    if ($status !== '') { $chips[] = ['status', 'Status: ' . ($statusMeta[$status][0] ?? $status)]; }
    if ($month !== '') { $chips[] = ['month', 'Mês: ' . ($monthNames[(int) $month] ?? $month)]; }
    if ($serviceType !== '') { $chips[] = ['serviceType', 'Tipo: ' . $serviceType]; }
    if ($due !== '') { $chips[] = ['due', ['today' => 'Vencem hoje', 'week' => 'Próximos 7 dias', 'overdue' => 'Atrasados'][$due] ?? $due]; }
@endphp
<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Calendário de depósitos</h2>
                <div class="dash-subtitle">Inventário da gestão de ativos: na data de cada depósito, confirme o bloqueio e as Ordens dos centros de trabalho vinculados passam a Depósito Bloqueado.</div>
            </div>
            <button type="button" class="btn btn-light btn-sm fw-semibold" wire:click="openImport"><i class="ri-upload-2-line"></i> Importar calendário</button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        @foreach ($kpiCards as [$key, $label, $value, $icon, $color, $note])
            <div class="col-6 col-xl-3" wire:key="dkpi-{{ $key }}">
                <button type="button" class="kpi w-100 text-start {{ $due === $key ? 'is-active' : '' }}" style="--k: {{ $color }}; {{ $key === 'active' ? 'cursor: default;' : 'cursor: pointer;' }}" @if ($key !== 'active') wire:click="setDue('{{ $key }}')" @endif>
                    <div class="kpi-top"><span>{{ $label }}</span><i class="{{ $icon }}"></i></div>
                    <div class="kpi-value">{{ number_format($value, 0, ',', '.') }}</div>
                    <div class="kpi-note">{{ $note }}</div>
                </button>
            </div>
        @endforeach
    </div>

    @if (!$hasEntries)
        <div class="table-card p-5 text-center">
            <i class="ri-calendar-line d-block text-muted" style="font-size: 2.6rem; opacity: .5;"></i>
            <h3 class="chart-title mt-2">Nenhum calendário carregado</h3>
            <p class="text-muted mb-3">Carregue a planilha de inventário entregue pela gestão de ativos (colunas Centro, Depósito e Data inicial das contagens). O sistema identifica as datas e os depósitos automaticamente.</p>
            <button type="button" class="btn btn-primary" wire:click="openImport"><i class="ri-upload-2-line"></i> Importar calendário</button>
        </div>
    @else
        <div class="table-card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2 fw-bold"><i class="ri-filter-3-line"></i> Filtros @if (count($chips))<span class="badge bg-light text-dark border">{{ count($chips) }} ativo(s)</span>@endif</div>
                @if (count($chips))
                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-danger" wire:click="clearFilters"><i class="ri-close-circle-line"></i> Limpar filtros</button>
                @endif
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="ctl-label">Busca</label>
                    <div class="input-group"><span class="input-group-text bg-white"><i class="ri-search-line"></i></span><input type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Depósito, denominação, cidade ou centro"></div>
                </div>
                <div class="col-6 col-xl-2">
                    <label class="ctl-label">Mês</label>
                    <select class="form-select" wire:model="month">
                        <option value="">Todos</option>
                        @foreach ($months as $m)
                            <option value="{{ $m }}">{{ $monthNames[$m] ?? $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-xl-3">
                    <label class="ctl-label">Tipo de serviço</label>
                    <select class="form-select" wire:model="serviceType">
                        <option value="">Todos</option>
                        @foreach ($serviceTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-xl-3">
                    <label class="ctl-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="">Todos</option>
                        @foreach ($statusMeta as $key => [$label])
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if (count($chips))
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <span class="small text-muted">Filtros aplicados:</span>
                    @foreach ($chips as [$kind, $label])
                        <span class="ctl-chip" wire:key="dchip-{{ $kind }}">{{ $label }}<button type="button" wire:click="$set('{{ $kind }}', '')" aria-label="Remover filtro"><i class="ri-close-line"></i></button></span>
                    @endforeach
                </div>
            @endif
        </div>

        @if (count($selected))
            @php
                $bulkMeta = [
                    'block'   => ['Confirmar bloqueio', 'ri-lock-line', 'PENDING', 'btn-danger'],
                    'skip'    => ['Dispensar', 'ri-forbid-line', 'PENDING', 'btn-outline-secondary'],
                    'release' => ['Liberar depósitos', 'ri-lock-unlock-line', 'BLOCKED', 'btn-success'],
                    'reopen'  => ['Reabrir', 'ri-restart-line', 'SKIPPED', 'btn-outline-secondary'],
                ];
            @endphp
            <div class="table-card p-3 mb-3 d-flex flex-wrap align-items-center gap-3" style="border-left: 4px solid #155f67;">
                <div>
                    <div class="fw-bold">{{ count($selected) }} depósito(s) selecionado(s)</div>
                    <div class="small text-muted">
                        @foreach ($selectedCounts as $st => $total){{ $total }} {{ strtolower($statusMeta[$st][0] ?? $st) }}(s){{ !$loop->last ? ' · ' : '' }}@endforeach
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 ms-auto align-items-center">
                    @foreach ($bulkMeta as $act => [$label, $icon, $needs, $btn])
                        @if (($selectedCounts[$needs] ?? 0) > 0)
                            <button type="button" class="btn btn-sm {{ $btn }} fw-semibold" wire:click="startBulk('{{ $act }}')"><i class="{{ $icon }}"></i> {{ $label }} ({{ $selectedCounts[$needs] }})</button>
                        @endif
                    @endforeach
                    <button type="button" class="btn btn-sm btn-outline-dark" wire:click="selectFiltered">Selecionar todos do filtro</button>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none text-danger" wire:click="clearSelection"><i class="ri-close-line"></i> Limpar seleção</button>
                </div>
            </div>
        @endif

        @if ($bulkResult)
            <div class="table-card p-3 mb-3" style="border-left: 4px solid {{ $bulkResult['refused'] ? '#b45309' : '#0f8a77' }};">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-bold">{{ $bulkResult['done'] }} depósito(s) processado(s)@if ($bulkResult['orders']) · {{ $bulkResult['orders'] }} Ordem(ns) {{ $bulkResult['action'] === 'release' ? 'liberadas' : 'bloqueadas' }}@endif</div>
                        @if ($bulkResult['ignored'])<div class="small text-muted">{{ $bulkResult['ignored'] }} selecionado(s) não estavam no status exigido e foram ignorados.</div>@endif
                    </div>
                    <button type="button" class="btn-close" aria-label="Fechar" wire:click="dismissBulkResult"></button>
                </div>
                @if ($bulkResult['refused'])
                    <div class="small fw-semibold mt-2" style="color:#b45309;">{{ count($bulkResult['refused']) }} recusado(s):</div>
                    <ul class="small mb-0 ps-3" style="max-height:160px;overflow:auto;">@foreach ($bulkResult['refused'] as $r)<li>{{ $r }}</li>@endforeach</ul>
                @endif
            </div>
        @endif

        <div class="table-card mb-3">
            <div class="od-section-head">
                <div><h3><i class="ri-calendar-check-line text-muted"></i> Datas de bloqueio <span class="od-count">{{ $entries->total() }} depósito(s)</span></h3><div class="small text-muted mt-1">Ordenadas pela data inicial das contagens.</div></div>
            </div>
            <div class="table-responsive dash-scroll">
                <table class="table table-sm align-middle mb-0 dash-table" style="min-width: 1040px;">
                    <thead>
                        <tr>
                            <th class="ps-3" style="width:34px;"><input type="checkbox" class="form-check-input" aria-label="Selecionar a página" wire:click="selectPage({{ json_encode($entries->pluck('id')->map(fn ($i) => (string) $i)->all()) }})" @checked($entries->count() && $entries->pluck('id')->map(fn ($i) => (string) $i)->diff($selected)->isEmpty())></th>
                            <th>Data</th>
                            <th>Depósito</th>
                            <th>Cidade / Serviço</th>
                            <th>Empresa e centro de trabalho</th>
                            <th class="text-center">Ordens afetadas</th>
                            <th>Status</th>
                            <th class="pe-3 text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $meta    = $statusMeta[$entry->status] ?? [$entry->status, '#f1f5f9', '#475569'];
                                $link    = $links[$entry->id];
                                $days    = (int) $today->diffInDays($entry->scheduled_date, false);
                                $isToday = $days === 0;
                                $late    = $days < 0 && $entry->status === 'PENDING';
                                $companies = $link['rows']->groupBy('company_id');
                            @endphp
                            <tr wire:key="dep-{{ $entry->id }}" @class(['is-selected' => in_array((string) $entry->id, $selected, true) || ($isToday && $entry->status === 'PENDING')])>
                                <td class="ps-3"><input type="checkbox" class="form-check-input" value="{{ $entry->id }}" wire:model="selected" aria-label="Selecionar depósito {{ $entry->deposit }}"></td>
                                <td class="text-nowrap">
                                    <div class="ord-main">{{ $entry->scheduled_date->format('d/m/Y') }}</div>
                                    <div class="ord-sub" @if ($late) style="color:#b45309;font-weight:700;" @endif>
                                        @if ($isToday) hoje @elseif ($days > 0) em {{ $days }} dia(s) @else há {{ abs($days) }} dia(s) @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="ord-main">{{ $entry->deposit }} <span class="fw-normal text-muted" style="font-size:.78rem;">centro {{ $entry->center }}</span></div>
                                    <div class="ord-sub text-truncate" style="max-width: 240px;" title="{{ $entry->denomination }}">{{ $entry->denomination ?: '—' }}</div>
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ $entry->city ?: '—' }}</div>
                                    <div class="ord-sub">{{ $entry->service_type ?: '—' }}</div>
                                </td>
                                <td>
                                    @if ($link['rows']->isEmpty())
                                        <span class="st-chip" style="background:#fff5f5;color:#b91c1c;" title="Cadastre em Administração > Empresa > Centros e depósitos">Sem centro de trabalho cadastrado</span>
                                    @else
                                        <div class="d-flex flex-column gap-1">
                                            @foreach ($companies as $companyRows)
                                                <div class="d-flex flex-wrap align-items-center gap-1">
                                                    <span class="small fw-semibold">{{ $companyRows->first()->Company?->display_name ?? '—' }}</span>
                                                    @foreach ($companyRows->pluck('centerjob')->unique() as $code)<span class="co-code">{{ $code }}</span>@endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($entry->status === 'BLOCKED' && $entry->Signal)
                                        <strong>{{ $entry->Signal->affected_count }}</strong> <span class="small text-muted">bloqueada(s)</span>
                                    @elseif ($link['affected'] !== null)
                                        <strong>{{ $link['affected'] }}</strong> <span class="small text-muted">em tratativa</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="st-chip" style="background: {{ $meta[1] }}; color: {{ $meta[2] }};">{{ $meta[0] }}</span>@if ($entry->status === 'SKIPPED' && $entry->skip_reason)<div class="ord-sub" title="{{ $entry->skip_reason }}">{{ \Illuminate\Support\Str::limit($entry->skip_reason, 30) }}</div>@endif</td>
                                <td class="pe-3 text-end cell-visible">
                                    @if ($entry->status === 'PENDING')
                                        <button type="button" class="btn btn-sm {{ $days <= 0 ? 'btn-danger fw-semibold' : 'btn-outline-secondary' }}" wire:click="startConfirm({{ $entry->id }})" @disabled($link['rows']->isEmpty())><i class="ri-lock-line"></i> Confirmar bloqueio</button>
                                        <div class="dropdown d-inline-block">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-closure-fixed-dropdown aria-expanded="false" aria-label="Mais ações"></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button type="button" class="dropdown-item" wire:click="startSkip({{ $entry->id }})"><i class="ri-forbid-line me-2"></i>Dispensar esta data</button></li>
                                            </ul>
                                        </div>
                                    @elseif ($entry->status === 'BLOCKED')
                                        <button type="button" class="btn btn-sm btn-success fw-semibold" wire:click="startRelease({{ $entry->id }})"><i class="ri-lock-unlock-line"></i> Liberar depósito</button>
                                    @elseif ($entry->status === 'SKIPPED')
                                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="reopen({{ $entry->id }})"><i class="ri-restart-line"></i> Reabrir</button>
                                    @else
                                        <span class="small text-muted">{{ $entry->Signal?->released_at?->format('d/m/Y H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5"><i class="ri-calendar-check-line d-block" style="font-size:2rem;opacity:.5;"></i>Nenhum depósito para os filtros selecionados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($entries->hasPages())
                <div class="p-3 border-top">{{ $entries->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Importar --}}
    @if ($importModal)
        <div class="cpop-overlay" wire:click.self="closeImport" x-data @keydown.escape.window="$wire.closeImport()">
            <div class="cpop" style="max-width: 600px; --tone: #155f67;" role="dialog" aria-modal="true" aria-label="Importar calendário">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-upload-2-line"></i></span>
                    <div><h3 class="cpop-title">Importar calendário de depósitos</h3><div class="cpop-sub">Planilha de inventário da gestão de ativos (.xlsx)</div></div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="closeImport"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="cpop-hint">Colunas usadas: <strong>Centro</strong>, <strong>Depósito</strong> e <strong>Data inicial das contagens</strong> (obrigatórias), mais Cidade, Denominação, Tipo de serviço, Mês e Responsável. Importar de novo atualiza as datas ainda pendentes e não mexe em depósitos já bloqueados, liberados ou dispensados.</div>
                    <label for="calendar-file" class="cpop-label">Planilha</label>
                    <input id="calendar-file" type="file" class="form-control @error('calendarFile') is-invalid @enderror" wire:model="calendarFile" accept=".xlsx,.xls">
                    @error('calendarFile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="calendarFile" class="small text-muted mt-2"><span class="spinner-border spinner-border-sm"></span> Carregando arquivo…</div>

                    @if ($importResult)
                        <div class="mt-3 p-3 rounded" style="background:#f6fafb;border:1px solid #e3edf0;">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-success">{{ $importResult['created'] }} nova(s)</span>
                                <span class="badge bg-primary">{{ $importResult['updated'] }} atualizada(s)</span>
                                <span class="badge bg-secondary">{{ $importResult['kept'] }} mantida(s)</span>
                                <span class="badge {{ count($importResult['errors']) ? 'bg-danger' : 'bg-light text-dark border' }}">{{ count($importResult['errors']) }} erro(s)</span>
                            </div>
                            @if ($importResult['errors'])
                                <ul class="small text-danger mb-0 ps-3" style="max-height: 160px; overflow:auto;">
                                    @foreach (array_slice($importResult['errors'], 0, 30) as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="closeImport">Fechar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background:#155f67;" wire:click="import" wire:loading.attr="disabled" wire:target="import,calendarFile"><i class="ri-upload-2-line"></i> Importar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Confirmar bloqueio --}}
    @if ($confirming && $confirmInfo)
        <div class="cpop-overlay" wire:click.self="cancelConfirm" x-data @keydown.escape.window="$wire.cancelConfirm()">
            <div class="cpop" style="max-width: 620px; --tone: #c0392b;" role="dialog" aria-modal="true" aria-label="Confirmar bloqueio de depósito">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-lock-line"></i></span>
                    <div><h3 class="cpop-title">Confirmar bloqueio do depósito {{ $confirming->deposit }}</h3><div class="cpop-sub">{{ $confirming->denomination }} · contagem em {{ $confirming->scheduled_date->format('d/m/Y') }}</div></div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelConfirm"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <dl class="cpop-meta">
                        <div><dt>Centro</dt><dd>{{ $confirming->center }}</dd></div>
                        <div><dt>Cidade</dt><dd>{{ $confirming->city ?: '—' }}</dd></div>
                        <div class="col-span-2" style="grid-column: 1 / -1;"><dt>Empresa e centro de trabalho cadastrados</dt><dd>
                            @foreach ($confirmInfo['rows']->groupBy('company_id') as $rows)
                                <div>{{ $rows->first()->Company?->display_name }} — @foreach ($rows->pluck('centerjob')->unique() as $code)<span class="co-code me-1">{{ $code }}</span>@endforeach</div>
                            @endforeach
                        </dd></div>
                    </dl>
                    <div class="cpop-hint"><strong>{{ $confirmInfo['affected'] }}</strong> Ordem(ns) em tratativa desses centros de trabalho passam para <strong>Depósito Bloqueado</strong> agora. Cada uma volta ao status anterior quando o depósito for liberado.</div>
                    @if ($confirmInfo['active'])
                        <div class="alert alert-warning py-2 small mb-3">Já existe um bloqueio ativo para este depósito. Libere-o antes de confirmar de novo.</div>
                    @endif
                    <label for="confirm-reason" class="cpop-label">Motivo <span class="text-danger">*</span></label>
                    <textarea id="confirm-reason" class="form-control @error('confirmReason') is-invalid @enderror" rows="3" wire:model.defer="confirmReason" maxlength="1000"></textarea>
                    @error('confirmReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelConfirm">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background:#c0392b;" wire:click="confirmBlock" wire:loading.attr="disabled" wire:target="confirmBlock"><i class="ri-lock-line"></i> Bloquear depósito</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Ação em massa --}}
    @if ($bulkAction)
        @php
            $bm = [
                'block'   => ['Confirmar bloqueio em massa', 'ri-lock-line', '#c0392b', 'Bloquear depósitos'],
                'skip'    => ['Dispensar datas em massa', 'ri-forbid-line', '#64748b', 'Dispensar'],
                'release' => ['Liberar depósitos em massa', 'ri-lock-unlock-line', '#0f8a77', 'Liberar todos'],
                'reopen'  => ['Reabrir datas em massa', 'ri-restart-line', '#155f67', 'Reabrir'],
            ][$bulkAction];
        @endphp
        <div class="cpop-overlay" wire:click.self="cancelBulk" x-data @keydown.escape.window="$wire.cancelBulk()">
            <div class="cpop" style="max-width: 560px; --tone: {{ $bm[2] }};" role="dialog" aria-modal="true" aria-label="{{ $bm[0] }}">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="{{ $bm[1] }}"></i></span>
                    <div><h3 class="cpop-title">{{ $bm[0] }}</h3><div class="cpop-sub">{{ $bulkCount }} depósito(s) elegíveis na seleção</div></div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelBulk"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="cpop-hint">
                        @if ($bulkAction === 'block')
                            Cada depósito selecionado bloqueia, de uma vez, as Ordens em tratativa dos centros de trabalho cadastrados. Depósitos sem centro de trabalho ou já bloqueados serão recusados e listados ao final.
                        @elseif ($bulkAction === 'release')
                            As Ordens bloqueadas de cada depósito voltam ao status que tinham antes do bloqueio.
                        @elseif ($bulkAction === 'skip')
                            Os depósitos não serão bloqueados nestas datas (ex.: inventário adiado). Você pode reabri-los depois.
                        @else
                            Os depósitos dispensados voltam a Pendente.
                        @endif
                        Apenas os selecionados no status exigido são processados.
                    </div>
                    @if (in_array($bulkAction, ['block', 'release', 'skip'], true))
                        <label for="bulk-reason" class="cpop-label">{{ $bulkAction === 'skip' ? 'Motivo (opcional)' : 'Motivo' }} @if ($bulkAction !== 'skip')<span class="text-danger">*</span>@endif</label>
                        <textarea id="bulk-reason" class="form-control @error('bulkReason') is-invalid @enderror" rows="3" wire:model.defer="bulkReason" maxlength="1000"></textarea>
                        @error('bulkReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @endif
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelBulk">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background:{{ $bm[2] }};" wire:click="confirmBulk" wire:loading.attr="disabled" wire:target="confirmBulk" @disabled($bulkCount === 0)><i class="{{ $bm[1] }}"></i> {{ $bm[3] }} ({{ $bulkCount }})</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Dispensar --}}
    @if ($skipping)
        <div class="cpop-overlay" wire:click.self="cancelSkip" x-data @keydown.escape.window="$wire.cancelSkip()">
            <div class="cpop" style="max-width: 480px; --tone: #64748b;" role="dialog" aria-modal="true" aria-label="Dispensar data">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-forbid-line"></i></span>
                    <div><h3 class="cpop-title">Dispensar esta data</h3><div class="cpop-sub">Depósito {{ $skipping->deposit }} · {{ $skipping->scheduled_date->format('d/m/Y') }}</div></div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelSkip"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="cpop-hint">O depósito não será bloqueado nesta data (por exemplo, inventário adiado). Você pode reabrir depois.</div>
                    <label for="skip-reason" class="cpop-label">Motivo (opcional)</label>
                    <textarea id="skip-reason" class="form-control" rows="3" wire:model.defer="skipReason" maxlength="500"></textarea>
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelSkip">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background:#64748b;" wire:click="confirmSkip">Dispensar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Liberar --}}
    @if ($releasing)
        <div class="cpop-overlay" wire:click.self="cancelRelease" x-data @keydown.escape.window="$wire.cancelRelease()">
            <div class="cpop" style="max-width: 500px; --tone: #0f8a77;" role="dialog" aria-modal="true" aria-label="Liberar depósito">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-lock-unlock-line"></i></span>
                    <div><h3 class="cpop-title">Liberar depósito {{ $releasing->deposit }}</h3><div class="cpop-sub">{{ $releasing->denomination }}</div></div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelRelease"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="cpop-hint">As <strong>{{ $releasing->Signal?->affected_count ?? 0 }}</strong> Ordem(ns) bloqueadas voltam, de uma vez, ao status que tinham antes do bloqueio.</div>
                    <label for="rel-note" class="cpop-label">Observação (opcional)</label>
                    <textarea id="rel-note" class="form-control" rows="3" wire:model.defer="releaseNote" maxlength="1000" placeholder="Ex.: inventário concluído."></textarea>
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelRelease">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background:#0f8a77;" wire:click="confirmRelease" wire:loading.attr="disabled" wire:target="confirmRelease"><i class="ri-lock-unlock-line"></i> Liberar todas</button>
                </div>
            </div>
        </div>
    @endif

    @once
        <script>
            if (!window.__closureFixedDropdown) {
                window.__closureFixedDropdown = true;
                document.addEventListener('click', function (event) {
                    const toggle = event.target.closest('[data-closure-fixed-dropdown]');
                    if (toggle && window.bootstrap && !window.bootstrap.Dropdown.getInstance(toggle)) {
                        window.bootstrap.Dropdown.getOrCreateInstance(toggle, { popperConfig: (defaults) => ({ ...defaults, strategy: 'fixed' }) });
                    }
                }, true);
            }
        </script>
    @endonce
</div>
