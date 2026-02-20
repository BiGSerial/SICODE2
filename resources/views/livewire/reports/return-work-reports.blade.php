@php
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        .rw-page {
            --rw-bg: #f6f7fb;
            --rw-surface: #ffffff;
            --rw-ink: #1f2933;
            --rw-muted: #6b7280;
            --rw-accent: #0f766e;
            --rw-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--rw-bg);
            padding: 1.5rem 0;
        }

        .rw-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .rw-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .rw-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .rw-filter-card {
            background-color: var(--rw-surface);
            border: 1px solid var(--rw-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .rw-filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--rw-muted);
        }

        .rw-table-card {
            background: var(--rw-surface);
            border: 1px solid var(--rw-border);
            border-radius: 0;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .rw-table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .rw-pagination-bar {
            background: #fff;
            border: 1px solid var(--rw-border);
            padding: 0.75rem 1rem;
        }

        .rw-pagination-bar.top {
            margin-bottom: 0.75rem;
        }

        .rw-pagination-bar.bottom {
            margin-top: 0.75rem;
        }
    </style>
@endpush

<div class="rw-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="rw-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>RELATÓRIO DE INFORMES REJEITADOS</h2>
                <div class="meta">Base de retornos `ReturnWork` com filtros e exportação em fila</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" wire:click="clearFilters">
                    <i class="ri-filter-off-line me-1"></i> Limpar filtros
                </button>
                <button class="btn btn-light btn-sm text-dark" wire:click="exportToExcel" wire:loading.attr="disabled"
                    wire:target="exportToExcel">
                    <span wire:loading.remove wire:target="exportToExcel">
                        <i class="ri-file-excel-2-line me-1"></i> Exportar
                    </span>
                    <span wire:loading wire:target="exportToExcel">
                        <i class="ri-loader-4-line me-1"></i> Gerando...
                    </span>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-3">
                <div class="rw-filter-card">
                    <h6>Período da rejeição</h6>
                    <div class="form-floating mb-2">
                        <input type="date" class="form-control border border-secondary" wire:model.lazy="dt_in"
                            max="{{ date('Y-m-d') }}" id="rw-dt-in">
                        <label for="rw-dt-in">Data inicial</label>
                    </div>
                    <div class="form-floating">
                        <input type="date" class="form-control border border-secondary" wire:model.lazy="dt_out"
                            max="{{ date('Y-m-d') }}" id="rw-dt-out">
                        <label for="rw-dt-out">Data final</label>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="rw-filter-card">
                    <h6>Pesquisa</h6>
                    <div class="form-floating">
                        <input type="text" class="form-control border border-secondary" wire:model.debounce.500ms="search"
                            id="rw-search" placeholder="Buscar">
                        <label for="rw-search">Nota/OV ou Ordem</label>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="rw-filter-card">
                    <h6>Classificações</h6>
                    <label class="form-label mb-1 small text-muted">Categoria(s)</label>
                    <select class="form-select form-select-sm mb-2" wire:model="categoryValues" multiple size="4">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                    <div class="form-floating">
                        <select class="form-select border border-secondary" wire:model="perPage" id="rw-per-page">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                        </select>
                        <label for="rw-per-page">Registros por página</label>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-3">
                <div class="rw-filter-card">
                    <h6>Empreiteira e serviço</h6>
                    <label class="form-label mb-1 small text-muted">Empreiteira(s)</label>
                    <select class="form-select form-select-sm mb-2" wire:model="companyIds" multiple size="4">
                        @foreach ($companies as $company)
                            <option value="{{ data_get($company, 'id') }}">{{ data_get($company, 'name') }}</option>
                        @endforeach
                    </select>
                    <label class="form-label mb-1 small text-muted">Serviço(s)</label>
                    <select class="form-select form-select-sm" wire:model="serviceIds" multiple size="4">
                        @foreach ($services as $service)
                            <option value="{{ data_get($service, 'uuid') }}">{{ data_get($service, 'service') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if ($rows->count())
            <div class="rw-pagination-bar top">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <small>
                            Exibindo {{ $rows->firstItem() }} até {{ $rows->lastItem() }} de {{ $rows->total() }}
                        </small>
                    </div>
                </div>
            </div>
        @endif

        <div class="rw-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="ri-list-check-2 me-1"></i>Informes Rejeitados</strong>
            </div>
            @if (!$rows->count())
                <div class="card-body">
                    <h5 class="text-center text-muted mb-0">Nenhum informe rejeitado encontrado.</h5>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Data rejeição</th>
                                <th>Informe</th>
                                <th>Nota</th>
                                <th>Empreiteira</th>
                                <th>Serviço rejeitou</th>
                                <th>Categoria</th>
                                <th>Quem rejeitou</th>
                                <th>Usuário informou</th>
                                <th>Criado por</th>
                                <th>Empresa criador</th>
                                <th>Criação informe</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $workreport = $row->Workreport;
                                    $creator = $workreport?->User;
                                    $creatorCompany =
                                        $creator?->Employee?->Contract?->company?->name ?? $creator?->Company?->name ?? '—';
                                @endphp
                                <tr>
                                    <td>{{ Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="fw-semibold">{{ $row->work_report_id }}</td>
                                    <td>{{ $workreport?->Note?->note ?? '—' }}</td>
                                    <td>{{ $workreport?->Company?->name ?? '—' }}</td>
                                    <td>{{ $row->Service?->service ?? '—' }}</td>
                                    <td><span class="badge bg-secondary">{{ $row->category ?? '—' }}</span></td>
                                    <td>{{ $row->User?->name ?? '—' }}</td>
                                    <td>{{ $workreport?->informer ?: ($creator?->name ?? '—') }}</td>
                                    <td>{{ $creator?->name ?? '—' }}</td>
                                    <td>{{ $creatorCompany }}</td>
                                    <td>{{ $workreport?->created_at ? Carbon::parse($workreport->created_at)->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td>{{ $row->text_obs ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($rows->count())
            <div class="rw-pagination-bar bottom">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $rows->onEachSide(1)->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <small>
                            Exibindo {{ $rows->firstItem() }} até {{ $rows->lastItem() }} de {{ $rows->total() }}
                        </small>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
