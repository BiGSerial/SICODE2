<div>
    <div class="my-2"> <button class="btn btn-sm btn-primary"
            onclick="document.getElementById('file-input').click()">ADICIONAR PROJETO</button>
        <button class="btn btn-sm btn-danger" wire:click.prevent='cancel' wire:loading.attr='disabled'><span
                wire:target='cancel' wire:loading.remove>REMOVER
                TUDO</span><span wire:target='cancel' wire:loading>REMOVENDO...</span></button>
    </div>
    <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
        x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress">

        <form wire:submit.prevent="saveFile">
            <input type="file" id="file-input" multiple wire:model="uploadsfiles" value=""
                accept=".pdf,.gif,.jpg,.png, .xls, .xlsx, .xlsm" hidden>

        </form>

        <div x-show="isUploading" class="mb-3">

            <div class="progress my-0" role="progressbar" aria-label="Danger example" aria-valuenow="100"
                aria-valuemin="0" aria-valuemax="100" style="width: 100%; border-radius: 0;">
                <span class="progress-bar bg-danger" x-bind:style="`width: ${progress}%`" x-text="`${progress}%`">
            </div>
        </div>
    </div>

    <div class="card">

        <div class="card-body p-1">
            @if (count($files))

                <div class="container">

                    @foreach ($files as $index => $file)
                        <div
                            class="col-4 border border-secondary d-flex justify-content-between align-items-center p-0 m-1 bg-white">
                            <div class="p-1 m-0 border-end border-secondary">
                                <i class="bx bxs-file-{{ $file->getClientOriginalExtension() }} text-danger fs-4"></i>
                            </div>
                            <div class="p-1 m-0 text-center no-wrap">
                                <p class="my-0 py-0">
                                    {{ $file->getClientOriginalName() }}
                                </p>

                            </div>
                            <div class="p-1 m-0 border-start border-secondary">
                                <i class="bx bxs-trash text-danger fs-4"
                                    wire:click.prevent="deleteFile({{ $index }})" style="cursor: pointer;"></i>
                            </div>
                        </div>
                        {{-- <p>{{ $index }} - {{ mb_strtoupper($file->getClientOriginalExtension()) }} |
                            {{ $file->getClientOriginalName() }} |
                            <button class="btn btn-sm btn-danger"
                                wire:click="deleteFile({{ $index }})">Delete</button>
                        </p> --}}
                    @endforeach

                </div>
            @else
                <div class="my-2 py-2 text-center">
                    <h4 class="fw-bold">SEM ARQUIVOS</h4>
                </div>
            @endif
        </div>

        @if ($notNote)
            <div class="card-footer text-bg-danger text-center">
                EXISTEM ARQUIVOS QUE PARECEM NÃO TER REFERÊNCIA A ESTA OBRA ({{ $note->note }}).
            </div>
        @endif
    </div>



</div>
