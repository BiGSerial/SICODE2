@php
    // use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div>

    <span class="badge {{ Notestatus::status($status)->colorbg }}" wire:click.prevent="open_status"
        style="cursor: pointer;">{{ Notestatus::status($status)->status }}</span>

    <div wire:ignore.self class="modal fade" id="view_status-{{ $idstatus }}" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">{{ Notestatus::status($status)->status }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body edp-bg-gray">
                    @if ($info)
                        <div class="card">
                            <h5 class="card-header {{ Notestatus::status($status)->colorbg }}">Info</h5>
                            <div class="card-body">
                                <p>{{ $info->info }}</p>
                            </div>
                            <div class="card-footer">
                                {{ date('d/m/Y H:i:s', strToTime($info->created_at)) }} - {{ $info->User->name }}
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

    <script>
        document.addEventListener('livewire:load', function() {
            Livewire.hook('message.processed', (message, component) => {
                if (message.updateQueue[0]?.type === 'refresh') {
                    // O código aqui será executado quando o componente for atualizado
                    console.log('Componente atualizado!');
                }
            });
        });
    </script>
</div>
