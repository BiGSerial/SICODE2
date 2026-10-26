{{-- Esqueleto do Painel da equipe: mesma grade da tela (cartões, fila, gráficos e tabelas) enquanto os dados carregam. --}}
<div class="tp-skeleton" role="status" aria-live="polite" aria-busy="true">
    <span class="visually-hidden">Carregando o painel da equipe…</span>

    <div class="tp-skel-title"></div>

    <div class="row g-3 mb-3">
        @foreach (range(1, 4) as $i)
            <div class="col-12 col-md-6 col-xl-3"><div class="tp-skel tp-skel-hero"></div></div>
        @endforeach
    </div>

    <div class="tp-skel tp-skel-next mb-4"></div>

    <div class="tp-skel-title"></div>

    <section class="mp-panel mb-3">
        <div class="tp-skel-head"><span class="tp-skel tp-skel-line w-40"></span></div>
        @foreach (range(1, 6) as $i)
            <div class="tp-skel-row">
                <span class="tp-skel tp-skel-dot"></span>
                <span class="tp-skel tp-skel-line w-20"></span>
                <span class="tp-skel tp-skel-line w-10"></span>
                <span class="tp-skel tp-skel-line w-20"></span>
                <span class="tp-skel tp-skel-line w-10"></span>
                <span class="tp-skel tp-skel-line w-20"></span>
            </div>
        @endforeach
    </section>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-5"><div class="tp-skel tp-skel-chart"></div></div>
        <div class="col-12 col-md-6 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
        <div class="col-12 col-md-6 col-xl-3"><div class="tp-skel tp-skel-chart"></div></div>
    </div>

    <div class="tp-skel-title"></div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8"><div class="tp-skel tp-skel-chart"></div></div>
        <div class="col-12 col-xl-4"><div class="tp-skel tp-skel-chart"></div></div>
    </div>
</div>
