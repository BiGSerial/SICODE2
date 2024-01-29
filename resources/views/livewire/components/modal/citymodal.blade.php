<div>
    <div wire:ignore.self class="modal fade" id="cityModal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Edição Município para
                        {{ $note ? $note->note : '' }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 class="text-center">Editar Município Ausente para Nota.</h4>
                    <p
                        class="text-justify p-2 border border-1 border-secondary edp-bg-sprucegreen-100 edp-text-verde-dark my-2">
                        Selecione o município correspondente para esta nota. Uma vez confirmado, não será possível
                        alterar o município cadastrado.
                    </p>
                    <div class="row">
                        <div class="col-4">
                            <select class="form-select" aria-label="Regiao dos municipios" wire:model="regiao">
                                @if (!$cities)
                                    <option selected>Sem conexao com a base de dados</option>
                                @else
                                    <option selected>Selecione</option>
                                    @if (count($regiao_l))
                                        @foreach ($regiao_l->sort() as $regiao)
                                            <option value="{{ $regiao }}">{{ $regiao }}</option>
                                        @endforeach
                                    @endif
                                @endif
                            </select>
                        </div>
                        <div class="col-8">
                            <select class="form-select" aria-label="Regiao dos municipios" wire:model.defer="municipio">
                                @if (!$cities)
                                    <option selected>Sem conexao com a base de dados</option>
                                @else
                                    <option selected>Selecione</option>
                                    @if ($cities->count())
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->rdMunicipio }}">{{ $city->municipio }}
                                                ({{ $city->rdMunicipio }})
                                            </option>
                                        @endforeach
                                    @endif
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" wire:click="setMunicipio"
                        wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm" role="status"
                            aria-hidden="true"></span>
                        <span wire:loading.remove>
                            Salvar
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
