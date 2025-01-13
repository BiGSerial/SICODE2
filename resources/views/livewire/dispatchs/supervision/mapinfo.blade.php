@php
    use App\Custom\WpaStatus;
@endphp
<div>
    <div class="card">
        <div class="card-header">Buscar</div>
        <div class="card-body">
            <div class="d-flex justify-content-center">
                <input type="text" class="form-control w-50" placeholder="Pesquisar..." wire:model="search">
                <button class="btn btn-primary ms-2" wire:click.prevent="toSearch">Pesquisar</button>
            </div>
        </div>
    </div>
    @if ($lists->count())
        <div class="table-responsive">
            <table class="table table-condensed table-striped">
                <theader>
                    <tr>
                        <th>Note</th>
                        <th>DD</th>
                        <th>Servico</th>
                        <th>Usuario</th>
                        <th>Status</th>
                    </tr>
                </theader>
                <tbody>
                    @foreach ($lists as $list)
                        <tr wire:click.prevent="pegarCoordenadaNota({{ $list->id }})">
                            <td>{{ $list->Note->note }}</td>
                            <td>{{ $list->dd }}</td>
                            <td>{{ $list->Production->Service ? $list->Production->Service->service : '' }}</td>
                            <td>{{ $list->Production->User ? $list->Production->User->name : '' }}</td>
                            <td class="{{ WpaStatus::status($list->stats, $list->execstats)->bg_color }}">
                                {{ WpaStatus::status($list->stats, $list->execstats)->info }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $lists->links() }}





    @endif
    <button wire:click.prevent="teste">Teste</button>

    {{-- <script>
        var map;

        document.addEventListener('DOMContentLoaded', function() {

            map = L.map('map-container').setView([-20.3155, -40.3128], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);


        });

        window.addEventListener('update_marks', event => {


            event.detail.wpa.forEach(function(marcador) {

                var marcadorLeaflet = L.marker(marcador.coordenadas).addTo(map);
                marcadorLeaflet.bindPopup(marcador.mensagem);

                marcadorLeaflet.on('click', function(e) {

                    if (e.originalEvent.ctrlKey) {

                        textos.push(marcador.mensagem);


                        document.getElementById('coordenadas').value = textos.join(
                            ', ');
                    }
                });
            });

        });
    </script> --}}
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

            var nivelDeZoom = distancia > 10000 ? 8 : 20;

            map.setView(bounds.getCenter(), nivelDeZoom);

        });
    </script>
</div>
