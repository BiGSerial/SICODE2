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
            <th>Valor ADS</th>
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
                <td>{{ $item->att_at ? $item->att_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) . ' dias' : '' }}</td>
                <td>{{ $item->dt_ads ? \Carbon\Carbon::parse($item->dt_ads)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($item->dt_ads)->startOfDay()->diffInDays(now()->startOfDay()) . ' dia(s)' : '' }}</td>
                <td>{{ number_format((float) $item->ads_amount, 2, ',', '.') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->pzo)->format('d/m/Y') }}</td>
                <td>{{ \App\Custom\Notestatus::status($item->status)->status ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
