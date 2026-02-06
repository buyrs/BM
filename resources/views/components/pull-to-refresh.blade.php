@props([
    'refreshUrl' => null,
    'threshold' => 80,
    'indicatorColor' => 'primary',
])

<!-- Pull to Refresh Wrapper -->
<div 
    x-data="pullToRefresh({ 
        refreshUrl: {{ $refreshUrl ? "'$refreshUrl'" : 'null' }},
        threshold: {{ $threshold }}
    })"
    @touchstart.passive="handleTouchStart($event)"
    @touchmove="handleTouchMove($event)"
    @touchend.passive="handleTouchEnd($event)"
    class="relative"
    {{ $attributes }}
>
    <!-- Refresh Indicator -->
    <div 
        x-ref="indicator"
        x-show="isPulling || isRefreshing"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        :style="{ transform: `translateY(${Math.min(pullDistance * 0.5, 60)}px)` }"
        class="absolute left-1/2 -translate-x-1/2 -top-16 flex flex-col items-center justify-center z-10 transition-transform"
    >
        <!-- Spinner -->
        <div 
            class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-secondary-800 shadow-medium"
            :class="{ 'animate-bounce-gentle': pullProgress >= 1 && !isRefreshing }"
        >
            <!-- Loading spinner when refreshing -->
            <svg 
                x-show="isRefreshing"
                class="w-5 h-5 text-{{ $indicatorColor }}-500 animate-spin" 
                fill="none" 
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            
            <!-- Arrow when pulling -->
            <svg 
                x-show="!isRefreshing"
                class="w-5 h-5 text-{{ $indicatorColor }}-500 transition-transform duration-200"
                :class="{ 'rotate-180': pullProgress >= 1 }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
        
        <!-- Status Text -->
        <span 
            class="mt-2 text-xs font-medium text-secondary-500 dark:text-secondary-400"
            x-text="statusText"
        ></span>
    </div>
    
    <!-- Content -->
    <div 
        x-ref="content"
        :style="{ transform: `translateY(${isPulling ? Math.min(pullDistance * 0.3, 40) : 0}px)` }"
        class="transition-transform duration-200"
        :class="{ 'transition-none': isPulling }"
    >
        {{ $slot }}
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pullToRefresh', (config) => ({
            isPulling: false,
            isRefreshing: false,
            pullDistance: 0,
            startY: 0,
            threshold: config.threshold || 80,
            refreshUrl: config.refreshUrl,
            
            get pullProgress() {
                return Math.min(this.pullDistance / this.threshold, 1);
            },
            
            get statusText() {
                if (this.isRefreshing) return 'Refreshing...';
                if (this.pullProgress >= 1) return 'Release to refresh';
                return 'Pull to refresh';
            },
            
            handleTouchStart(event) {
                // Only activate if at top of page/container
                if (window.scrollY > 0) return;
                if (this.isRefreshing) return;
                
                this.startY = event.touches[0].clientY;
                this.isPulling = true;
            },
            
            handleTouchMove(event) {
                if (!this.isPulling || this.isRefreshing) return;
                if (window.scrollY > 0) {
                    this.isPulling = false;
                    this.pullDistance = 0;
                    return;
                }
                
                const currentY = event.touches[0].clientY;
                const delta = currentY - this.startY;
                
                if (delta > 0) {
                    event.preventDefault();
                    // Apply resistance
                    this.pullDistance = delta * 0.5;
                    
                    // Haptic feedback at threshold
                    if (this.pullProgress >= 1 && !this._hapticTriggered) {
                        this._hapticTriggered = true;
                        if (window.haptics) {
                            window.haptics.selection();
                        }
                    } else if (this.pullProgress < 1) {
                        this._hapticTriggered = false;
                    }
                }
            },
            
            async handleTouchEnd() {
                if (!this.isPulling || this.isRefreshing) return;
                
                this.isPulling = false;
                
                if (this.pullProgress >= 1) {
                    await this.refresh();
                } else {
                    this.pullDistance = 0;
                }
            },
            
            async refresh() {
                this.isRefreshing = true;
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.refresh();
                }
                
                try {
                    if (this.refreshUrl) {
                        // Fetch new data
                        const response = await fetch(this.refreshUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        });
                        
                        if (!response.ok) throw new Error('Refresh failed');
                        
                        // Dispatch event with response data
                        const data = await response.json();
                        this.$dispatch('refresh-complete', data);
                    } else {
                        // Just reload the page
                        window.location.reload();
                        return;
                    }
                    
                    // Success haptic
                    if (window.haptics) {
                        window.haptics.success();
                    }
                } catch (error) {
                    console.error('Refresh error:', error);
                    // Error haptic
                    if (window.haptics) {
                        window.haptics.error();
                    }
                    this.$dispatch('refresh-error', { error });
                }
                
                // Delay to show success state
                await new Promise(resolve => setTimeout(resolve, 300));
                
                this.isRefreshing = false;
                this.pullDistance = 0;
                this._hapticTriggered = false;
            }
        }));
    });
</script>
