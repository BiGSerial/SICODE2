<table class="table table-sm table-condensed table-hover">
    <thead>
        <tr class="table-dark">

            <th class="text-center">Nota</th>
            <th class="text-center">Nome</th>
            <th class="text-center">Ext</th>
            <th class="text-center">Tam</th>
            <th class="text-center">Serviço</th>
            <th class="text-center">Usuário</th>
            <th class="text-center">Data Criação</th>
            <th class="text-center">Existe Arquivo</th>
        </tr>
    </thead>
    <tbody>
        @if ($lists->count())

            @foreach ($lists as $list)
                @php
                    $f_exists = Storage::exists($list->path);
                @endphp
                <tr wire:key="fileRow-{{ $list->id }}"
                    class="
                    text-center align-middle
                    @if (!$f_exists) table-warning @endif

                ">

                    <td class="text-center align-middle">{{ $list->Note->note }}</td>
                    <td class="text-center align-middle">{{ $list->file_name }}</td>
                    <td class="text-center align-middle">{{ $list->ext }}</td>
                    <td class="text-center align-middle">
                        @if ($f_exists)
                            {{ number_format(Storage::size($list->path) / 1024, 2) }} KB
                        @else
                            ---
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        {{ isset($list->Service->service) ? $list->Service->service : '---' }}
                    </td>
                    <td class="text-center align-middle">{{ $list->User->name }}</td>
                    <td class="text-center align-middle">
                        {{ date('d/m/Y H:i:s', strtotime($list->created_at)) }}
                    </td>
                    <td class="text-center align-middle">
                        {{ $f_exists ? 'SIM' : 'NÃO' }}
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
