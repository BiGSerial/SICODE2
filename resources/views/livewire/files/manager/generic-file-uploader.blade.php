@php
    use App\Helpers\SelectOptions;
@endphp
<div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress">

    <div class="mb-3">
        <div class="form-floating">
            <select wire:model="selectedType" class="form-select @error('selectedType') is-invalid @enderror">
                <option value="">— selecione —</option>
                @foreach (SelectOptions::getProtocolReasons() as $type)
                    <option value="{{ $type->{$uploadColValue} }}">
                        {{ $type->{$uploadColValue} }} – {{ $type->reason }}
                    </option>
                @endforeach
            </select>
            <label class="form-label">Tipo de Envio</label>
            @error('selectedType')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <div class="mb-3">
        <input type="file" wire:model="files" multiple class="form-control @error('files.*') is-invalid @enderror"
            @disabled(!$selectedType) />
        @error('files.*')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
        {{-- Adicionar validação para 'files' também, caso o array esteja vazio e seja requerido --}}
        @error('files')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div x-show="isUploading" class="mb-3">
        <div class="progress">
            <div class="progress-bar" role="progressbar" :style="`width: ${progress}%`">
                <span x-text="progress + '%'"></span>
            </div>
        </div>
    </div>

    @if (count($tempFiles))
        <ul class="list-group mb-3">
            @foreach ($tempFiles as $i => $t)
                <li class="list-group-item d-flex justify-content-between align-items-center small">
                    {{ $t['original_name'] }}
                    <span class="badge bg-secondary">{{ $t['uploadType'] }}</span>
                </li>
            @endforeach
        </ul>
        {{-- <button wire:click="saveFiles" class="btn btn-primary">Salvar Todos</button> --}}
    @endif

</div>
