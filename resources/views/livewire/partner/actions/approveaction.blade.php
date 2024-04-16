<div>
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label for="exampleFormControlTextarea1" class="form-label">Inserir
                    Comentário:</label>
                <textarea class="form-control border border-secondary" id="exampleFormControlTextarea1" rows="6"></textarea>
            </div>
            <button class="btn btn-primary" wire:click="agree">Concordar</button>
            <button class="btn btn-danger">Discordar</button>
        </div>
    </div>


    <script>
        Livewire.on('swal2', function(e) {
            alert('funciona');
        })





        // document.addEventListener('swal2', function(e) {

        //     const Confirmation = Swal.mixin({
        //         customClass: {
        //             confirmButton: 'btn btn-success',
        //             cancelButton: 'btn btn-danger'
        //         },
        //         buttonsStyling: false
        //     });

        //     Swal.fire({
        //         title: e.detail.title,
        //         html: e.detail.msg,
        //         icon: e.detail.icon,
        //         showCancelButton: true,
        //         confirmButtonText: e.detail.btnOktxt,
        //         cancelButtonText: e.detail.btnCanceltxt,
        //         reverseButtons: true
        //     }).then((result) => {
        //         if (result.isConfirmed) {

        //             Livewire.emit(e.detail.action, e.detail.chave)

        //         } else if (
        //             /* Read more about handling dismissals below */
        //             result.dismiss === Swal.DismissReason.cancel
        //         ) {
        //             Swal.fire(
        //                 e.detail.cancel_titulo,
        //                 e.detail.cancel_msg,
        //                 'success'
        //             )
        //         }
        //     })
        // });
    </script>
</div>
