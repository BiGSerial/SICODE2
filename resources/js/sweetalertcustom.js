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

            Livewire.emitTo(e.target, e.detail.action);

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
