<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nota</th>
            <th>Ordens</th>
            <th>Categoria</th>
            <th>Solicitante</th>
            <th>Executando</th>
            <th>Status</th>
            <th>Solicitado em</th>
            <th>Assumido em</th>
            <th>Tempo assumido (min)</th>
            <th>Tempo total (min)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            @php
                $start = $row->submitted_at ?? $row->created_at;
                $end = $row->closed_at ?? now();
                $minutes = $start ? $start->diffInMinutes($end) : null;
                $assignedMinutes = $row->assigned_at ? $row->assigned_at->diffInMinutes($row->closed_at ?? now()) : null;
            @endphp
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->Note->note ?? '-' }}</td>
                <td>{{ $row->Orders->pluck('ordem')->implode(', ') }}</td>
                <td>{{ $row->Category->name ?? '-' }}</td>
                <td>{{ $row->Requester->name ?? '-' }}</td>
                <td>{{ $row->Assignee->name ?? '-' }}</td>
                <td>{{ $row->status?->label() ?? $row->status?->value ?? $row->status }}</td>
                <td>{{ optional($row->submitted_at)->format('d/m/Y H:i') }}</td>
                <td>{{ optional($row->assigned_at)->format('d/m/Y H:i') }}</td>
                <td>{{ $assignedMinutes ?? '-' }}</td>
                <td>{{ $minutes ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

