@props([
    'data' => [],           // Array of numbers
    'width' => 100,
    'height' => 30,
    'color' => 'primary',   // primary, success, warning, danger
    'fill' => true,         // Show gradient fill
    'showDots' => false,    // Show data points
    'animate' => true,      // Animate on load
])

@php
    $colors = [
        'primary' => ['stroke' => '#3b82f6', 'fill' => 'rgba(59, 130, 246, 0.1)'],
        'success' => ['stroke' => '#22c55e', 'fill' => 'rgba(34, 197, 94, 0.1)'],
        'warning' => ['stroke' => '#f59e0b', 'fill' => 'rgba(245, 158, 11, 0.1)'],
        'danger' => ['stroke' => '#ef4444', 'fill' => 'rgba(239, 68, 68, 0.1)'],
    ];
    
    $colorConfig = $colors[$color] ?? $colors['primary'];
    $jsonData = json_encode($data);
@endphp

<div 
    x-data="sparkline({ 
        data: {{ $jsonData }},
        width: {{ $width }},
        height: {{ $height }},
        strokeColor: '{{ $colorConfig['stroke'] }}',
        fillColor: '{{ $colorConfig['fill'] }}',
        fill: {{ $fill ? 'true' : 'false' }},
        showDots: {{ $showDots ? 'true' : 'false' }},
        animate: {{ $animate ? 'true' : 'false' }}
    })"
    class="inline-block"
    {{ $attributes }}
>
    <svg 
        x-ref="svg"
        :width="width"
        :height="height"
        class="overflow-visible"
    >
        <!-- Gradient definition -->
        <defs>
            <linearGradient :id="'sparkline-gradient-' + $id('')" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" :stop-color="strokeColor" stop-opacity="0.3" />
                <stop offset="100%" :stop-color="strokeColor" stop-opacity="0" />
            </linearGradient>
        </defs>
        
        <!-- Fill area -->
        <path
            x-show="fill && fillPath"
            :d="fillPath"
            :fill="'url(#sparkline-gradient-' + $id('') + ')'"
            class="transition-opacity duration-500"
            :class="{ 'opacity-0': !hasAnimated && animate }"
        />
        
        <!-- Line -->
        <path
            x-show="linePath"
            :d="linePath"
            fill="none"
            :stroke="strokeColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            :stroke-dasharray="animate ? pathLength : 'none'"
            :stroke-dashoffset="animate && !hasAnimated ? pathLength : 0"
            class="transition-all duration-1000 ease-out"
        />
        
        <!-- Data points -->
        <template x-if="showDots">
            <g>
                <template x-for="(point, index) in points" :key="index">
                    <circle
                        :cx="point.x"
                        :cy="point.y"
                        r="3"
                        :fill="strokeColor"
                        class="transition-all duration-300"
                        :class="{ 'opacity-0 scale-0': !hasAnimated && animate }"
                        :style="{ transitionDelay: (index * 50) + 'ms' }"
                    />
                </template>
            </g>
        </template>
    </svg>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sparkline', (config) => ({
            data: config.data || [],
            width: config.width || 100,
            height: config.height || 30,
            strokeColor: config.strokeColor || '#3b82f6',
            fillColor: config.fillColor || 'rgba(59, 130, 246, 0.1)',
            fill: config.fill !== false,
            showDots: config.showDots || false,
            animate: config.animate !== false,
            hasAnimated: false,
            points: [],
            linePath: '',
            fillPath: '',
            pathLength: 0,
            
            init() {
                this.calculatePaths();
                
                if (this.animate) {
                    this.observeVisibility();
                } else {
                    this.hasAnimated = true;
                }
            },
            
            calculatePaths() {
                if (this.data.length < 2) return;
                
                const padding = 4;
                const min = Math.min(...this.data);
                const max = Math.max(...this.data);
                const range = max - min || 1;
                
                const xStep = (this.width - padding * 2) / (this.data.length - 1);
                
                this.points = this.data.map((value, index) => ({
                    x: padding + index * xStep,
                    y: padding + (1 - (value - min) / range) * (this.height - padding * 2)
                }));
                
                // Create smooth curve using catmull-rom spline
                this.linePath = this.createSmoothPath(this.points);
                
                // Create fill path
                if (this.fill) {
                    this.fillPath = this.linePath + 
                        ` L ${this.points[this.points.length - 1].x} ${this.height}` +
                        ` L ${this.points[0].x} ${this.height} Z`;
                }
                
                // Calculate path length for animation
                this.$nextTick(() => {
                    const path = this.$refs.svg?.querySelector('path:nth-child(2)');
                    if (path) {
                        this.pathLength = path.getTotalLength() || 200;
                    }
                });
            },
            
            createSmoothPath(points) {
                if (points.length < 2) return '';
                
                let path = `M ${points[0].x} ${points[0].y}`;
                
                for (let i = 0; i < points.length - 1; i++) {
                    const p0 = points[i - 1] || points[i];
                    const p1 = points[i];
                    const p2 = points[i + 1];
                    const p3 = points[i + 2] || p2;
                    
                    const cp1x = p1.x + (p2.x - p0.x) / 6;
                    const cp1y = p1.y + (p2.y - p0.y) / 6;
                    const cp2x = p2.x - (p3.x - p1.x) / 6;
                    const cp2y = p2.y - (p3.y - p1.y) / 6;
                    
                    path += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`;
                }
                
                return path;
            },
            
            observeVisibility() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !this.hasAnimated) {
                            setTimeout(() => {
                                this.hasAnimated = true;
                            }, 100);
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.1 });
                
                observer.observe(this.$el);
            },
            
            // Update data dynamically
            updateData(newData) {
                this.data = newData;
                this.hasAnimated = false;
                this.calculatePaths();
                setTimeout(() => {
                    this.hasAnimated = true;
                }, 100);
            }
        }));
    });
</script>
