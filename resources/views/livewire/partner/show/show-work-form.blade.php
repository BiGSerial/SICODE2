<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="modal_form_work" tabindex="-1" aria-labelledby="modalFormWorkLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content inform-modal">
                <div class="modal-header inform-header">
                    <div>
                        <h4 class="my-0 fw-bold" id="modalFormWorkLabel">
                            INFORME FINAL
                            @if ($form)
                                <span class="badge {{ $form->rejected ? 'text-bg-danger' : 'text-bg-success' }} ms-2 align-middle">{{ $form->rejected ? 'REJEITADO' : 'INFORMADO' }}</span>
                            @endif
                        </h4>
                        @if ($form)
                            <small class="d-block mt-1">
                                Nota/OV {{ $form->Note->note }} · {{ mb_strtoupper($form->Note->lexp ?? '') }}
                                @foreach ($form->finalScopeBadges() as $scopeBadge)
                                    <span class="badge {{ $scopeBadge['class'] }} ms-1">{{ $scopeBadge['label'] }}</span>
                                @endforeach
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    @if ($form)
                        @php
                            $files           = $reportFiles;
                            $flowProductions = $internal ? ($form->FlowProductions ?? collect()) : collect();
                            $lastReturn      = $form->rejected ? $form->Returnwork->last() : null;
                        @endphp

                        <x-inform.kpis :items="[
                            ['label' => 'Nota/OV', 'value' => $form->Note->note],
                            ['label' => 'Empreiteira', 'value' => mb_strtoupper($form->Company->name ?? '-')],
                            ['label' => 'Data de execução', 'value' => $form->date?->format('d/m/Y')],
                            ['label' => 'Arquivos', 'value' => $files->count()],
                        ]" />

                        @if ($lastReturn)
                            <x-inform.card title="Motivo da rejeição" tone="danger">
                                <div class="inform-note is-danger">
                                    <strong>{{ $lastReturn->category ?: 'Sem categoria' }}</strong>
                                    <div class="inform-text">{!! nl2br(e($lastReturn->text_obs ?: 'Motivo não informado.')) !!}</div>
                                    <small>{{ $lastReturn->created_at?->format('d/m/Y H:i') }}</small>
                                </div>
                            </x-inform.card>
                        @endif

                        <x-inform.card title="Informações">
                            <x-inform.facts :items="[
                                ['label' => 'Ordem', 'value' => $form->Orders->pluck('ordem')->implode(', ')],
                                ['label' => 'Rubrica', 'value' => $form->Note->rubrica],
                                ['label' => 'Município', 'value' => $form->Note->lexp],
                                ['label' => 'Mudança no projeto', 'value' => $form->changes ? 'Sim' : 'Não'],
                                ['label' => 'Data primeiro informe', 'value' => $form->created_at?->format('d/m/Y H:i:s')],
                                ['label' => 'Data do informe', 'value' => $form->informed_at?->format('d/m/Y H:i:s')],
                                ['label' => 'Número DD', 'value' => $form->dd],
                                ['label' => 'Equipe WPA', 'value' => $form->team],
                                ['label' => 'Encarregado responsável', 'value' => $form->responsible],
                                ['label' => 'Responsável pelo informe', 'value' => $form->informer],
                            ]" />
                        </x-inform.card>

                        @if ($internal)
                            <x-inform.card title="Vínculos de produção" :count="$flowProductions->count() . ' vínculo(s)'">
                                @if ($flowProductions->isEmpty())
                                    <x-inform.empty text="Nenhuma produção vinculada ao informe" icon="ri-task-line" />
                                @else
                                    <div class="table-responsive">
                                        <table class="inform-table">
                                            <thead><tr><th>Etapa</th><th>Escopo</th><th>Produção</th><th>Serviço</th><th>Responsável</th><th>Atribuído em</th><th>Concluído em</th><th>Origem</th><th>Atual</th></tr></thead>
                                            <tbody>
                                                @foreach ($flowProductions as $flowProduction)
                                                    @php $production = $flowProduction->Production; @endphp
                                                    <tr>
                                                        <td><span class="badge {{ $flowProduction->stageBadgeClass() }}">{{ $flowProduction->stageLabel() }}</span></td>
                                                        <td><span class="badge {{ $form->finalScopeBadgeClass($flowProduction->final_scope ?? 'general') }}">{{ $form->finalScopeLabel($flowProduction->final_scope ?? 'general') }}</span></td>
                                                        <td>#{{ $production?->id ?? $flowProduction->production_id }}</td>
                                                        <td>{{ $production?->Service?->service ?? '-' }}</td>
                                                        <td>{{ $production?->User?->name ?? '-' }}</td>
                                                        <td>{{ $production?->att_at ? $production->att_at->format('d/m/Y H:i') : ($flowProduction->linked_at?->format('d/m/Y H:i') ?? '-') }}</td>
                                                        <td>{{ $production?->completed_at ? $production->completed_at->format('d/m/Y H:i') : '-' }}</td>
                                                        <td>{{ $flowProduction->sourceLabel() }}</td>
                                                        <td>{{ $flowProduction->is_current ? 'Sim' : 'Não' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </x-inform.card>
                        @endif

                        <x-inform.card title="Arquivos anexados" :count="$files->count() . ' arquivo(s)'">
                            @if ($files->isNotEmpty())
                                <x-inform.files :files="$files" />
                            @else
                                <x-inform.empty text="Nenhum arquivo anexado" icon="ri-folder-open-line" />
                            @endif
                        </x-inform.card>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <x-inform.card title="Observações" class="h-100">
                                    @if (trim((string) $form->observation))
                                        <div class="inform-text">{!! nl2br(e(strip_tags($form->observation))) !!}</div>
                                    @else
                                        <x-inform.empty text="Nenhuma observação" icon="ri-chat-off-line" />
                                    @endif
                                </x-inform.card>
                            </div>
                            <div class="col-lg-6">
                                <x-inform.card title="Informação de danos" class="h-100">
                                    @if (trim((string) $form->description))
                                        <div class="inform-text">{!! nl2br(e(strip_tags($form->description))) !!}</div>
                                    @else
                                        <x-inform.empty text="Nenhuma informação" icon="ri-chat-off-line" />
                                    @endif
                                </x-inform.card>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <x-inform.card title="Equipamentos" :count="$form->Equipment->count()" class="h-100">
                                    @forelse ($form->Equipment as $equip)
                                        <div class="inform-note">
                                            <strong>{{ $equip->type }} · {{ $equip->patrimony }}</strong>
                                            <small>{{ $equip->installed ? 'Instalado' : 'Retirado' }} · fases {{ $equip->fases ?: '—' }} · poste {{ $equip->pole ?: '—' }}</small>
                                        </div>
                                    @empty
                                        <x-inform.empty text="Nenhum equipamento informado" icon="ri-tools-line" />
                                    @endforelse
                                </x-inform.card>
                            </div>
                            <div class="col-lg-6">
                                <x-inform.card title="Medidores" :count="$form->Meeters->count()" class="h-100">
                                    @forelse ($form->Meeters as $meeter)
                                        <div class="inform-note"><strong>{{ $meeter->number }}</strong><small>Borne {{ $meeter->borne ?: '—' }} · fases {{ $meeter->fases ?: '—' }}</small></div>
                                    @empty
                                        <x-inform.empty text="Nenhum medidor informado" icon="ri-dashboard-3-line" />
                                    @endforelse
                                </x-inform.card>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
