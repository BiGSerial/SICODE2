// Painel de filtros multi-seleção das listas operacionais (docs/PADRAO_LISTAS_OPERACIONAIS.md §5).
// Uso: <x-ops.filters :payload="$this->listFilterPayload()" /> (trait WithListFilters no componente Livewire).
// A seleção fica pendente no navegador e vai ao servidor numa chamada só ao fechar o menu (commit → $wire.applyListFilters).
// Cascata genérica: uma opção com `p: {region: 'X'}` só aparece se, havendo Regiões marcadas, 'X' estiver entre elas.
window.opsFilterPanel = window.opsFilterPanel || function () {
    const norm = (t) => String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    return {
        defs: [],
        opts: {},
        sel: {},
        q: {},
        committed: '',
        init() {
            const d = JSON.parse(this.$root.dataset.config || '{}');
            this.defs = d.defs || [];
            this.opts = d.options || {};
            this.receive(d.sel || {});
        },
        // Seleção confirmada pelo servidor (carga inicial, chip removido, "limpar filtros").
        receive(sel) {
            this.defs.forEach((def) => {
                this.sel[def.key] = [...((sel || {})[def.key] || [])];
                this.q[def.key] = this.q[def.key] || '';
            });
            this.committed = this.state();
        },
        state() { return JSON.stringify(this.defs.map((def) => this.sel[def.key])); },
        defOf(key) { return this.defs.find((d) => d.key === key); },
        // Opções no escopo dos filtros-pai marcados.
        scoped(key) {
            const depends = (this.defOf(key) || {}).depends || [];
            return (this.opts[key] || []).filter((o) => depends.every((parent) => !(this.sel[parent] || []).length
                || (this.sel[parent] || []).includes(String((o.p || {})[parent] ?? ''))));
        },
        visible(key) {
            const term = norm(this.q[key]);
            const list = this.scoped(key);
            return term ? list.filter((o) => norm(o.l).includes(term)) : list;
        },
        toggle(key, v) {
            this.sel[key] = this.sel[key].includes(v) ? this.sel[key].filter((x) => x !== v) : [...this.sel[key], v];
            this.prune();
        },
        selectVisible(key) {
            this.sel[key] = [...new Set([...this.sel[key], ...this.visible(key).map((o) => o.v)])];
            this.prune();
        },
        clear(key) { this.sel[key] = []; this.prune(); },
        // Filhos que saíram do escopo dos pais marcados são descartados (Região → Regional → Município).
        prune() {
            this.defs.forEach((def) => {
                const ok = new Set(this.scoped(def.key).map((o) => o.v));
                this.sel[def.key] = this.sel[def.key].filter((v) => ok.has(v));
            });
        },
        commit() {
            const now = this.state();
            if (now === this.committed) { return; }
            this.committed = now;
            this.$wire.applyListFilters(Object.fromEntries(this.defs.map((def) => [def.key, this.sel[def.key]])));
        },
    };
};
