// "Meu painel" (home do usuário): cria os gráficos Chart.js e os atualiza IN-PLACE quando o Livewire envia dados novos.
// Regra do CLAUDE.md/PADRAO_UI §7: nunca substituir `chart.data.datasets` — cada dataset é atualizado por índice, novos entram por push
// e os excedentes saem por splice; o gráfico nunca é recriado nem esvaziado só para "limpar" a tela.
//
// Extensões do payload (ver MyPanelCharts.php): `dataset.gradient = [de, para]` vira degradê (vertical; horizontal em indexAxis y),
// `options.plugins.mpCenter = {text, sub}` escreve o total no centro das roscas e `options.plugins.mpBarLabels = {segments, totals, decimals}`
// escreve o valor dentro de cada faixa das barras empilhadas e o total acima da pilha (dataset.labelColor muda a cor do texto);
// em datasets de linha, `showValues` escreve o valor sobre cada ponto (`decimals`, `valueSuffix`).

const registry = (window.__myPanelCharts = window.__myPanelCharts || { instances: {}, bound: false, tries: 0, defaults: false });

function readConfig(canvas) {
    try {
        return JSON.parse(canvas.dataset.config || 'null');
    } catch (error) {
        return null;
    }
}

/** Troca `gradient` por um backgroundColor "scriptable" que desenha o degradê na área do gráfico. */
function applyGradients(config) {
    const horizontal = config.options && config.options.indexAxis === 'y';

    config.data.datasets.forEach((dataset) => {
        if (!dataset.gradient) {
            return;
        }

        const [from, to] = dataset.gradient;

        dataset.backgroundColor = (context) => {
            const { chart } = context;
            const area = chart.chartArea;

            if (!area) {
                return from;
            }

            const gradient = horizontal
                ? chart.ctx.createLinearGradient(area.left, 0, area.right, 0)
                : chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);

            gradient.addColorStop(0, from);
            gradient.addColorStop(1, to);

            return gradient;
        };
    });

    return config;
}

function setDefaults() {
    if (registry.defaults) {
        return;
    }

    registry.defaults = true;

    const { Chart } = window;

    Chart.defaults.font.family = window.getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#64748b';
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, .94)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { weight: '700' };
    Chart.defaults.plugins.tooltip.boxPadding = 4;

    Chart.register({
        id: 'mpBarLabels',
        afterDatasetsDraw(chart) {
            const config = chart.options.plugins && chart.options.plugins.mpBarLabels;

            if (!config) {
                return;
            }

            const { ctx } = chart;
            const family = Chart.defaults.font.family;
            const format = (value, decimals = 0, suffix = '') => `${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}${suffix}`;
            const bars = chart.data.datasets
                .map((dataset, index) => ({ dataset, index, meta: chart.getDatasetMeta(index) }))
                .filter(({ meta, index }) => meta.type === 'bar' && chart.isDatasetVisible(index));

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            chart.data.labels.forEach((label, position) => {
                let total = 0;
                let top = null;
                let x = null;

                bars.forEach(({ dataset, meta }) => {
                    const element = meta.data[position];
                    const value = Number(dataset.data[position] || 0);

                    if (!element || value <= 0) {
                        return;
                    }

                    total += value;
                    top = top === null ? Math.min(element.y, element.base) : Math.min(top, element.y, element.base);
                    x = element.x;

                    if (config.segments && Math.abs(element.base - element.y) >= 16) {
                        ctx.fillStyle = dataset.labelColor || '#ffffff';
                        ctx.font = `600 11px ${family}`;
                        ctx.fillText(format(value, config.decimals || 0), element.x, element.y + (element.base - element.y) / 2);
                    }
                });

                if (config.totals && total > 0 && top !== null) {
                    ctx.fillStyle = '#0f172a';
                    ctx.font = `800 12px ${family}`;
                    ctx.fillText(format(total, config.decimals || 0), x, top - 11);
                }
            });

            chart.data.datasets.forEach((dataset, index) => {
                const meta = chart.getDatasetMeta(index);

                if (!dataset.showValues || meta.type !== 'line' || !chart.isDatasetVisible(index)) {
                    return;
                }

                ctx.fillStyle = dataset.borderColor || '#0f172a';
                ctx.font = `700 11px ${family}`;

                meta.data.forEach((point, position) => {
                    const value = dataset.data[position];

                    if (value === null || value === undefined) {
                        return;
                    }

                    ctx.fillText(format(value, dataset.decimals || 0, dataset.valueSuffix || ''), point.x, point.y - 12);
                });
            });

            ctx.restore();
        },
    });

    Chart.register({
        id: 'mpCenter',
        afterDraw(chart) {
            const center = chart.options.plugins && chart.options.plugins.mpCenter;

            if (!center || !center.text || chart.config.type !== 'doughnut') {
                return;
            }

            const { ctx, chartArea } = chart;
            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#0f172a';
            ctx.font = `800 ${Math.round(chart.height / 7)}px ${Chart.defaults.font.family}`;
            ctx.fillText(center.text, x, y - 6);
            ctx.fillStyle = '#64748b';
            ctx.font = `600 12px ${Chart.defaults.font.family}`;
            ctx.fillText(center.sub || '', x, y + chart.height / 12);
            ctx.restore();
        },
    });
}

function mount(canvas) {
    const id = canvas.dataset.myPanelChart;
    const current = registry.instances[id];

    if (current && current.canvas === canvas) {
        return;
    }

    if (current) {
        current.destroy();
    }

    const config = readConfig(canvas);

    if (!config) {
        return;
    }

    setDefaults();
    applyGradients(config);
    registry.instances[id] = new window.Chart(canvas, { type: config.type, data: config.data, options: config.options });
}

function syncInPlace(chart, next) {
    applyGradients(next);

    const labels = chart.data.labels;
    labels.splice(0, labels.length, ...next.data.labels);

    next.data.datasets.forEach((dataset, index) => {
        if (chart.data.datasets[index]) {
            Object.assign(chart.data.datasets[index], dataset);
        } else {
            chart.data.datasets.push(dataset);
        }
    });

    if (chart.data.datasets.length > next.data.datasets.length) {
        chart.data.datasets.splice(next.data.datasets.length);
    }

    const center = next.options && next.options.plugins && next.options.plugins.mpCenter;

    if (center && chart.options.plugins && chart.options.plugins.mpCenter) {
        Object.assign(chart.options.plugins.mpCenter, center);
    }

    chart.update();
}

function mountAll() {
    const canvases = document.querySelectorAll('canvas[data-my-panel-chart]');

    if (canvases.length === 0) {
        return;
    }

    // Chart.js vem do CDN (empurrado pela view); espera ele ficar disponível.
    if (!window.Chart) {
        if (registry.tries++ < 100) {
            setTimeout(mountAll, 50);
        }

        return;
    }

    registry.tries = 0;
    canvases.forEach(mount);
}

function bind() {
    if (registry.bound) {
        return;
    }

    registry.bound = true;

    window.addEventListener('my-panel:charts', (event) => {
        const charts = (event.detail && event.detail.charts) || {};

        Object.keys(charts).forEach((id) => {
            const chart = registry.instances[id];

            if (chart) {
                syncInPlace(chart, charts[id]);
            }
        });
    });

    document.addEventListener('livewire:load', () => {
        mountAll();

        if (window.Livewire && window.Livewire.hook) {
            window.Livewire.hook('message.processed', mountAll);
        }
    });
}

bind();

if (document.readyState !== 'loading') {
    mountAll();
} else {
    document.addEventListener('DOMContentLoaded', mountAll);
}
