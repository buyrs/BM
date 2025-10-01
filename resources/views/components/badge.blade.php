@props(['variant' => 'default', 'size' => 'md', 'dot' => false])

@php
$baseClasses = 'inline-flex items-center font-medium rounded-full';

$variants = [
    'default' => 'bg-secondary-100 text-secondary-800',
    'primary' => 'bg-primary-100 text-primary-800',
    'secondary' => 'bg-secondary-100 text-secondary-800',
    'accent' => 'bg-accent-100 text-accent-800',
    'success' => 'bg-success-100 text-success-800',
    'warning' => 'bg-warning-100 text-warning-800',
    'danger' => 'bg-danger-100 text-danger-800',
    'info' => 'bg-primary-100 text-primary-800',
    'outline' => 'border border-secondary-300 text-secondary-700',
];

$sizes = [
    'sm' => 'px-2 py-1 text-xs',
    'md' => 'px-2.5 py-1.5 text-sm',
    'lg' => 'px-3 py-2 text-base',
];

$classes = $baseClasses . ' ' . $variants[$variant] . ' ' . $sizes[$size];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)
        <span class="mr-1.5 h-2 w-2 rounded-full bg-current opacity-75"></span>
    @endif
    {{ $slot }}
</span>