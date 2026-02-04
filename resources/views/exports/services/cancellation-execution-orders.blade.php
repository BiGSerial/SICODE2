<table>
    <thead>
        <tr>
            <th>Solicitação</th>
            <th>Nota</th>
            <th>Ordem</th>
            <th>Categoria</th>
            <th>Escopo</th>
            <th>Status Solicitação</th>
            <th>Solicitante</th>
            <th>Executor</th>
            <th>Assumido em</th>
            <th>Encerrado em</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $request)
            @foreach($request->Orders as $order)
                <tr>
                    <td>{{ $request->id }}</td>
                    <td>{{ $request->Note->note ?? '-' }}</td>
                    <td>{{ $order->ordem }}</td>
                    <td>{{ $request->Category->name ?? '-' }}</td>
                    <td>{{ $request->scope?->label() ?? $request->scope?->value ?? $request->scope }}</td>
                    <td>{{ $request->status?->label() ?? $request->status?->value ?? $request->status }}</td>
                    <td>{{ $request->Requester->name ?? '-' }}</td>
                    <td>{{ $request->Assignee->name ?? '-' }}</td>
                    <td>{{ optional($request->assigned_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($request->closed_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
