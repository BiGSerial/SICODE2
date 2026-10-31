@php
    $money = fn ($v) => $v === null ? '—' : 'R$ ' . number_format((float) $v, 2, ',', '.');
    $dt    = fn ($v, $f = 'd/m/Y H:i') => $v ? \Carbon\Carbon::parse($v)->format($f) : '—';
@endphp

<div>
    <div wire:ignore.self class="modal fade" id="modal_inform_detail" tabindex="-1" aria-labelledby="modalInformDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content inform-modal">
                <div class="modal-header inform-header">
                    <div>
                        <h4 class="my-0 fw-bold" id="modalInformDetailLabel">
                            {{ $d ? mb_strtoupper($d['title']) : 'INFORME' }}
                            @if ($d)
                                <span class="badge {{ $d['status']['class'] }} ms-2 align-middle">{{ mb_strtoupper($d['status']['label']) }}</span>
                            @endif
                        </h4>
                        @if ($d)
                            <small class="d-block mt-1">
                                Nota/OV {{ $d['note']?->note }} · {{ mb_strtoupper($d['note']?->lexp ?? '') }} · {{ mb_strtoupper($d['company'] ?? '—') }}
                                @foreach ($d['scopes'] as $scope)
                                    <span class="badge {{ $scope['class'] }} ms-1">{{ $scope['label'] }}</span>
                                @endforeach
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                @if ($d)
                    <div class="inform-tabs" role="tablist">
                        @foreach (\App\Http\Livewire\Engineers\InformDetail::TABS as $key => $label)
                            <button type="button" role="tab" class="{{ $tab === $key ? 'is-active' : '' }}" wire:click="setTab('{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                                {{ $label }}
                                @if ($key === 'evidence')
                                    <span class="badge text-bg-light">{{ collect($d['evidence'])->sum('count') }}</span>
                                @elseif ($key === 'history' && $d['rejections']->isNotEmpty())
                                    <span class="badge text-bg-danger">{{ $d['rejections']->count() }}</span>
                                @elseif ($key === 'budget' && $d['pep']['shortfall'])
                                    <span class="badge text-bg-danger">!</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="modal-body">
                    @if ($d)
                        <div wire:loading.class="opacity-50" wire:target="setTab">
                        <x-inform.kpis :items="$d['kpis']" />

                        {{-- ============ VISÃO GERAL ============ --}}
                        @if ($tab === 'overview')
                            @if ($d['rejected_now'] && $d['rejections']->isNotEmpty())
                                @php $last = $d['rejections']->last(); @endphp
                                <x-inform.card title="Rejeitado" tone="danger">
                                    <div class="inform-note is-danger">
                                        <strong>{{ $last['category'] ?: 'Sem categoria' }}</strong>
                                        <div class="inform-text">{!! nl2br(e($last['text'] ?: 'Motivo não informado.')) !!}</div>
                                        <small>{{ $last['by'] ?? '—' }} · {{ $dt($last['at']) }}</small>
                                    </div>
                                </x-inform.card>
                            @endif

                            @if ($d['ads'])
                                @php $ads = $d['ads']; @endphp
                                <x-inform.card title="ADS e prazo tácito">
                                    <x-inform.facts :items="[
                                        ['label' => 'Data do informe', 'value' => $dt($ads['informed_at'], 'd/m/Y H:i:s')],
                                        ['label' => 'ADS chegou?', 'value' => $ads['arrived'] ? 'Sim' : 'Não'],
                                        ['label' => 'Quando chegou', 'value' => $ads['arrived'] ? $dt($ads['arrived_at']) : 'Ainda não chegou'],
                                        ['label' => 'Prazo de entrega', 'value' => $dt($ads['due_at'], 'd/m/Y')],
                                        ['label' => 'É tácita?', 'value' => $ads['tacit'] ? 'Sim, ADS tácita' : 'Não'],
                                        ['label' => 'Atraso', 'value' => $ads['late_days'] > 0 ? $ads['late_days'] . ' dia(s) útil(eis) · multa ' . number_format($ads['penalty'], 2, ',', '.') . '%' : 'Sem atraso'],
                                        ['label' => 'Valor', 'value' => $money($ads['amount'])],
                                        ['label' => 'Contrato / centro', 'value' => trim(($ads['contract'] ?? '') . ' ' . ($ads['center'] ?? ''))],
                                        ['label' => 'Aceite do informe', 'value' => $ads['acceptance'] ? ($ads['acceptance']['name'] . ' em ' . $dt($ads['acceptance']['at'])) : null],
                                    ]" />
                                    @if (filled($ads['obs']))
                                        <div class="inform-note mt-3"><strong>Observação da ADS</strong><div class="inform-text">{!! nl2br(e($ads['obs'])) !!}</div></div>
                                    @endif
                                </x-inform.card>
                            @endif

                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <x-inform.card title="Informações" class="h-100">
                                        <x-inform.facts :items="$d['facts']" />
                                    </x-inform.card>
                                </div>
                                <div class="col-lg-6">
                                    <x-inform.card title="Linha do tempo" class="h-100">
                                        <x-inform.timeline :steps="$d['timeline']" />
                                    </x-inform.card>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <x-inform.card title="Observação da empreiteira" class="h-100">
                                        @if (filled(trim((string) $d['observation']['contractor'])))
                                            <div class="inform-text">{!! nl2br(e(strip_tags($d['observation']['contractor']))) !!}</div>
                                            <small class="text-muted d-block mt-2">Responsável: {{ $d['observation']['by'] ?: '—' }}</small>
                                        @else
                                            <x-inform.empty text="Nenhuma observação" icon="ri-chat-off-line" />
                                        @endif
                                        @if (filled(trim((string) $d['observation']['damage'])))
                                            <hr>
                                            <strong class="small text-uppercase text-muted">Informação de danos</strong>
                                            <div class="inform-text">{!! nl2br(e(strip_tags($d['observation']['damage']))) !!}</div>
                                        @endif
                                    </x-inform.card>
                                </div>
                                <div class="col-lg-6">
                                    <x-inform.card title="Parecer da engenharia" class="h-100">
                                        @if (filled(trim((string) ($d['observation']['engineer'] ?? ''))))
                                            <div class="inform-text">{!! nl2br(e($d['observation']['engineer'])) !!}</div>
                                            <small class="text-muted d-block mt-2">Engenheiro: {{ $d['observation']['engineer_by'] ?: '—' }}</small>
                                        @else
                                            <x-inform.empty text="Nenhum parecer informado" icon="ri-file-list-3-line" />
                                        @endif
                                    </x-inform.card>
                                </div>
                            </div>

                            @if ($d['equipment']->isNotEmpty() || $d['meeters']->isNotEmpty())
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <x-inform.card title="Equipamentos" :count="$d['equipment']->count()" class="h-100">
                                            @forelse ($d['equipment'] as $equip)
                                                <div class="inform-note">
                                                    <strong>{{ $equip->type }} · {{ $equip->patrimony }}</strong>
                                                    <small>{{ $equip->installed ? 'Instalado' : 'Retirado' }} · fases {{ $equip->fases ?: '—' }} · poste {{ $equip->pole ?: '—' }}</small>
                                                </div>
                                            @empty
                                                <x-inform.empty text="Nenhum equipamento informado" />
                                            @endforelse
                                        </x-inform.card>
                                    </div>
                                    <div class="col-lg-6">
                                        <x-inform.card title="Medidores" :count="$d['meeters']->count()" class="h-100">
                                            @forelse ($d['meeters'] as $meeter)
                                                <div class="inform-note"><strong>{{ $meeter->number }}</strong><small>Borne {{ $meeter->borne ?: '—' }} · fases {{ $meeter->fases ?: '—' }}</small></div>
                                            @empty
                                                <x-inform.empty text="Nenhum medidor informado" />
                                            @endforelse
                                        </x-inform.card>
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- ============ EVIDÊNCIAS ============ --}}
                        @if ($tab === 'evidence')
                            @foreach ($d['evidence'] as $group)
                                @continue($group['key'] === 'other' && $group['count'] === 0)
                                <x-inform.card :title="'Evidências · ' . $group['label']" :count="$group['count'] . ' arquivo(s)'">
                                    @if ($group['count'])
                                        <x-inform.files :files="$group['files']" />
                                    @else
                                        <x-inform.empty :icon="$group['icon']" :text="'Nenhuma evidência de ' . mb_strtolower($group['label'])" />
                                    @endif
                                </x-inform.card>
                            @endforeach

                            @if ($d['ads'] && $d['ads']['files']->isNotEmpty())
                                <x-inform.card title="Arquivos da ADS" :count="$d['ads']['files']->count() . ' arquivo(s)'">
                                    <x-inform.files :files="$d['ads']['files']" />
                                </x-inform.card>
                            @endif
                        @endif

                        {{-- ============ PEP E OPERAÇÕES ============ --}}
                        @if ($tab === 'budget')
                            @php $pep = $d['pep']; @endphp
                            @if ($pep['shortfall'])
                                <div class="alert alert-danger">
                                    <i class="ri-error-warning-line me-1"></i>
                                    <strong>{{ $pep['shortfall']['reason'] === 'no_balance' ? 'Sem saldo no PEP.' : 'ADS acima do saldo somado dos PEPs.' }}</strong>
                                    Valor do informe: {{ $money($pep['amount']) }}.
                                </div>
                            @endif

                            <x-inform.card title="PEP e saldo" :count="$pep['peps']->count() . ' PEP(s)'">
                                @if ($pep['peps']->isEmpty())
                                    <x-inform.empty text="Nenhum PEP nas ordens deste informe" icon="ri-wallet-3-line" />
                                @else
                                    <div class="table-responsive">
                                        <table class="inform-table">
                                            <thead><tr><th>PEP</th><th>Ordem</th><th class="text-end">Orçamento</th><th class="text-end">Realizado</th><th class="text-end">Compromisso</th><th class="text-end">Disponível</th><th>Situação</th></tr></thead>
                                            <tbody>
                                                @foreach ($pep['peps'] as $p)
                                                    <tr>
                                                        <td><strong>{{ $p['code'] }}</strong>@if ($p['region']) <small class="text-muted">· {{ $p['region'] }}</small>@endif</td>
                                                        <td>{{ $p['order'] }}</td>
                                                        <td class="text-end">{{ $money($p['budget']) }}</td>
                                                        <td class="text-end">{{ $money($p['real']) }}</td>
                                                        <td class="text-end">{{ $money($p['commitment']) }}</td>
                                                        <td class="text-end"><strong class="{{ $p['synced'] && (float) $p['available'] <= 0 && $p['carries'] ? 'text-danger' : '' }}">{{ $money($p['available']) }}</strong></td>
                                                        <td>
                                                            @if (!$p['synced']) <span class="badge text-bg-secondary">Não sincronizado</span>
                                                            @elseif (!$p['carries']) <span class="badge text-bg-light border">Não controla orçamento</span>
                                                            @elseif ($p['closed']) <span class="badge text-bg-secondary">Ordem encerrada</span>
                                                            @elseif ((float) $p['available'] <= 0) <span class="badge text-bg-danger">Sem saldo</span>
                                                            @else <span class="badge text-bg-success">Com saldo</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                                <small class="text-muted d-block mt-2">Saldo lido da última sincronização do PEP. Só PEPs terminados em -2 controlam o orçamento do informe.</small>
                            </x-inform.card>

                            @if ($pep['alerts']->isNotEmpty())
                                <x-inform.card title="Alertas de saldo deste informe" :count="$pep['alerts']->count()">
                                    @foreach ($pep['alerts'] as $alert)
                                        <div class="inform-note {{ $alert->status === 'open' ? 'is-danger' : '' }}">
                                            <strong>{{ $alert->reasonLabel() }}</strong>
                                            <span class="badge {{ $alert->status === 'open' ? 'text-bg-danger' : 'text-bg-success' }} ms-1">{{ $alert->status === 'open' ? 'Pendente' : 'Tratado' }}</span>
                                            <small>Entrou na lista em {{ $dt($alert->created_at) }}
                                                @if ($alert->covered_since) · saldo cobriu desde {{ $dt($alert->covered_since) }} @endif
                                                @if ($alert->balances) · saldo na entrada: @foreach ($alert->balances as $code => $bal) {{ $code }} {{ $money($bal) }}@if (!$loop->last), @endif @endforeach @endif
                                            </small>
                                            @if ($alert->status === 'handled')
                                                <small>Tratado em {{ $dt($alert->handled_at) }} por {{ $alert->handled_via === 'auto' ? 'saída automática' : ($alert->Handler?->name ?? '—') }}@if ($alert->handled_notes) · {{ $alert->handled_notes }}@endif</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </x-inform.card>
                            @endif

                            <x-inform.card title="Operações das ordens">
                                @forelse ($d['operations'] as $order)
                                    <div class="d-flex justify-content-between align-items-center mb-2 {{ $loop->first ? '' : 'mt-3' }}">
                                        <strong>Ordem {{ $order['order'] }} <small class="text-muted">· {{ $order['status'] ?: 'sem status' }}</small></strong>
                                        <span class="badge {{ $order['confirmed'] ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $order['confirmed'] }} de {{ $order['ops']->count() }} confirmada(s)</span>
                                    </div>
                                    @if ($order['ops']->isEmpty())
                                        <x-inform.empty text="Nenhuma operação sincronizada" icon="ri-list-check-2" />
                                    @else
                                        <div class="table-responsive">
                                            <table class="inform-table">
                                                <thead><tr><th>Operação</th><th>Descrição</th><th>Status</th><th>Confirmada em</th></tr></thead>
                                                <tbody>
                                                    @foreach ($order['ops'] as $op)
                                                        <tr class="{{ $op['confirmed'] ? 'is-confirmed' : '' }}">
                                                            <td><strong>{{ $op['code'] }}</strong></td>
                                                            <td>{{ $op['desc'] }}</td>
                                                            <td><span class="badge {{ $op['confirmed'] ? 'text-bg-success' : 'text-bg-light border' }}">{{ $op['status'] ?: '—' }}</span></td>
                                                            <td>{{ $op['confirmed'] ? $dt($op['at'], 'd/m/Y') : '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                @empty
                                    <x-inform.empty text="Nenhuma ordem vinculada" icon="ri-list-check-2" />
                                @endforelse
                            </x-inform.card>
                        @endif

                        {{-- ============ FLUXO E REJEIÇÕES ============ --}}
                        @if ($tab === 'history')
                            <x-inform.card title="Motivos de rejeição" :count="$d['rejections']->count()" :tone="$d['rejections']->isNotEmpty() ? 'danger' : null">
                                @forelse ($d['rejections']->reverse() as $r)
                                    <div class="inform-note is-danger">
                                        <strong>{{ $r['category'] ?: 'Sem categoria' }}@if ($r['service']) <small class="d-inline text-muted">· {{ $r['service'] }}</small>@endif</strong>
                                        <div class="inform-text">{!! nl2br(e($r['text'] ?: 'Motivo não informado.')) !!}</div>
                                        <small>{{ $r['by'] ?? '—' }} · {{ $dt($r['at']) }}</small>
                                    </div>
                                @empty
                                    <x-inform.empty text="Este informe nunca foi rejeitado" icon="ri-checkbox-circle-line" />
                                @endforelse
                            </x-inform.card>

                            <x-inform.card title="Atividades associadas" :count="$d['activities']->count()">
                                @if ($d['activities']->isEmpty())
                                    <x-inform.empty text="Nenhuma atividade vinculada ao informe" icon="ri-task-line" />
                                @else
                                    <div class="table-responsive">
                                        <table class="inform-table">
                                            <thead><tr><th>Etapa</th><th>Atividade</th><th>Responsável</th><th>Atribuído em</th><th>Concluído em</th><th>Origem</th></tr></thead>
                                            <tbody>
                                                @foreach ($d['activities'] as $a)
                                                    <tr>
                                                        <td>@if ($a['stage']) <span class="badge {{ $a['stage_class'] }}">{{ $a['stage'] }}</span> @else — @endif</td>
                                                        <td>#{{ $a['production'] }} {{ $a['service'] }}</td>
                                                        <td>{{ $a['responsible'] ?: '—' }}</td>
                                                        <td>{{ $dt($a['assigned_at']) }}</td>
                                                        <td>{{ $dt($a['completed_at']) }}</td>
                                                        <td>
                                                            {{ $a['origin'] ?: '—' }}
                                                            @if ($a['current'] === false) <span class="badge text-bg-secondary">Substituída</span>@endif
                                                            @if ($a['reversed_at']) <small class="d-block text-muted">Revertida {{ $dt($a['reversed_at']) }}{{ $a['reverse_reason'] ? ': ' . $a['reverse_reason'] : '' }}</small>@endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </x-inform.card>

                            <x-inform.card title="Linha do tempo completa">
                                <x-inform.timeline :steps="$d['timeline']" />
                            </x-inform.card>
                        @endif
                        </div>
                    @else
                        <x-inform.empty text="Selecione um informe" icon="ri-file-search-line" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
