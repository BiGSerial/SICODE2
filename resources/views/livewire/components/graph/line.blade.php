<div x-data="{ myLabels: @entangle('labels'), myData: @entangle('datas'), title: @entangle('title'), myId: @entangle('Chartid'), label: @entangle('label') }" x-init="data = {
    labels: myLabels,
    datasets: [{
            label: label,
            data: myData,
            borderColor: generateContrastingColors(myLabels),
            {{-- hoverOffset: 4, --}}
            tension: 0.1,
            fill: true
        }

    ]
};

new Chart($refs.myChart, {
    type: 'line',
    data: data,
    options: {
        plugins: {
            annotation: {
                annotations: {
                    line1: {
                        type: 'line',
                        yMin: 60,
                        yMax: 60,
                        borderColor: 'rgb(255, 99, 132)',
                        borderWidth: 2,
                    }
                }
            }
        }
    }
});





function generateContrastingColors(labels) {
    const vibrantColors = [
        'red',
        'green',
        'blue',
        'yellow',
        'purple',
        'orange',
        'pink',
        'cyan',
        'lime',
        'teal',
        'indigo',
        'deeppink',
        'gold',
        'darkgreen',
        'mediumblue',
        'magenta',
        'saddlebrown',
        'tomato',
        'springgreen',
        'slateblue',
        // Adicione mais cores vivas conforme necessário
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
