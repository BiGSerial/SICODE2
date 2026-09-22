@php
    $activeTokens = $tokens->where('active', true)->count();
    $revokedTokens = $tokens->where('active', false)->count();
    $successfulCalls = $audits->filter(fn ($audit) => $audit->http_status >= 200 && $audit->http_status < 300)->count();
    $lastActivity = $tokens->filter(fn ($token) => $token->last_used_at)->sortByDesc('last_used_at')->first();
@endphp

<div class="api-tokens-page">
    <style>
        .api-tokens-page { --api-navy:#16263d; --api-blue:#2944c9; --api-ink:#18304c; --api-muted:#64748b; color:var(--api-ink); }
        .api-tokens-page .api-header { position:relative; overflow:hidden; border-radius:16px; padding:28px 30px; color:#fff; background:linear-gradient(112deg,#13243a 0%,#1b4561 46%,#117d78 100%); box-shadow:0 12px 28px rgba(23,48,76,.14); }
        .api-tokens-page .api-header::after { content:''; position:absolute; width:280px; height:280px; right:-90px; top:-150px; border:30px solid rgba(255,255,255,.08); border-radius:50%; }
        .api-tokens-page .api-header-content { position:relative; z-index:1; }
        .api-tokens-page .api-kicker { font-size:.72rem; letter-spacing:.13em; text-transform:uppercase; opacity:.72; font-weight:700; }
        .api-tokens-page .api-header h1 { margin:5px 0 4px; font-size:1.7rem; font-weight:700; }
        .api-tokens-page .api-header p { margin:0; color:rgba(255,255,255,.78); }
        .api-tokens-page .api-header-badge { padding:10px 15px; border:1px solid rgba(255,255,255,.2); border-radius:10px; background:rgba(255,255,255,.1); font-size:.82rem; }
        .api-tokens-page .api-stat { height:100%; border:1px solid #e5eaf1; border-radius:12px; background:#fff; box-shadow:0 5px 16px rgba(25,47,72,.06); }
        .api-tokens-page .api-stat-label { color:var(--api-muted); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
        .api-tokens-page .api-stat-value { margin-top:7px; color:var(--api-navy); font-size:1.45rem; font-weight:700; }
        .api-tokens-page .api-stat-icon { width:38px; height:38px; display:grid; place-items:center; border-radius:10px; color:#fff; background:var(--api-blue); }
        .api-tokens-page .api-panel,.api-tokens-page .api-table-card { border:1px solid #e5eaf1; border-radius:14px; background:#fff; box-shadow:0 6px 18px rgba(25,47,72,.06); }
        .api-tokens-page .api-panel-title { color:var(--api-navy); font-weight:700; }
        .api-tokens-page .api-panel-subtitle { color:var(--api-muted); font-size:.86rem; }
        .api-tokens-page .api-create-note { height:100%; padding:24px; border-radius:14px 0 0 14px; color:#fff; background:linear-gradient(145deg,#172942,#236879); }
        .api-tokens-page .api-create-note p { color:rgba(255,255,255,.76); font-size:.87rem; line-height:1.55; }
        .api-tokens-page .api-create-form { padding:24px; }
        .api-tokens-page .form-label { color:var(--api-navy); font-size:.82rem; font-weight:700; }
        .api-tokens-page .form-control { min-height:42px; border-color:#d8e0ea; border-radius:8px; }
        .api-tokens-page .form-control:focus { border-color:var(--api-blue); box-shadow:0 0 0 .2rem rgba(41,68,201,.12); }
        .api-tokens-page .btn-api-primary { border:0; border-radius:8px; background:var(--api-blue); font-weight:700; }
        .api-tokens-page .btn-api-primary:hover { background:#2038ad; }
        .api-tokens-page .api-reveal { border:1px solid #f2d88b; border-radius:10px; background:#fff9e8; }
        .api-tokens-page .api-reveal-title { color:#795b00; font-weight:700; }
        .api-tokens-page .api-reveal .form-control { background:#fff; font-family:monospace; font-size:.82rem; }
        .api-tokens-page .api-table-card { overflow:hidden; }
        .api-tokens-page .api-table-head { padding:18px 20px; border-bottom:1px solid #e8edf3; }
        .api-tokens-page .table { color:var(--api-ink); }
        .api-tokens-page .table thead th { padding:13px 16px; border-bottom:1px solid #dfe6ef; color:#6b7b90; background:#f8fafc; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
        .api-tokens-page .table tbody td { padding:14px 16px; border-color:#edf1f5; font-size:.86rem; }
        .api-tokens-page .table tbody tr:last-child td { border-bottom:0; }
        .api-tokens-page .token-name { color:var(--api-navy); font-weight:700; }
        .api-tokens-page code { color:#3949ab; background:#f0f3ff; border-radius:5px; padding:4px 6px; }
        .api-tokens-page .status-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; border-radius:999px; font-size:.72rem; font-weight:700; }
        .api-tokens-page .status-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
        .api-tokens-page .status-active { color:#16704d; background:#e7f7ef; }
        .api-tokens-page .status-revoked { color:#687586; background:#eef1f4; }
        .api-tokens-page .empty-state { padding:34px 16px !important; color:var(--api-muted); text-align:center; }
        .api-tokens-page .empty-state i { display:block; margin-bottom:8px; color:#aab7c7; font-size:1.5rem; }
        @media (max-width:767.98px) { .api-tokens-page .api-header { padding:22px; } .api-tokens-page .api-header-badge { display:none; } .api-tokens-page .api-create-note { border-radius:14px 14px 0 0; } }
    </style>

    <div class="api-header mb-4">
        <div class="api-header-content d-flex justify-content-between align-items-center gap-3">
            <div><div class="api-kicker">Configuração · Integrações</div><h1>Tokens de aplicação</h1><p>Gerencie o acesso seguro às integrações de importação manual.</p></div>
            <div class="api-header-badge"><i class="fa fa-shield-alt me-2"></i> Bearer · API v1</div>
        </div>
    </div>

    @if (session('message')) <div class="alert alert-success border-0 shadow-sm">{{ session('message') }}</div> @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="api-stat p-3"><div class="d-flex justify-content-between align-items-start"><div><div class="api-stat-label">Tokens ativos</div><div class="api-stat-value">{{ $activeTokens }}</div></div><div class="api-stat-icon"><i class="fa fa-key"></i></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="api-stat p-3"><div class="d-flex justify-content-between align-items-start"><div><div class="api-stat-label">Tokens revogados</div><div class="api-stat-value">{{ $revokedTokens }}</div></div><div class="api-stat-icon" style="background:#738095"><i class="fa fa-ban"></i></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="api-stat p-3"><div class="d-flex justify-content-between align-items-start"><div><div class="api-stat-label">Chamadas bem-sucedidas</div><div class="api-stat-value">{{ $successfulCalls }}</div></div><div class="api-stat-icon" style="background:#117d78"><i class="fa fa-check"></i></div></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="api-stat p-3"><div class="d-flex justify-content-between align-items-start"><div><div class="api-stat-label">Última atividade</div><div class="api-stat-value" style="font-size:1rem">{{ $lastActivity?->last_used_at?->format('d/m/Y H:i') ?? 'Nenhuma' }}</div></div><div class="api-stat-icon" style="background:#8b5ca8"><i class="fa fa-clock-o"></i></div></div></div></div>
    </div>

    @if ($generatedToken)
        <div class="api-reveal p-3 mb-4"><div class="api-reveal-title"><i class="fa fa-exclamation-triangle me-2"></i>Token gerado — copie agora</div><div class="small text-muted mt-1">Por segurança, este token não será exibido novamente.</div><div class="input-group mt-3"><input type="text" class="form-control" value="{{ $generatedToken }}" readonly><button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard.writeText(@js($generatedToken))"><i class="fa fa-copy me-1"></i> Copiar</button><button type="button" class="btn btn-outline-secondary" wire:click="clearGeneratedToken">Fechar</button></div></div>
    @endif

    <div class="api-panel mb-4"><div class="row g-0"><div class="col-lg-4"><div class="api-create-note"><div class="api-kicker">Nova credencial</div><h2 class="h5 mt-2">Cadastrar integração</h2><p class="mb-0">Crie uma chave exclusiva para cada sistema consumidor. O responsável e o uso da credencial ficam registrados na auditoria.</p></div></div><div class="col-lg-8"><div class="api-create-form"><div class="api-panel-title mb-1">Gerar chave de acesso</div><div class="api-panel-subtitle mb-3">Use um nome que identifique claramente a origem da integração.</div><div class="row g-3 align-items-end"><div class="col-md-8"><label class="form-label">Nome da integração</label><input type="text" class="form-control @error('name') is-invalid @enderror" wire:model.defer="name" placeholder="Ex.: Integração SAP manual">@error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror</div><div class="col-md-4 d-grid"><button type="button" class="btn btn-primary btn-api-primary" wire:click="createToken"><i class="fa fa-plus me-1"></i> Gerar token Bearer</button></div></div></div></div></div></div>

    <div class="api-table-card mb-4"><div class="api-table-head d-flex justify-content-between align-items-center gap-2"><div><div class="api-panel-title">Chaves cadastradas</div><div class="api-panel-subtitle">Credenciais disponíveis para as integrações do sistema.</div></div><span class="badge rounded-pill text-bg-light">{{ $tokens->count() }} registro(s)</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Nome</th><th>Prefixo</th><th>Responsável</th><th>Status</th><th>Último uso</th><th></th></tr></thead><tbody>
        @forelse ($tokens as $token)
            <tr><td><div class="token-name">{{ $token->name }}</div><small class="text-muted">Criado em {{ $token->created_at?->format('d/m/Y H:i') }}</small></td><td><code>{{ $token->token_prefix }}…</code></td><td>{{ $token->createdBy?->name ?? '—' }}</td><td><span class="status-badge {{ $token->active ? 'status-active' : 'status-revoked' }}">{{ $token->active ? 'Ativo' : 'Revogado' }}</span></td><td>{{ $token->last_used_at?->format('d/m/Y H:i') ?? 'Nunca' }}<br><small class="text-muted">{{ $token->last_used_ip ?? '' }}</small></td><td class="text-end">@if ($token->active)<button type="button" class="btn btn-sm btn-outline-danger" wire:click="revoke({{ $token->id }})">Revogar</button>@endif</td></tr>
        @empty
            <tr><td colspan="6" class="empty-state"><i class="fa fa-key"></i>Nenhuma chave cadastrada.</td></tr>
        @endforelse
    </tbody></table></div></div>

    <div class="api-table-card"><div class="api-table-head d-flex justify-content-between align-items-center gap-2"><div><div class="api-panel-title">Auditoria das chamadas</div><div class="api-panel-subtitle">Histórico de acessos e resultados da API.</div></div><span class="badge rounded-pill text-bg-light">Últimos 50 registros</span></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Quando</th><th>Token</th><th>IP</th><th>Status</th><th>Recebidos</th><th>Criados / atualizados</th></tr></thead><tbody>
        @forelse ($audits as $audit)
            <tr><td>{{ $audit->created_at?->format('d/m/Y H:i:s') }}</td><td class="token-name">{{ $audit->token?->name ?? '—' }}</td><td>{{ $audit->ip_address ?? '—' }}</td><td><span class="status-badge {{ $audit->http_status >= 200 && $audit->http_status < 300 ? 'status-active' : 'status-revoked' }}">{{ $audit->http_status }}</span></td><td>{{ $audit->records_received }}</td><td>{{ $audit->records_created }} / {{ $audit->records_updated }} · operações {{ $audit->operations_created }} / {{ $audit->operations_updated }}</td></tr>
        @empty
            <tr><td colspan="6" class="empty-state"><i class="fa fa-history"></i>Nenhuma chamada registrada.</td></tr>
        @endforelse
    </tbody></table></div></div>
</div>
