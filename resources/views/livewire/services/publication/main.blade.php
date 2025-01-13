@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row mb-3">
        <div class="col-2">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>
        <div class="col-md-10 d-flex mb-3 justify-content-end align-items-end pt-4">
            @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'publication', 'values' => 'name', 'direction' => 'ASC', 'query' => 'EXISTS (SELECT 1 FROM work_reports WHERE work_reports.company_id = companies.id)'], key('company'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'publication', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'publication', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'publication', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'publication'], key('removeAll'))
        </div>
    </div>


    <div class="mb-3">
        <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
            data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Exibir Apenas Notas Nao Atribuidas"
            data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribu[ida. </p> <pA palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
            <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                wire:click.prevent="filterStatus()">
                {{ Notestatus::status(1)->status }}
                @if ($not_assigned)
                    <span class="badge text-bg-success">ON</span>
                @else
                    <span class="badge text-bg-danger">OFF</span>
                @endif
            </button>

        </div>

        <div class="btn-group ms-2" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
            data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Exibir Informadas BT Zero"
            data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribuida. </p> <pA palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
            <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}" wire:click.prevent="btzeroform()">
                Info BT Zero
                @if ($btzeroform)
                    <span class="badge text-bg-success">ON</span>
                @else
                    <span class="badge text-bg-danger">OFF</span>
                @endif
            </button>

        </div>
    </div>

    <div class="row">
        <div class="col-6">
            {{ $lists->links() }}
        </div>
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de
                {{ $lists->total() }} registros.
                @if ($update)
                    Ultima Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </span>
        </div>
    </div>

    <div class="card">
        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }}</h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-secondary">LISTA PARA {{ mb_strtoupper($service->service) }}
                @if ($service->Status->count())
                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                        ({{ $sts->value }})
                    @endforeach
                @endif
            </h4>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark">
                        <tr>
                            <th class="align-middle text-center">Note</th>
                            <th class="align-middle text-center">Inf Digitacao</th>
                            <th class="align-middle text-center">Empresa</th>
                            <th class="align-middle text-center">Município</th>
                            <th class="align-middle text-center">Data Execução</th>
                            <th class="align-middle text-center">Data Informe</th>
                            <th class="align-middle text-center">Dias Pilha</th>
                            <th class="align-middle text-center">Dt Vencimento</th>
                            <th class="align-middle text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $block = 0;

                                if ($production = $this->hasPublication($list)) {
                                    if ($production->confirmed) {
                                        $block = 4;
                                    } elseif (
                                        $production->completed ||
                                        ($production->Note->RamalForm &&
                                            !$production->Note->WorkForm &&
                                            $production->status == 28)
                                    ) {
                                        $block = 3;
                                    } elseif ($production->status == 1) {
                                        $block = 2;
                                    } else {
                                        $block = 1;
                                    }
                                }

                                // Cores das linhas com base no status
                                $rowClass = '';
                                if ($block == 4) {
                                    $rowClass = 'table-danger';
                                } elseif ($block == 3) {
                                    $rowClass = 'table-success';
                                } elseif ($block == 2) {
                                    $rowClass = 'table-warning';
                                } elseif ($block == 1) {
                                    $rowClass = 'table-primary';
                                }
                            @endphp

                            <tr class="align-middle text-center">

                                @if (Auth()->User()->management || Auth()->User()->superadm || ($block && $production->user_id === Auth()->User()->id))
                                    <td class="fw-bold {{ $rowClass }}" data-value="{{ $list->note }}">
                                        {{ $list->note }}
                                    </td>
                                @else
                                    <td class="{{ $rowClass }}"></td>
                                @endif

                                <td class="fw-light {{ $rowClass }}">
                                    @if (!$list->WorkForm && $list->RamalForm)
                                        <i class="ri-alert-line text-danger align-middle fs-4"></i>
                                    @endif

                                </td>

                                <td class="fw-light {{ $rowClass }}">

                                    @if ($list->WorkForm)
                                        {{ $list->WorkForm->Company ? $list->WorkForm->Company->name : '---' }}
                                    @elseif ($list->RamalForm)
                                        {{ $list->RamalForm->Company ? $list->RamalForm->Company->name : '---' }}
                                    @endif
                                </td>

                                <td class="fw-light {{ $rowClass }}">{{ $list->lexp }}</td>

                                <td class="fw-light {{ $rowClass }}">
                                    {{ $list->WorkForm ? date('d/m/Y', strToTime($list->WorkForm->date)) : '---' }}
                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    {{ $list->WorkForm ? date('d/m/Y H:i:s', strToTime($list->WorkForm->informed_at)) : '---' }}
                                </td>

                                <td scope="col" class="text-center {{ $rowClass }}">
                                    {{ $list->WorkForm ? Carbon::parse($list->WorkForm->informed_at)->diffInDays(Carbon::now(), false) : '---' }}
                                </td>

                                @php
                                    $daysLeft = new DaysLeft($list);
                                    $prazoClass = '';

                                    if ($daysLeft->getDaysLeft() < 0) {
                                        $prazoClass = 'text-bg-danger';
                                    } elseif ($daysLeft->getDaysLeft() > 15) {
                                        $prazoClass = 'text-bg-success';
                                    } else {
                                        $prazoClass = 'text-bg-warning';
                                    }
                                @endphp

                                <!-- Prioridade de estilo da célula 'Prazo Restante' -->
                                <td scope="col" class="text-center {{ $prazoClass }}"
                                    style="background-color: inherit;">
                                    {{ $daysLeft->getLastDate() }}

                                </td>

                                <td class="fw-bold text-center {{ $rowClass }}">
                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="to_accompany({{ $list->id }})"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Enviar para Acompanhamento"></i>
                                    @else
                                        @php
                                            $name = $production->User->name ?? 'DESCONHECIDO';
                                            $nameParts = explode(' ', $name);
                                            $shortName = $nameParts[0] . ' ' . end($nameParts);
                                        @endphp
                                        <span style="font-size: 11px">{{ $shortName }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-6">
            {{ $lists->links() }}
        </div>
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de
                {{ $lists->total() }} registros.</span>
        </div>
    </div>
</div>

@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy', `Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    </script>
@endpush
