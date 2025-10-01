@props(['disabled' => false, 'error' => false, 'placeholder' => 'Select an option...', 'size' => 'md', 'multiple' => false])

@php
$baseClasses = 'block w-full rounded-lg border-0 py-1.5 text-secondary-900 shadow-sm ring-1 ring-inset placeholder:text-secondary-400 focus:ring-2 focus:ring-inset transition-colors duration-200 disabled:cursor-not-allowed disabled:bg-secondary-50 disabled:text-secondary-500 disabled:ring-secondary-200';

$stateClasses = $error
    ? 'ring-danger-300 focus:ring-danger-600'
    : 'ring-secondary-300 focus:ring-primary-600';

$sizeClasses = [
    'sm' => 'px-2.5 py-1.5 text-sm',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-3.5 py-2.5 text-base',
];

$classes = $baseClasses . ' ' . $stateClasses . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<div class="relative">
    <select {{ $attributes->merge(['class' => $classes, 'disabled' => $disabled, 'multiple' => $multiple]) }}>
        @if(!$multiple && $placeholder)
            <option value="" disabled selected>{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>
    
    @if(!$multiple)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-secondary-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </div>
    @endif
    
    @if($error)
        <div class="absolute inset-y-0 right-8 pr-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-danger-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
    @endif
</div>