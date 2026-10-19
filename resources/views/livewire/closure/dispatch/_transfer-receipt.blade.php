    @if ($transferReceipt)
        <div class="table-card mb-3" id="closure-transfer-receipt">
            <div class="chart-head">
                <div>
                    <h3 class="chart-title">Recibo da transferência</h3>
                    <div class="chart-subtitle">
                        Obra {{ $transferReceipt['note_id'] }} para {{ $transferReceipt['target'] }} ({{ $transferReceipt['company'] }}) em {{ $transferReceipt['at'] }}. Motivo: {{ $transferReceipt['reason'] }}
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="dismissReceipt">Fechar</button>
            </div>
            <div class="p-3 small">
                <div><strong>Transferidas ({{ count($transferReceipt['moved']) }}):</strong> {{ implode(', ', $transferReceipt['moved']) ?: 'nenhuma' }}</div>
                @foreach ($transferReceipt['skipped'] as $skipped)
                    <div class="text-danger">Recusada {{ $skipped['ordem'] }}: {{ $skipped['reason'] }}</div>
                @endforeach
            </div>
        </div>
    @endif
