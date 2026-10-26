@props(['icon' => 'users', 'label', 'value', 'tone' => 'brand'])

<div {{ $attributes->merge(['class' => 'card flex items-center gap-4 p-5']) }}>
    <span class="icon-chip icon-{{ $tone }}"><x-ui.icon :name="$icon" class="h-6 w-6" /></span>
    <div>
        <p class="numeral text-4xl text-navy">{{ $value }}</p>
        <p class="mt-1 text-sm text-muted">{{ $label }}</p>
    </div>
</div>
