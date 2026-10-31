@props(['title', 'count' => null, 'tone' => null])

<section {{ $attributes->class(['inform-card', 'is-danger' => $tone === 'danger']) }}>
    <div class="inform-card-head">
        <h5>{{ $title }}</h5>
        @if ($count !== null)
            <span class="badge text-bg-light">{{ $count }}</span>
        @endif
    </div>
    <div class="inform-card-body">{{ $slot }}</div>
</section>
