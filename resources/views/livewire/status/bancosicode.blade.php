<div>
    <div class="row justify-contents-center">
        <span class="text-center fs-6">last run: {{ date('d/m/Y H:i:s', strToTime($bdupdate->created_at)) }} -
            (N:{{ $bdupdate->inserts }}/U:{{ $bdupdate->updates }}/E:{{ $bdupdate->errornpm }})</span>
    </div>
</div>
