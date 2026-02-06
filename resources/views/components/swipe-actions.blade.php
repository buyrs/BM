@props([
    'leftActions' => [],     // Array of actions: [['label' => 'Archive', 'icon' => '...', 'color' => 'warning', 'action' => 'archive']]
    'rightActions' => [],    // Array of actions: [['label' => 'Delete', 'icon' => '...', 'color' => 'danger', 'action' => 'delete']]
    'threshold' => 80,       // Swipe distance to trigger action
    'disabled' => false,
])

@php
    $colorClasses = [
        'primary' => 'bg-primary-500 text-white',
        'secondary' => 'bg-secondary-500 text-white',
        'success' => 'bg-success-500 text-white',
        'warning' => 'bg-warning-500 text-white',
        'danger' => 'bg-danger-500 text-white',
        'info' => 'bg-primary-400 text-white',
    ];
@endphp

<!-- Swipe Actions Component -->
<div 
    x-data="swipeActions({ 
        leftActions: {{ json_encode($leftActions) }},
        rightActions: {{ json_encode($rightActions) }},
        threshold: {{ $threshold }},
        disabled: {{ $disabled ? 'true' : 'false' }}
    })"
    class="relative overflow-hidden"
    {{ $attributes }}
>
    <!-- Left Actions (revealed when swiping right) -->
    @if(count($leftActions) > 0)
        <div 
            x-ref="leftActions"
            class="absolute inset-y-0 left-0 flex items-stretch"
            :style="{ width: `${Math.abs(Math.min(0, swipeOffset))}px`, opacity: leftActionsOpacity }"
        >
            @foreach($leftActions as $index => $action)
                <button 
                    type="button"
                    @click="triggerAction('left', {{ $index }})"
                    class="flex-1 flex items-center justify-center px-4 {{ $colorClasses[$action['color'] ?? 'primary'] }} transition-colors hover:brightness-110"
                >
                    @if(isset($action['icon']))
                        <span class="text-xl">{!! $action['icon'] !!}</span>
                    @endif
                    <span 
                        x-show="Math.abs(swipeOffset) > 60"
                        x-transition
                        class="ml-2 text-sm font-medium whitespace-nowrap"
                    >{{ $action['label'] ?? '' }}</span>
                </button>
            @endforeach
        </div>
    @endif
    
    <!-- Right Actions (revealed when swiping left) -->
    @if(count($rightActions) > 0)
        <div 
            x-ref="rightActions"
            class="absolute inset-y-0 right-0 flex items-stretch"
            :style="{ width: `${Math.max(0, -swipeOffset)}px`, opacity: rightActionsOpacity }"
        >
            @foreach($rightActions as $index => $action)
                <button 
                    type="button"
                    @click="triggerAction('right', {{ $index }})"
                    class="flex-1 flex items-center justify-center px-4 {{ $colorClasses[$action['color'] ?? 'danger'] }} transition-colors hover:brightness-110"
                >
                    @if(isset($action['icon']))
                        <span class="text-xl">{!! $action['icon'] !!}</span>
                    @endif
                    <span 
                        x-show="Math.abs(swipeOffset) > 60"
                        x-transition
                        class="ml-2 text-sm font-medium whitespace-nowrap"
                    >{{ $action['label'] ?? '' }}</span>
                </button>
            @endforeach
        </div>
    @endif
    
    <!-- Main Content -->
    <div 
        x-ref="content"
        @touchstart.passive="handleTouchStart($event)"
        @touchmove="handleTouchMove($event)"
        @touchend.passive="handleTouchEnd($event)"
        :style="{ transform: `translateX(${swipeOffset}px)` }"
        class="relative bg-white dark:bg-secondary-800 transition-transform duration-200"
        :class="{ 'transition-none': isSwiping }"
    >
        {{ $slot }}
    </div>
    
    <!-- Accessibility: Fallback buttons for non-touch devices -->
    <div class="hidden sm:flex absolute right-2 top-1/2 -translate-y-1/2 gap-1 opacity-0 group-hover/swipe:opacity-100 transition-opacity">
        @foreach($rightActions as $action)
            <button 
                type="button"
                x-on:click="$dispatch('swipe-action', { action: '{{ $action['action'] ?? '' }}', direction: 'right' })"
                class="p-2 rounded-lg {{ $colorClasses[$action['color'] ?? 'danger'] }} text-xs"
                title="{{ $action['label'] ?? '' }}"
            >
                @if(isset($action['icon']))
                    {!! $action['icon'] !!}
                @else
                    {{ $action['label'] ?? '' }}
                @endif
            </button>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('swipeActions', (config) => ({
            swipeOffset: 0,
            startX: 0,
            startY: 0,
            isSwiping: false,
            isHorizontalSwipe: null,
            threshold: config.threshold || 80,
            leftActions: config.leftActions || [],
            rightActions: config.rightActions || [],
            disabled: config.disabled || false,
            
            get leftActionsOpacity() {
                if (this.swipeOffset <= 0) return 0;
                return Math.min(this.swipeOffset / 50, 1);
            },
            
            get rightActionsOpacity() {
                if (this.swipeOffset >= 0) return 0;
                return Math.min(Math.abs(this.swipeOffset) / 50, 1);
            },
            
            handleTouchStart(event) {
                if (this.disabled) return;
                
                const touch = event.touches[0];
                this.startX = touch.clientX;
                this.startY = touch.clientY;
                this.isSwiping = true;
                this.isHorizontalSwipe = null;
            },
            
            handleTouchMove(event) {
                if (!this.isSwiping || this.disabled) return;
                
                const touch = event.touches[0];
                const deltaX = touch.clientX - this.startX;
                const deltaY = touch.clientY - this.startY;
                
                // Determine swipe direction on first significant move
                if (this.isHorizontalSwipe === null) {
                    if (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10) {
                        this.isHorizontalSwipe = Math.abs(deltaX) > Math.abs(deltaY);
                    }
                    return;
                }
                
                // Only process horizontal swipes
                if (!this.isHorizontalSwipe) return;
                
                event.preventDefault();
                
                // Check if actions exist for this direction
                const canSwipeRight = deltaX > 0 && this.leftActions.length > 0;
                const canSwipeLeft = deltaX < 0 && this.rightActions.length > 0;
                
                if (canSwipeRight || canSwipeLeft) {
                    // Apply resistance at edges
                    const maxSwipe = 150;
                    const resistance = 0.5;
                    
                    if (Math.abs(deltaX) > maxSwipe) {
                        const excess = Math.abs(deltaX) - maxSwipe;
                        this.swipeOffset = Math.sign(deltaX) * (maxSwipe + excess * resistance);
                    } else {
                        this.swipeOffset = deltaX;
                    }
                }
            },
            
            handleTouchEnd() {
                if (!this.isSwiping || this.disabled) return;
                
                this.isSwiping = false;
                
                // Check if threshold was met
                if (Math.abs(this.swipeOffset) >= this.threshold) {
                    // Determine which action to trigger
                    const direction = this.swipeOffset > 0 ? 'left' : 'right';
                    const actions = direction === 'left' ? this.leftActions : this.rightActions;
                    
                    if (actions.length === 1) {
                        // Auto-trigger single action
                        this.triggerAction(direction, 0);
                    } else {
                        // Hold open for user to select
                        this.swipeOffset = this.swipeOffset > 0 ? 100 : -100;
                    }
                } else {
                    // Snap back
                    this.swipeOffset = 0;
                }
            },
            
            triggerAction(direction, index) {
                const actions = direction === 'left' ? this.leftActions : this.rightActions;
                const action = actions[index];
                
                if (!action) return;
                
                // Haptic feedback
                if (window.haptics) {
                    if (action.color === 'danger') {
                        window.haptics.warning();
                    } else {
                        window.haptics.selection();
                    }
                }
                
                // Dispatch event
                this.$dispatch('swipe-action', { 
                    action: action.action || action.label, 
                    direction,
                    index
                });
                
                // Animate out and reset
                this.swipeOffset = direction === 'left' ? 300 : -300;
                
                setTimeout(() => {
                    this.swipeOffset = 0;
                }, 300);
            },
            
            reset() {
                this.swipeOffset = 0;
            }
        }));
    });
</script>
