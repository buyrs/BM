@props([
    'variant' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'loading' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'ripple' => true,          // Enable ripple effect
    'haptic' => true,          // Enable haptic feedback
    'success' => false,        // Show success state
    'error' => false,          // Show error state (shake animation)
])

@php
$baseClasses = 'relative inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed overflow-hidden select-none active:scale-[0.98]';

$variants = [
    'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 shadow-sm hover:shadow-md',
    'secondary' => 'bg-secondary-100 text-secondary-900 hover:bg-secondary-200 focus:ring-secondary-500 border border-secondary-200',
    'success' => 'bg-success-600 text-white hover:bg-success-700 focus:ring-success-500 shadow-sm hover:shadow-md',
    'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500 shadow-sm hover:shadow-md',
    'warning' => 'bg-warning-600 text-white hover:bg-warning-700 focus:ring-warning-500 shadow-sm hover:shadow-md',
    'outline' => 'border-2 border-primary-600 text-primary-600 hover:bg-primary-600 hover:text-white focus:ring-primary-500',
    'ghost' => 'text-secondary-700 hover:bg-secondary-100 focus:ring-secondary-500',
];

$sizes = [
    'xs' => 'px-2.5 py-1.5 text-xs gap-1',
    'sm' => 'px-3 py-2 text-sm gap-1.5',
    'md' => 'px-4 py-2.5 text-sm gap-2',
    'lg' => 'px-6 py-3 text-base gap-2',
    'xl' => 'px-8 py-4 text-lg gap-2.5',
];

$iconSizes = [
    'xs' => 'w-3 h-3',
    'sm' => 'w-4 h-4',
    'md' => 'w-4 h-4',
    'lg' => 'w-5 h-5',
    'xl' => 'w-6 h-6',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
$iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

<button 
    x-data="enhancedButton({ 
        ripple: {{ $ripple ? 'true' : 'false' }},
        haptic: {{ $haptic ? 'true' : 'false' }}
    })"
    @click="handleClick($event)"
    {{ $attributes->merge([
        'type' => 'submit', 
        'class' => $classes . ($error ? ' animate-shake' : '') . ($success ? ' !bg-success-500' : ''),
        'disabled' => $disabled || $loading
    ]) }}
>
    {{-- Ripple effect container --}}
    @if($ripple)
        <span 
            x-ref="rippleContainer" 
            class="absolute inset-0 pointer-events-none overflow-hidden rounded-lg"
        ></span>
    @endif
    
    {{-- Button content --}}
    <span class="relative flex items-center justify-center {{ $sizes[$size] ?? 'gap-2' }}">
        {{-- Loading spinner --}}
        @if($loading)
            <svg class="animate-spin {{ $iconSize }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Loading...</span>
        
        {{-- Success state --}}
        @elseif($success)
            <svg class="{{ $iconSize }} animate-success-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path 
                    stroke-linecap="round" 
                    stroke-linejoin="round" 
                    stroke-width="3" 
                    d="M5 13l4 4L19 7"
                    class="animate-check"
                    style="stroke-dasharray: 100; stroke-dashoffset: 100;"
                ></path>
            </svg>
            <span>{{ $slot->isEmpty() ? 'Success!' : $slot }}</span>
        
        {{-- Normal state --}}
        @else
            {{-- Icon (left position) --}}
            @if($icon && $iconPosition === 'left')
                <span class="{{ $iconSize }}">{!! $icon !!}</span>
            @endif
            
            {{-- Button text --}}
            {{ $slot }}
            
            {{-- Icon (right position) --}}
            @if($icon && $iconPosition === 'right')
                <span class="{{ $iconSize }}">{!! $icon !!}</span>
            @endif
        @endif
    </span>
</button>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('enhancedButton', (config) => ({
            ripple: config.ripple !== false,
            haptic: config.haptic !== false,
            
            handleClick(event) {
                // Haptic feedback
                if (this.haptic && window.haptics) {
                    window.haptics.impact();
                }
                
                // Ripple effect
                if (this.ripple) {
                    this.createRipple(event);
                }
            },
            
            createRipple(event) {
                const container = this.$refs.rippleContainer;
                if (!container) return;
                
                const button = this.$el;
                const rect = button.getBoundingClientRect();
                
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;
                
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: currentColor;
                    opacity: 0.2;
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;
                
                container.appendChild(ripple);
                
                ripple.addEventListener('animationend', () => {
                    ripple.remove();
                });
            }
        }));
    });
</script>

<style>
    @keyframes ripple {
        0% {
            transform: scale(0);
            opacity: 0.3;
        }
        100% {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes check {
        0% {
            stroke-dashoffset: 100;
        }
        100% {
            stroke-dashoffset: 0;
        }
    }
    
    .animate-check {
        animation: check 0.3s ease-out forwards;
    }
</style>
