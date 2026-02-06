@props([
    'name' => 'bottom-sheet',
    'size' => 'medium',        // small, medium, large, full
    'title' => null,
    'showHandle' => true,
    'showClose' => true,
    'closeOnBackdrop' => true,
    'snapPoints' => null,      // Array of snap points (e.g., [0.25, 0.5, 1])
])

@php
    $sizeClasses = [
        'small' => 'max-h-[30vh]',
        'medium' => 'max-h-[50vh]',
        'large' => 'max-h-[75vh]',
        'full' => 'max-h-[95vh]',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['medium'];
@endphp

<!-- Bottom Sheet Component -->
<div 
    x-data="bottomSheet({
        name: '{{ $name }}',
        snapPoints: {{ $snapPoints ? json_encode($snapPoints) : 'null' }},
        closeOnBackdrop: {{ $closeOnBackdrop ? 'true' : 'false' }}
    })"
    x-show="isOpen"
    x-cloak
    @open-{{ $name }}.window="open()"
    @close-{{ $name }}.window="close()"
    class="fixed inset-0 z-50"
    {{ $attributes }}
>
    <!-- Backdrop -->
    <div 
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeOnBackdrop && close()"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
    ></div>
    
    <!-- Sheet -->
    <div 
        x-ref="sheet"
        x-show="isOpen"
        x-transition:enter="transition ease-smooth duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        @touchstart.passive="handleTouchStart($event)"
        @touchmove.passive="handleTouchMove($event)"
        @touchend.passive="handleTouchEnd($event)"
        :style="{ transform: `translateY(${dragOffset}px)` }"
        class="fixed bottom-0 left-0 right-0 bg-white dark:bg-secondary-800 rounded-t-3xl shadow-bottom-sheet {{ $sizeClass }} flex flex-col overflow-hidden touch-none"
        style="padding-bottom: env(safe-area-inset-bottom, 0px);"
    >
        <!-- Drag Handle -->
        @if($showHandle)
            <div class="flex justify-center pt-3 pb-2 cursor-grab active:cursor-grabbing">
                <div class="w-12 h-1.5 bg-secondary-300 dark:bg-secondary-600 rounded-full"></div>
            </div>
        @endif
        
        <!-- Header -->
        @if($title || $showClose)
            <div class="flex items-center justify-between px-4 py-3 border-b border-secondary-100 dark:border-secondary-700">
                @if($title)
                    <h3 class="text-lg font-semibold text-secondary-900 dark:text-white">{{ $title }}</h3>
                @else
                    <div></div>
                @endif
                
                @if($showClose)
                    <button 
                        @click="close()" 
                        class="p-2 -mr-2 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 transition-colors rounded-full hover:bg-secondary-100 dark:hover:bg-secondary-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        @endif
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto overscroll-contain p-4">
            {{ $slot }}
        </div>
        
        <!-- Footer (optional) -->
        @isset($footer)
            <div class="border-t border-secondary-100 dark:border-secondary-700 p-4 bg-white dark:bg-secondary-800">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('bottomSheet', (config) => ({
            isOpen: false,
            dragOffset: 0,
            startY: 0,
            currentY: 0,
            isDragging: false,
            sheetHeight: 0,
            closeOnBackdrop: config.closeOnBackdrop ?? true,
            snapPoints: config.snapPoints,
            
            init() {
                // Listen for open/close events
                this.$watch('isOpen', (value) => {
                    if (value) {
                        document.body.style.overflow = 'hidden';
                        this.$nextTick(() => {
                            this.sheetHeight = this.$refs.sheet?.offsetHeight || 0;
                        });
                        // Haptic feedback on open
                        if (window.haptics) {
                            window.haptics.selection();
                        }
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            },
            
            open() {
                this.isOpen = true;
                this.dragOffset = 0;
            },
            
            close() {
                this.isOpen = false;
                this.dragOffset = 0;
                if (window.haptics) {
                    window.haptics.selection();
                }
            },
            
            handleTouchStart(event) {
                // Only allow dragging from the handle area or top of sheet
                const touch = event.touches[0];
                const sheetTop = this.$refs.sheet?.getBoundingClientRect().top || 0;
                
                // Allow drag if within top 60px of sheet (handle area)
                if (touch.clientY - sheetTop < 60) {
                    this.isDragging = true;
                    this.startY = touch.clientY;
                    this.currentY = touch.clientY;
                }
            },
            
            handleTouchMove(event) {
                if (!this.isDragging) return;
                
                this.currentY = event.touches[0].clientY;
                const delta = this.currentY - this.startY;
                
                // Only allow dragging down
                if (delta > 0) {
                    // Apply resistance
                    this.dragOffset = delta * 0.8;
                }
            },
            
            handleTouchEnd() {
                if (!this.isDragging) return;
                this.isDragging = false;
                
                const threshold = this.sheetHeight * 0.3;
                
                if (this.snapPoints && this.snapPoints.length > 0) {
                    // Snap to nearest point
                    this.snapToNearestPoint();
                } else if (this.dragOffset > threshold) {
                    // Close if dragged past threshold
                    this.close();
                } else {
                    // Snap back
                    this.dragOffset = 0;
                }
            },
            
            snapToNearestPoint() {
                const currentPosition = this.dragOffset / this.sheetHeight;
                const snapPoint = this.snapPoints.reduce((prev, curr) => {
                    return Math.abs(curr - currentPosition) < Math.abs(prev - currentPosition) ? curr : prev;
                });
                
                if (snapPoint >= 1) {
                    this.close();
                } else {
                    this.dragOffset = this.sheetHeight * snapPoint;
                }
            }
        }));
    });
</script>
