import { createApp, h, nextTick } from 'vue';

const root = document.getElementById('wall-v2-vue-app');

// Estilo global desta versão comparativa (injetado antes do mount)
const style = document.createElement('style');
style.textContent = `
.wall-vue{position:fixed;inset:0;background:#061321;color:#fff;padding:14px;display:flex;flex-direction:column;gap:12px}
.wall-vue__top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
.wall-vue__title{font-size:20px;font-weight:800;letter-spacing:.04em}
.wall-vue__sub{color:#c9d6e8;font-size:13px}
.wall-vue__meta{display:flex;gap:10px;flex-wrap:wrap;font-size:13px;color:#dce8f5}
.wall-vue__meta span{padding:6px 10px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(255,255,255,.08)}
.wall-vue__loading,.wall-vue__error{flex:1;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700}
.wall-vue__error{color:#FF644B}
.wall-vue__content{flex:1;min-height:0;display:flex;flex-direction:column;gap:10px}
.wall-vue__service{font-size:28px;font-weight:900;letter-spacing:.03em}
.wall-vue__cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}
.wall-vue__card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.16);border-radius:10px;padding:8px;display:flex;flex-direction:column;gap:6px}
.wall-vue__card small{font-size:12px;color:#bdd0e6;text-transform:uppercase}
.wall-vue__card strong{font-size:24px;line-height:1}
.wall-vue__charts{flex:1;min-height:0;display:grid;grid-template-columns:1fr 1fr;gap:10px}
.wall-vue__chart-box{border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.04);border-radius:12px;padding:10px;display:flex;flex-direction:column;min-height:0}
.wall-vue__chart-title{font-size:13px;color:#dce8f5;margin-bottom:6px}
.wall-vue__chart-wrap{flex:1;min-height:0;position:relative}
.wall-vue__chart-wrap canvas{width:100%!important;height:100%!important}
@media (max-width:1100px){.wall-vue__cards{grid-template-columns:repeat(2,minmax(0,1fr))}.wall-vue__charts{grid-template-columns:1fr}}
`;
document.head.appendChild(style);

