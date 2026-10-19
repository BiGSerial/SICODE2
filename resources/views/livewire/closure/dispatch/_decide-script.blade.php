    @once
        @push('script')
            <script>
                window.closureDashDecide = function (button, requestId, decision) {
                    const root = button.closest('[wire\\:id]');
                    const component = root ? window.Livewire.find(root.getAttribute('wire:id')) : null;
                    if (!component) return;
                    const accept = decision === 'accept';
                    const send = (note) => component.call('decidePriority', requestId, decision, note || null);

                    if (!window.Swal) {
                        send(window.prompt(accept ? 'Nota de decisão (opcional):' : 'Motivo da recusa (opcional):'));
                        return;
                    }

                    window.Swal.fire({
                        title: accept ? 'Aceitar prioridade?' : 'Recusar pedido?',
                        text: accept ? 'Todas as Ordens abertas da obra serão marcadas com prioridade.' : 'O solicitante será notificado da recusa.',
                        icon: accept ? 'question' : 'warning',
                        input: 'textarea',
                        inputPlaceholder: 'Nota de decisão (opcional)',
                        showCancelButton: true,
                        confirmButtonText: accept ? 'Aceitar' : 'Recusar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) send(result.value);
                    });
                };
            </script>
        @endpush
    @endonce
