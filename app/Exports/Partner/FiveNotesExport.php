<?php

namespace App\Exports\Partner;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Carbon\Carbon;

class FiveNotesExport implements FromQuery, WithHeadings, WithMapping, WithProperties
{
    use Exportable;

    protected Builder $query;
    protected bool $historic;

    public function __construct(Builder $query, bool $historic = false)
    {
        $this->query    = $query;
        $this->historic = $historic;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        $columns = [
            'Nota D5',
            'Nota',
            'Ordem',
            'PEP',
            'Local',
            'Motivo',
            'CodificaÃ§Ã£o',
            'Despachado em',
        ];

        if ($this->historic) {
            $columns[] = 'ConcluÃ­do em';
            $columns[] = 'Status';
        } else {
            $columns[] = 'Dias em aberto';
            $columns[] = 'Status';
        }

        $columns[] = 'Empresa';
        $columns[] = 'Passivo?';

        return $columns;
    }

    public function map($five): array
    {
        $dispatchAt = optional($five->dispatch_at)->format('d/m/Y H:i');
        $row = [
            $five->note_d5,
            optional($five->note)->note,
            $this->resolveOrder($five),
            $five->pep,
            $five->loc_install,
            $five->reason,
            $five->codify,
            $dispatchAt,
        ];

        if ($this->historic) {
            $row[] = optional($five->completed_at)->format('d/m/Y H:i');
            $row[] = $this->resolveStatus($five);
        } else {
            $row[] = $five->dispatch_at instanceof Carbon
                ? $five->dispatch_at->diffInDays(now())
                : '';
            $row[] = $this->resolveStatus($five);
        }

        $row[] = optional($five->company)->name;
        $row[] = $five->isPassive ? 'Sim' : 'NÃ£o';

        return $row;
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => $this->historic ? 'HistÃ³rico de D5' : 'Lista de D5 pendentes',
            'description'    => 'ExportaÃ§Ã£o contendo os registros filtrados em tela.',
            'subject'        => 'FiveNotes',
            'keywords'       => 'five, notas, export, excel',
            'category'       => 'Exports',
        ];
    }

    protected function resolveOrder($five): string
    {
        $workFormOrders = optional($five->note?->WorkForm)->Orders;
        $workFormOrder  = optional(optional($workFormOrders)->sortBy('ordem'))->first();

        if ($workFormOrder) {
            return (string) $workFormOrder->ordem;
        }

        $orders = optional($five->note)->Orders;
        $order  = optional(optional($orders)->sortBy('ordem'))->first();

        return (string) ($order->ordem ?? '');
    }

    protected function resolveStatus($five): string
    {
        if ($five->is_payed) {
            if ($five->is_archived) {
                return 'Finalizada';
            }

            if ($five->is_supervisioned) {
                return 'Aguardando LiberaÃ§Ã£o Pagamento';
            }

            if ($five->is_completed) {
                return 'Aguardando FiscalizaÃ§Ã£o';
            }

            if ($five->visible_partner) {
                return 'Aguardando ConclusÃ£o Parceira';
            }
        }

        return 'Aguardando Despacho Pagamento';
    }
}



