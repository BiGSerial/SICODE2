<div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Daily Posts Comparison</h5>
            <div id="chart001"></div>
        </div>
    </div>

    {{-- <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script> --}}
    <script>
        var chartLine;


        document.addEventListener('DOMContentLoaded', function() {

            let data1 = @json($data['previousDataNotes']);

            console.log(data1);
            let data2 = @json($data['presentDataNotes']);

            console.log(data2);

            var options = {
                chart: {
                    type: 'line'
                },
                series: [{
                    name: 'Mes Atual',
                    data: [] // Replace with your data
                }, {
                    name: 'Mês Anterior',
                    data: data1 // Replace with your data
                }],
                xaxis: {
                    categories: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                },
                yaxis: {

                }
            };

            chartLine = new ApexCharts(document.querySelector("#chart001"), options);


            livewire.emit(' sendDespatch');

            chartLine.render();
        });

        window.addEventListener('sendData001', function(event) {
            alert(event.detail);
            updateChartData(event);
        });



        function updateChartData(event) {
            var newData = event.detail.newData;

            chartLine.updateSeries([{
                name: 'Mes Atual',
                data: newData.presentDataNotes
            }, {
                name: 'Mes Anterior',
                data: newData.previousDataNotes
            }]);
        };
    </script>
</div>
