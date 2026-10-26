@props(['label', 'for' => null, 'hint' => null])

<div {{ $attributes }}>
    <label @if ($for) for="{{ $for }}" @endif class="label">{{ $label }}</label>
    <div class="mt-1">{{ $slot }}</div>
    @if ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
