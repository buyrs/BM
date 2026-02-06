<!-- Keyboard Shortcuts Help Modal -->
<div 
    x-data="{ open: false }"
    @show-shortcuts-help.window="open = true"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] overflow-y-auto"
>
    <!-- Backdrop -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-secondary-900/60 backdrop-blur-sm"
    ></div>
    
    <!-- Modal -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.away="open = false"
            class="w-full max-w-2xl bg-white dark:bg-secondary-800 rounded-2xl shadow-strong overflow-hidden"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
                <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">Keyboard Shortcuts</h2>
                <button 
                    @click="open = false"
                    class="p-2 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="p-6 max-h-[60vh] overflow-y-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Navigation -->
                    <div>
                        <h3 class="text-sm font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mb-3">Navigation</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Go to Dashboard</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">G</kbd>
                                    <span class="text-secondary-400 text-xs">then</span>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">D</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Go to Missions</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">G</kbd>
                                    <span class="text-secondary-400 text-xs">then</span>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">M</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Go to Properties</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">G</kbd>
                                    <span class="text-secondary-400 text-xs">then</span>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">P</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Go to Users</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">G</kbd>
                                    <span class="text-secondary-400 text-xs">then</span>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">U</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Go to Settings</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">G</kbd>
                                    <span class="text-secondary-400 text-xs">then</span>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">S</kbd>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div>
                        <h3 class="text-sm font-semibold text-secondary-500 dark:text-secondary-400 uppercase tracking-wider mb-3">Actions</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Command Palette</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">⌘</kbd>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">K</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Search</span>
                                <div class="flex items-center gap-1">
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">⌘</kbd>
                                    <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">/</kbd>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">New Mission</span>
                                <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">N</kbd>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Show this Help</span>
                                <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">?</kbd>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-secondary-700 dark:text-secondary-300">Close Modal/Panel</span>
                                <kbd class="px-2 py-1 text-xs font-medium text-secondary-500 bg-secondary-100 dark:bg-secondary-700 dark:text-secondary-400 rounded">Esc</kbd>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tip -->
                <div class="mt-6 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-xl border border-primary-100 dark:border-primary-800">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-primary-800 dark:text-primary-200">
                                <strong>Pro tip:</strong> Press <kbd class="px-1.5 py-0.5 text-xs bg-primary-100 dark:bg-primary-800 rounded">⌘ K</kbd> to open the Command Palette for quick access to all actions and pages.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-4 bg-secondary-50 dark:bg-secondary-900/50 border-t border-secondary-200 dark:border-secondary-700">
                <p class="text-xs text-secondary-500 dark:text-secondary-400 text-center">
                    On Windows/Linux, use <kbd class="px-1 py-0.5 bg-secondary-200 dark:bg-secondary-700 rounded">Ctrl</kbd> instead of <kbd class="px-1 py-0.5 bg-secondary-200 dark:bg-secondary-700 rounded">⌘</kbd>
                </p>
            </div>
        </div>
    </div>
</div>
