@props([
    'value' => 0,            // Current progress (0-100)
    'max' => 100,            // Maximum value
    'size' => 'md',          // sm, md, lg, xl
    'strokeWidth' => null,   // Override stroke width
    'color' => 'primary',    // primary, success, warning, danger, or hex color
    'trackColor' => null,    // Background track color
    'showLabel' => true,     // Show percentage in center
    'labelFormat' => 'percent', // percent, value, custom
    'animate' => true,       // Animate on load
    'duration' => 1000,      // Animation duration
])

@php
    $sizes = [
        'sm' => ['size' => 48, 'stroke' => 4, 'text' => 'text-xs'],
        'md' => ['size' => 64, 'stroke' => 6, 'text' => 'text-sm'],
        'lg' => ['size' => 96, 'stroke' => 8, 'text' => 'text-lg'],
        'xl' => ['size' => 128, 'stroke' => 10, 'text' => 'text-xl'],
    ];
    
    $config = $sizes[$size] ?? $sizes['md'];
    $svgSize = $config['size'];
    $stroke = $strokeWidth ?? $config['stroke'];
    $textClass = $config['text'];
    
    $radius = ($svgSize - $stroke) / 2;
    $circumference = 2 * pi() * $radius;
    
    $colors = [
        'primary' => 'text-primary-500',
        'success' => 'text-success-500',
        'warning' => 'text-warning-500',
        'danger' => 'text-danger-500',
        'info' => 'text-primary-400',
    ];
    
    $strokeColor = $colors[$color] ?? 'text-primary-500';
    $isCustomColor = !isset($colors[$color]) && str_starts_with($color, '#');
@endphp

<!-- Progress Ring Component -->
<div 
    x-data="progressRing({ 
        value: {{ $value }},
        max: {{ $max }},
        circumference: {{ $circumference }},
        animate: {{ $animate ? 'true' : 'false' }},
        duration: {{ $duration }}
    })"
    x-init="init()"
    class="relative inline-flex items-center justify-center"
    {{ $attributes }}
>
    <svg 
        width="{{ $svgSize }}" 
        height="{{ $svgSize }}" 
        class="transform -rotate-90"
    >
        <!-- Background track -->
        <circle
            cx="{{ $svgSize / 2 }}"
            cy="{{ $svgSize / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke="{{ $trackColor ?? 'currentColor' }}"
            stroke-width="{{ $stroke }}"
            class="{{ $trackColor ? '' : 'text-secondary-200 dark:text-secondary-700' }}"
        />
        
        <!-- Progress arc -->
        <circle
            x-ref="progressCircle"
            cx="{{ $svgSize / 2 }}"
            cy="{{ $svgSize / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke="{{ $isCustomColor ? $color : 'currentColor' }}"
            stroke-width="{{ $stroke }}"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="strokeDashoffset"
            class="{{ $isCustomColor ? '' : $strokeColor }} transition-all duration-500 ease-out"
        />
    </svg>
    
    <!-- Label -->
    @if($showLabel)
        <div class="absolute inset-0 flex items-center justify-center">
            @if($labelFormat === 'percent')
                <span class="font-semibold {{ $textClass }} text-secondary-900 dark:text-white" x-text="Math.round(currentValue) + '%'"></span>
            @elseif($labelFormat === 'value')
                <span class="font-semibold {{ $textClass }} text-secondary-900 dark:text-white" x-text="Math.round(currentValue * {{ $max }} / 100)"></span>
            @else
                {{ $slot }}
            @endif
        </div>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('progressRing', (config) => ({
            targetValue: config.value || 0,
            currentValue: 0,
            max: config.max || 100,
            circumference: config.circumference,
            animate: config.animate !== false,
            duration: config.duration || 1000,
            hasStarted: false,
            
            get normalizedValue() {
                return Math.min(Math.max(this.currentValue, 0), 100);
            },
            
            get strokeDashoffset() {
                const progress = this.normalizedValue / 100;
                return this.circumference * (1 - progress);
            },
            
            init() {
                if (this.animate) {
                    this.observeVisibility();
                } else {
                    this.currentValue = this.targetValue;
                }
            },
            
            observeVisibility() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !this.hasStarted) {
                            this.startAnimation();
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.1 });
                
                observer.observe(this.$el);
            },
            
            startAnimation() {
                this.hasStarted = true;
                const startTime = performance.now();
                const startValue = 0;
                const endValue = this.targetValue;
                
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / this.duration, 1);
                    
                    // Ease out cubic
                    const eased = 1 - Math.pow(1 - progress, 3);
                    
                    this.currentValue = startValue + (endValue - startValue) * eased;
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        this.currentValue = endValue;
                    }
                };
                
                requestAnimationFrame(animate);
            },
            
            // Allow external updates
            updateValue(newValue) {
                const startValue = this.currentValue;
                const startTime = performance.now();
                
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / 500, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    
                    this.currentValue = startValue + (newValue - startValue) * eased;
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        this.currentValue = newValue;
                        this.targetValue = newValue;
                    }
                };
                
                requestAnimationFrame(animate);
            }
        }));
    });
</script>
