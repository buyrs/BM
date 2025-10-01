@props(['variant' => 'default', 'padding' => 'default', 'hover' => false])

@php
$baseClasses = 'bg-white rounded-xl border border-secondary-200 transition-all duration-200';

$variants = [
    'default' => '',
    'bordered' => 'border-2',
    'elevated' => 'shadow-medium',
    'ghost' => 'border-0 bg-secondary-50',
];

$paddings = [
    'none' => '',
    'sm' => 'p-4',
    'default' => 'p-6',
    'lg' => 'p-8',
];

$hoverClasses = $hover ? 'hover:shadow-medium hover:-translate-y-1 cursor-pointer' : '';

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $paddings[$padding] . ' ' . $hoverClasses;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @isset($header)
        <div class="mb-4 pb-4 border-b border-secondary-200">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}

    @isset($footer)
        <div class="mt-4 pt-4 border-t border-secondary-200">
            {{ $footer }}
        </div>
    @endisset
</div>