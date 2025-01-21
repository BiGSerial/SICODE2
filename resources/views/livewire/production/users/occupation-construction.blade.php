<div wire:poll>
    @if ($lists->count())
        <div class="card mt-3">
            <div class="card-header py-0 edp-bg-sprucegreen-70">
                <h5 class="card-title py-1 edp-text-verde-dark">Taxa Ocupação - <span
                        class="fw-bold">({{ $lists->sum('registros') }})</span>
                </h5>
            </div>
            <div class="card-body">

                <div class="col my-2">
                    <select class="form-select form-select-sm border border-1 border-secondary"
                        aria-label="Seleciona Empresa para Filtro" wire:model="company_s">
                        <option value="" selected>Todas..</option>
                        @if ($this->company_l->count())
                            @foreach ($this->company_l as $company)
                                <option value="{{ $company->id }}">{{ explode(' ', $company->name)[0] }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>


                <table class="table table-sm table-condensed table-hover table-striped ">

                    <thead>
                        <tr>
                            <th scope="col" class="text-center">#</th>
                            <th scope="col" class="text-center">Usuario</th>
                            <th scope="col" class="text-center">Ocupação</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($lists as $indice => $list)
                            @php
                                $name = explode(' ', $list->name);
                                $name = $name[0] . ' ' . end($name);
                            @endphp
                            <tr role="button" wire:click.defer="$emit('filterUser', '{{ $list->id }}')">
                                <td class="fw-bold text-center align-middle">
                                    {{ $indice + 1 }}

                                </td>
                                <td class="text-center position-relative align-middle">

                                    {{ $name }}

                                    @if (isset($list->Watchdog) && $list->Watchdog->watchdog)
                                        <span
                                            class="position-absolute top-50 spinner-grow me-2 start-100 translate-middle p-1
                                    border border-light rounded-circle"
                                            style="background-color: #28FF52; width: 10px; height: 10px">
                                            <span class="visually-hidden">New
                                                alerts</span>
                                        </span>
                                    @else
                                        <span
                                            class="position-absolute top-50 me-2 start-100 translate-middle p-1
                            border border-light rounded-circle"
                                            style="background-color: #ef2727; width: 10px; height: 10px">
                                            <span class="visually-hidden">New
                                                alerts</span>
                                        </span>
                                    @endif

                                </td>
                                <td class="text-center">
                                    <p class="my-0 py-0">{{ $list->registros }} - <span
                                            style="">{{ round(($list->registros / $lists->sum('registros')) * 100, 2) }}%</span>
                                    </p>
                                    <p class="my-0 py-0"><span style="font-size: 12px;"> N: {{ $list->notes }} / O:
                                            {{ $list->ov }}</span></p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- <div wire:ignore>
        @dump($lists)
    </div> --}}
</div>
