@php
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="return_modal" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg ">
            <div class="modal-content bg-light">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="exampleModalLabel">RETORNO PARA SERVIÇOS</h5>
                </div>
                <div class="modal-body  edp-bg-stategrey-50">

                    <div class="row mb-3">
                        <div class="col">
                            <label for="category" class="form-label">Selecione o Motivo <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" id="category"
                                wire:model.defer='category'>
                                <option value="" selected>Selecione...</option>
                                @foreach (SelectOptions::getReclaimsOptions() as $option)
                                    <option value="{{ $option->value }}">{{ $option->info }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="service_s" class="form-label">Selecione o Serviço <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select @error('service_s') is-invalid @enderror" id="service_s"
                                wire:model.debounce.500ms="service_s">
                                <option value="" selected>Selecione...</option>
                                @if ($services)
                                    @foreach ($services as $serv)
                                        <option value="{{ $serv->uuid }}">{{ $serv->service }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('service_s')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Mensagem <span
                                        class="text-danger fw-bold">*</span></label>
                                <textarea name="message" class="form-control shadow @error('comment') is-invalid @enderror" id="comment"
                                    cols="30" rows="5" wire:model.defer="comment" placeholder="Informe os Detalhes do retorno..."></textarea>
                                @error('comment')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="row edp-bg-sprucegreen-70 text-edp-verde py-1">
                        {{-- <h5 class="modal-title" id="exampleModalLabel">RETORNO PARA
                            {{ $this->services->where('uuid', $service_s)->first() ? mb_strToUpper($this->services->where('uuid', $service_s)->first()->service) : 'SERVIÇOS' }}
                            ({{ $show_returns ? $show_returns->count() : '' }})</h5> --}}
                    </div>
                    <div class="mx-0 px-0 border-botton border-2 border-secondary rounded mb-3"
                        style="
                        overflow: auto;
                        max-height: 200px;

                        scrollbar-width: thin; /* para Firefox */
                        scrollbar-color: rgb(16, 16, 16) rgb(161, 161, 160); /* para Firefox */
                        ">
                        <table class="table bordered table-sm table-condensed table-striped-columns mx-0 px-0">
                            <thead class="sticky-top table-success">
                                <tr class="text-center">
                                    <th>Note</th>
                                    <th>Rubrica</th>
                                    <th>Localidade</th>
                                    <th>Ultimo Usuario</th>
                                    <th>Data Conclusao</th>
                                </tr>
                            </thead>

                            <tbody>
                                @if (count($this->productions) > 0)
                                    @foreach ($this->productions as $prod)
                                        <tr class="text-center border-botton">
                                            <td>{{ $prod['note']['note'] }}</td>
                                            <td>{{ $prod['note']['rubrica'] }}</td>
                                            <td>{{ $prod['note']['lexp'] }}</td>
                                            <td>{{ $prod['production'] && isset($prod['production']['user']) ? $prod['production']['user']['name'] : '---' }}
                                            </td>
                                            <td>{{ $prod['production'] ? Carbon::parse($prod['production']['completed_at'])->format('d/m/Y H:i:s') : '---' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>

                        </table>

                    </div>
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70 text-edp-verde">
                    <button class="btn btn-danger" wire:click.prevent="closeAll">Cancelar</button>
                    <button class="btn btn-primary" wire:click.prevent="go_return">Enviar</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('return_modal').addEventListener('hidden.bs.modal', () => {

            // Emitindo o evento para o componente pai
            // Livewire.emitTo('construction.hiring.main', 'closeAll');
            // Livewire.emitTo('construction.hiring.main', 'closeAll');
            Livewire.emitTo('construction.hiring.actions.go-waiting', 'closeAll');


        });
    </script>

</div>
