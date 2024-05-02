<div>
    @if ($list->d5)
        <span class="badge text-bg-primary" class="btn btn-primary" data-bs-toggle="modal"
            data-bs-target="#show-ri-{{ $list->id }}" style='cursor: pointer;'><span
                class="align-middle fs-6">{{ $list->Note->note }}
                RI</span></span>
    @else
        <span class="fw-bold">{{ $list->Note->note }}</span>
    @endif

    <!-- Modal -->
    <div class="modal fade" id="show-ri-{{ $list->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header edp-bg-seoweedgreen-100 text-white">
                    <h1 class="modal-title fs-5 " id="exampleModalLabel">{{ $list->Note->note }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if (isset($list->Reclaim->Comments) && $list->Reclaim->Comments->count())
                        {{-- @dump($list->Reclaim->Comments) --}}
                        @foreach ($list->Reclaim->Comments as $comment)
                            <div class="card shadow border border-1">
                                <div class="card-body">
                                    {{ $comment->message }}
                                </div>
                                <div class="card-footer py-0" style="size: 10px;">
                                    Por: {{ $comment->User->name }} -
                                    {{ date('d/m/Y H:i:s', strToTime($comment->created_at)) }}
                                </div>
                            </div>
                        @endforeach

                    @endif
                </div>
                <div class="modal-footer edp-bg-seoweedgreen-100 text-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

</div>
