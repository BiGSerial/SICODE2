@php

    use Carbon\Carbon;

@endphp
<div>
    <div wire:ignore.self class="modal fade" id="addTimeToViabModal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-stategrey-50">
                @if ($viability)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h4 class="my-auto fw-bold">
                            ADICIONAR TEMPO PARA {{ $viability->Note->note }}
                        </h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped">

                            <tbody>
                                <tr>
                                    <td class="text-end fw-bold" style="width: 40%;">
                                        Limite de Dias:
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $limitDays }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold" style="width: 40%;">
                                        Dias Adicionados:
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ $viability->getDays() }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold" style="width: 40%;">
                                        Prazo Original:
                                    </td>
                                    <td class="text-center align-middle fw-bold">
                                        {{ Carbon::parse($viability->sended_at)->addDays(7)->format('d/m/Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold" style="width: 40%;">
                                        Prazo Atual:
                                    </td>
                                    <td class="text-center align-middle fw-bold text-primary">
                                        {{ Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays())->format('d/m/Y') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="form-group mt-3">
                            <label for="reason" class="fw-bold">Motivo</label>
                            <textarea id="reason" class="form-control @error('reason') is-invalid @enderror" wire:model.defer="reason"
                                rows="4" required></textarea>
                            @error('reason')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-group mt-3">
                            <input type="number" class="form-control @error('days') is-invalid @enderror"
                                wire:model.defer="days">
                            <button class="btn btn-primary" type="button" wire:click="addTimeToViab">Adicionar</button>
                            @error('days')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endif
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click.defer="closeAll">Fechar</button>
                </div>
            </div>
        </div>
