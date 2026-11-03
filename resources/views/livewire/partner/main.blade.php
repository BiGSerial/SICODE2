@php
    use App\Support\Ops\{ControlAge, PrazoReal};
    use Carbon\Carbon;

    $pct   = fn ($part, $whole) => $whole > 0 ? round(($part / $whole) * 100) : null;
    $tone  = fn ($value, $good, $warn, $higherIsBetter = true) => $value === null
        ? ''
        : ($higherIsBetter
            ? ($value >= $good ? 'is-good' : ($value >= $warn ? 'is-warn' : 'is-danger'))
            : ($value <= $good ? 'is-good' : ($value <= $warn ? 'is-warn' : 'is-danger')));
    $money = fn ($value) => 'R$ ' . number_format((float) $value, 2, ',', '.');
    $int   = fn ($value) => number_format((int) $value, 0, ',', '.');

    $exec        = $executive ?? [];
    $lastIndex   = (int) ($exec['last_index'] ?? 11);
    $monthLabels = array_slice($exec['labels'] ?? [], 0, $lastIndex + 1);
    $upToNow     = fn (array $values) => array_slice(array_values($values), 0, $lastIndex + 1);
    $bestOf      = function (array $values) use ($upToNow, $monthLabels) {
        $values = $upToNow($values);
        $max    = $values ? max($values) : 0;

        return $max > 0 ? ['label' => $monthLabels[array_search($max, $values)] ?? '', 'value' => $max] : null;
    };

    $actionKeys   = ['work_without_ads_overdue', 'work_without_ads_due_soon', 'viability_due_soon', 'viability_rejected_waiting', 'd5_returned', 'informs_rejected'];
    $actionTotal  = collect($actionKeys)->sum(fn ($key) => (int) ($kpis[$key] ?? 0));
    $adsRate      = $pct($workReportKpis['ads_delivered_total'] ?? 0, $workReportKpis['valid_informed_total'] ?? 0);
    $rejectRate   = $pct($workReportKpis['canceled_rejected_total'] ?? 0, $workReportKpis['informed_total'] ?? 0);
    $partialRate  = $pct($workReportKpis['partials_completed_total'] ?? 0, $workReportKpis['partials_total'] ?? 0);
    $dueAdsTotal  = (int) ($kpis['work_without_ads_due_soon'] ?? 0);
    $dueViabTotal = (int) ($kpis['viability_due_soon'] ?? 0);
    $agingTotal   = (int) collect($exec['aging'] ?? [])->sum('total');
    $dueTotal     = (int) collect($exec['due_by_day'] ?? [])->sum(fn ($d) => $d['ads'] + $d['viability']);
    $informsTotal = (int) (array_sum($exec['informs_valid'] ?? []) + array_sum($exec['informs_invalid'] ?? []));
    $selectedCompany = (string) ($partnerCompanyFilter ?? '') !== ''
        ? $this->partnerCompanyFilterOptions()->firstWhere('id', $partnerCompanyFilter)
        : null;
    $legalNotices = app(\App\Services\Partner\DashboardLegalNotices::class);
    $scope        = $selectedCompany ? ' · ' . $selectedCompany->name : '';
@endphp

