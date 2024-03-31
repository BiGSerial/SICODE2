<div>
    @php

        if ($status->count()) {
            $completed = 0;
            $rejected = 0;
            $approved = 0;
            $canceled = 0;
            $tacit = 0;

            foreach ($status as $stat) {
                if ($stat->completed) {
                    $completed++;
                }

                if ($stat->rejected) {
                    $rejected++;
                }

                if ($stat->approved) {
                    $approved++;
                }

                if ($stat->canceled) {
                    $canceled++;
                }

                if ($stat->tacit) {
                    $tacit++;
                }
            }

            if ($completed == $status->count()) {
                $completed = 1;
            } else {
                $completed = 0;
            }

            if ($rejected) {
                $rejected = 1;
            } else {
                $rejected = 0;
            }

            if ($approved == $status->count()) {
                $approved = 1;
            } else {
                $approved = 0;
            }

            if ($canceled == $status->count()) {
                $canceled = 1;
            } else {
                $canceled = 0;
            }

            if ($tacit == $status->count()) {
                $tacit = 1;
            } else {
                $tacit = 0;
            }
        }
    @endphp

    @if ($completed)
        <span class="badge text-bg-success">Contratado</span>
    @elseif ($approved && !$tacit)
        <span class="badge text-bg-success">Aprovado</span>
    @elseif ($approved && $tacit)
        <span class="badge text-bg-warning">Aprovação Tácita</span>
    @elseif ($rejected && !$canceled)
        <span class="badge text-bg-danger">Rejeitado</span>
    @elseif ($canceled)
        <span class="badge text-bg-secondary">Cancelado</span>
    @endif


</div>
