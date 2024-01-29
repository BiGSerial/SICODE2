<div>
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

    <!-- Modal -->
    <div class="modal fade" id="singleMapPosition" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($wpa)
                        <div class="row">
                            <div class="col-8">
                                <div id="map-container"></div>
                            </div>
                            <div class="col-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Nota/OV </h4>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Cliente:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Nota/OV:</dt>
                                            <dt class="col-sm-9">300003000</dt>

                                            <dt class="col-sm-3">Latitude, Longitude</dt>
                                            <dt class="col-sm-9">300003000</dt>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-area-select@1.0.5/dist/Map.SelectArea.min.js"></script>
    <script src="https://unpkg.com/leaflet.awesome-markers/dist/leaflet.awesome-markers.js"></script>

    <script>
        var map;
        var textos = [];
        var todasCoordenadas = [];

        document.addEventListener('DOMContentLoaded', function() {
            map = L.map('map-container').setView([-20.3155, -40.3128], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        });

        window.addEventListener('update_marks', event => {
            console.log(event.detail.wpa);

            if (event.detail.clear) {
                todasCoordenadas = [];
                map.eachLayer(function(layer) {
                    if (layer instanceof L.Marker) {
                        map.removeLayer(layer);
                    }
                });
            }


            event.detail.wpa.forEach(function(marcador) {
                var iconColor = marcador.color;
                var iconIcon = marcador.icon;

                todasCoordenadas.push(marcador.coordenadas);

                var icon = L.AwesomeMarkers.icon({
                    icon: "info",
                    markerColor: iconColor,
                });

                var marcadorLeaflet = L.marker(marcador.coordenadas, {
                    icon: icon
                }).addTo(map);
                marcadorLeaflet.bindPopup(`
                <strong>Nota:</strong> ${marcador.nota}<br>
                <strong>DD:</strong> ${marcador.dd}<br>
                <strong>Serviço:</strong> ${marcador.service}<br>
                <strong>Grupo2:</strong> ${marcador.group2}<br>
                <strong>Material:</strong> ${marcador.material}<br>
                <strong>Municipio:</strong> ${marcador.municipio}<br>
                <strong>Usuario:</strong> ${marcador.equipe}<br>
                <strong>Status:</strong> ${marcador.status}<br>
                <strong>Coordenada:</strong> [${marcador.coordenadas[0]}, ${marcador.coordenadas[1]}]<br>
                (${marcador.nstat},  ${marcador.estat})<br>
                
                `);

                marcadorLeaflet.on('click', function(e) {
                    if (e.originalEvent.ctrlKey) {
                        textos.push(marcador.mensagem);
                        document.getElementById('coordenadas').value = textos.join(', ');
                    }
                });
            });

            var bounds = L.latLngBounds(todasCoordenadas);
            var sw = bounds.getSouthWest();
            var ne = bounds.getNorthEast();
            var distancia = sw.distanceTo(ne);

            console.log(distancia);

            var nivelDeZoom = distancia > 10000 ? 8 : 15;

            map.setView(bounds.getCenter(), nivelDeZoom);

        });
    </script>
</div>
