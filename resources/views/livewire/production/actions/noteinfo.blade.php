<div>

    <li><a class="dropdown-item" href="#" wire:click.prevent="to_delete" data-bs-toggle="modal"
            data-bs-target="#production-{{ $production->id }}">{{ $conclusion }}><i
                class="ri-delete-bin-2-line text-danger align-middle"></i>
            Informação</a></li>

    <!-- Modal -->
    <div class="modal fade" id="production-{{ $production->id }}" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">
                        {{ mb_strtoupper($production->Service->service) }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($conclusion)
                        <div class="card">
                            <h4 class="card-header">RESULTADO - {{ $production->load('Note')->Note->note }}</h4>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-condensed table-striped table-sm">
                                        <tbody>
                                            @if ($exibition)
                                                @foreach ($exibition as $prod)
                                                    @if ($prod['valor'] !== null && $prod['chave'] !== 'production_id')
                                                        <tr>
                                                            @php
                                                                $valores = explode('<br>', $prod['valor']);
                                                            @endphp
                                                            <td class="fw-bold">{{ $prod['chave'] }}</td>
                                                            <td>
                                                                @foreach ($valores as $indice => $valor)
                                                                    @if ($indice > 0)
                                                                        <br>
                                                                    @endif
                                                                    {!! nl2br($valor) !!}
                                                                @endforeach

                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

</div>
