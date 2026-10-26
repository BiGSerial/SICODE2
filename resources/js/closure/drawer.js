// Painel lateral (offcanvas Bootstrap) do Encerramento — ver <x-closure.drawer>.
// Abrir/fechar sem sair da lista:
//   Blade:  <button type="button" x-data @click="$dispatch('closure:drawer-open', { id: 'inbox-detail' })">…</button>
//   PHP:    $this->dispatchBrowserEvent('closure:drawer-open', ['id' => 'inbox-detail']);
// O foco volta ao gatilho ao fechar (comportamento nativo do offcanvas) e Esc fecha.
if (!window.__closureDrawerBound) {
    window.__closureDrawerBound = true;

    const instance = (id) => {
        const el = document.getElementById(id);

        return el && window.bootstrap ? window.bootstrap.Offcanvas.getOrCreateInstance(el) : null;
    };

    window.addEventListener('closure:drawer-open', (event) => instance((event.detail || {}).id)?.show());
    window.addEventListener('closure:drawer-close', (event) => instance((event.detail || {}).id)?.hide());
}
