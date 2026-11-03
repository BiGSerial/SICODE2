@php
    use App\Services\Partner\ViabilityListRowPresenter as Row;

    $canRespond = $this->can('viability.respond');
@endphp

{{-- VIABILIDADES REJEITADAS (lista de controle da parceira, duas abas). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Viabilidade" title="Viabilidades rejeitadas"
        subtitle="Acompanhe as viabilidades que a Engenharia rejeitou: responda as que aguardam a sua parceira e veja as que já estão em tratativa." />

    <section class="ops-stats" aria-label="Resumo das viabilidades rejeitadas">
        <x-ops.stat tone="red" label="Aguardando resposta" :value="number_format($counts['awaiting'], 0, ',', '.')" icon="ri-chat-reply-line" :active="$awaiting"
            wire:click.prevent="$set('tab', 'awaiting')" title="Ver as que aguardam a resposta da parceira" />
        <x-ops.stat tone="blue" label="Em tratativa" :value="number_format($counts['treating'], 0, ',', '.')" icon="ri-time-line" :active="!$awaiting"
            wire:click.prevent="$set('tab', 'treating')" title="Ver as que já estão sendo tratadas pela Engenharia" />
    </section>

    @include('livewire.partner.partials.viability-toolbar', [
        'placeholder' => 'Buscar por nota ou ordem…',
        'tabsLabel'   => 'Situação da tratativa',
        'tabs'        => ['awaiting' => 'Aguardando resposta (' . number_format($counts['awaiting'], 0, ',', '.') . ')', 'treating' => 'Em tratativa (' . number_format($counts['treating'], 0, ',', '.') . ')'],
    ])

    <section class="mp-panel" aria-label="{{ $awaiting ? 'Viabilidades aguardando resposta' : 'Viabilidades em tratativa' }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">{{ $awaiting ? 'Viabilidades aguardando resposta' : 'Viabilidades em tratativa' }}</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'viabilidade' : 'viabilidades' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-checkbox-circle-line"
                    :title="$hasCriteria ? 'Nenhuma viabilidade com esses filtros' : ($awaiting ? 'Nada aguardando a sua resposta' : 'Nenhuma viabilidade em tratativa')"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais viabilidades.' : ($awaiting ? 'Quando a Engenharia rejeitar uma viabilidade, ela aparece aqui para você responder.' : 'As viabilidades que já foram respondidas e estão com a Engenharia aparecem aqui.')" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 110px">
                            <col style="width: 70px">
                            <col style="width: 110px">
                            <col style="width: 100px">
                            <col style="width: 120px">
                            <col>
                            <col style="width: 18%">
                            <col style="width: 110px">
                            <col style="width: 130px">
                            <col style="width: 150px">
                            <col style="width: 16%">
                            <col style="width: 130px">
                            @if ($awaiting && $canRespond)
                                <col style="width: 130px">
                            @endif
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota/OV</th>
                                <th class="text-center">Arquivos</th>
                                <th>Ordem</th>
                                <th>Contratado</th>
                                <th>Viabilizado em</th>
                                <th>Motivo</th>
                                <th>Responsável</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Status</th>
                                <th>Empreiteira</th>
                                <th>Tempo</th>
                                @if ($awaiting && $canRespond)
                                    <th><span class="visually-hidden">Ações</span></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $viab)
                                @php
                                    $status = Row::status($viab);
                                    $orders = Row::orders($viab);
                                @endphp
                                <tr wire:key="viab-rejected-{{ $viab->id }}">
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
                                    <td><x-ops.date :date="$viab->returned_at" hint="Data em que a parceira devolveu a viabilidade" /></td>
                                    <td>
                                        @if ($viab->Form?->reason)
                                            <span class="fw-semibold">{{ $viab->Form->reason }}</span>
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
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
                                    <td><x-ops.date :date="$viab->updated_at" :sub="$viab->updated_at?->diffForHumans()" hint="Última atualização desta viabilidade" /></td>
                                    @if ($awaiting && $canRespond)
                                        <td class="text-end">
                                            <button type="button" class="btn btn-primary btn-sm ops-action"
                                                wire:click.prevent="$emitTo('partner.actions.viab-response', 'getResponse', '{{ $viab->id }}')"
                                                title="Tréplica ao questionamento da Viabilidade">
                                                <i class="ri-reply-line" aria-hidden="true"></i> Responder
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

    {{-- Componentes Livewire --}}
    @livewire('partner.actions.viab-response', key('actions-viab-response'))
</div>
