@props(['disabled' => false, 'error' => false, 'icon' => null, 'size' => 'md', 'placeholder' => ''])

@php
$baseClasses = 'block w-full rounded-lg border-0 py-1.5 shadow-sm ring-1 ring-inset placeholder:text-secondary-400 dark:placeholder:text-secondary-500 focus:ring-2 focus:ring-inset transition-colors duration-200 disabled:cursor-not-allowed disabled:bg-secondary-50 dark:disabled:bg-secondary-800 disabled:text-secondary-500 disabled:ring-secondary-200 dark:disabled:ring-secondary-700 bg-white dark:bg-secondary-800 text-secondary-900 dark:text-secondary-100';

$stateClasses = $error
    ? 'ring-danger-300 dark:ring-danger-500 placeholder:text-danger-300 focus:ring-danger-600'
    : 'ring-secondary-300 dark:ring-secondary-600 focus:ring-primary-600 dark:focus:ring-primary-500';

$sizeClasses = [
    'sm' => 'px-2.5 py-1.5 text-sm',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-3.5 py-2.5 text-base',
];

$classes = $baseClasses . ' ' . $stateClasses . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<div class="relative">
    @if ($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-secondary-400 dark:text-secondary-500 sm:text-sm">{!! $icon !!}</span>
        </div>
    @endif
    
    <input 
        @disabled($disabled) 
        {{ $attributes->merge([
            'class' => $classes . ($icon ? ' pl-10' : ''), 
            'placeholder' => $placeholder
        ]) }}
    >
    
    @if ($error)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-danger-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
    @endif
</div>
