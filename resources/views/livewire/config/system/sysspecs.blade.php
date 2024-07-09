<div wire:poll.1000ms>
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Status do Sistema</h5>
        </div>
        <div class="card-body">
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <strong>Espaço disponível/total:</strong> {{ $freeSpace }} GB / {{ $totalSpace }} GB
                    @if ($totalSpace > 0)
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar"
                                style="width: {{ (($totalSpace - $freeSpace) / $totalSpace) * 100 }}%;"
                                aria-valuenow="{{ (($totalSpace - $freeSpace) / $totalSpace) * 100 }}" aria-valuemin="0"
                                aria-valuemax="100">
                                {{ round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2) }}%
                            </div>
                        </div>
                    @else
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100">
                                0%
                            </div>
                        </div>
                    @endif
                </li>
                <li class="list-group-item">
                    <strong>Memória usada/livre:</strong> {{ $memoryTotal - $memoryFree }} MB / {{ $memoryTotal }} MB
                    @if ($memoryTotal > 0)
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar"
                                style="width: {{ (($memoryTotal - $memoryFree) / $memoryTotal) * 100 }}%;"
                                aria-valuenow="{{ (($memoryTotal - $memoryFree) / $memoryTotal) * 100 }}"
                                aria-valuemin="0" aria-valuemax="100">
                                {{ round((($memoryTotal - $memoryFree) / $memoryTotal) * 100, 2) }}%
                            </div>
                        </div>
                    @else
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100">
                                0%
                            </div>
                        </div>
                    @endif
                </li>
                <li class="list-group-item">
                    <strong>Carga do sistema (1 min):</strong> {{ $load['1min'] }}
                </li>
                <li class="list-group-item">
                    <strong>Carga do sistema (5 min):</strong> {{ $load['5min'] }}
                </li>
                <li class="list-group-item">
                    <strong>Carga do sistema (15 min):</strong> {{ $load['15min'] }}
                </li>
            </ul>
        </div>
    </div>
</div>