<div class="closure-dash my-panel ops-list" wire:key="partner-dashboard">
    <x-show-loading />

    {{-- Hero --}}
    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Portal da parceira</div>
            <h1 class="mp-hero-title">Dashboard da Parceira</h1>
            <p class="mp-hero-sub">Visão operacional de viabilidade, ADS, D5, rejeições e pendências: o que vence, o que está atrasado e o que foi devolvido.</p>
        </div>
        <div class="mp-hero-actions d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportSummaryXlsx" wire:loading.attr="disabled" wire:target="exportSummaryXlsx">
                <i class="ri-file-excel-2-line"></i> Exportar resumo
            </button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click="exportPendenciesCsv" wire:loading.attr="disabled" wire:target="exportPendenciesCsv">
                <i class="ri-download-2-line"></i> Exportar pendências
            </button>
        </div>
    </header>

    {{-- Filtros --}}
    <section class="mp-filters filter-bar" aria-label="Filtros do painel">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-xl-2">
                <label for="pd-month" class="form-label small mb-1">Mês referência</label>
                <input type="month" id="pd-month" class="form-control form-control-sm" wire:model="month" max="{{ now()->format('Y-m') }}">
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label for="pd-dt-ini" class="form-label small mb-1">Data inicial</label>
                <input type="date" id="pd-dt-ini" class="form-control form-control-sm" wire:model="dt_ini">
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <label for="pd-dt-fim" class="form-label small mb-1">Data final</label>
                <input type="date" id="pd-dt-fim" class="form-control form-control-sm" wire:model="dt_fim">
            </div>
            @if ($selectedCompany)
                <div class="col-12 col-xl-6">
                    <span class="mp-chip">Somente {{ $selectedCompany->name }}
                        <button type="button" class="btn-close btn-close-sm ms-2" aria-label="Ver todas as empresas" wire:click="$set('partnerCompanyFilter', '')"></button>
                    </span>
                </div>
            @endif
        </div>
        <div class="mp-period-note">
            Período exibido: <strong>{{ Carbon::parse($dt_ini)->format('d/m/Y') }}</strong> até <strong>{{ Carbon::parse($dt_fim)->format('d/m/Y') }}</strong>.
            Vale para os indicadores de informes e os motivos de rejeição; as pendências e os prazos mostram a situação de hoje.
        </div>
    </section>

    <div wire:loading.class="is-loading-soft" wire:target="month,dt_ini,dt_fim,partnerCompanyFilter">

        {{-- Painel executivo --}}
        <h2 class="mp-section-title">Painel executivo{{ $scope }}</h2>

        <div class="row g-3 mb-3">
            @php
                $bestF = $bestOf($exec['final_amount'] ?? []);
                $bestP = $bestOf($exec['partial_amount'] ?? []);
            @endphp
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card class="h-100" tone="blue" icon="ri-money-dollar-circle-line" label="ADS final solicitado" :value="$money($workReportKpis['ads_amount_total'] ?? 0)"
                    :note="'Período: ' . ($workReportKpis['ads_delivered_total'] ?? 0) . ' ADS entregues' . ($bestF ? ' · Melhor mês: ' . $bestF['label'] . ' ' . $money($bestF['value']) : '')"
                    :values="$upToNow($exec['final_amount'] ?? [])" :labels="$monthLabels" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card class="h-100" tone="green" icon="ri-percent-line" label="ADS parcial solicitado" :value="$money($workReportKpis['partials_amount_total'] ?? 0)"
                    :note="'Período: ' . ($workReportKpis['partials_completed_total'] ?? 0) . ' de ' . ($workReportKpis['partials_total'] ?? 0) . ' finalizadas' . ($bestP ? ' · Melhor mês: ' . $bestP['label'] . ' ' . $money($bestP['value']) : '')"
                    :values="$upToNow($exec['partial_amount'] ?? [])" :labels="$monthLabels" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card class="h-100" tone="red" icon="ri-alarm-warning-line" label="Entregas de ADS em atraso" :value="$int($exec['overdue_total'] ?? ($kpis['work_without_ads_overdue'] ?? 0))"
                    :note="(($exec['overdue_total'] ?? 0) - ($exec['overdue_old'] ?? 0)) . ' até 30 dias de atraso · ' . ($exec['overdue_old'] ?? 0) . ' acima de 30'"
                    :values="collect($exec['aging'] ?? [])->pluck('total')->all()" :labels="collect($exec['aging'] ?? [])->pluck('label')->all()" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card class="h-100" tone="amber" icon="ri-time-line" :label="'A vencer em ' . $daysAhead . ' dias'" :value="$int($dueAdsTotal + $dueViabTotal)"
                    :note="$dueAdsTotal . ' entregas de ADS · ' . $dueViabTotal . ' viabilidades'"
                    :values="collect($exec['due_by_day'] ?? [])->map(fn ($d) => $d['ads'] + $d['viability'])->all()" :labels="collect($exec['due_by_day'] ?? [])->pluck('label')->all()" />
            </div>
        </div>

        <section class="mp-panel mb-3" aria-label="Aviso sobre os valores">
            <div class="mp-panel-body small">
                <i class="ri-information-line text-warning" aria-hidden="true"></i>
                {!! $legalNotices->valuesDisclaimerText() !!}
            </div>
        </section>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Atraso de ADS por tempo</h3>
                            <div class="mp-panel-sub">Dias após o prazo de entrega ({{ \App\Support\SicodeRules::adsDeliveryDeadlineDays() }} dias {{ \App\Support\SicodeRules::adsDeliveryDeadlineUsesBusinessDays() ? 'úteis' : 'corridos' }} da conclusão informada)</div>
                        </div>
                        <span class="mp-pill">{{ $int($agingTotal) }} em atraso</span>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="aging" data-config="{{ json_encode($charts['aging']) }}" role="img" aria-label="Barras horizontais: ADS em atraso por faixa de dias"></canvas>
                        </div>
                        @if ($agingTotal === 0)
                            <div class="mp-chart-empty">Nenhuma entrega de ADS em atraso</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Vencimentos dos próximos dias</h3>
                            <div class="mp-panel-sub">Entregas de ADS e viabilidades que vencem nos próximos {{ $daysAhead }} dias</div>
                        </div>
                        <span class="mp-pill">{{ $int($dueTotal) }} a vencer</span>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="due" data-config="{{ json_encode($charts['due']) }}" role="img" aria-label="Barras empilhadas: vencimentos de ADS e viabilidades por dia"></canvas>
                        </div>
                        @if ($dueTotal === 0)
                            <div class="mp-chart-empty">Nada vence nos próximos dias</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Informes finais por mês ({{ Carbon::parse($dt_fim)->year }})</h3>
                            <div class="mp-panel-sub">Válidos e cancelados/rejeitados, com o total de cada mês</div>
                        </div>
                        <span class="mp-pill">{{ $int($informsTotal) }} no ano</span>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="informs" data-config="{{ json_encode($charts['informs']) }}" role="img" aria-label="Barras empilhadas: informes finais válidos e cancelados ou rejeitados por mês"></canvas>
                        </div>
                        @if ($informsTotal === 0)
                            <div class="mp-chart-empty">Nenhum informe final neste ano</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        {{-- Resumo executivo --}}
        <h2 class="mp-section-title">Resumo executivo{{ $selectedCompany ? $scope : (count($companyBreakdown) ? ' · total consolidado' : '') }}</h2>
        <section class="mp-panel mb-3">
            <div class="mp-stat-grid">
                <div class="mp-stat {{ $actionTotal === 0 ? 'is-good' : ($actionTotal <= 10 ? 'is-warn' : 'is-danger') }}">
                    <span>Exigem ação agora</span>
                    <strong>{{ $int($actionTotal) }}</strong>
                    <small>{{ $kpis['work_without_ads_overdue'] ?? 0 }} ADS em atraso · {{ $dueAdsTotal + $dueViabTotal }} a vencer · {{ ($kpis['informs_rejected'] ?? 0) + ($kpis['viability_rejected_waiting'] ?? 0) + ($kpis['d5_returned'] ?? 0) }} devolvidos</small>
                </div>
                <div class="mp-stat {{ $tone($adsRate, 90, 70) }}">
                    <span>Entrega de ADS no período</span>
                    <strong>{{ $adsRate === null ? '—' : $adsRate . '%' }}</strong>
                    <small>{{ $workReportKpis['ads_delivered_total'] ?? 0 }} de {{ $workReportKpis['valid_informed_total'] ?? 0 }} informes válidos</small>
                </div>
                <div class="mp-stat {{ $tone($rejectRate, 5, 15, false) }}">
                    <span>Rejeição/cancelamento de informes</span>
                    <strong>{{ $rejectRate === null ? '—' : $rejectRate . '%' }}</strong>
                    <small>{{ $workReportKpis['canceled_rejected_total'] ?? 0 }} de {{ $workReportKpis['informed_total'] ?? 0 }} informes do período</small>
                </div>
                <div class="mp-stat {{ $tone($partialRate, 70, 40) }}">
                    <span>Parciais finalizadas</span>
                    <strong>{{ $partialRate === null ? '—' : $partialRate . '%' }}</strong>
                    <small>{{ $workReportKpis['partials_completed_total'] ?? 0 }} de {{ $workReportKpis['partials_total'] ?? 0 }} · {{ $money($workReportKpis['partials_amount_total'] ?? 0) }} pagos</small>
                </div>
            </div>
        </section>

        @if (count($companyBreakdown))
            @php
                $rows  = collect($companyBreakdown)->map(function ($row) use ($pct, $actionKeys) {
                    $row['action']      = collect($actionKeys)->sum(fn ($key) => (int) ($row['kpis'][$key] ?? 0));
                    $row['ads_rate']    = $pct($row['period']['ads_delivered_total'] ?? 0, $row['period']['valid_informed_total'] ?? 0);
                    $row['reject_rate'] = $pct($row['period']['canceled_rejected_total'] ?? 0, $row['period']['informed_total'] ?? 0);

                    return $row;
                });
                $worst = $rows->sortByDesc('action')->first();
                $sum   = fn ($group, $key) => $rows->sum(fn ($row) => (float) ($row[$group][$key] ?? 0));
                $cell  = fn ($value, $class = 'text-danger fw-bold') => $value > 0 ? $class : 'text-muted';
            @endphp
            <section class="mp-panel mb-3">
                <div class="mp-panel-head">
                    <div>
                        <h3 class="mp-panel-title">Desempenho por empresa / filial</h3>
                        <div class="mp-panel-sub">Período e pendências de cada empresa, com o total consolidado no rodapé. “Detalhar” abre o painel só daquela empresa.</div>
                    </div>
                    @if ($worst && $worst['action'] > 0)
                        <span class="mp-pill">Maior atenção: {{ $worst['name'] }} ({{ $worst['action'] }} itens)</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 mp-table">
                        <colgroup>
                            <col style="width: 17%"><col style="width: 6%"><col style="width: 7%"><col style="width: 9%"><col style="width: 9%">
                            <col style="width: 6%"><col style="width: 9%"><col style="width: 5%"><col style="width: 5%"><col style="width: 5%">
                            <col style="width: 5%"><col style="width: 5%"><col style="width: 5%"><col style="width: 7%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th rowspan="2">Empresa</th>
                                <th colspan="6" class="text-center">Período</th>
                                <th colspan="7" class="text-center">Pendências atuais</th>
                            </tr>
                            <tr>
                                <th class="text-end">Obras informadas</th>
                                <th class="text-end">Rejeição/cancel.</th>
                                <th>ADS entregues</th>
                                <th class="text-end">Valor ADS</th>
                                <th class="text-end">Parciais</th>
                                <th class="text-end">Valor parciais</th>
                                <th class="text-end">ADS atrasadas</th>
                                <th class="text-end">A vencer</th>
                                <th class="text-end">Viab. pend.</th>
                                <th class="text-end">D5 pend.</th>
                                <th class="text-end">D5 devolv.</th>
                                <th class="text-end">Inf. rejeit.</th>
                                <th class="text-end">Reclam.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $k          = $row['kpis'];
                                    $p          = $row['period'];
                                    $rowDueSoon = (int) ($k['work_without_ads_due_soon'] ?? 0) + (int) ($k['viability_due_soon'] ?? 0);
                                @endphp
                                <tr wire:key="branch-{{ $row['id'] }}">
                                    <td class="fw-semibold">
                                        {{ $row['name'] }}
                                        @if ($row['is_branch'])
                                            <span class="mp-pill ms-1">Filial</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $p['informed_total'] ?? 0 }}</td>
                                    <td class="text-end {{ ($row['reject_rate'] ?? 0) > 15 ? 'text-danger fw-bold' : (($row['reject_rate'] ?? 0) > 5 ? 'text-warning fw-bold' : '') }}">{{ $row['reject_rate'] === null ? '—' : $row['reject_rate'] . '%' }}</td>
                                    <td>
                                        <div class="mp-age {{ ($row['ads_rate'] ?? 0) >= 90 ? 'mp-age-green' : '' }}">
                                            <span class="mp-age-bar"><span style="--pct: {{ max(1, 100 - ($row['ads_rate'] ?? 0)) }}"></span></span>
                                            <span class="small">{{ $row['ads_rate'] === null ? '—' : $row['ads_rate'] . '%' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ $money($p['ads_amount_total'] ?? 0) }}</td>
                                    <td class="text-end">{{ $p['partials_total'] ?? 0 }}</td>
                                    <td class="text-end">{{ $money($p['partials_amount_total'] ?? 0) }}</td>
                                    <td class="text-end {{ $cell($k['work_without_ads_overdue'] ?? 0) }}">{{ $k['work_without_ads_overdue'] ?? 0 }}</td>
                                    <td class="text-end {{ $cell($rowDueSoon, 'text-warning fw-bold') }}">{{ $rowDueSoon }}</td>
                                    <td class="text-end">{{ $k['pending_viability'] ?? 0 }}</td>
                                    <td class="text-end">{{ $k['d5_pending'] ?? 0 }}</td>
                                    <td class="text-end {{ $cell($k['d5_returned'] ?? 0, 'text-warning fw-bold') }}">{{ $k['d5_returned'] ?? 0 }}</td>
                                    <td class="text-end {{ $cell($k['informs_rejected'] ?? 0) }}">{{ $k['informs_rejected'] ?? 0 }}</td>
                                    <td class="text-end {{ $cell($k['reclaims_pending'] ?? 0, 'text-warning fw-bold') }}">{{ $k['reclaims_pending'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total consolidado</td>
                                <td class="text-end">{{ (int) $sum('period', 'informed_total') }}</td>
                                <td class="text-end">{{ $rejectRate === null ? '—' : $rejectRate . '%' }}</td>
                                <td>{{ $adsRate === null ? '—' : $adsRate . '%' }}</td>
                                <td class="text-end">{{ $money($sum('period', 'ads_amount_total')) }}</td>
                                <td class="text-end">{{ (int) $sum('period', 'partials_total') }}</td>
                                <td class="text-end">{{ $money($sum('period', 'partials_amount_total')) }}</td>
                                <td class="text-end">{{ (int) $sum('kpis', 'work_without_ads_overdue') }}</td>
                                <td class="text-end">{{ (int) ($sum('kpis', 'work_without_ads_due_soon') + $sum('kpis', 'viability_due_soon')) }}</td>
                                <td class="text-end">{{ (int) $sum('kpis', 'pending_viability') }}</td>
                                <td class="text-end">{{ (int) $sum('kpis', 'd5_pending') }}</td>
                                <td class="text-end">{{ (int) $sum('kpis', 'd5_returned') }}</td>
                                <td class="text-end">{{ (int) $sum('kpis', 'informs_rejected') }}</td>
                                <td class="text-end">{{ (int) $sum('kpis', 'reclaims_pending') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mp-panel-foot">
                    @foreach ($rows as $row)
                        <button type="button" class="btn btn-sm btn-outline-primary row-action" wire:key="detail-{{ $row['id'] }}" wire:click="$set('partnerCompanyFilter', '{{ $row['id'] }}')">
                            <i class="ri-search-eye-line"></i> Detalhar {{ $row['name'] }}
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Pendências e informes --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Pendências detalhadas</h3>
                            <div class="mp-panel-sub">Situação de hoje, sem filtro de período</div>
                        </div>
                    </div>
                    <div class="mp-stat-grid is-fit">
                        <div class="mp-stat"><span>Viabilidade pendente</span><strong>{{ $int($kpis['pending_viability'] ?? 0) }}</strong><small>Ainda não concluídas</small></div>
                        <div class="mp-stat"><span>D5 aguardando solução</span><strong>{{ $int($kpis['d5_pending'] ?? 0) }}</strong><small>Pendentes de fechamento</small></div>
                        <div class="mp-stat {{ ($kpis['d5_returned'] ?? 0) > 0 ? 'is-warn' : '' }}"><span>D5 devolvidos</span><strong>{{ $int($kpis['d5_returned'] ?? 0) }}</strong><small>Aguardando retorno</small></div>
                        <div class="mp-stat {{ ($kpis['viability_rejected_waiting'] ?? 0) > 0 ? 'is-danger' : '' }}"><span>Viabilidades rejeitadas</span><strong>{{ $int($kpis['viability_rejected_waiting'] ?? 0) }}</strong><small>Aguardando resposta</small></div>
                        <div class="mp-stat {{ ($kpis['informs_rejected'] ?? 0) > 0 ? 'is-danger' : '' }}"><span>Informes rejeitados</span><strong>{{ $int($kpis['informs_rejected'] ?? 0) }}</strong><small>Com devolução</small></div>
                        <div class="mp-stat {{ ($kpis['reclaims_pending'] ?? 0) > 0 ? 'is-warn' : '' }}"><span>Reclamações pendentes</span><strong>{{ $int($kpis['reclaims_pending'] ?? 0) }}</strong><small>Aguardando solução</small></div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Informes do período</h3>
                            <div class="mp-panel-sub">Parciais e finais solicitados entre as datas do filtro</div>
                        </div>
                    </div>
                    <div class="mp-stat-grid is-fit">
                        <div class="mp-stat"><span>Parciais totais</span><strong>{{ $int($workReportKpis['partials_total'] ?? 0) }}</strong><small>Solicitados no período</small></div>
                        <div class="mp-stat {{ ($workReportKpis['partials_rejected_total'] ?? 0) > 0 ? 'is-danger' : '' }}"><span>Parciais rejeitadas</span><strong>{{ $int($workReportKpis['partials_rejected_total'] ?? 0) }}</strong><small>Não aprovadas para pagamento</small></div>
                        <div class="mp-stat is-good"><span>Parciais finalizadas</span><strong>{{ $int($workReportKpis['partials_completed_total'] ?? 0) }}</strong><small>Aprovadas e pagas no fluxo</small></div>
                        <div class="mp-stat"><span>Obras informadas</span><strong>{{ $int($workReportKpis['informed_total'] ?? 0) }}</strong><small>Total por conclusão informada</small></div>
                        <div class="mp-stat {{ ($workReportKpis['canceled_rejected_total'] ?? 0) > 0 ? 'is-danger' : '' }}"><span>Cancelados/rejeitados</span><strong>{{ $int($workReportKpis['canceled_rejected_total'] ?? 0) }}</strong><small>Dentro dos informes do período</small></div>
                        <div class="mp-stat is-good"><span>ADS entregues</span><strong>{{ $int($workReportKpis['ads_delivered_total'] ?? 0) }}</strong><small>Vinculadas a informes válidos*</small></div>
                        <div class="mp-stat {{ ($workReportKpis['ads_not_delivered_total'] ?? 0) > 0 ? 'is-warn' : '' }}"><span>ADS não entregues</span><strong>{{ $int($workReportKpis['ads_not_delivered_total'] ?? 0) }}</strong><small>Informes válidos* sem ADS</small></div>
                    </div>
                    <div class="mp-panel-body border-top small text-muted">* Válidos são informes que não foram cancelados nem rejeitados.</div>
                </section>
            </div>
        </div>

        {{-- Histogramas --}}
        <h2 class="mp-section-title">Tempo em aberto</h2>
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Viabilidades em aberto</h3>
                            <div class="mp-panel-sub">Quantidade por dias desde o envio para a parceira</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <span class="mp-pill">Total: {{ $int($openViabilityAgeHistogram['total'] ?? 0) }}</span>
                            <span class="mp-pill">Média: {{ number_format($openViabilityAgeHistogram['average'] ?? 0, 1, ',', '.') }} dias</span>
                            <span class="mp-pill">Maior idade: {{ $openViabilityAgeHistogram['oldest'] ?? 0 }} dias</span>
                        </div>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="viabilityAge" data-config="{{ json_encode($charts['viabilityAge']) }}" role="img" aria-label="Barras: viabilidades em aberto por dias desde o envio"></canvas>
                        </div>
                        @if (($openViabilityAgeHistogram['total'] ?? 0) === 0)
                            <div class="mp-chart-empty">Nenhuma viabilidade em aberto</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">D5 em espera</h3>
                            <div class="mp-panel-sub">Quantidade por dias desde o despacho para a parceira</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <span class="mp-pill">Total: {{ $int($openD5AgeHistogram['total'] ?? 0) }}</span>
                            <span class="mp-pill">Em espera: {{ $int($openD5AgeHistogram['waiting_total'] ?? 0) }}</span>
                            <span class="mp-pill">Rejeitados: {{ $int($openD5AgeHistogram['rejected_total'] ?? 0) }}</span>
                            <span class="mp-pill">Passivos: {{ $int($openD5AgeHistogram['passive_total'] ?? 0) }}</span>
                            <span class="mp-pill">Média: {{ number_format($openD5AgeHistogram['average'] ?? 0, 1, ',', '.') }} dias</span>
                            <span class="mp-pill">Maior idade: {{ $openD5AgeHistogram['oldest'] ?? 0 }} dias</span>
                        </div>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="d5Age" data-config="{{ json_encode($charts['d5Age']) }}" role="img" aria-label="Barras empilhadas: D5 em espera e rejeitados por dias desde o despacho"></canvas>
                        </div>
                        @if (($openD5AgeHistogram['total'] ?? 0) === 0)
                            <div class="mp-chart-empty">Nenhum D5 em aberto</div>
                        @endif
                    </div>
                </section>
            </div>
            <div class="col-12">
                <section class="mp-panel">
                    <div class="mp-panel-head">
                        <div>
                            <h3 class="mp-panel-title">Motivos de informes rejeitados</h3>
                            <div class="mp-panel-sub">Quantidade de devoluções por motivo no período selecionado</div>
                        </div>
                        <span class="mp-pill">Total: {{ $int($rejectedWorkReasons['total'] ?? 0) }}</span>
                    </div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="reasons" data-config="{{ json_encode($charts['reasons']) }}" role="img" aria-label="Barras horizontais: informes rejeitados por motivo"></canvas>
                        </div>
                        @if (($rejectedWorkReasons['total'] ?? 0) === 0)
                            <div class="mp-chart-empty">Nenhum informe rejeitado neste período</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        {{-- Prazos --}}
        <h2 class="mp-section-title">Prazos</h2>
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <h3 class="mp-panel-title">Viabilidades vencendo</h3>
                        <a class="btn btn-sm btn-outline-primary row-action" href="{{ route('partner.todo.viability') }}"><i class="ri-list-check-2"></i> Abrir lista</a>
                    </div>
                    @if ($dueSoon->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup><col style="width: 34%"><col style="width: 30%"><col style="width: 36%"></colgroup>
                                <thead><tr><th>Nota</th><th class="text-center">Recebido</th><th class="text-center">Vence</th></tr></thead>
                                <tbody>
                                    @foreach ($dueSoon as $item)
                                        @php
                                            $dueDate  = $item->sended_at->copy()->addDays(7 + $item->getDays());
                                            $daysLeft = (int) now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $item->note->note ?? '—' }}</td>
                                            <td class="text-center"><x-ops.date :date="$item->sended_at" /></td>
                                            <td class="text-center"><x-ops.date :date="$dueDate" :tone="PrazoReal::tone($daysLeft)" :sub="ControlAge::remainingLabel($daysLeft)" hint="Data de vencimento e dias que faltam" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mp-panel-body text-center text-muted">Nenhuma viabilidade vencendo em breve.</div>
                    @endif
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <h3 class="mp-panel-title">Entregas de ADS a vencer</h3>
                        <a class="btn btn-sm btn-outline-primary row-action" href="{{ route('partner.report.workedlist') }}"><i class="ri-list-check-2"></i> Ver informes</a>
                    </div>
                    @if ($workReportsWithoutAdsDueSoon->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup><col style="width: 34%"><col style="width: 30%"><col style="width: 36%"></colgroup>
                                <thead><tr><th>Nota</th><th class="text-center">Informe</th><th class="text-center">Prazo ADS</th></tr></thead>
                                <tbody>
                                    @foreach ($workReportsWithoutAdsDueSoon as $item)
                                        @php $daysLeft = $item->ads_days_left !== null ? (int) $item->ads_days_left : null; @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $item->note->note ?? '—' }}</td>
                                            <td class="text-center"><x-ops.date :date="$item->informed_at" time /></td>
                                            <td class="text-center"><x-ops.date :date="$item->ads_due_at" :tone="PrazoReal::tone($daysLeft)" :sub="ControlAge::remainingLabel($daysLeft)" hint="Prazo de entrega da ADS e dias que faltam" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mp-panel-body text-center text-muted">Sem entregas de ADS vencendo no horizonte atual.</div>
                    @endif
                    <div class="mp-panel-body border-top small">
                        <strong class="d-block mb-1">Base contratual</strong>
                        {!! $legalNotices->adsDueText() !!}
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-4">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <h3 class="mp-panel-title">Entregas de ADS em atraso</h3>
                        <a class="btn btn-sm btn-outline-primary row-action" href="{{ route('partner.report.workedlist') }}"><i class="ri-list-check-2"></i> Ver informes</a>
                    </div>
                    @if ($tacitAdsOverdueWithoutDelivery->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup><col style="width: 40%"><col style="width: 60%"></colgroup>
                                <thead><tr><th>Obra</th><th class="text-center">Venceu em</th></tr></thead>
                                <tbody>
                                    @foreach ($tacitAdsOverdueWithoutDelivery as $item)
                                        <tr>
                                            <td class="fw-bold">{{ $item->note->note ?? '—' }}</td>
                                            <td class="text-center"><x-ops.date :date="$item->Adsform?->tacit_due_at" tone="red" :sub="ControlAge::remainingLabel(-1 * (int) ($item->ads_late_days ?? 0))" hint="Data em que a ADS venceu e dias de atraso" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="mp-panel-body text-center text-muted">Sem entregas de ADS em atraso.</div>
                    @endif
                    <div class="mp-panel-body border-top small">
                        <strong class="d-block mb-1">Base contratual</strong>
                        {!! $legalNotices->adsOverdueText() !!}
                    </div>
                </section>
            </div>
        </div>

        {{-- Atalhos --}}
        <section class="mp-panel mb-3">
            <div class="mp-panel-head">
                <h3 class="mp-panel-title">Atalhos</h3>
            </div>
            <div class="mp-panel-body d-flex flex-wrap gap-2">
                <a href="{{ route('partner.note_d5.list') }}" class="btn btn-outline-primary btn-sm row-action"><i class="ri-file-list-3-line"></i> D5 pendentes</a>
                <a href="{{ route('partner.note_d5.returned') }}" class="btn btn-outline-primary btn-sm row-action"><i class="ri-arrow-go-back-line"></i> D5 devolvidos</a>
                <a href="{{ route('partner.rejected.viability') }}" class="btn btn-outline-primary btn-sm row-action"><i class="ri-close-circle-line"></i> Viabilidades rejeitadas</a>
                <a href="{{ route('partner.report.rejectedWorked') }}" class="btn btn-outline-primary btn-sm row-action"><i class="ri-file-warning-line"></i> Informes rejeitados</a>
                <a href="{{ route('partner.tacit.viability') }}" class="btn btn-outline-primary btn-sm row-action"><i class="ri-time-line"></i> Tacitativas sem justificativa</a>
            </div>
        </section>
    </div>
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
