<div>
    @if ($status->approved && !$status->rejected && !$status->canceled && !$status->tacit)
        <span class="badge text-bg-success">Aprovado</span>
    @elseif ($status->approved && $status->tacit)
        <span class="badge text-bg-warning">Aprovação Tácita</span>
    @elseif ($status->rejected && !$status->canceled)
        <span class="badge text-bg-danger">Rejeitado</span>
    @elseif ($status->canceled)
        <span class="badge text-bg-secondary">Cancelado</span>
    @else
        <span class="badge text-bg-primary">Em Viabilidade</span>
    @endif


</div>
