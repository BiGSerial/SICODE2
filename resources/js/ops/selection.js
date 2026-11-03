// Contador de seleção em lote das listas operacionais. Uso: <div x-data="opsSelection()"> … <input type="checkbox" data-ops-row> … <x-ops.selection-bar>.
// Conta no navegador (as caixas usam wire:model.defer) e recontarem depois de cada resposta do Livewire (paginação, "selecionar todos").
window.opsSelection = window.opsSelection || function () {
    return {
        count: 0,
        init() {
            this.recount();
            if (window.Livewire && window.Livewire.hook) { window.Livewire.hook('message.processed', () => this.recount()); }
        },
        recount() {
            this.count = this.$root.querySelectorAll('input[data-ops-row]:checked').length;
        },
    };
};
