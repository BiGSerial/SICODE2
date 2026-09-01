window.addEventListener('swalcustom', function(e) {

    const Confirmation = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
    });

    Swal.fire({
        title: e.detail.title,
        html: e.detail.msg,
        icon: e.detail.icon,
        showCancelButton: true,
        confirmButtonText: e.detail.btnOktxt,
        cancelButtonText: e.detail.btnCanceltxt,
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {

            if (e.detail.target) {
                Livewire.emitTo(e.detail.target, e.detail.action, e.detail.chave);
            } else {
                Livewire.emit(e.detail.action, e.detail.chave);
            }

        } else if (
            /* Read more about handling dismissals below */
            result.dismiss === Swal.DismissReason.cancel
        ) {
            Swal.fire(
                e.detail.cancel_titulo,
                e.detail.cancel_msg,
                'success'
            )
        }
    })
});
