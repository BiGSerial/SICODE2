<script>
    if (!window.__closureConfirmBound) {
        window.__closureConfirmBound = true;
        window.addEventListener('closure:confirm', function (event) {
            const d = event.detail || {};
            Swal.fire({
                title: d.title || 'Confirmar',
                html: d.html || '',
                icon: d.icon || 'question',
                showCancelButton: true,
                confirmButtonText: d.confirmText || 'Confirmar',
                cancelButtonText: d.cancelText || 'Cancelar',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed && d.action) {
                    Livewire.emit(d.action);
                }
            });
        });
    }
</script>
