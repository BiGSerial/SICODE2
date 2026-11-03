<div class="imp-banner" role="status">
    <i class="ri-eye-line imp-banner__icon" aria-hidden="true"></i>
    <span class="imp-banner__text">
        <span class="imp-banner__label">Visão como</span>
        <strong class="imp-banner__name">{{ Auth()->User()->name }}</strong>
    </span>
    <a href="{{ route('stopImpersonating') }}" class="imp-banner__stop">
        <i class="ri-logout-box-r-line" aria-hidden="true"></i> Sair da visão
    </a>
</div>
