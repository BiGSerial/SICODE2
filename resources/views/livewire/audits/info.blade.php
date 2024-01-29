<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />
    <!-- Modal -->
    <div class="modal fade" id="audit_info" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="audit_infoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="audit_infoLabel">Auditoria de Açoes</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($this->auditprod)
                        @foreach ($this->auditprod as $audit)
                            <div class="card">
                                <h6 class="card-header">Ação: {{ mb_strtoupper($audit->action) }}</h6>
                                <div class="card-body py-0">
                                    <dl class="row">
                                        <dt class="col-sm-4">Usuario:</dt>
                                        <dd class="col-sm-8">{{ $audit->User->name }}</dd>
                                        <dt class="col-sm-4">Data:</dt>
                                        <dd class="col-sm-8">{{ date('d/m/Y - H:i:s', strToTime($audit->created_at)) }}
                                        </dd>
                                        <dt class="col-sm-4">Modelo:</dt>
                                        <dd class="col-sm-8">{{ $audit->model_class }}</dd>
                                        <dt class="col-sm-4">Mudanças:</dt>
                                        @if ($audit->before)
                                            @php
                                                $before = json_decode($audit->before);
                                                $after = json_decode($audit->after);

                                                unset($after->created_at);
                                                unset($after->updated_at);
                                                $count = 0;
                                            @endphp

                                            @foreach ($after as $key => $value)
                                                @if (isset($before->$key) && $before->$key !== $value)
                                                    @if ($count != 0)
                                                        <dt class="col-sm-4"></dt>
                                                    @endif
                                                    <dd class="col-sm-8"><span
                                                            class="fw-bold">{{ $key }}:</span>
                                                        {{ $before->$key ? $before->$key : 'N/A' }} <span
                                                            class="fw-bold"> =></span>
                                                        {{ $value ? $value : 'N/A' }}
                                                    </dd>
                                                    @php
                                                        $count++;
                                                    @endphp
                                                @endif
                                            @endforeach
                                        @endif




                                    </dl>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
