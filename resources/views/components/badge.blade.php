@props(['variant' => 'default', 'size' => 'md', 'dot' => false])

@php
$baseClasses = 'inline-flex items-center font-medium rounded-full';

$variants = [
    'default' => 'bg-secondary-100 dark:bg-secondary-700 text-secondary-800 dark:text-secondary-200',
    'primary' => 'bg-primary-100 dark:bg-primary-900/50 text-primary-800 dark:text-primary-300',
    'secondary' => 'bg-secondary-100 dark:bg-secondary-700 text-secondary-800 dark:text-secondary-200',
    'accent' => 'bg-accent-100 dark:bg-accent-900/50 text-accent-800 dark:text-accent-300',
    'success' => 'bg-success-100 dark:bg-success-900/50 text-success-800 dark:text-success-300',
    'warning' => 'bg-warning-100 dark:bg-warning-900/50 text-warning-800 dark:text-warning-300',
    'danger' => 'bg-danger-100 dark:bg-danger-900/50 text-danger-800 dark:text-danger-300',
    'info' => 'bg-primary-100 dark:bg-primary-900/50 text-primary-800 dark:text-primary-300',
    'outline' => 'border border-secondary-300 dark:border-secondary-600 text-secondary-700 dark:text-secondary-300',
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