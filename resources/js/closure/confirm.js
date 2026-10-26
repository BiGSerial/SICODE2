// Confirmação padronizada do Encerramento.
// Disparo (PHP): $this->dispatchBrowserEvent('closure:confirm', ['title' => ..., 'html' => ..., 'icon' => ..., 'confirmText' => ..., 'action' => 'evento-livewire']);
// Ao confirmar, emite o evento Livewire informado em `action`.
if (!window.__closureConfirmBound) {
    window.__closureConfirmBound = true;

    window.addEventListener('closure:confirm', (event) => {
        const d = event.detail || {};

        window.Swal.fire({
            title: d.title || 'Confirmar',
            html: d.html || '',
            icon: d.icon || 'question',
            showCancelButton: true,
            confirmButtonText: d.confirmText || 'Confirmar',
            cancelButtonText: d.cancelText || 'Cancelar',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed && d.action) {
                window.Livewire.emit(d.action);
            }
        });
    });
}
