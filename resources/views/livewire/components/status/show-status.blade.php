@php

    use App\Custom\Notestatus;

@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="statusView" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content">
                @if ($status)
                    <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">{{ $production->Note->note }} -
                            {{ Notestatus::status($status->status)->status }}</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body edp-bg-gray">

                        <div class="card">
                            <h5 class="card-header {{ Notestatus::status($status->status)->colorbg }}">Info</h5>
                            <div class="card-body">
                                <p>{{ $status->info }}</p>
                            </div>
                            <div class="card-footer">
                                <p class="my-0 py-0"> {{ date('d/m/Y H:i:s', strToTime($status->created_at)) }} -
                                    {{ $status->User->name }}</p>
                                <p class="my-0 py-0">{{ $status->Service ? $status->Service->service : '---' }}</p>
                            </div>
                        </div>
                @endif
            </div>

            <div class="modal-footer edp-bg-sprucegreen-100 edp-text-verde-dark">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>

</div>
