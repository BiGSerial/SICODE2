<div x-data="{ isShow: false }" style="position: inherit;">

    @if ($files->count())

        <i class="ri-file-3-line text-danger" @click="isShow=!isShow"></i>
        <div class="position-absolute start-0 ms-5" style="display: none; z-index: 99999; width: 300px; right: 100px"
            x-show="isShow" @click.away="isShow=false">

            <div class="list-group shadow border border-1 border-secondary">
                <div class="list-group-item edp-bg-sprucegreen-70 text-edp-verde text-center fw-bold">
                    LISTA DE ARQUIVOS</div>
                @foreach ($files->sortBy('file_name') as $file)
                    <button type="button" class="list-group-item group-item-action"
                        wire:click.prevent="downloadFile({{ $file->id }})"><i
                            class="ri-file-3-line fs-5 text-danger align-middle"></i>
                        {{ $file->file_name }}</button>
                @endforeach
            </div>
        </div>

    @endif
</div>
