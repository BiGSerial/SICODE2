@once

    <style>
        .file-dropdown-menu {
            /* max-height: 300px; */
            overflow-y: auto;
            box-shadow: 6px 6px 20px rgba(0, 0, 0, 0.7), 8px 10px 25px rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            border: 0px solid #cfcfcf;
            /* transform: translateY(3px); */
            /* position: absolute; */
            top: 100%;
            left: 0;
            z-index: 9999;
        }

        .file-dropdown-header {
            background-color: #e2f4e2;
            color: #026402;
        }

        .file-dropdown-item {
            color: #161718;
        }

        .file-dropdown-item:hover {
            color: rgba(214, 43, 21, 0.979) !important;
            font-weight: bold;
        }

        .file-collapse {
            overflow: hidden;
            transition: height 0.3s ease;
            height: 0;
        }

        .file-collapse.open {
            /* height é ajustado dinamicamente via JS */
        }

        .file-accordion-button {
            background-color: #a0a0a0;
            border: none;
            font-weight: bold;
            padding: 6px 12px;
        }

        .file-accordion-button.collapsed {
            background-color: #e7f5e7;
        }

        .accordion-icon {
            transition: transform 0.3s ease;
        }

        .file-accordion-button.open .accordion-icon {
            transform: rotate(180deg);
        }
    </style>
@endonce

@if ($files?->isNotEmpty())
    @php
        $adsTacitFileIds = \Illuminate\Support\Facades\DB::table('adsforms_files as af')
            ->join('adsforms as a', 'a.id', '=', 'af.adsform_id')
            ->whereIn('af.file_id', $files->pluck('id')->all())
            ->where('a.tacit', true)
            ->whereNotNull('a.work_report_id')
            ->pluck('af.file_id')
            ->unique()
            ->all();
    @endphp
    <!-- Alteramos o container para não ter restrição de posicionamento -->
    <div class="dropdown d-inline file-dropdown" style="position: inherit;">
        <i class="ri-file-3-line fs-4 file-dropdown-toggle-icon text-danger" type="button" data-bs-toggle="dropdown"
            data-bs-boundary="viewport" aria-expanded="false" style="cursor: pointer;"></i>

        <ul class="dropdown-menu file-dropdown-menu py-0 edp-bg-gray" style="min-width: 350px;">
            <li
                class="file-dropdown-header text-center fw-bold py-1 edp-bg-seoweedgreen-100 edp-text-green edp-text-verde-dark">
                ARQUIVOS - {{ $files[0]->note->note }}
            </li>

            <div class="file-accordion" data-accordion-id="fileAccordion-{{ $files[0]->note_id }}">
                @php
                    $service = '';
                    $accordionCounter = 0;
                @endphp

                @foreach ($files->sortBy(fn($file) => [$file->service_id === null ? 1 : 0, $file->service->service ?? '', $file->file_name]) as $file)
                    @php
                        $currentServiceId = $file->service_id ?? 'others';
                        $isNewService = $service !== $currentServiceId;

                        if ($isNewService) {
                            $service = $currentServiceId;
                            $accordionCounter++;
                            $service_name = $file->service->service ?? 'OUTROS';
                            $accordionId = 'file-accordion-' . $file->note_id . '-' . $accordionCounter;
                            $collapseId = 'file-collapse-' . $file->note_id . '-' . $accordionCounter;
                        }
                    @endphp

                    @if ($isNewService)
                        @if ($accordionCounter > 1)
            </div>
    </div>
@endif

<div class="file-accordion-item">
    <h6 class="file-accordion-header mt-1 mb-0" id="heading-{{ $accordionId }}">
        <button
            class="file-accordion-button w-100 collapsed edp-bg-seoweedgreen-70 edp-text-green edp-text-verde-dark d-flex align-items-center justify-content-between"
            type="button" data-target="#{{ $collapseId }}"
            onclick="event.stopPropagation(); toggleFileAccordion(this)">
            <span>{{ mb_strtoupper($service_name) }}</span>
            <i class="ri-arrow-down-s-line accordion-icon"></i>
        </button>
    </h6>
    <div id="{{ $collapseId }}" class="file-collapse" style="height: 0;">
        <div class="file-accordion-body py-0">
            @endif
            @php
                $icon = \App\Helpers\FileIcon::getIcon($file->ext)->icon;
                $isTacitAdsFile = in_array($file->id, $adsTacitFileIds, true);
            @endphp
            <li wire:key="file-{{ $file->id }}" class="text-center py-1">
                @if ($isTacitAdsFile)
                    <span class="file-dropdown-item text-center text-muted d-inline-flex align-items-center gap-1"
                        title="Arquivo de ADS tácita. Download bloqueado nesta tela.">
                        <i class="{{ $icon }}"></i> {{ $file->file_name }}
                        <span class="badge bg-warning text-dark">TÁCITO</span>
                        <i class="ri-lock-line"></i>
                    </span>
                @else
                    <a class="file-dropdown-item text-center" href="#"
                        wire:click.prevent="downloadFile({{ $file->id }})" onclick="event.stopPropagation();">
                        <i class="{{ $icon }}"></i> {{ $file->file_name }}
                    </a>
                @endif
            </li>
            @endforeach
        </div>
    </div>
</div>
</div>
</ul>
</div>
@endif

@once
    @push('script')
        <script>
            function toggleFileAccordion(button) {
                const parentAccordion = button.closest('[data-accordion-id]');
                const targetId = button.getAttribute('data-target').replace('#', '');
                const target = document.getElementById(targetId);

                parentAccordion.querySelectorAll('.file-collapse').forEach(collapse => {
                    if (collapse.id !== targetId) {
                        collapse.classList.remove('open');
                        collapse.style.height = '0';

                        const btn = parentAccordion.querySelector(`[data-target="#${collapse.id}"]`);
                        if (btn) btn.classList.remove('open');
                    }
                });

                if (target.classList.contains('open')) {
                    target.classList.remove('open');
                    target.style.height = '0';
                    button.classList.remove('open');
                } else {
                    target.classList.add('open');
                    target.style.height = target.scrollHeight + 'px';
                    button.classList.add('open');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.file-dropdown').forEach(dropdown => {
                    const toggleIcon = dropdown.querySelector('.file-dropdown-toggle-icon');
                    const bsDropdown = bootstrap.Dropdown.getOrCreateInstance(toggleIcon);

                    dropdown.addEventListener('show.bs.dropdown', function() {
                        toggleIcon.classList.remove('text-danger');
                        toggleIcon.classList.add('text-success');
                    });

                    dropdown.addEventListener('hide.bs.dropdown', function() {
                        toggleIcon.classList.remove('text-success');
                        toggleIcon.classList.add('text-danger');
                    });
                });
            });
        </script>
    @endpush
@endonce
