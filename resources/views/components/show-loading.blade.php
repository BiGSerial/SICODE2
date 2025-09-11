@props([
    'target' => null, // string|array|null
    'text' => 'CARREGANDO...<br>AGUARDE...',
])

@php
    $targetAttr = $target ? (is_array($target) ? implode(',', $target) : $target) : null;
@endphp

@once
    @push('css')
        <style>
            :root {
                --ld-bg: rgba(18, 18, 20, .72);
                --ld-border: rgba(255, 255, 255, .12);
                --ld-shadow: 0 10px 35px rgba(0, 0, 0, .35);
                --ld-text: #f8fafc;
                --ld-sub: #cbd5e1;
                --ld-a1: #6D32FF;
                --ld-a2: #0CD3F8;
                --ld-a3: #28FF52;
                --ld-a4: #225E66;
            }

            .loading-aurora {
                position: fixed;
                bottom: 24px;
                left: 24px;
                z-index: 9999;
                width: 280px;
                border-radius: 16px;
                background: var(--ld-bg);
                border: 1px solid var(--ld-border);
                box-shadow: var(--ld-shadow);
                backdrop-filter: blur(10px);
                color: var(--ld-text);
                overflow: hidden;
            }

            .loading-aurora::before {
                content: "";
                position: absolute;
                inset: -60% -60% auto auto;
                width: 280%;
                height: 280%;
                background: conic-gradient(from 0deg, var(--ld-a1), var(--ld-a2), var(--ld-a3), var(--ld-a4), var(--ld-a1));
                filter: blur(28px) saturate(120%);
                opacity: .35;
                animation: ld-rotate 12s linear infinite;
            }

            .ld-inner {
                position: relative;
                display: grid;
                grid-template-columns: 60px 1fr;
                gap: 12px;
                padding: 14px 16px 12px;
                align-items: center;
            }

            .ld-gauge {
                position: relative;
                width: 48px;
                height: 48px;
            }

            .ld-ring {
                position: absolute;
                inset: 0;
                border-radius: 50%;
                background: conic-gradient(from 0deg, var(--ld-a1) 0 25%, transparent 25% 100%);
                -webkit-mask: radial-gradient(farthest-side, transparent 62%, #000 63%);
                mask: radial-gradient(farthest-side, transparent 62%, #000 63%);
                animation: ld-spin 1s linear infinite;
                box-shadow: 0 0 0 1px rgba(255, 255, 255, .06) inset;
            }

            .ld-dot {
                position: absolute;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #fff;
                top: 2px;
                left: 50%;
                transform: translate(-50%, 0);
                filter: drop-shadow(0 0 8px rgba(255, 255, 255, .65));
                animation: ld-dot 1s ease-in-out infinite;
            }

            .ld-center {
                position: absolute;
                inset: 0;
                display: grid;
                place-items: center;
            }

            .ld-pill {
                position: absolute;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: rgba(0, 0, 0, .32);
                border: 1px solid rgba(255, 255, 255, .15);
                backdrop-filter: blur(2px);
                transform: translateZ(0);
            }

            .ld-counter {
                position: relative;
                z-index: 1;
                font-variant-numeric: tabular-nums;
                font-weight: 700;
                font-size: .95rem;
                color: #fff;
                text-shadow: 0 1px 2px rgba(0, 0, 0, .35);
            }

            .ld-text {
                line-height: 1.2;
                font-size: .95rem;
                font-weight: 600;
            }

            .ld-sub {
                margin-top: 4px;
                font-size: .8rem;
                color: var(--ld-sub);
            }

            .ld-bar {
                position: relative;
                height: 4px;
                border-radius: 999px;
                overflow: hidden;
                margin: 10px 16px 14px;
                background: rgba(255, 255, 255, .12);
            }

            .ld-bar::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, .35) 25%, rgba(255, 255, 255, .85) 50%, rgba(255, 255, 255, .35) 75%, transparent 100%);
                width: 140%;
                transform: translateX(-100%);
                animation: ld-sweep 1.4s ease-in-out infinite;
            }

            .ld-dots {
                display: inline-flex;
                gap: 2px;
                margin-left: 2px;
                vertical-align: baseline;
            }

            .ld-dots span {
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: currentColor;
                opacity: .35;
                animation: ld-bounce 1s infinite;
            }

            .ld-dots span:nth-child(2) {
                animation-delay: .15s
            }

            .ld-dots span:nth-child(3) {
                animation-delay: .3s
            }

            @keyframes ld-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            @keyframes ld-rotate {
                to {
                    transform: rotate(360deg);
                }
            }

            @keyframes ld-sweep {
                0% {
                    transform: translateX(-100%);
                }

                50% {
                    transform: translateX(10%);
                }

                100% {
                    transform: translateX(100%);
                }
            }

            @keyframes ld-bounce {

                0%,
                80%,
                100% {
                    transform: translateY(0);
                    opacity: .35;
                }

                40% {
                    transform: translateY(-3px);
                    opacity: 1;
                }
            }

            @keyframes ld-dot {

                0%,
                100% {
                    transform: translate(-50%, 0) scale(1);
                }

                50% {
                    transform: translate(-50%, 0) scale(1.2);
                }
            }

            @media (prefers-reduced-motion: reduce) {

                .loading-aurora::before,
                .ld-ring,
                .ld-bar::before,
                .ld-dots span,
                .ld-dot {
                    animation: none !important;
                }
            }
        </style>
    @endpush
