@props([
    'showOnConflict' => true,
])

<!-- Conflict Resolution Modal Component -->
<div 
    x-data="conflictResolver()"
    x-show="hasConflicts"
    x-transition
    class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="dismiss()"
>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

    <!-- Modal -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Header -->
            <div class="flex items-center gap-4 px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 bg-warning-50 dark:bg-warning-900/20">
                <div class="w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-secondary-900 dark:text-white">
                        Sync Conflict Detected
                    </h2>
                    <p class="text-sm text-secondary-500 dark:text-secondary-400">
                        Your local changes conflict with the server version.
                    </p>
                </div>
            </div>

            <!-- Conflict Details -->
            <div class="p-6">
                <template x-if="currentConflict">
                    <div class="space-y-6">
                        <!-- Item Info -->
                        <div class="text-center mb-6">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-secondary-100 dark:bg-secondary-800 text-secondary-700 dark:text-secondary-300">
                                <span x-text="currentConflict.type"></span>
                            </span>
                        </div>

                        <!-- Side by Side Comparison -->
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Local Version -->
                            <div class="rounded-xl border-2 border-primary-200 dark:border-primary-800 overflow-hidden">
                                <div class="px-4 py-2 bg-primary-50 dark:bg-primary-900/30 border-b border-primary-200 dark:border-primary-800">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Your Version</span>
                                    </div>
                                    <p class="text-xs text-primary-500 mt-1" x-text="formatTime(currentConflict.local.updatedAt)"></p>
                                </div>
                                <div class="p-4 bg-white dark:bg-secondary-900">
                                    <pre class="text-xs text-secondary-600 dark:text-secondary-400 overflow-x-auto" x-text="formatData(currentConflict.local.data)"></pre>
                                </div>
                            </div>

                            <!-- Server Version -->
                            <div class="rounded-xl border-2 border-secondary-200 dark:border-secondary-700 overflow-hidden">
                                <div class="px-4 py-2 bg-secondary-100 dark:bg-secondary-800 border-b border-secondary-200 dark:border-secondary-700">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                        </svg>
                                        <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Server Version</span>
                                    </div>
                                    <p class="text-xs text-secondary-500 mt-1" x-text="formatTime(currentConflict.server.updated_at)"></p>
                                </div>
                                <div class="p-4 bg-white dark:bg-secondary-900">
                                    <pre class="text-xs text-secondary-600 dark:text-secondary-400 overflow-x-auto" x-text="formatData(currentConflict.server)"></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Resolution Options -->
                        <div class="space-y-3">
                            <p class="text-sm font-medium text-secondary-900 dark:text-white">Choose how to resolve:</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <!-- Keep Local -->
                                <button
                                    @click="resolve('keep-local')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-transparent hover:border-primary-500 bg-primary-50 dark:bg-primary-900/20 transition-all group"
                                >
                                    <svg class="w-8 h-8 text-primary-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Keep Mine</span>
                                    <span class="text-xs text-primary-500">Overwrite server</span>
                                </button>

                                <!-- Keep Server -->
                                <button
                                    @click="resolve('keep-server')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-transparent hover:border-secondary-400 bg-secondary-100 dark:bg-secondary-800 transition-all group"
                                >
                                    <svg class="w-8 h-8 text-secondary-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span class="text-sm font-medium text-secondary-700 dark:text-secondary-300">Use Server</span>
                                    <span class="text-xs text-secondary-500">Discard my changes</span>
                                </button>

                                <!-- Merge -->
                                <button
                                    @click="resolve('merge')"
                                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-transparent hover:border-success-500 bg-success-50 dark:bg-success-900/20 transition-all group"
                                >
                                    <svg class="w-8 h-8 text-success-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <span class="text-sm font-medium text-success-700 dark:text-success-300">Merge Both</span>
                                    <span class="text-xs text-success-500">Combine changes</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
                <p class="text-sm text-secondary-500 dark:text-secondary-400">
                    <span x-text="conflictIndex + 1"></span> of <span x-text="conflicts.length"></span> conflicts
                </p>
                <button
                    @click="dismiss()"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white transition-colors"
                >
                    Resolve Later
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('conflictResolver', () => ({
            conflicts: [],
            conflictIndex: 0,
            hasConflicts: false,

            get currentConflict() {
                return this.conflicts[this.conflictIndex] || null;
            },

            init() {
                // Listen for conflicts from sync manager
                window.addEventListener('sync-conflict', (e) => {
                    this.conflicts.push(e.detail);
                    this.hasConflicts = true;
                });
            },

            async resolve(strategy) {
                const conflict = this.currentConflict;
                if (!conflict) return;

                try {
                    if (window.missionDataManager?.syncManager) {
                        await window.missionDataManager.syncManager.resolveConflict(
                            conflict.local,
                            conflict.server,
                            strategy
                        );
                    }

                    // Move to next conflict
                    this.conflicts.splice(this.conflictIndex, 1);
                    
                    if (this.conflicts.length === 0) {
                        this.hasConflicts = false;
                    } else if (this.conflictIndex >= this.conflicts.length) {
                        this.conflictIndex = this.conflicts.length - 1;
                    }

                    // Haptic feedback
                    if (window.haptics) {
                        window.haptics.success();
                    }

                    if (window.toast) {
                        window.toast.show({
                            message: 'Conflict resolved!',
                            type: 'success',
                            duration: 2000,
                        });
                    }
                } catch (error) {
                    console.error('Error resolving conflict:', error);
                    if (window.toast) {
                        window.toast.show({
                            message: 'Failed to resolve conflict',
                            type: 'error',
                        });
                    }
                }
            },

            dismiss() {
                this.hasConflicts = false;
            },

            formatTime(timestamp) {
                if (!timestamp) return 'Unknown';
                const date = new Date(typeof timestamp === 'number' ? timestamp : timestamp);
                return date.toLocaleString();
            },

            formatData(data) {
                if (!data) return 'No data';
                try {
                    // Show only relevant fields, not the entire object
                    const relevant = {};
                    const keys = ['status', 'notes', 'completed', 'value', 'title', 'description'];
                    for (const key of keys) {
                        if (data[key] !== undefined) {
                            relevant[key] = data[key];
                        }
                    }
                    return JSON.stringify(relevant, null, 2);
                } catch (e) {
                    return String(data);
                }
            }
        }));
    });
</script>
