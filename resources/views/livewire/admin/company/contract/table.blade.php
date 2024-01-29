<?php
use Carbon\Carbon;
?>
<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />


    <div class="container">
        <div class="row">
            <div class="row justify-content-between">
                <div class="mb-3 col-3">
                    <label for="search" class="form-label">Buscar</label>
                    <input wire:model.bounce.2s="search" type="email"
                        class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                </div>

                <div class="mb-3 col-1">
                    <button type="button" class="btn btn-primary align-end" data-bs-toggle="modal"
                        data-bs-target="#create_modal" style="height: 50px">
                        <i class="ri-scales-3-fill fs-4"></i>
                    </button>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="main-box clearfix">
                    @if ($contracts_l && $contracts_l->count())
                        <div class="table-responsive">
                            <table class="table user-list">
                                <thead>
                                    <tr>
                                        <th><span>Contrato</span></th>
                                        <th class="text-center"><span>Vigência</span></th>
                                        <th class="text-center"><span>Tipo</span></th>
                                        <th class="text-center"><span>Tempo Restante</span></th>
                                        <th class="text-center"><span>Criado em</span></th>


                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($contracts_l as $contract)
                                        <tr>
                                            <td>
                                                <img src="{{ asset('img/edp-img/edp-avatar.jpg') }}" alt="">
                                                <a href="#"
                                                    class="user-link text-dark">{{ $contract->number }}</a>
                                                @if (isset($contract->company->name))
                                                    <span
                                                        class="user-subhead mr-2">{{ $contract->company->name }}</span>
                                                @endif


                                            </td>
                                            <td class="text-center">
                                                {{ date('d/m/Y', strToTime($contract->date_end)) }}
                                            </td>
                                            <td class="text-center">
                                                @if ($contract->service)
                                                    <span class="mr-2">Serviços</span>
                                                @endif
                                                @if ($contract->construction)
                                                    <span class="mr-2">Construção</span>
                                                @endif
                                            </td>

                                            <td class="fs-5 fw-bold text-center">
                                                {{ Carbon::now()->diffInDays(date('Y-m-d', strtotime($contract->date_end))) }}
                                                dias
                                            </td>
                                            <td class="text-center">
                                                {{ date('d/m/Y', strToTime($contract->created_at)) }}

                                            </td>



                                            <td style="width: 20%;">

                                                <a href="#" class="table-link">
                                                    <span class="fa-stack">
                                                        <i class="ri-eye-line btn btn-info btn-sm"></i>
                                                    </span>
                                                </a>
                                                <a href="#" class="table-link"
                                                    wire:click.prevent="update_contract('{{ $contract->id }}')">
                                                    <span class="fa-stack">
                                                        <i class="ri-pencil-fill btn btn-primary btn-sm"></i>
                                                    </span>
                                                </a>
                                                @if ($contract->id !== Auth()->USer()->id)
                                                    <a href="#" class="table-link">
                                                        <span class="fa-stack"
                                                            wire:click.prevent="$emit('delete_contract', '{{ $contract->id }}')">
                                                            <i class="ri-delete-bin-2-fill btn btn-danger btn-sm"></i>
                                                        </span>
                                                    </a>
                                                @endif


                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <h4 class="text-center my-4 fw-bold">SEM CONTRATOS PARA EXIBIÇÃO</h4>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- MODAIS --}}

    @livewire('admin.company.contract.delete')

    <div wire:ignore.self class="modal fade" id="create_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-community-fill fs-4 align-middle"></i> CRIAR NOVO CONTRATO</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('admin.company.contract.create', key(hash('ripemd160', now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_create_contract')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="update_modal" tabindex="-1" aria-labelledby="update"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-user-add-fill fs-4 align-middle"></i> ATUALIZAR EMPRESA</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    @livewire('admin.company.contract.update', ['contract_id' => $contract_id], key(hash('ripemd160', now())))

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_update_contract')">Salvar</button>
                </div>
            </div>
        </div>
    </div>


    {{-- FIM MODAIS --}}

</div>
