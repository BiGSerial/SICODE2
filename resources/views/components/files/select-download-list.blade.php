<div>
    @if ($files->count())
        <div class="dropdown" style="position: inherit;">
            <i class="ri-file-3-line fs-4 text-danger" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                style="cursor: pointer;"></i>
            {{-- <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                Dropdown button
            </button> --}}
            <ul class="dropdown-menu edp-bg-gray py-0">
                <li class="edp-bg-sprucegreen-70 text-edp-verde text-center fw-bold py-1">ARQUIVOS</li>
                @foreach ($files->sortBy('file_name') as $file)
                    <li wire:key="file-{{ $file->id }}">
                        <a class="dropdown-item" href="#"
                            wire:click.prevent="downloadFile({{ $file->id }})">{{ $file->file_name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
