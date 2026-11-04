// Seleção do Despacho de Encerramento: vive 100% no navegador (sem round-trip por caixa) e vale entre páginas.
// Uso: <div x-data="closureDispatchSelection({ limit })"> … <x-ops.selection-bar clear="clear()">. O modal abre via evento de janela.
window.closureDispatchSelection = window.closureDispatchSelection || function (config) {
    return {
        selected: [],
        loading: false,
        tick: 0,
        meta: {},
        // Lido pela barra de seleção (`<x-ops.selection-bar>`).
        get count() { return this.selected.length; },
        init() {
            const bump = () => { this.tick++; this.remember(); };
            this.remember();
            // Após cada re-render da lista, reavalia "selecionar página" (as caixas novas já nascem marcadas pelo :checked).
            if (window.Livewire && window.Livewire.hook) { window.Livewire.hook('message.processed', bump); }
            // Depois do despacho, ficam selecionadas só as Ordens recusadas.
            if (window.Livewire) {
                window.Livewire.on('closureOrdersDispatched', (refused) => {
                    this.selected = (refused || []).map(Number);
                });
            }
        },
        // Dados das linhas da página (data-attributes) guardados por id: resumo do modal sem ida ao servidor.
        remember() {
            this.$root.querySelectorAll('[data-order-row]').forEach((tr) => {
                const id = Number(tr.dataset.orderRow);
                this.meta[id] = { id, ordem: tr.dataset.ordem || null, note: tr.dataset.note || null, type: tr.dataset.type || null, comp: tr.dataset.comp || '—', company: tr.dataset.company || null };
            });
        },
        isSelected(id) { return this.selected.includes(id); },
        toggle(id, on) {
            this.selected = on ? (this.selected.includes(id) ? this.selected : [...this.selected, id]) : this.selected.filter((x) => x !== id);
        },
        pageIdsOnScreen() {
            this.tick;
            return Array.from(this.$root.querySelectorAll('[data-order-check]')).map((el) => Number(el.dataset.orderCheck));
        },
        pageCount() { return this.pageIdsOnScreen().length; },
        allVisible() {
            const ids = this.pageIdsOnScreen();
            return ids.length > 0 && ids.every((id) => this.selected.includes(id));
        },
        togglePage() {
            const ids = this.pageIdsOnScreen();
            this.selected = this.allVisible()
                ? this.selected.filter((id) => !ids.includes(id))
                : [...new Set([...this.selected, ...ids])];
        },
        async selectAllFiltered() {
            this.loading = true;
            try {
                const briefs = await this.$wire.filteredOrderBriefs();
                (briefs || []).forEach((b) => { this.meta[Number(b.id)] = b; });
                this.selected = (briefs || []).map((b) => Number(b.id));
            } finally {
                this.loading = false;
            }
        },
        clear() { this.selected = []; },
        openModal(ids) {
            if (!ids.length) { return; }
            this.remember();
            // Abre já no cliente (0 rede): o modal escuta este evento; a prévia de elegibilidade vem depois.
            window.dispatchEvent(new CustomEvent('closure-dispatch-open', { detail: { rows: ids.map((id) => this.meta[id] || { id }) } }));
        },
    };
};
