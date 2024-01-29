<div x-data="{
    myLabels: @entangle('labels'),
    datasets: @entangle('datasets'),
    title: @entangle('title'),
    myId: @entangle('Chartid'),
    label: @entangle('label'),
    stack: @entangle('stack')
}" x-init="data = {
    labels: myLabels,
    datasets: datasets.map((dataset, i) => {
        return {
            label: dataset.label,
            data: dataset.data,
            borderColor: dataset.type === 'bar' ? 'transparent' : color(i), // Escolha a cor com base no tipo
            backgroundColor: background(i),
            stack: dataset.stack,
        };
    })
};

new Chart($refs.myChart, {
    type: 'bar',
    data: data,
    options: {
        {{-- plugins: {
            title: {
                display: true,
                text: 'Chart.js Bar Chart - Stacked'
            },
        }, --}}
        responsive: true,
        interaction: {
            intersect: false,
        },
        scales: {
            x: {
                stacked: true,
            },
            y: {
                stacked: true
            }
        }
    }
});

function color(index) {
    const vibrantColors = [
        'rgb(40, 255, 82)',
        'rgb(109, 50, 255)',
        'rgb(12, 211, 248)',
        'rgb(38, 60, 200)',
        'rgb(33, 46, 62)',
        'rgb(20, 63, 71)',
        'rgb(34, 94, 102)',
        'rgb(124, 149, 153)',

    ];

    return vibrantColors[index];
}

function background(index) {
    const Colors = [
        'rgb(169, 255, 186)',
        'rgb(167, 132, 255)',
        'rgb(158, 237, 252)',
        'rgb(168, 177, 233)',
        'rgb(144, 151, 159)',
        'rgb(91, 121, 126)',
        'rgb(145, 175, 179)',
        'rgb(190, 202, 204)',

    ];

    return Colors[index];
}





function generateContrastingColors(labels) {
    const vibrantColors = [
        'rgb(40, 255, 82)',
        'rgb(82, 255, 117)',
        'rgb(126, 255, 151)',
        'rgb(169, 255, 186)',
        'rgb(109, 50, 255)',
        'rgb(131, 81, 255)',
        'rgb(167, 132, 255)',
        'rgb(197, 173, 255)',
        'rgb(12, 211, 248)',
        'rgb(61, 220, 249)',
        'rgb(109, 229, 251)',
        'rgb(158, 237, 252)',
        'rgb(38, 60, 200)',
        'rgb(71, 89, 208)',
        'rgb(125, 141, 222)',
        'rgb(168, 177, 233)',
        'rgb(33, 46, 62)',
        'rgb(66, 77, 91)',
        'rgb(100, 109, 120)',
        'rgb(144, 151, 159)',
        'rgb(20, 63, 71)',
        'rgb(54, 89, 96)',
        'rgb(91, 121, 126)',
        'rgb(138, 159, 163)',
        'rgb(34, 94, 102)',
        'rgb(67, 118, 125)',
        'rgb(100, 142, 148)',
        'rgb(145, 175, 179)',
        'rgb(124, 149, 153)',
        'rgb(144, 165, 168)',
        'rgb(163, 181, 184)',
        'rgb(190, 202, 204)',
    ];

    const colors = [];

    for (let i = 0; i < labels.length; i++) {
        const label = labels[i];
        const color = vibrantColors[Math.floor(Math.random() * vibrantColors.length)];

        colors.push(color);
    }

    return colors;
}" x-on:livewire:load>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/2.2.1/chartjs-plugin-annotation.min.js"
        integrity="sha512-qF3T5CaMgSRNrxzu69V3ZrYGnrbRMIqrkE+OrE01DDsYDNo8R1VrtYL8pk+fqhKxUBXQ2z+yV/irk+AbbHtBAg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <div class="card">
        <h5 class="card-header">{{ $title }}</h5>

        <div class="card-body">


            <canvas id="{{ $Chartid }}" wire:key="{{ $Chartid }}" height="80" x-ref="myChart"
                wire:ignore></canvas>


        </div>

    </div>
</div>
