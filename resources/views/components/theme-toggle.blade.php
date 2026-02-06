@props([
    'size' => 'md',       // sm, md, lg
    'showLabel' => false, // Show "Light" / "Dark" label
    'position' => 'right', // For positioning (left, right)
])

@php
    $sizes = [
        'sm' => ['toggle' => 'w-10 h-6', 'circle' => 'w-4 h-4', 'translate' => 'translate-x-4', 'icon' => 'w-3 h-3'],
        'md' => ['toggle' => 'w-12 h-7', 'circle' => 'w-5 h-5', 'translate' => 'translate-x-5', 'icon' => 'w-3.5 h-3.5'],
        'lg' => ['toggle' => 'w-14 h-8', 'circle' => 'w-6 h-6', 'translate' => 'translate-x-6', 'icon' => 'w-4 h-4'],
    ];
    
    $config = $sizes[$size] ?? $sizes['md'];
@endphp

<!-- Theme Toggle Component -->
<div 
    x-data="themeToggle()"
    x-init="init()"
    class="inline-flex items-center gap-2"
    {{ $attributes }}
>
    @if($showLabel)
        <span 
            class="text-sm font-medium text-secondary-600 dark:text-secondary-300 transition-colors"
            x-text="isDark ? 'Dark' : 'Light'"
        ></span>
    @endif
    
    <button 
        type="button"
        @click="toggle()"
        :class="isDark ? 'bg-primary-600' : 'bg-secondary-200'"
        class="{{ $config['toggle'] }} relative inline-flex items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-secondary-900"
        role="switch"
        :aria-checked="isDark"
        aria-label="Toggle dark mode"
    >
        <span class="sr-only">Toggle dark mode</span>
        
        <!-- Toggle circle with icons -->
        <span 
            :class="isDark ? '{{ $config['translate'] }} bg-secondary-900' : 'translate-x-1 bg-white'"
            class="{{ $config['circle'] }} inline-flex items-center justify-center rounded-full shadow-md transform transition-all duration-300"
        >
            <!-- Sun icon (light mode) -->
            <svg 
                x-show="!isDark"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 rotate-90"
                x-transition:enter-end="opacity-100 rotate-0"
                class="{{ $config['icon'] }} text-warning-500"
                fill="currentColor" 
                viewBox="0 0 20 20"
            >
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
            </svg>
            
            <!-- Moon icon (dark mode) -->
            <svg 
                x-show="isDark"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -rotate-90"
                x-transition:enter-end="opacity-100 rotate-0"
                class="{{ $config['icon'] }} text-primary-400"
                fill="currentColor" 
                viewBox="0 0 20 20"
            >
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
            </svg>
        </span>
    </button>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('themeToggle', () => ({
            isDark: false,
            
            init() {
                // Check saved preference or system preference
                this.isDark = this.getStoredTheme() === 'dark' || 
                    (!this.getStoredTheme() && window.matchMedia('(prefers-color-scheme: dark)').matches);
                
                this.applyTheme();
                
                // Listen for system preference changes
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (!this.getStoredTheme()) {
                        this.isDark = e.matches;
                        this.applyTheme();
                    }
                });
            },
            
            toggle() {
                this.isDark = !this.isDark;
                this.applyTheme();
                this.storeTheme();
                
                // Haptic feedback
                if (window.haptics) {
                    window.haptics.selection();
                }
                
                // Dispatch event for other components
                window.dispatchEvent(new CustomEvent('theme-changed', { 
                    detail: { theme: this.isDark ? 'dark' : 'light' }
                }));
            },
            
            applyTheme() {
                if (this.isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                
                // Update meta theme-color for mobile browsers
                const metaTheme = document.querySelector('meta[name="theme-color"]');
                if (metaTheme) {
                    metaTheme.content = this.isDark ? '#1f2937' : '#ffffff';
                }
            },
            
            getStoredTheme() {
                try {
                    return localStorage.getItem('theme');
                } catch {
                    return null;
                }
            },
            
            storeTheme() {
                try {
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                } catch {
                    // localStorage not available
                }
            },
            
            // Public method to set theme programmatically
            setTheme(theme) {
                this.isDark = theme === 'dark';
                this.applyTheme();
                this.storeTheme();
            }
        }));
    });
    
    // Apply theme immediately on page load to prevent flash
    (function() {
        const stored = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (stored === 'dark' || (!stored && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
