@props([
    'position' => 'bottom-right',  // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
    'duration' => 5000,            // Auto-dismiss duration in ms (0 = no auto-dismiss)
    'maxToasts' => 5,
])

@php
    $positions = [
        'top-left' => 'top-4 left-4',
        'top-center' => 'top-4 left-1/2 -translate-x-1/2',
        'top-right' => 'top-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'bottom-center' => 'bottom-4 left-1/2 -translate-x-1/2',
        'bottom-right' => 'bottom-4 right-4',
    ];
    
    $positionClasses = $positions[$position] ?? $positions['bottom-right'];
    $isBottom = str_starts_with($position, 'bottom');
@endphp

<!-- Toast Container -->
<div 
    x-data="toastNotifications({ 
        position: '{{ $position }}',
        duration: {{ $duration }},
        maxToasts: {{ $maxToasts }}
    })"
    @toast.window="addToast($event.detail)"
    class="fixed z-[200] {{ $positionClasses }} flex flex-col {{ $isBottom ? 'flex-col-reverse' : '' }} gap-3 pointer-events-none"
    style="max-width: calc(100vw - 2rem);"
    {{ $attributes }}
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.visible"
            x-transition:enter="transition ease-smooth duration-300"
            x-transition:enter-start="opacity-0 {{ $isBottom ? 'translate-y-4' : '-translate-y-4' }} scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="pointer-events-auto w-full max-w-sm bg-white dark:bg-secondary-800 rounded-xl shadow-strong border border-secondary-200 dark:border-secondary-700 overflow-hidden"
        >
            <div class="flex items-start gap-3 p-4">
                <!-- Icon -->
                <div 
                    class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full"
                    :class="{
                        'bg-success-100 dark:bg-success-900/50 text-success-500': toast.type === 'success',
                        'bg-danger-100 dark:bg-danger-900/50 text-danger-500': toast.type === 'error',
                        'bg-warning-100 dark:bg-warning-900/50 text-warning-500': toast.type === 'warning',
                        'bg-primary-100 dark:bg-primary-900/50 text-primary-500': toast.type === 'info' || !toast.type
                    }"
                >
                    <!-- Success icon -->
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 animate-success-ring" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </template>
                    
                    <!-- Error icon -->
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </template>
                    
                    <!-- Warning icon -->
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </template>
                    
                    <!-- Info icon (default) -->
                    <template x-if="toast.type === 'info' || !toast.type">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </template>
                </div>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <p 
                        x-show="toast.title"
                        class="text-sm font-semibold text-secondary-900 dark:text-white"
                        x-text="toast.title"
                    ></p>
                    <p 
                        class="text-sm text-secondary-600 dark:text-secondary-300"
                        :class="{ 'mt-1': toast.title }"
                        x-text="toast.message"
                    ></p>
                    
                    <!-- Action button -->
                    <button 
                        x-show="toast.action"
                        @click="handleAction(toast)"
                        class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                        x-text="toast.actionLabel || 'View'"
                    ></button>
                </div>
                
                <!-- Close button -->
                <button 
                    @click="removeToast(toast.id)"
                    class="flex-shrink-0 p-1 rounded-lg text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Progress bar (auto-dismiss indicator) -->
            <div 
                x-show="toast.duration > 0"
                class="h-1 bg-secondary-100 dark:bg-secondary-700"
            >
                <div 
                    class="h-full transition-all ease-linear"
                    :class="{
                        'bg-success-500': toast.type === 'success',
                        'bg-danger-500': toast.type === 'error',
                        'bg-warning-500': toast.type === 'warning',
                        'bg-primary-500': toast.type === 'info' || !toast.type
                    }"
                    :style="{ 
                        width: toast.progress + '%',
                        transitionDuration: (toast.duration / 100) + 'ms'
                    }"
                ></div>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastNotifications', (config) => ({
            toasts: [],
            position: config.position || 'bottom-right',
            defaultDuration: config.duration || 5000,
            maxToasts: config.maxToasts || 5,
            toastIdCounter: 0,
            
            addToast(options) {
                const id = ++this.toastIdCounter;
                const duration = options.duration ?? this.defaultDuration;
                
                const toast = {
                    id,
                    type: options.type || 'info',
                    title: options.title || '',
                    message: options.message || '',
                    action: options.action,
                    actionLabel: options.actionLabel,
                    duration,
                    progress: 100,
                    visible: true
                };
                
                // Limit number of toasts
                if (this.toasts.length >= this.maxToasts) {
                    this.toasts.shift();
                }
                
                this.toasts.push(toast);
                
                // Haptic feedback
                if (window.haptics) {
                    if (toast.type === 'success') window.haptics.success();
                    else if (toast.type === 'error') window.haptics.error();
                    else if (toast.type === 'warning') window.haptics.warning();
                    else window.haptics.light();
                }
                
                // Auto-dismiss with progress
                if (duration > 0) {
                    this.startProgress(toast);
                }
            },
            
            startProgress(toast) {
                const interval = duration / 100;
                const timer = setInterval(() => {
                    const t = this.toasts.find(t => t.id === toast.id);
                    if (!t) {
                        clearInterval(timer);
                        return;
                    }
                    
                    t.progress -= 1;
                    
                    if (t.progress <= 0) {
                        clearInterval(timer);
                        this.removeToast(toast.id);
                    }
                }, toast.duration / 100);
            },
            
            removeToast(id) {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 200);
                }
            },
            
            handleAction(toast) {
                if (typeof toast.action === 'function') {
                    toast.action();
                } else if (typeof toast.action === 'string') {
                    window.location.href = toast.action;
                }
                this.removeToast(toast.id);
            },
            
            // Public API for showing toasts
            success(message, options = {}) {
                this.addToast({ type: 'success', message, ...options });
            },
            
            error(message, options = {}) {
                this.addToast({ type: 'error', message, ...options });
            },
            
            warning(message, options = {}) {
                this.addToast({ type: 'warning', message, ...options });
            },
            
            info(message, options = {}) {
                this.addToast({ type: 'info', message, ...options });
            }
        }));
    });
    
    // Global helper function
    window.showToast = function(options) {
        window.dispatchEvent(new CustomEvent('toast', { detail: options }));
    };
    
    // Convenience methods
    window.toast = {
        success: (message, options = {}) => showToast({ type: 'success', message, ...options }),
        error: (message, options = {}) => showToast({ type: 'error', message, ...options }),
        warning: (message, options = {}) => showToast({ type: 'warning', message, ...options }),
        info: (message, options = {}) => showToast({ type: 'info', message, ...options })
    };
</script>
