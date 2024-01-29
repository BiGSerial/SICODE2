<div>
    <!-- resources/views/livewire/artisan-command-executor.blade.php -->
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div>
        <div class="input-group mb-3">
            <input type="text" class="form-control" placeholder="Digite o comando..." aria-label="Recipient's username"
                aria-describedby="button-addon2" wire:model.defer="command">
            <button class="btn btn-outline-secondary" type="button" id="button-addon2"
                wire:click="executeCommand">Executar</button>
        </div>


        @if (count($output) > 0)
            <div class="card mt-3">
                <div class="card-header bg-dark text-light">
                    <h2 class="mb-0">Resultado do Comando:</h2>
                </div>
                <div class="card-body bg-dark text-info"> <!-- Adicionando a classe de cor lime -->
                    <ul class="list-unstyled">
                        @foreach ($output as $line)
                            <li class="text-lime">{{ $line }}</li> <!-- Adicionando a classe de cor lime -->
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

</div>
