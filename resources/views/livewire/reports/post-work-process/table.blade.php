        <div class="ppr-card overflow-hidden mb-2">
            <div class="table-responsive">
                <table class="table ppr-table align-middle">
                    <colgroup>
                        <col style="width:15%"><col style="width:9%"><col style="width:14%"><col style="width:9%">
                        <col style="width:9%"><col style="width:9%"><col style="width:9%"><col style="width:9%">
                        <col style="width:12%"><col style="width:5%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Informe</th>
                            <th>Nota</th>
                            <th>Empresa do informe</th>
                            <th>Data do informe</th>
                            @foreach ($stageDefs as [$label, $limit])
                                <th class="text-center">{{ $label }}<small>prazo {{ $limit }}d</small></th>
                            @endforeach
                            <th>Fiscalização / Pagamento</th>
                            <th class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $key = $row['type'] . '-' . $row['id']; @endphp
                            <tr wire:key="ppr-{{ $key }}" class="ppr-row-{{ $row['overall'] }}">
                                <td>
                                    <span class="ppr-type ppr-type-{{ $row['type'] }}">{{ $row['type_label'] }}</span>
                                    <span class="ppr-id">#{{ $row['id'] }}</span>
                                    @foreach ($row['scopes'] as $scope)
                                        <span class="badge {{ $scope['class'] }} ms-1">{{ $scope['label'] }}</span>
                                    @endforeach
                                    @if (!empty($row['note_d5']))
                                        <div class="ppr-orders">D5 nº {{ $row['note_d5'] }}</div>
                                    @endif
                                    @if ($row['orders'])
                                        <div class="ppr-orders" title="{{ implode(', ', $row['orders']) }}">Ordens: {{ implode(', ', $row['orders']) }}</div>
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $row['note'] ?? '—' }}</td>
                                <td>{{ $row['company'] ?? '—' }}</td>
                                <td>{{ $fmt($row['start_at']) }}</td>
                                @foreach ($stageDefs as $stageKey => [$label, $limit])
                                    @php
                                        $st = $row['stages'][$stageKey];
                                        $pct = $st['days'] === null ? 0 : min(100, round($st['days'] / max(1, $st['limit']) * 100));
                                    @endphp
                                    <td>
                                        <div class="ppr-stage ppr-{{ $st['status'] }}"
                                            title="{{ ['on_time' => 'Concluída no prazo', 'late' => 'Concluída fora do prazo', 'open_late' => 'Em aberto e estourada', 'open' => 'Em aberto, dentro do prazo', 'pending' => 'Sem dados'][$st['status']] }}">
                                            @if ($st['days'] === null)
                                                <span class="days">—</span>
                                            @else
                                                <span class="days">{{ $st['days'] }}d</span>
                                                <span class="lim">/ {{ $st['limit'] }}</span>
                                                @if ($st['late_days'] > 0)
                                                    <div class="ppr-late-tag">+{{ $st['late_days'] }}d</div>
                                                @endif
                                                <div class="ppr-bar"><span style="width:{{ $pct }}%"></span></div>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                                <td>
                                    <div class="ppr-user">{{ $row['fiscal']['user'] ?? '—' }}<small>{{ $row['fiscal']['company'] ?? '' }}</small></div>
                                    <div class="ppr-user mt-1">{{ $row['payment']['user'] ?? '—' }}<small>{{ $row['payment']['company'] ?? '' }}</small></div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                                        data-bs-target="#ppr-detail-{{ $key }}" title="Ver datas e responsáveis">
                                        <i class="ri-arrow-down-s-line"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr wire:key="ppr-detail-{{ $key }}" class="collapse" id="ppr-detail-{{ $key }}">
                                <td colspan="10" class="ppr-detail p-3">
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <h6>Fiscalização</h6>
                                            <dl>
                                                <dt>Despacho</dt><dd>{{ $fmt($row['fiscal']['dispatch_at'] ?? null) }}</dd>
                                                <dt>Atribuição</dt><dd>{{ $fmt($row['fiscal']['att_at'] ?? null) }}</dd>
                                                <dt>Conclusão</dt><dd>{{ $fmt($row['fiscal']['completed_at'] ?? null) }}</dd>
                                                <dt>Usuário</dt><dd>{{ $row['fiscal']['user'] ?? '—' }} <span class="text-muted">{{ $row['fiscal']['company'] ?? '' }}</span></dd>
                                            </dl>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Medição / Pagamento</h6>
                                            <dl>
                                                <dt>Despacho</dt><dd>{{ $fmt($row['payment']['dispatch_at'] ?? null) }}</dd>
                                                <dt>Atribuição</dt><dd>{{ $fmt($row['payment']['att_at'] ?? null) }}</dd>
                                                <dt>Conclusão</dt><dd>{{ $fmt($row['payment']['completed_at'] ?? null) }}</dd>
                                                <dt>Usuário</dt><dd>{{ $row['payment']['user'] ?? '—' }} <span class="text-muted">{{ $row['payment']['company'] ?? '' }}</span></dd>
                                            </dl>
                                        </div>
                                        <div class="col-md-4">
                                            <h6>Marcos do informe</h6>
                                            <dl>
                                                <dt>Informante</dt><dd>{{ $row['informer'] ?? '—' }}</dd>
                                                @if ($row['type'] === 'partial')
                                                    <dt>Aprovação eng.</dt><dd>{{ $fmt($row['decision_at'] ?? null) }} <span class="text-muted">{{ $row['engineer'] ?? '' }}</span></dd>
                                                @endif
                                                @if ($row['type'] !== 'final')
                                                    <dt>Supervisão</dt><dd>{{ $fmt($row['supervision_at'] ?? null) }} <span class="text-muted">{{ $row['supervisor'] ?? '' }}</span></dd>
                                                    <dt>Pago/medido</dt><dd>{{ $fmt($row['payment_at'] ?? null) }} <span class="text-muted">{{ $row['payer'] ?? '' }}</span></dd>
                                                @endif
                                                @if ($row['type'] === 'final')
                                                    <dt>ADS</dt>
                                                    <dd>
                                                        @if ($row['has_ads'] ?? false)
                                                            {{ $fmt($row['ads_at'] ?? null) }} · R$ {{ number_format((float) ($row['ads_amount'] ?? 0), 2, ',', '.') }}
                                                        @else
                                                            sem ADS
                                                        @endif
                                                    </dd>
                                                @endif
                                                @if ($row['late_stages'])
                                                    <dt>Estourou em</dt><dd class="text-danger fw-semibold">{{ implode(', ', $row['late_stages']) }}</dd>
                                                @endif
                                            </dl>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-5">Nenhum informe encontrado para os filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

