<table>
    <thead>
        <tr>
            <th>Note</th>
            <th>DD</th>
            <th>Rubrica</th>
            <th>Município</th>
            <th>Postes</th>
            <th>Usuário</th>
            <th>AttAt</th>
            <th>DtAds</th>
            <th>MoAberto</th>
            <th>Prazo</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $item)
            <tr>
                <td>{{ $item->note?->note }}</td>
                <td>{{ $item->wpas?->last()?->dd }}</td>
                <td>{{ $item->note?->rubrica }}</td>
                <td>{{ $item->note?->lexp }}</td>
                <td>{{ $item->note?->postes ?? '---' }}</td>
                <td>{{ $item->user?->name }}</td>
                <td>{{ $item->att_at ? $item->att_at->diffInDays(now()) . ' dias' : '' }}</td>
                <td>{{ $item->note?->workform?->adsform?->created_at ? $item->note?->workform?->adsform?->created_at->diffInDays(now()) . ' dias' : '' }}
                </td>
                <td>{{ number_format($item->note?->orders?->sum('moaberto'), 2, ',', '.') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->pzo)->format('d/m/Y') }}</td>
                <td>{{ \App\Custom\Notestatus::status($item->status)->status ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
