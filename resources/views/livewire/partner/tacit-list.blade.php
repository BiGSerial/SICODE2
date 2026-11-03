@php
    use App\Services\Partner\ViabilityListRowPresenter as Row;

    $canJustify = $this->can('viability.tacit');
@endphp

{{-- VIABILIDADES VENCIDAS TACITAMENTE (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Viabilidade" title="Vencidas tacitamente"
        subtitle="Viabilidades que passaram do prazo sem resposta nos últimos 7 dias. Justifique cada uma para que a Engenharia avalie." />

    @include('livewire.partner.partials.viability-toolbar', ['placeholder' => 'Buscar por nota ou ordem…'])

    <section class="mp-panel" aria-label="Viabilidades vencidas tacitamente">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Aguardando justificativa</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'viabilidade' : 'viabilidades' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-checkbox-circle-line"
                    :title="$hasCriteria ? 'Nenhuma viabilidade com esses filtros' : 'Nenhuma viabilidade a justificar'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais viabilidades.' : 'Quando uma viabilidade vencer sem resposta, ela aparece aqui por 7 dias.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 110px">
                            <col style="width: 70px">
                            <col style="width: 110px">
                            <col style="width: 100px">
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col>
                            <col style="width: 110px">
                            <col style="width: 130px">
                            <col style="width: 150px">
                            <col style="width: 16%">
                            <col style="width: 130px">
                            @if ($canJustify)
                                <col style="width: 130px">
                            @endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota/OV</th>
                                <th class="text-center">Arquivos</th>
                                <th>Ordem</th>
                                <th>Contratado</th>
                                <th>Enviado em</th>
                                <th>Vencido em</th>
                                <th>Responsável</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Status</th>
                                <th>Empreiteira</th>
                                <th>Tempo</th>
                                @if ($canJustify)
                                    <th><span class="visually-hidden">Ações</span></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $viab)
                                @php
                                    $status = Row::status($viab);
                                    $orders = Row::orders($viab, false);
                                @endphp
                                <tr wire:key="viab-tacit-{{ $viab->id }}">
                                    <td><x-ops.note :note="$viab->Note->note" /></td>
                                    <td class="text-center"><x-files.select-download-list :files="$viab->Note->Files" :tacit-file-ids="$tacitFileIds" /></td>
                                    <td>
                                        @forelse ($orders as $order)
                                            <div>{{ $order }}</div>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td><x-ops.badge sm :tone="$viab->hired ? 'green' : 'muted'" :label="$viab->hired ? 'Sim' : 'Não'" hint="Se a obra desta viabilidade já foi contratada" /></td>
                                    <td><x-ops.date :date="$viab->sended_at" hint="Data em que a viabilidade chegou à parceira" /></td>
                                    <td><x-ops.date :date="$viab->tacit_at" tone="red" hint="Venceu tacitamente: o prazo acabou sem resposta" /></td>
                                    <td>
                                        @if ($viab->Engineer)
                                            <div class="ops-person-name">{{ $viab->Engineer->name }}</div>
                                            <div class="ops-person-sub" title="{{ $viab->Engineer->email }}">{{ $viab->Engineer->email }}</div>
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $viab->Note->rubrica ?: '—' }}</td>
                                    <td>{{ $viab->Note->lexp ?: '—' }}</td>
                                    <td><x-ops.badge :tone="$status['tone']" :label="$status['label']" /></td>
                                    <td>{{ $viab->Company?->name ?? '—' }}</td>
                                    <td><x-ops.date :date="$viab->tacit_at" :sub="$viab->tacit_at?->diffForHumans()" tone="red" hint="Há quanto tempo venceu" /></td>
                                    @if ($canJustify)
                                        <td class="text-end">
                                            <button type="button" class="btn btn-primary btn-sm ops-action"
                                                wire:click.prevent="$emitTo('partner.actions.tacit-justify', 'getResponse', '{{ $viab->id }}')">
                                                <i class="ri-file-list-3-line" aria-hidden="true"></i> Justificar
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$lists" :per-page="$perPage" :sizes="[25, 50, 100, 250, 500]" />
            @endif
        </div>
    </section>

    {{-- Livewire Components --}}
    @livewire('partner.actions.tacit-justify', key('tacit-justify'))
</div>
