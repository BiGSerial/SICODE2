@php
    use App\Helpers\FileIcon;
@endphp
<div>
    <style>
        .swfile {
            cursor: pointer;

        }

        .swfile:hover {
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.4);
            transition: box-shadow 0.5s ease-in-out;
            border: 3px solid rgba(16, 80, 255, 0.897);

        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-lg">
                <div class="card-header bg-secondary text-white py-1 my-0">
                    <h5 class="card-title py-0 my-0">Arquivos Anexados</h5>
                </div>
                <div class="card-body p-1">
                    @if ($files->isNotEmpty())
                        <div class="row">
                            @foreach ($files->sortBy('file_name') as $file)
                                <div class="col-md-3 text-center">
                                    <div class="card mb-2 swfile" wire:click="downloadFile({{ $file->id }})">
                                        <div class="card-body p-2">
                                            <i class="{{ FileIcon::getIcon($file->ext)->icon }} fs-2"></i>
                                            <p class="mb-0">{{ $file->file_name }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <h5 class="text-center">SEM ARQUIVOS ANEXADOS</h5>
                    @endif


                </div>
            </div>
        </div>
    </div>
</div>
