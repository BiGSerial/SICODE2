<?php

namespace App\Exports\Closure;

use Maatwebsite\Excel\Concerns\{WithMultipleSheets, WithProperties};

/** Excel do detalhe de UMA pendência: o pedido, as exigências com a resposta e o histórico com data e tempo entre etapas. */
class ClosureIssueDetailExport implements WithMultipleSheets, WithProperties
{
    /** @param array{info: array<int, array<int, mixed>>, items: array<int, array<int, mixed>>, history: array<int, array<int, mixed>>} $sheets */
    public function __construct(private readonly string $title, private readonly array $sheets)
    {
    }

    public function sheets(): array
    {
        return [
            new ClosureStrictSheet('Pendência', ['Campo', 'Valor'], $this->sheets['info']),
            new ClosureStrictSheet('Exigências e respostas', ['Item', 'Exigência', 'Resposta', 'Observação'], $this->sheets['items']),
            new ClosureStrictSheet('Histórico', ['Data/Hora', 'Etapa', 'Por', 'Tempo desde a etapa anterior', 'Mensagem'], $this->sheets['history']),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title'          => $this->title,
            'description'    => 'Pendência de Encerramento exportada',
            'subject'        => 'Encerramento',
        ];
    }
}
