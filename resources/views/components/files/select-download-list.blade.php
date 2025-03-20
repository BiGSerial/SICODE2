<style>
    .dropdown-item:hover {
        background-color: rgba(40, 253, 40, 0.692) !important;
        /* LightGreen with 50% opacity */
    }

    .dropdown-item {
        /* Explicitly set background for normal state */
        background-color: #f8f9fa;
        /* Or whatever your default is */
    }
</style>

<div>
    @if ($files->count())
        <div class="dropdown" style="position: inherit;">
            <i class="ri-file-3-line fs-4 text-danger" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                style="cursor: pointer;"></i>
            <ul class="dropdown-menu edp-bg-gray py-0" style="min-width: 300px;">
                <li class="edp-bg-sprucegreen-100 text-edp-verde text-center fw-bold py-1">ARQUIVOS -
                    {{ $files[0]->note->note }}</li>

                <div class="accordion" id="filesAccordion">
                    @php
                        $service = '';
                        $accordionCounter = 0;
                    @endphp

                    @foreach ($files->sortBy(function ($file) {
        return [$file->service_id === null ? 1 : 0, $file->service_id === null ? '' : $file->service->service, $file->file_name];
    }) as $file)
                        @php
                            $currentServiceId = $file->service_id ?? 'others';
                            $isNewService = $service != $currentServiceId;

                            if ($isNewService) {
                                $service = $currentServiceId;
                                $accordionCounter++;
                                $service_name = $file->service_id ? mb_strtoupper($file->service->service) : 'OUTROS';
                                // Use note_id to create unique IDs for each accordion based on the file.
                                $accordionId = 'accordion-' . $file->note_id . '-' . $accordionCounter;
                                $collapseId = 'collapse-' . $file->note_id . '-' . $accordionCounter;
                            }
                        @endphp

                        @if ($isNewService)
                            @if ($accordionCounter > 1)
                </div>
        </div>
</div>
@endif

<div class="accordion-item edp-bg-gray">
    <h2 class="accordion-header" id="heading-{{ $accordionId }}">
        <span class="w-100 my-0 py-0" tabindex="0" data-bs-container="body" data-bs-toggle="popover"
            data-bs-placement="left" data-bs-trigger="hover" data-bs-content="Clique para Expandir">
            <button
                class="accordion-button collapsed edp-bg-sprucegreen-70 text-edp-verde text-center fw-bold py-1 w-100"
                type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false"
                aria-controls="{{ $collapseId }}" onclick="event.stopPropagation(); closeOtherAccordions(this);">
                {{ $service_name }}
            </button>
        </span>
    </h2>
    <div id="{{ $collapseId }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $accordionId }}"
        data-bs-parent="#filesAccordion">
        <div class="accordion-body py-0">
            @endif

            <li wire:key="file-{{ $file->id }}">
                <a class="dropdown-item edp-bg-gray py-1" href="#"
                    wire:click.prevent="downloadFile({{ $file->id }})">{{ $file->file_name }}
                </a>
            </li>
            @endforeach

            @if ($files->count() > 0)
        </div>
    </div>
</div>
@endif
</div>
</ul>
</div>
@endif
</div>

<script>
    function closeOtherAccordions(element) {
        const accordions = document.querySelectorAll('.accordion-collapse'); // Get all accordion collapse elements

        accordions.forEach(accordion => {
            if (accordion.id !== element.getAttribute('data-bs-target').substring(
                    1)) { // Exclude the current element
                const bsCollapse = new bootstrap.Collapse(accordion, {
                    toggle: false
                }); // Create a Bootstrap collapse object
                bsCollapse.hide(); // Hide the accordion
            }
        });
    }
</script>
