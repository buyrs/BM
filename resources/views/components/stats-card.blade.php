@props([
    'title' => '',
    'value' => 0,
    'prefix' => '',
    'suffix' => '',
    'trend' => null,         // Percentage change (positive or negative)
    'trendLabel' => '',      // e.g., "vs last month"
    'icon' => null,          // SVG icon content
    'iconColor' => 'primary', // primary, success, warning, danger
    'sparklineData' => null, // Array of numbers for sparkline
    'href' => null,          // Optional link
    'loading' => false,
])

@php
    $iconBgColors = [
        'primary' => 'bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400',
        'success' => 'bg-success-100 dark:bg-success-900/50 text-success-600 dark:text-success-400',
        'warning' => 'bg-warning-100 dark:bg-warning-900/50 text-warning-600 dark:text-warning-400',
        'danger' => 'bg-danger-100 dark:bg-danger-900/50 text-danger-600 dark:text-danger-400',
    ];
    
    $iconBgClass = $iconBgColors[$iconColor] ?? $iconBgColors['primary'];
    
    $trendColor = $trend > 0 ? 'text-success-600 dark:text-success-400' : ($trend < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-secondary-500');
    $trendBgColor = $trend > 0 ? 'bg-success-50 dark:bg-success-900/30' : ($trend < 0 ? 'bg-danger-50 dark:bg-danger-900/30' : 'bg-secondary-50 dark:bg-secondary-800');
    $sparklineColor = $trend >= 0 ? 'success' : 'danger';
@endphp

@if($loading)
    <x-skeleton-loader type="stats-card" />
@else
    <{{ $href ? 'a' : 'div' }} 
        {{ $href ? 'href=' . $href : '' }}
        x-data="{ hovered: false }"
        @mouseenter="hovered = true"
        @mouseleave="hovered = false"
        class="group relative bg-white dark:bg-secondary-800 rounded-xl shadow-soft border border-secondary-100 dark:border-secondary-700 p-5 transition-all duration-300 {{ $href ? 'cursor-pointer hover:shadow-medium hover:-translate-y-0.5' : '' }}"
        {{ $attributes }}
    >
        {{-- Background pattern on hover --}}
        <div 
            class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl overflow-hidden pointer-events-none"
            style="background-image: radial-gradient(circle at 100% 0%, {{ $iconColor === 'success' ? 'rgba(34, 197, 94, 0.05)' : ($iconColor === 'warning' ? 'rgba(245, 158, 11, 0.05)' : ($iconColor === 'danger' ? 'rgba(239, 68, 68, 0.05)' : 'rgba(59, 130, 246, 0.05)')) }} 0%, transparent 50%);"
        ></div>
        
        <div class="relative flex items-start justify-between">
            {{-- Content --}}
            <div class="flex-1 min-w-0">
                {{-- Title --}}
                <p class="text-sm font-medium text-secondary-500 dark:text-secondary-400 truncate">
                    {{ $title }}
                </p>
                
                {{-- Value with animation --}}
                <div class="mt-2 flex items-baseline gap-1">
                    @if($prefix)
                        <span class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ $prefix }}</span>
                    @endif
                    
                    <x-animated-counter 
                        :value="$value" 
                        class="text-2xl font-bold text-secondary-900 dark:text-white"
                    />
                    
                    @if($suffix)
                        <span class="text-sm font-medium text-secondary-500 dark:text-secondary-400">{{ $suffix }}</span>
                    @endif
                </div>
                
                {{-- Trend indicator --}}
                @if($trend !== null)
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $trendBgColor }} {{ $trendColor }}">
                            @if($trend > 0)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                            @elseif($trend < 0)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            @else
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                                </svg>
                            @endif
                            {{ abs($trend) }}%
                        </span>
                        
                        @if($trendLabel)
                            <span class="text-xs text-secondary-400 dark:text-secondary-500">{{ $trendLabel }}</span>
                        @endif
                    </div>
                @endif
            </div>
            
            {{-- Icon --}}
            @if($icon)
                <div class="flex-shrink-0 ml-4 transition-transform duration-300 group-hover:scale-110">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $iconBgClass }}">
                        {!! $icon !!}
                    </div>
                </div>
            @endif
        </div>
        
        {{-- Sparkline --}}
        @if($sparklineData && count($sparklineData) > 1)
            <div class="mt-4 -mx-1">
                <x-sparkline 
                    :data="$sparklineData" 
                    :width="200" 
                    :height="40" 
                    :color="$sparklineColor"
                />
            </div>
        @endif
        
        {{-- Click indicator for links --}}
        @if($href)
            <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <svg class="w-5 h-5 text-secondary-400 dark:text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        @endif
        
        {{-- Slot for additional content --}}
        @if(!$slot->isEmpty())
            <div class="mt-4 pt-4 border-t border-secondary-100 dark:border-secondary-700">
                {{ $slot }}
            </div>
        @endif
    </{{ $href ? 'a' : 'div' }}>
@endif
