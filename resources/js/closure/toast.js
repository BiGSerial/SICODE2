// Toast padronizado do Encerramento.
// Disparo (PHP): $this->dispatchBrowserEvent('closure:toast', ['message' => '...', 'type' => 'success|error|warning|info']);
// Disparo (JS):  window.dispatchEvent(new CustomEvent('closure:toast', { detail: { message: '...', type: 'success' } }));
if (!window.__closureToastBound) {
    window.__closureToastBound = true;

    window.addEventListener('closure:toast', (event) => {
        const d = event.detail || {};

        if (!d.message || !window.Swal) {
            return;
        }

        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: d.type || 'success',
            title: d.message,
            showConfirmButton: false,
            timer: d.timer || 4000,
            timerProgressBar: true,
        });
    });
}
