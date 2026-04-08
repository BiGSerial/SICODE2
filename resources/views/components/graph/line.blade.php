<div x-data="{ myLabels: @entangle('labels'), myData: @entangle('datas'), title: @entangle('title'), myId: @entangle('Chartid'), label: @entangle('label') }" x-init="data = {
    labels: myLabels,
    datasets: [{
        label: label,
        data: myData,
        borderColor: generateContrastingColors(myLabels),
        {{-- hoverOffset: 4, --}}
        tension: 0.1,
        fill: true
    }]
};

new Chart($refs.myChart, {
    type: 'line',
    data: data,
    options: {}
});





function generateContrastingColors(labels) {
    {{-- const vibrantColors = [
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
    ]; --}}

    const vibrantColors = ["#263CC8", "#225E66", "#E32C2C", "#F7D200", "#A8B1E9", "#91AFB3", "#212E3E"]; return
    vibrantColors; }" x-on:livewire:load>


    <div class="card">
        <h5 class="card-header">{{ $title }}</h5>

        <div class="card-body">


            <canvas id="{{ $Chartid }}" wire:key="{{ $Chartid }}" height="80" x-ref="myChart"
                wire:ignore></canvas>


        </div>

    </div>
</div>
