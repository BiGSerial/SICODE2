<div>
    {{-- @dd($files) --}}

    @if ($files->count())
        <div x-data="{ isShow: false }" class="position-relative">
            <i class="ri-file-3-line text-danger" @click="isShow=!isShow"></i>
            <div class="position-absolute start-0" style="display: none; z-index: 99999; width: 300px;" x-show="isShow"
                @click.away="isShow=false">

                <div class="list-group shadown border border-1 border-secondary">
                    <div class="list-group-item edp-bg-sprucegreen-70 text-edp-verde text-center fw-bold">
                        LISTA DE ARQUIVOS</div>
                    @foreach ($files->sortBy('file_name') as $file)
                        <button type="button" class="list-group-item group-item-action"
                            wire:click.prevent="downloadFile({{ $file->id }})">{{ $file->file_name }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