if (root) {
    const App = {
        data() {
            return {
                endpoint: String(root.dataset.endpoint || ''),
                itemChartsEndpointTemplate: String(root.dataset.itemChartsEndpointTemplate || ''),
                fixedScreenId: Number(root.dataset.screenId || 0),
                wallId: String(root.dataset.wallId || ''),
                payload: {
                    wall: null,
                    updated_at: '',
                    rotation_seconds: 180,
                    refresh_seconds: 60,
                    screens: [],
                },
                screenIndex: 0,
                serviceIndex: 0,
                screenTimer: null,
                rotateRemaining: 0,
                refreshRemaining: 0,
                refreshingItem: false,
                renderingCharts: false,
                loading: true,
                initialLoaded: false,
                error: '',
                queueChart: null,
                flowChart: null,
            };
        },
        computed: {
            currentScreen() {
                return this.payload.screens?.[this.screenIndex] || null;
            },
            currentItem() {
                const items = this.currentScreen?.items || [];
                return items[this.serviceIndex] || null;
            },
            panelTitle() {
                return this.currentItem?.service_name || '-';
            },
            cards() {
                return this.currentItem?.cards || {};
            },
            queueHistogram() {
                return this.currentItem?.queue_histogram || { labels: [], values: [] };
            },
            productionDaily() {
                return this.currentItem?.production_daily || { labels: [], assigned: [], delivered: [] };
            },
        },
        methods: {
            normalize(raw) {
                return {
                    wall: raw?.wall || null,
                    updated_at: raw?.updated_at || '',
                    rotation_seconds: Number(raw?.rotation_seconds || 180),
                    refresh_seconds: Number(raw?.refresh_seconds || 60),
                    screens: Array.isArray(raw?.screens) ? raw.screens : [],
                };
            },
            itemChartsUrl(screenId, serviceId) {
                if (!this.itemChartsEndpointTemplate) throw new Error('Template item/charts não configurado');
                return this.itemChartsEndpointTemplate
                    .replace('__SCREEN__', encodeURIComponent(String(screenId)))
                    .replace('__SERVICE__', encodeURIComponent(String(serviceId)));
            },
            async fetchPayload() {
                if (!this.endpoint) {
                    this.error = 'Endpoint não configurado';
                    this.loading = false;
                    return;
                }
                this.loading = !this.initialLoaded;
                try {
                    const res = await fetch(this.endpoint, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    this.payload = this.normalize(data);
                    this.rotateRemaining = Number(this.payload.rotation_seconds || 180);
                    this.refreshRemaining = Number(this.payload.refresh_seconds || 60);
                    this.error = '';

                    if (this.screenIndex >= this.payload.screens.length) this.screenIndex = 0;
                    const items = this.currentScreen?.items || [];
                    if (this.serviceIndex >= items.length) this.serviceIndex = 0;

                    await nextTick();
                    this.renderCharts();
                    this.initialLoaded = true;
                } catch (e) {
                    this.error = `Falha ao carregar painel Vue: ${e?.message || 'erro desconhecido'}`;
                } finally {
                    this.loading = false;
                }
            },
            async refreshCurrentItem() {
                if (!this.currentScreen || !this.currentItem) return;
                if (this.refreshingItem) return;
                this.refreshingItem = true;
                try {
                    const url = this.itemChartsUrl(this.currentScreen.id, this.currentItem.service_id);
                    const res = await fetch(url, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    const item = this.currentItem;
                    item.cards = data?.cards || item.cards || {};
                    item.queue_histogram = data?.charts?.queue_histogram || item.queue_histogram || { labels: [], values: [] };
                    item.production_daily = data?.charts?.production_daily || item.production_daily || { labels: [], assigned: [], delivered: [] };
                    this.payload.updated_at = data?.updated_at || this.payload.updated_at || '';
                    this.refreshRemaining = Number(this.payload.refresh_seconds || 60);
                    await nextTick();
                    this.renderCharts();
                } catch (e) {
                    console.error('wall-v2-vue refresh item error', e);
                } finally {
                    this.refreshingItem = false;
                }
            },
            nextService() {
                const items = this.currentScreen?.items || [];
                if (!items.length) return;
                this.serviceIndex = (this.serviceIndex + 1) % items.length;
                this.refreshRemaining = Number(this.payload.refresh_seconds || 60);
                nextTick(() => this.renderCharts());
            },
            tick() {
                if (!this.currentItem) return;

                this.rotateRemaining = Math.max(0, Number(this.rotateRemaining || 0) - 1);
                this.refreshRemaining = Math.max(0, Number(this.refreshRemaining || 0) - 1);

                if (this.rotateRemaining === 0) {
                    this.rotateRemaining = Number(this.payload.rotation_seconds || 180);
                    this.nextService();
                }

                if (this.refreshRemaining === 0) {
                    this.refreshRemaining = Number(this.payload.refresh_seconds || 60);
                    this.refreshCurrentItem();
                }
            },
            ensureCharts() {
                if (!window.Chart) return;
                const queueCanvas = document.getElementById('vue-queue-chart');
                const flowCanvas = document.getElementById('vue-flow-chart');
                if (!queueCanvas || !flowCanvas) return;

                if (!this.queueChart) {
                    this.queueChart = new window.Chart(queueCanvas.getContext('2d'), {
                        type: 'bar',
                        data: { labels: [], datasets: [{ label: 'Pilha', data: [], backgroundColor: 'rgba(191,64,83,.72)', borderColor: '#BF4053', borderWidth: 1 }] },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { color: '#FFFFFF', precision: 0 }, grid: { color: 'rgba(255,255,255,.15)' } },
                                x: { ticks: { color: '#FFFFFF' }, grid: { color: 'rgba(255,255,255,.08)' } },
                            },
                            plugins: { legend: { labels: { color: '#FFFFFF' } } },
                        },
                    });
                }

                if (!this.flowChart) {
                    this.flowChart = new window.Chart(flowCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: [],
                            datasets: [
                                { label: 'Atribuído', data: [], backgroundColor: 'rgba(255,100,75,.72)', borderColor: '#FF644B', borderWidth: 1 },
                                { label: 'Entregue', data: [], backgroundColor: 'rgba(40,255,82,.72)', borderColor: '#28FF52', borderWidth: 1 },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            scales: {
                                y: { beginAtZero: true, ticks: { color: '#FFFFFF', precision: 0 }, grid: { color: 'rgba(255,255,255,.15)' } },
                                x: { ticks: { color: '#FFFFFF' }, grid: { color: 'rgba(255,255,255,.08)' } },
                            },
                            plugins: { legend: { labels: { color: '#FFFFFF' } } },
                        },
                    });
                }
            },
            renderCharts() {
                if (this.renderingCharts) return;
                this.renderingCharts = true;
                this.ensureCharts();
                try {
                    if (this.queueChart) {
                        const labels = Array.isArray(this.queueHistogram.labels) ? this.queueHistogram.labels.map((v) => String(v ?? '')) : [];
                        const values = Array.isArray(this.queueHistogram.values) ? this.queueHistogram.values.map((v) => Number(v || 0)) : [];
                        this.queueChart.data.labels = [...labels];
                        this.queueChart.data.datasets[0].data = [...values];
                        this.queueChart.update(0);
                    }
                    if (this.flowChart) {
                        const labels = Array.isArray(this.productionDaily.labels) ? this.productionDaily.labels.map((v) => String(v ?? '')) : [];
                        const assigned = Array.isArray(this.productionDaily.assigned) ? this.productionDaily.assigned.map((v) => Number(v || 0)) : [];
                        const delivered = Array.isArray(this.productionDaily.delivered) ? this.productionDaily.delivered.map((v) => Number(v || 0)) : [];
                        this.flowChart.data.labels = [...labels];
                        this.flowChart.data.datasets[0].data = [...assigned];
                        this.flowChart.data.datasets[1].data = [...delivered];
                        this.flowChart.update(0);
                    }
                } finally {
                    this.renderingCharts = false;
                }
            },
            setupTimers() {
                if (this.screenTimer) clearInterval(this.screenTimer);
                this.screenTimer = setInterval(() => this.tick(), 1000);
            },
            cardNode(label, value) {
                return h('div', { class: 'wall-vue__card' }, [
                    h('small', label),
                    h('strong', String(value ?? 0)),
                ]);
            },
        },
        async mounted() {
            await this.fetchPayload();
            this.setupTimers();
        },
        beforeUnmount() {
            if (this.screenTimer) clearInterval(this.screenTimer);
            if (this.queueChart) this.queueChart.destroy();
            if (this.flowChart) this.flowChart.destroy();
        },
        render() {
            if (this.error) {
                return h('div', { class: 'wall-vue' }, [
                    h('div', { class: 'wall-vue__error' }, this.error),
                ]);
            }

            if (this.loading) {
                return h('div', { class: 'wall-vue' }, [
                    h('div', { class: 'wall-vue__loading' }, 'Carregando dados...'),
                ]);
            }

            if (!this.currentItem) {
                return h('div', { class: 'wall-vue' }, [
                    h('div', { class: 'wall-vue__loading' }, 'Sem itens para exibir.'),
                ]);
            }

            return h('div', { class: 'wall-vue' }, [
                h('div', { class: 'wall-vue__top' }, [
                    h('div', [
                        h('div', { class: 'wall-vue__title' }, 'WALL PRODUÇÃO V2 - VUE (Comparativo)'),
                        h('div', { class: 'wall-vue__sub' }, `Wall: ${this.payload.wall?.name || ('#' + this.wallId)} | Tela: ${this.currentScreen?.name || '-'}`),
                    ]),
                    h('div', { class: 'wall-vue__meta' }, [
                        h('span', `Atualizado: ${this.payload.updated_at || '-'}`),
                        h('span', `Rotação: ${this.rotateRemaining}s`),
                        h('span', `Refresh item: ${this.refreshRemaining}s`),
                    ]),
                ]),

                h('div', { class: 'wall-vue__content' }, [
                    h('div', { class: 'wall-vue__service' }, this.panelTitle),
                    h('div', { class: 'wall-vue__cards' }, [
                        this.cardNode('Total fila', this.cards.queue_total),
                        this.cardNode('OV', this.cards.queue_ov),
                        this.cardNode('Notas', this.cards.queue_notes),
                        this.cardNode('Retornos', this.cards.returned),
                        this.cardNode('Finalizadas', this.cards.previous_done),
                    ]),
                    h('div', { class: 'wall-vue__charts' }, [
                        h('div', { class: 'wall-vue__chart-box' }, [
                            h('div', { class: 'wall-vue__chart-title' }, 'Pilha da atividade'),
                            h('div', { class: 'wall-vue__chart-wrap' }, [
                                h('canvas', { id: 'vue-queue-chart' }),
                            ]),
                        ]),
                        h('div', { class: 'wall-vue__chart-box' }, [
                            h('div', { class: 'wall-vue__chart-title' }, 'Produção dia a dia'),
                            h('div', { class: 'wall-vue__chart-wrap' }, [
                                h('canvas', { id: 'vue-flow-chart' }),
                            ]),
                        ]),
                    ]),
                ]),
            ]);
        },
    };

    try {
        createApp(App).mount(root);
    } catch (e) {
        console.error('wall-v2-vue mount error', e);
        root.innerHTML = '<div class="wall-vue"><div class="wall-vue__error">Falha ao montar Vue: ' + (e?.message || 'erro desconhecido') + '</div></div>';
    }
}
