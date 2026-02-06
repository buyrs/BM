@props([
    'value' => 0,
    'prefix' => '',
    'suffix' => '',
    'duration' => 1000,       // Animation duration in ms
    'decimals' => 0,
    'separator' => ',',
    'startOnView' => true,    // Start animation when element enters viewport
])

<!-- Animated Counter Component -->
<span 
    x-data="animatedCounter({ 
        targetValue: {{ $value }},
        duration: {{ $duration }},
        decimals: {{ $decimals }},
        separator: '{{ $separator }}',
        startOnView: {{ $startOnView ? 'true' : 'false' }}
    })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'inline-flex items-baseline tabular-nums']) }}
>
    <span x-show="prefix" class="mr-0.5">{{ $prefix }}</span>
    <span 
        x-text="displayValue"
        class="animate-count-up"
        :class="{ 'opacity-0': !hasStarted }"
    ></span>
    <span x-show="suffix" class="ml-0.5">{{ $suffix }}</span>
</span>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('animatedCounter', (config) => ({
            targetValue: config.targetValue || 0,
            currentValue: 0,
            duration: config.duration || 1000,
            decimals: config.decimals || 0,
            separator: config.separator || ',',
            startOnView: config.startOnView !== false,
            hasStarted: false,
            animationFrame: null,
            
            get displayValue() {
                return this.formatNumber(this.currentValue);
            },
            
            init() {
                if (this.startOnView) {
                    this.observeVisibility();
                } else {
                    this.startAnimation();
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
                    
                    // Easing function (ease-out cubic)
                    const eased = 1 - Math.pow(1 - progress, 3);
                    
                    this.currentValue = startValue + (endValue - startValue) * eased;
                    
                    if (progress < 1) {
                        this.animationFrame = requestAnimationFrame(animate);
                    } else {
                        this.currentValue = endValue;
                    }
                };
                
                this.animationFrame = requestAnimationFrame(animate);
            },
            
            formatNumber(num) {
                const fixed = num.toFixed(this.decimals);
                
                if (!this.separator) return fixed;
                
                const parts = fixed.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.separator);
                return parts.join('.');
            },
            
            // Allow external updates
            updateValue(newValue) {
                if (this.animationFrame) {
                    cancelAnimationFrame(this.animationFrame);
                }
                
                const startValue = this.currentValue;
                const startTime = performance.now();
                
                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / 500, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    
                    this.currentValue = startValue + (newValue - startValue) * eased;
                    
                    if (progress < 1) {
                        this.animationFrame = requestAnimationFrame(animate);
                    } else {
                        this.currentValue = newValue;
                        this.targetValue = newValue;
                    }
                };
                
                this.animationFrame = requestAnimationFrame(animate);
            }
        }));
    });
</script>
