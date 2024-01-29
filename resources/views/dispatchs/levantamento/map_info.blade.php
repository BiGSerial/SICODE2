@extends('layouts.padrao')

@section('menu')
    {{-- @include('services.analises_pre.menu') --}}
    @include('dispatchs.levantamento.menu')
@endsection

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.awesome-markers/dist/leaflet.awesome-markers.css" />
    <link rel="stylesheet" href="https://unpkg.com/ionicons@4.5.10/dist/css/ionicons.min.css"
        integrity="sha384-EzZXRqPjk6Vor4HHtGV3OxsNc+e3e1ZnU4flLqxt2Iu8b03QE8HxP5geFE1K4GFA" crossorigin="anonymous">

    <style>
        .awesome-marker i {
            font-size: 18px;
            margin-top: 8px;
        }
    </style>
@endpush

@section('content')
    <style>
        #map-container {
            height: 600px;

            /* Altura desejada para o mapa */
            overflow: hidden;
            /* Impede que o mapa ultrapasse os limites do contêiner */
        }
    </style>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-8">
                <div class="card">
                    <div class="card-body">
                        <div id="map-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                @livewire('dispatchs.survey.mapinfo', ['service' => $service->uuid])
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-area-select@1.0.5/dist/Map.SelectArea.min.js"></script>
    <script src="https://unpkg.com/leaflet.awesome-markers/dist/leaflet.awesome-markers.js"></script>
    {{-- <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script> --}}
@endsection


@push('script')
    <script>
        window.addEventListener('alertar', function(e) {

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

                    Livewire.emit(e.detail.action, e.detail.chave)

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
    </script>
@endpush
