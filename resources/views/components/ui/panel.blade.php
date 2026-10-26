@props(['title' => null, 'flush' => false])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <div class="card-head">
            <h2 class="card-title">{{ $title }}</h2>
            @isset($action)
                <div>{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div @class(['p-5' => ! $flush])>
        {{ $slot }}
    </div>
</section>
