@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">Critérios e atividades</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3">
            <h1 class="dash-title">Critérios e atividades</h1>
            <div class="dash-subtitle">Defina quais Notas entram no Pool, qual atividade cada ciclo cria na pilha do usuário </div>
        </div>

        @include('quality._feedback')

        <div class="row g-3">
            <div class="col-xl-7">
                <div class="table-card p-3 mb-3">
                    <div class="chart-title">Critérios do Pool (Notas)</div>
                    <div class="chart-subtitle mb-3">Mesmo modelo do cadastro de Serviços: regras de inclusão valem por “ou”; regras de exclusão, por “e”. Sem critérios o Pool fica vazio.</div>
                    <div class="small text-uppercase text-muted fw-bold mb-1">Regras fixas</div>
                    <ul class="small">@foreach ($fixedRules as $rule)<li>{{ $rule }}</li>@endforeach</ul>
                    @include('quality._rules-list', ['rules' => $rules, 'deletable' => true])

                    <hr>
                    <form method="post" action="{{ route('quality.rules.store') }}">
                        @csrf
                        <div class="fw-bold small mb-2">Adicionar critério</div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Campo da Nota</label>
                                <select name="column_search" class="form-select form-select-sm" required>
                                    <option value="">Selecione</option>
                                    @foreach ($columns as $column)<option value="{{ $column }}">{{ \App\Support\QualityUi::noteColumn($column) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Condição</label>
                                <select name="condition" class="form-select form-select-sm" required>
                                    <option value="">Selecione</option>
                                    @foreach ($conditions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-5"><label class="form-label small mb-1">Valor (listas: separe por vírgula)</label><input name="value" class="form-control form-control-sm" required></div>
                        </div>
                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="exclusion" value="1" id="excl1"><label class="form-check-label small" for="excl1">Regra de exclusão (remove do Pool as Notas que baterem)</label></div>

                        <details class="mb-3">
                            <summary class="small text-muted">Adicionar segunda condição (“e”)</summary>
                            <div class="row g-2 mt-1">
                                <div class="col-md-4">
                                    <select name="column_search2" class="form-select form-select-sm">
                                        <option value="">Campo</option>
                                        @foreach ($columns as $column)<option value="{{ $column }}">{{ \App\Support\QualityUi::noteColumn($column) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="condition2" class="form-select form-select-sm"><option value="">Condição</option>@foreach ($conditions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                                </div>
                                <div class="col-md-5"><input name="value2" class="form-control form-control-sm" placeholder="Valor"></div>
                            </div>
                            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="exclusion2" value="1" id="excl2"><label class="form-check-label small" for="excl2">Exclusão na 2ª condição</label></div>
                        </details>
                        <button class="btn btn-sm btn-primary btn-dash"><i class="ri-add-line"></i> Adicionar critério</button>
                    </form>
                </div>
            </div>

            <div class="col-xl-5">
                <form method="post" action="{{ route('quality.settings.save') }}">
                    @csrf @method('PUT')
                    <div class="table-card p-3 mb-3">
                        <div class="chart-title">Atividade de cada ciclo</div>
                        <div class="chart-subtitle mb-3">1º ciclo: a atividade é criada na pilha do usuário quando o N1 despacha. Quando o N2 aprova o 1º ciclo, essa atividade é encerrada e a do 2º ciclo é aberta automaticamente para o mesmo usuário e empresa. Ao aprovar o 2º ciclo, encerra-se a atividade e a Qualidade.</div>
                        <label class="form-label small mb-1">1º ciclo</label>
                        <select name="activity_project" class="form-select form-select-sm mb-2" required>
                            <option value="">Selecione a atividade</option>
                            @foreach ($services as $service)<option value="{{ $service->uuid }}" @selected(($activities['PROJECT'] ?? null) === $service->uuid)>{{ $service->service }}</option>@endforeach
                        </select>
                        <label class="form-label small mb-1">2º ciclo</label>
                        <select name="activity_budget" class="form-select form-select-sm" required>
                            <option value="">Selecione a atividade</option>
                            @foreach ($services as $service)<option value="{{ $service->uuid }}" @selected(($activities['BUDGET'] ?? null) === $service->uuid)>{{ $service->service }}</option>@endforeach
                        </select>
                    </div>
<button class="btn btn-primary btn-dash"><i class="ri-save-line"></i> Salvar atividades</button>
                </form>
            </div>
        </div>
    </div>
@endsection
