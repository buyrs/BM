@props([
    'type' => 'property',  // property, mission
    'id' => null,
    'isFavorited' => false,
    'size' => 'md',  // sm, md, lg
    'showLabel' => false,
])

@php
    $sizes = [
        'sm' => 'w-5 h-5',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
    ];
    $iconSize = $sizes[$size] ?? $sizes['md'];
@endphp

<!-- Favorite Button Component -->
<div 
    x-data="favoriteButton({ 
        type: '{{ $type }}', 
        id: {{ $id ?? 'null' }}, 
        initialState: {{ $isFavorited ? 'true' : 'false' }} 
    })"
    {{ $attributes->merge(['class' => 'inline-flex']) }}
>
    <button
        type="button"
        @click.prevent.stop="toggle()"
        :disabled="loading"
        class="group relative inline-flex items-center justify-center rounded-full p-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-secondary-900"
        :class="{
            'text-warning-500 hover:text-warning-600': isFavorited,
            'text-secondary-400 hover:text-warning-500 dark:text-secondary-500 dark:hover:text-warning-400': !isFavorited,
            'opacity-50 cursor-not-allowed': loading
        }"
        :aria-label="isFavorited ? 'Remove from favorites' : 'Add to favorites'"
        :aria-pressed="isFavorited"
    >
        <!-- Filled Star (favorited) -->
        <svg 
            x-show="isFavorited" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-50"
            x-transition:enter-end="opacity-100 scale-100"
            class="{{ $iconSize }} fill-current"
            viewBox="0 0 24 24"
        >
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
        
        <!-- Outline Star (not favorited) -->
        <svg 
            x-show="!isFavorited" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-50"
            x-transition:enter-end="opacity-100 scale-100"
            class="{{ $iconSize }} fill-none stroke-current stroke-2"
            viewBox="0 0 24 24"
        >
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>

        <!-- Loading Spinner -->
        <svg 
            x-show="loading" 
            class="{{ $iconSize }} absolute animate-spin text-primary-500"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <!-- Pulse animation on click -->
        <span 
            x-show="animating"
            x-transition:leave="transition ease-out duration-300"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-150"
            class="absolute inset-0 rounded-full bg-warning-500/20"
        ></span>
    </button>

    @if($showLabel)
        <span 
            class="ml-1 text-sm font-medium transition-colors"
            :class="isFavorited ? 'text-warning-600 dark:text-warning-400' : 'text-secondary-500 dark:text-secondary-400'"
            x-text="isFavorited ? 'Favorited' : 'Add to favorites'"
        ></span>
    @endif
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('favoriteButton', (config) => ({
            type: config.type,
            id: config.id,
            isFavorited: config.initialState,
            loading: false,
            animating: false,

            async toggle() {
                if (this.loading || !this.id) return;

                this.loading = true;
                this.animating = true;

                // Haptic feedback
                if (window.haptics) {
                    window.haptics.selection();
                }

                // Optimistic update
                const previousState = this.isFavorited;
                this.isFavorited = !this.isFavorited;

                try {
                    const response = await fetch('/api/favorites/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            type: this.type,
                            id: this.id,
                        }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.isFavorited = data.is_favorited;
                        
                        // Show toast notification
                        if (window.toast) {
                            window.toast.show({
                                message: data.message,
                                type: 'success',
                                duration: 2000,
                            });
                        }

                        // Dispatch event for other components
                        window.dispatchEvent(new CustomEvent('favorite-toggled', {
                            detail: {
                                type: this.type,
                                id: this.id,
                                isFavorited: this.isFavorited,
                            }
                        }));
                    } else {
                        // Revert on failure
                        this.isFavorited = previousState;
                        if (window.toast) {
                            window.toast.show({
                                message: data.message || 'Failed to update favorite',
                                type: 'error',
                            });
                        }
                    }
                } catch (error) {
                    console.error('Favorite toggle error:', error);
                    // Revert on error
                    this.isFavorited = previousState;
                    if (window.toast) {
                        window.toast.show({
                            message: 'Network error. Please try again.',
                            type: 'error',
                        });
                    }
                } finally {
                    this.loading = false;
                    setTimeout(() => {
                        this.animating = false;
                    }, 300);
                }
            }
        }));
    });
</script>