@endonce

<div class="loading-aurora" x-data="loader()" x-init="init()" role="status" aria-live="polite" aria-busy="true"
    wire:loading.delay @if ($targetAttr) wire:target="{{ $targetAttr }}" @endif>
    <div class="ld-inner">
        <div class="ld-gauge">
            <div class="ld-ring"><span class="ld-dot"></span></div>
            <div class="ld-center">
                <div class="ld-pill"></div>
                <div class="ld-counter" x-text="percent.toFixed(0) + '%'">0%</div>
            </div>
        </div>

        <div>
            <div class="ld-text">
                {!! $text !!}
                <span class="ld-dots" aria-hidden="true"><span></span><span></span><span></span></span>
            </div>
            <div class="ld-sub">Processando sua solicitação…</div>
        </div>
    </div>

    <div class="ld-bar"></div>
</div>

@push('script')
    <script>
        window.__LD = window.__LD || {
            active: 0
        };

        function loader() {
            return {
                el: null,
                percent: 0,
                running: false,
                rafId: null,
                trickleId: null,

                // CORREÇÃO 2: A função não recebe mais 'el' como parâmetro.
                init() {
                    // CORREÇÃO 3: Usamos this.$el, que é injetado pelo Alpine no contexto do componente.
                    // Isso garante que sempre teremos um Node válido.
                    this.el = this.$el;

                    const compute = () => {
                        const visible = this.isVisible();
                        if (visible && !this.running) this.start();
                        if (!visible && this.running) this.stop(true);
                    };
                    compute();

                    const mo = new MutationObserver(compute);
                    // CORREÇÃO 4: Observamos o this.$el que agora é uma referência garantida.
                    mo.observe(this.el, {
                        attributes: true,
                        attributeFilter: ['style', 'class']
                    });

                    if (window.Livewire && Livewire.hook) {
                        Livewire.hook('message.sent', () => setTimeout(compute, 0));
                        Livewire.hook('message.processed', () => setTimeout(compute, 0));
                    }

                    window.addEventListener('beforeunload', () => this.cleanup());
                },

                isVisible() {
                    // Nenhuma mudança necessária aqui, pois 'this.el' já foi definido corretamente no init().
                    const style = window.getComputedStyle(this.el);
                    return style.display !== 'none' && style.visibility !== 'hidden';
                },

                start() {
                    this.running = true;
                    window.__LD.active++;
                    this.percent = 0;

                    this.trickleId = setInterval(() => {
                        if (!this.running) return;
                        let inc = 0;
                        if (this.percent < 25) inc = Math.random() * 5 + 3;
                        else if (this.percent < 65) inc = Math.random() * 3 + 2;
                        else if (this.percent < 85) inc = Math.random() * 2 + 1;
                        else if (this.percent < 97) inc = Math.random() * 1;
                        else inc = 0;
                        this.percent = Math.min(this.percent + inc, 99);
                    }, 140);

                    const breathe = () => {
                        if (!this.running) return;
                        this.rafId = requestAnimationFrame(breathe);
                    };
                    this.rafId = requestAnimationFrame(breathe);
                },

                stop(finish = false) {
                    if (!this.running) return;
                    this.running = false;

                    if (finish) {
                        const start = this.percent,
                            dur = 240,
                            t0 = performance.now();
                        const step = (t) => {
                            const k = Math.min(1, (t - t0) / dur);
                            const eased = 1 - (1 - k) * (1 - k);
                            this.percent = start + (100 - start) * eased;
                            if (k < 1) this.rafId = requestAnimationFrame(step);
                            else this.cleanup();
                        };
                        this.rafId = requestAnimationFrame(step);
                    } else {
                        this.cleanup();
                    }
                },

                cleanup() {
                    if (this.trickleId) {
                        clearInterval(this.trickleId);
                        this.trickleId = null;
                    }
                    if (this.rafId) {
                        cancelAnimationFrame(this.rafId);
                        this.rafId = null;
                    }
                    this.percent = 0;
                    window.__LD.active = Math.max(0, window.__LD.active - 1);
                },
            }
        }
    </script>
@endpush
