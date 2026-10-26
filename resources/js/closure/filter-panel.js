// Painel de filtros multi-select do Despacho (Região > Regional > Município + Status usuário), em cascata.
// Uso: <div x-data="closureFilterPanel()" data-options='{"options": {...}, "sel": {...}}'> + <x-closure.multi-select …>.
// A seleção fica pendente no cliente e vai ao servidor numa chamada só ao fechar o menu (commit → $wire.applyMultiFilters).
window.closureFilterPanel = window.closureFilterPanel || function () {
    const norm = (t) => String(t || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const keys = ['regions', 'regionals', 'cityCodes', 'statusUsers'];
    return {
        more: false,
        opts: { regions: [], regionals: [], cities: [], statusUsers: [] },
        sel: { regions: [], regionals: [], cityCodes: [], statusUsers: [] },
        q: { regions: '', regionals: '', cityCodes: '', statusUsers: '' },
        committed: '',
        init() {
            this.load();
            if (window.Livewire && window.Livewire.hook) { window.Livewire.hook('message.processed', () => this.load()); }
        },
        // Lê opções e seleção do servidor; não sobrescreve a seleção pendente se o servidor não mudou.
        load() {
            const d = JSON.parse(this.$root.dataset.options || '{}');
            this.opts = d.options || this.opts;
            const server = JSON.stringify(d.sel || {});
            if (server !== this.committed) {
                this.committed = server;
                keys.forEach((k) => { this.sel[k] = [...((d.sel || {})[k] || [])]; });
            }
        },
        state() { return JSON.stringify(Object.fromEntries(keys.map((k) => [k, this.sel[k]]))); },
        regionalOptions() {
            return this.sel.regions.length ? this.opts.regionals.filter((o) => this.sel.regions.includes(o.g)) : this.opts.regionals;
        },
        cityOptions() {
            return this.opts.cities.filter((o) => (!this.sel.regions.length || this.sel.regions.includes(o.g))
                && (!this.sel.regionals.length || this.sel.regionals.includes(o.r)))
                .map((o) => ({ v: o.v, l: o.l, n: o.n }));
        },
        visible(key, list) {
            const term = norm(this.q[key]);
            return term ? list.filter((o) => norm(o.l).includes(term)) : list;
        },
        buttonLabel(key, label) { return this.sel[key].length ? label + ' (' + this.sel[key].length + ')' : label; },
        toggle(key, v) {
            this.sel[key] = this.sel[key].includes(v) ? this.sel[key].filter((x) => x !== v) : [...this.sel[key], v];
            this.prune();
        },
        selectVisible(key, list) {
            this.sel[key] = [...new Set([...this.sel[key], ...this.visible(key, list).map((o) => o.v)])];
            this.prune();
        },
        clear(key) { this.sel[key] = []; this.prune(); },
        // Cascata: Regional/Município que saíram do escopo das Regiões/Regionais escolhidas são descartados.
        prune() {
            const regionals = new Set(this.regionalOptions().map((o) => o.v));
            this.sel.regionals = this.sel.regionals.filter((v) => regionals.has(v));
            const cities = new Set(this.cityOptions().map((o) => o.v));
            this.sel.cityCodes = this.sel.cityCodes.filter((v) => cities.has(v));
        },
        commit() {
            const now = this.state();
            if (now === this.committed) { return; }
            this.committed = now;
            this.$wire.applyMultiFilters(this.sel.regions, this.sel.regionals, this.sel.cityCodes, this.sel.statusUsers);
        },
    };
};
