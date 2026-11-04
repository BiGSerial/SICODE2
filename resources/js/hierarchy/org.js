// Organograma de Administração → Hierarquia (`<div x-data="hierOrg()">` em livewire/admin/hierarchy/org.blade.php).
// A árvore chega plana (uma linha por usuário) de `admin.user.hierarchy.nodes`; expandir, buscar e filtrar acontecem aqui, sem ida ao servidor.
// O Livewire só entra para selecionar, calcular o impacto de uma mudança (`prepareMove`) e gravar.
// Os mapas pesados (nodes/kids/totals) ficam FORA do estado do Alpine para não virarem proxies reativos.
const norm = (t) => String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const esc = (t) => String(t).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const SOLO = 'grp:solo';
const GUTTER = 28;

window.hierOrg = function () {
    let nodes = {};
    let kids = {};
    let totals = {};
    let rootIds = [];
    let hoverTimer = null;
    let chart = null;
    let matchSet = new Set();

    return {
        loading: true,
        rows: [],
        companies: [],
        covers: {},
        covering: {},
        observers: {},
        q: '',
        company: '',
        onlyCover: false,
        open: {},
        marks: {},
        selected: null,
        dragIds: [],
        overId: null,
        view: 'chart',
        showLinks: true,
        links: [],
        loops: [],
        focusId: null,
        layout: 'top',
        solos: [],
        trayOpen: true,

        boot() {
            this.selected = this.$root.dataset.selected || null;

            // Os eventos do servidor chegam em `window`: troca os ouvintes antigos se o componente for montado de novo.
            (window.__hierOff || []).forEach((off) => off());
            const on = (name, fn) => { window.addEventListener(name, fn); return () => window.removeEventListener(name, fn); };
            window.__hierOff = [
                on('hier-selected', (e) => { this.selected = e.detail.id || null; if (this.selected) { this.reveal(this.selected); } }),
                on('hier-reload', () => this.load()),
                on('hier-modal', (e) => {
                    const el = document.getElementById(e.detail.id);
                    if (!el || !window.bootstrap) { return; }
                    const modal = window.bootstrap.Modal.getOrCreateInstance(el);
                    e.detail.show ? modal.show() : modal.hide();
                }),
            ];

            ['q', 'company', 'onlyCover', 'showLinks'].forEach((k) => this.$watch(k, () => { this.refresh(); this.drawChart(); }));
            ['view', 'focusId'].forEach((k) => this.$watch(k, () => this.$nextTick(() => this.drawChart())));
            this.$nextTick(() => this.bindChartDrag());

            // O Livewire pode esvaziar a área do gráfico ao processar uma resposta (depois do nosso desenho): redesenha se sumiu.
            if (window.livewire && window.livewire.hook) {
                window.livewire.hook('message.processed', () => {
                    if (this.view === 'chart' && !this.loading && this.chartEl() && !this.chartEl().querySelector('svg')) { this.drawChart(); }
                });
            }
            this.load();
        },

        async load() {
            try {
                const res = await fetch(this.$root.dataset.nodesUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) { throw new Error(res.status); }
                this.build(await res.json());
            } catch (e) {
                this.loading = false;
                this.rows = [];
            }
        },

        build(data) {
            nodes = {}; kids = {}; totals = {}; rootIds = [];
            data.nodes.forEach((n) => { nodes[n.i] = n; kids[n.i] = []; });
            data.nodes.forEach((n) => {
                if (n.m && nodes[n.m]) { kids[n.m].push(n.i); } else { rootIds.push(n.i); }
            });

            this.loops = this.breakCycles();

            // Total de descendentes de cada um (percurso iterativo; protege contra ciclo com `seen`).
            const seen = new Set();
            const countOf = (id) => {
                if (totals[id] !== undefined) { return totals[id]; }
                if (seen.has(id)) { return 0; }
                seen.add(id);
                totals[id] = kids[id].reduce((sum, k) => sum + 1 + countOf(k), 0);
                return totals[id];
            };
            Object.keys(nodes).forEach(countOf);

            this.links = data.links || [];
            this.covers = data.covers || {};
            this.observers = data.observers || {};
            this.covering = Object.fromEntries(Object.entries(this.covers).map(([principal, c]) => [c.d, principal]));
            this.companies = [...new Set(data.nodes.map((n) => n.c).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'pt-BR'));

            if (!Object.keys(this.open).length && !this.q) { this.expandTo(2, false); }
            this.loading = false;
            this.refresh();
            this.drawChart();
            if (this.selected && nodes[this.selected]) { this.reveal(this.selected); }
        },

        // `users.manager_id` pode formar um ciclo (A → B → C → A): ninguém do ciclo chega a uma raiz e o ramo sumiria do desenho.
        // Aqui o ciclo é aberto só na tela: fica no topo quem tem a maior equipe na user_closure e o vínculo que fecha o ciclo é guardado em `loops`.
        breakCycles() {
            const reach = new Set();
            const mark = (start) => {
                const stack = [start];
                while (stack.length) {
                    const id = stack.pop();
                    if (reach.has(id)) { continue; }
                    reach.add(id);
                    kids[id].forEach((k) => stack.push(k));
                }
            };
            rootIds.forEach(mark);

            const loops = [];
            Object.keys(nodes).forEach((start) => {
                if (reach.has(start)) { return; }
                const path = [];
                const pos = {};
                let cur = start;
                while (cur && nodes[cur] && !reach.has(cur) && pos[cur] === undefined) { pos[cur] = path.length; path.push(cur); cur = nodes[cur].m; }
                if (!(cur && pos[cur] !== undefined)) { return; }

                const members = path.slice(pos[cur]);
                const top = members.slice().sort((a, b) => (nodes[b].x - nodes[a].x) || (kids[b].length - kids[a].length))[0];
                kids[nodes[top].m] = kids[nodes[top].m].filter((k) => k !== top);
                rootIds.push(top);
                loops.push({ id: top, via: nodes[top].m, members });
                mark(top);
            });

            return loops;
        },

        loopOf(id) { return this.loops.find((l) => l.id === id) || null; },

        direct(id) { return (kids[id] || []).length; },
        total(id) { return totals[id] || 0; },
        nameOf(id) { return (nodes[id] || {}).n || '—'; },
        norm(t) { return norm(t); },
        firstName(id) { return String(this.nameOf(id)).split(' ')[0]; },
        initials(n) { return String(n || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join('').toUpperCase(); },
        coverTitle(id) { const c = this.covers[id]; return c ? `Coberto por ${this.nameOf(c.d)}${c.until ? ' até ' + c.until : ''}` : ''; },

        isOpen(r) { return r.forced || !!this.open[r.key]; },

        toggle(r) {
            this.open[r.key] = !this.isOpen(r);
            this.refresh();
        },

        expandTo(levels, redraw = true) {
            const open = {};
            const walk = (id, depth) => {
                if (depth + 1 < levels && kids[id].length) { open[id] = true; kids[id].forEach((k) => walk(k, depth + 1)); }
            };
            rootIds.forEach((id) => walk(id, 0));
            this.open = open;
            if (redraw) { this.refresh(); }
        },

        reveal(id) {
            for (let cur = nodes[id] && nodes[id].m, guard = 0; cur && nodes[cur] && guard < 100; cur = nodes[cur].m, guard++) { this.open[cur] = true; }
            if (nodes[id] && !kids[id].length && !nodes[id].m) { this.open[SOLO] = true; }
            this.refresh();
            this.$nextTick(() => {
                const el = this.$root.querySelector('.hier-row.is-selected');
                if (el) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
            });
        },

        select(id) {
            this.selected = id;
            this.$wire.select(id);
            if (chart) { this.revealRelated(id); chart.render(); }
        },

        /* ---------- Organograma gráfico (d3-org-chart) ---------- */

        toneOf(company) {
            const tones = ['teal', 'blue', 'green', 'amber', 'red', 'muted'];
            const i = this.companies.indexOf(company);
            return tones[(i < 0 ? tones.length - 1 : i) % tones.length];
        },

        // Dados planos do gráfico: com `focusId` só aquela equipe; sem ele, todas as equipes sob um nó "Organização".
        chartData() {
            const out = [];
            const add = (id, parentId) => {
                const n = nodes[id];
                out.push({ id, parentId, n: n.n, e: n.e, c: n.c, direct: kids[id].length, total: totals[id] || 0 });
            };
            const walk = (id, parentId, guard) => {
                if (guard > 60) { return; }
                add(id, parentId);
                kids[id].forEach((k) => walk(k, id, guard + 1));
            };

            if (this.focusId && nodes[this.focusId]) {
                walk(this.focusId, null, 0);
            } else {
                out.push({ id: '__org', parentId: null, n: 'Organização', root: true, direct: 0, total: 0 });
                rootIds.filter((id) => kids[id].length).forEach((id) => walk(id, '__org', 0));
                out[0].direct = out.filter((d) => d.parentId === '__org').length;
                out[0].total = Object.keys(nodes).length - this.solos.length;
            }

            return out;
        },

        // Linhas de delegação e observação entre cartões que estão no desenho (os dois lados precisam estar nos dados).
        chartLinks(byId) {
            if (!this.showLinks) { return []; }
            return this.links.filter((l) => byId[l.f] && byId[l.t]).map((l) => ({
                id: `${l.k}-${l.f}-${l.t}`,
                from: l.f,
                to: l.t,
                tone: l.k === 'cover' ? 'tone-amber' : 'tone-blue',
                scheduled: l.s,
                label: l.k === 'cover'
                    ? `${this.nameOf(l.f)} é coberto por ${this.nameOf(l.t)}${l.s ? ' (agendada)' : ''}${l.u ? ' até ' + l.u : ''}`
                    : `${this.nameOf(l.f)} observa ${this.nameOf(l.t)}${l.m === 'subtree' ? ' e a equipe' : ''}${l.u ? ' até ' + l.u : ''}`,
            }));
        },

        // Ao selecionar alguém, abre os ramos das pessoas ligadas a ele por cobertura/observação, para a linha aparecer.
        revealRelated(id) {
            if (!chart || !this.showLinks) { return false; }
            const data = chart.getChartState().data;
            const byId = Object.fromEntries(data.map((d) => [d.id, d]));
            let changed = false;
            this.links.filter((l) => l.f === id || l.t === id).forEach((l) => {
                const other = l.f === id ? l.t : l.f;
                for (let cur = byId[other] && byId[other].parentId, g = 0; cur && byId[cur] && g < 60; cur = byId[cur].parentId, g++) {
                    if (!byId[cur]._expanded) { byId[cur]._expanded = true; changed = true; }
                }
            });
            return changed;
        },

        cardHtml(d) {
            const x = d.data;
            if (x.root) {
                return `<div class="hier-card is-root has-kids ${this.layout === 'left' ? 'is-left' : ''}" data-hier-id="__org" title="Solte uma pessoa aqui para deixá-la sem gestor"><i class="ri-building-2-line" aria-hidden="true"></i><strong>Organização</strong><small>${x.direct} equipe(s) · ${x.total} pessoas</small></div>`;
            }

            const tags = [];
            const loop = this.loopOf(x.id);
            if (loop) { tags.push(`<span class="hier-tag tone-red" title="No cadastro, ${esc(this.nameOf(x.id))} responde a ${esc(this.nameOf(loop.via))}, que está abaixo dele: ciclo de gestão"><i class="ri-error-warning-line"></i> Ciclo com ${esc(this.firstName(loop.via))}</span>`); }
            const cover = this.covers[x.id];
            if (cover) { tags.push(`<span class="hier-tag tone-amber" title="${esc(this.coverTitle(x.id))}"><i class="ri-shield-user-line"></i> Coberto por ${esc(this.firstName(cover.d))}</span>`); }
            if (this.covering[x.id]) { tags.push(`<span class="hier-tag tone-blue"><i class="ri-user-follow-line"></i> Cobre ${esc(this.firstName(this.covering[x.id]))}</span>`); }
            if (this.observers[x.id]) { tags.push(`<span class="hier-tag tone-muted" title="Observações vigentes"><i class="ri-eye-line"></i> ${this.observers[x.id]}</span>`); }
            if (x.total) { tags.push(`<span class="hier-tag tone-muted" title="${x.direct} direto(s), ${x.total} no total"><i class="ri-team-line"></i> ${x.direct}${x.total > x.direct ? ' / ' + x.total : ''}</span>`); }

            const cls = ['hier-card', 'tone-' + this.toneOf(x.c), this.selected === x.id ? 'is-selected' : '', matchSet.has(x.id) ? 'is-match' : '', this.focusId === x.id ? 'is-focus' : '', x.total ? 'has-kids' : '', this.layout === 'left' ? 'is-left' : ''].join(' ');

            return `<div class="${cls}" data-hier-id="${esc(x.id)}" draggable="true">
                <div class="hier-card-main"><span class="ops-avatar">${esc(this.initials(x.n))}</span>
                <span class="hier-card-name"><strong title="${esc(x.n)}">${esc(x.n)}</strong><small title="${esc(x.c || '')}">${esc(x.c || '—')}</small></span></div>
                <div class="hier-card-tags">${tags.join('')}</div></div>`;
        },

        async drawChart() {
            const el = this.chartEl();
            if (this.view !== 'chart' || this.loading || !el) { return; }
            // O Livewire pode trocar o elemento do gráfico por um novo: o gráfico antigo (e os eventos de arrastar) ficariam no elemento morto.
            if (chart && chart.getChartState().container !== el) { chart = null; }
            this.bindChartDrag();
            if (!el.clientWidth) { return; }

            const { OrgChart } = await import('d3-org-chart');
            const data = this.chartData();
            const term = norm(this.q.trim());
            matchSet = new Set();

            if (this.filtering()) {
                data.forEach((d) => {
                    if (d.root) { return; }
                    const hit = (!term || norm(d.n).includes(term) || norm(d.e).includes(term))
                        && (!this.company || d.c === this.company)
                        && (!this.onlyCover || this.covers[d.id] || this.covering[d.id] || this.observers[d.id]);
                    if (hit) { matchSet.add(d.id); }
                });
            }

            // O contêiner pode ter sido esvaziado (o Livewire refaz o DOM ao mover alguém): o gráfico antigo ficaria desenhando no vazio.
            if (chart && !el.querySelector('svg')) { chart = null; }

            const fresh = !chart;
            if (fresh) {
                chart = new OrgChart()
                    .container(el)
                    // O botão de abrir/recolher fica numa faixa própria (embaixo no vertical, à direita no horizontal), sem tapar o cartão.
                    .nodeWidth(() => (this.layout === 'left' ? 252 + GUTTER : 252))
                    .nodeHeight((d) => (d.data.root ? 80 : 94) + (this.layout === 'left' ? 0 : GUTTER))
                    .childrenMargin(() => (this.layout === 'left' ? 70 : 46))
                    .defs((state, conns) => `<defs>${conns.map((c) => `
                        <marker id="mk-${c.id}" refX="1" refY="2" markerWidth="500" markerHeight="500" orient="auto"><path d="M0,0 V4 L2,2 Z" class="hier-mk ${c.tone}"></path></marker>`).join('')}</defs>`)
                    .connectionsUpdate(function (c) {
                        this.setAttribute('class', `connection hier-conn ${c.tone}${c.scheduled ? ' is-scheduled' : ''}`);
                        this.setAttribute('marker-end', `url(#mk-${c.id})`);
                        this.setAttribute('pointer-events', 'stroke');
                        this.innerHTML = `<title>${esc(c.label)}</title>`;
                    })
                    .siblingsMargin(() => 18)
                    .compactMarginBetween(() => 18)
                    .compactMarginPair(() => 24)
                    .initialZoom(1)
                    .initialExpandLevel(2)
                    .nodeContent((d) => this.cardHtml(d))
                    .buttonContent(({ node }) => `<div class="hier-expander"><i class="ri-${node.children ? 'subtract' : 'add'}-line"></i> ${node.data._directSubordinates}</div>`)
                    .onNodeClick((node) => { if (node.data.id !== '__org') { this.select(node.data.id); } });
            }

            // Ancestrais dos resultados da busca abertos (e o primeiro resultado centralizado), direto nos dados.
            const byId = Object.fromEntries(data.map((d) => [d.id, d]));
            matchSet.forEach((id) => {
                for (let cur = byId[id] && byId[id].parentId, g = 0; cur && byId[cur] && g < 60; cur = byId[cur].parentId, g++) { byId[cur]._expanded = true; }
            });
            const first = [...matchSet][0];
            if (first) { byId[first]._centered = true; }

            chart.svgWidth(el.clientWidth).svgHeight(el.clientHeight || 640).layout(this.layout).connections(this.chartLinks(byId)).data(data).render();

            if (fresh) {
                // Arrastar um cartão não pode virar "arrastar o fundo": só fora dos cartões o mouse move o desenho.
                chart.getChartState().zoomBehavior.filter((e) => (e.type === 'wheel' || !e.target.closest('.hier-card')) && !e.ctrlKey && !e.button || e.type === 'wheel');
            }

            // Primeira vista em tamanho legível (só centraliza); o botão "Enquadrar" é que encolhe para caber tudo.
            if (!first) { setTimeout(() => chart.fit({ scale: false }), 450); }
        },

        chartFit() { if (chart) { chart.fit(); } },
        chartZoom(dir) { if (chart) { dir > 0 ? chart.zoomIn() : chart.zoomOut(); } },
        chartExpandAll() { if (chart) { chart.expandAll().fit(); } },
        chartCollapseAll() { if (chart) { chart.collapseAll().fit(); } },
        chartLayout() { this.layout = this.layout === 'top' ? 'left' : 'top'; if (chart) { chart.layout(this.layout).render().fit(); } },
        chartImage() { if (chart) { chart.exportImg({ full: true, scale: 2, backgroundColor: '#ffffff' }); } },
        focus(id) { this.focusId = id; this.view = 'chart'; },

        // Arrastar e soltar sobre os cartões (eventos nativos delegados: o d3 recria o HTML a cada desenho).
        chartEl() { return this.$root.querySelector('.hier-chart'); },

        bindChartDrag() {
            const el = this.chartEl();
            if (!el || el.__hierDrag) { return; }
            el.__hierDrag = true;
            const cardOf = (e) => e.target.closest && e.target.closest('[data-hier-id]');
            const clear = () => el.querySelectorAll('.is-over').forEach((c) => c.classList.remove('is-over', 'is-invalid'));

            el.addEventListener('dragstart', (e) => { const c = cardOf(e); if (c && c.dataset.hierId !== '__org') { this.dragStart(e, c.dataset.hierId); } });
            el.addEventListener('dragover', (e) => {
                const c = cardOf(e);
                if (!c || !this.dragIds.length) { return; }
                e.preventDefault();
                const id = c.dataset.hierId;
                clear();
                c.classList.add('is-over');
                if (id !== '__org' && this.invalidTarget(id)) { c.classList.add('is-invalid'); }
            });
            el.addEventListener('drop', (e) => {
                const c = cardOf(e);
                clear();
                if (!c) { return; }
                e.preventDefault();
                this.drop(c.dataset.hierId === '__org' ? null : c.dataset.hierId);
            });
            el.addEventListener('dragend', () => { clear(); this.dragEnd(); });
        },

        filtering() { return !!(this.q.trim() || this.company || this.onlyCover); },

        // Monta a lista plana das linhas visíveis (recalculada só quando algo muda).
        refresh() {
            const term = norm(this.q.trim());
            const filtering = this.filtering();
            let visible = null;

            if (filtering) {
                visible = new Set();
                Object.values(nodes).forEach((n) => {
                    const hit = (!term || norm(n.n).includes(term) || norm(n.e).includes(term))
                        && (!this.company || n.c === this.company)
                        && (!this.onlyCover || this.covers[n.i] || this.covering[n.i] || this.observers[n.i]);
                    if (!hit) { return; }
                    for (let cur = n.i, guard = 0; cur && nodes[cur] && !visible.has(cur) && guard < 100; cur = nodes[cur].m, guard++) { visible.add(cur); }
                });
            }

            const out = [];
            const walk = (id, depth) => {
                if (visible && !visible.has(id)) { return; }
                const n = nodes[id];
                const kidIds = visible ? kids[id].filter((k) => visible.has(k)) : kids[id];
                const row = { key: id, id, n: n.n, e: n.e, c: n.c, depth, hasKids: kidIds.length > 0, forced: filtering && kidIds.length > 0 };
                out.push(row);
                if (row.forced || this.open[id]) { kidIds.forEach((k) => walk(k, depth + 1)); }
            };

            const teams = rootIds.filter((id) => kids[id].length);
            const solos = rootIds.filter((id) => !kids[id].length && (!visible || visible.has(id)));
            this.solos = rootIds.filter((id) => !kids[id].length).map((id) => ({ id, n: nodes[id].n, c: nodes[id].c }));
            teams.forEach((id) => walk(id, 0));

            if (solos.length) {
                const group = { key: SOLO, group: true, depth: 0, label: 'Sem gestor e sem equipe', count: solos.length, hasKids: true, forced: filtering };
                out.push(group);
                if (group.forced || this.open[SOLO]) { solos.forEach((id) => walk(id, 1)); }
            }

            this.rows = out;
        },

        // Texto com o termo da busca destacado (HTML escapado antes).
        hl(text) {
            const term = norm(this.q.trim());
            const s = String(text || '');
            if (!term) { return esc(s); }
            const flat = [...s].map((c) => norm(c));
            if (flat.some((c) => c.length !== 1)) { return esc(s); }
            const at = flat.join('').indexOf(term);
            if (at < 0) { return esc(s); }
            return esc(s.slice(0, at)) + '<mark>' + esc(s.slice(at, at + term.length)) + '</mark>' + esc(s.slice(at + term.length));
        },

        /* ---------- Marcar vários e arrastar ---------- */

        mark(id) {
            if (this.marks[id]) { delete this.marks[id]; } else { this.marks[id] = true; }
        },

        markedIds() { return Object.keys(this.marks); },

        dragStart(e, id) {
            const ids = this.marks[id] ? this.markedIds() : [id];
            this.dragIds = ids;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', ids.join(','));
        },

        dragEnd() {
            this.dragIds = [];
            this.overId = null;
            clearTimeout(hoverTimer);
        },

        // Alvo inválido: ele mesmo, alguém da equipe de quem está sendo arrastado, ou quem já é o gestor de todos.
        invalidTarget(targetId) {
            if (!this.dragIds.length) { return false; }
            const dragged = new Set(this.dragIds);
            for (let cur = targetId, guard = 0; cur && nodes[cur] && guard < 100; cur = nodes[cur].m, guard++) {
                if (dragged.has(cur)) { return true; }
            }
            return this.dragIds.every((id) => nodes[id] && nodes[id].m === targetId);
        },

        dragOver(id) {
            if (this.overId === id) { return; }
            this.overId = id;
            clearTimeout(hoverTimer);
            // Abre o ramo fechado depois de um instante, para soltar dentro de equipes que estão recolhidas.
            if (kids[id] && kids[id].length && !this.open[id] && !this.invalidTarget(id)) {
                hoverTimer = setTimeout(() => { this.open[id] = true; this.refresh(); }, 700);
            }
        },

        drop(targetId) {
            const ids = this.dragIds.slice();
            this.dragEnd();
            if (!ids.length || (targetId && this.invalidTarget(targetId))) { return; }
            this.$wire.prepareMove(ids, targetId);
        },
    };
};
