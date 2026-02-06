@props([
    'currentMissionId' => null,
    'showButton' => true,
])

<!-- Inspection Comparison Modal Component -->
<div 
    x-data="inspectionComparison({ 
        currentMissionId: {{ $currentMissionId ?? 'null' }}
    })"
    {{ $attributes }}
>
    @if($showButton)
    <!-- Trigger Button -->
    <button
        @click="open()"
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 rounded-lg transition-colors"
        :disabled="loading"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span>Compare with Previous</span>
    </button>
    @endif

    <!-- Modal -->
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="close()"
    >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

        <!-- Modal Content -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div 
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-6xl bg-white dark:bg-secondary-900 rounded-2xl shadow-2xl overflow-hidden"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-200 dark:border-secondary-700">
                    <div>
                        <h2 class="text-xl font-semibold text-secondary-900 dark:text-white">
                            Inspection Comparison
                        </h2>
                        <p class="text-sm text-secondary-500 dark:text-secondary-400" x-text="comparisonData?.previous ? `Comparing with inspection from ${formatDate(comparisonData.previous.completed_at)}` : 'No previous inspection found'"></p>
                    </div>
                    <button @click="close()" class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors">
                        <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="p-12 text-center">
                    <svg class="animate-spin h-10 w-10 text-primary-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-secondary-500 dark:text-secondary-400">Loading comparison data...</p>
                </div>

                <!-- No Previous Data -->
                <div x-show="!loading && comparisonData && !comparisonData.has_previous" class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                        <svg class="w-8 h-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-secondary-900 dark:text-white mb-2">No Previous Inspection</h3>
                    <p class="text-secondary-500 dark:text-secondary-400">This is the first recorded inspection for this property.</p>
                </div>

                <!-- Comparison Content -->
                <div x-show="!loading && comparisonData?.has_previous" class="max-h-[70vh] overflow-y-auto">
                    
                    <!-- Summary Cards -->
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4 border-b border-secondary-200 dark:border-secondary-700">
                        <div class="text-center p-4 rounded-xl bg-success-50 dark:bg-success-900/20">
                            <div class="text-2xl font-bold text-success-600 dark:text-success-400" x-text="comparisonData?.summary?.improved_count || 0"></div>
                            <div class="text-sm text-success-700 dark:text-success-300">Improved</div>
                        </div>
                        <div class="text-center p-4 rounded-xl bg-danger-50 dark:bg-danger-900/20">
                            <div class="text-2xl font-bold text-danger-600 dark:text-danger-400" x-text="comparisonData?.summary?.declined_count || 0"></div>
                            <div class="text-sm text-danger-700 dark:text-danger-300">Declined</div>
                        </div>
                        <div class="text-center p-4 rounded-xl bg-warning-50 dark:bg-warning-900/20">
                            <div class="text-2xl font-bold text-warning-600 dark:text-warning-400" x-text="comparisonData?.summary?.new_issues_count || 0"></div>
                            <div class="text-sm text-warning-700 dark:text-warning-300">New Issues</div>
                        </div>
                        <div class="text-center p-4 rounded-xl bg-primary-50 dark:bg-primary-900/20">
                            <div class="text-2xl font-bold text-primary-600 dark:text-primary-400" x-text="comparisonData?.summary?.resolved_issues_count || 0"></div>
                            <div class="text-sm text-primary-700 dark:text-primary-300">Resolved</div>
                        </div>
                    </div>

                    <!-- Overall Trend Badge -->
                    <div class="px-6 py-4 flex items-center justify-center border-b border-secondary-200 dark:border-secondary-700">
                        <div 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium"
                            :class="{
                                'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400': comparisonData?.summary?.overall_trend === 'improving',
                                'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400': comparisonData?.summary?.overall_trend === 'declining',
                                'bg-secondary-100 text-secondary-700 dark:bg-secondary-800 dark:text-secondary-400': comparisonData?.summary?.overall_trend === 'stable'
                            }"
                        >
                            <!-- Trend Icon -->
                            <svg x-show="comparisonData?.summary?.overall_trend === 'improving'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <svg x-show="comparisonData?.summary?.overall_trend === 'declining'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                            </svg>
                            <svg x-show="comparisonData?.summary?.overall_trend === 'stable'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
                            </svg>
                            <span x-text="comparisonData?.summary?.overall_trend === 'improving' ? 'Property Improving' : comparisonData?.summary?.overall_trend === 'declining' ? 'Property Declining' : 'Property Stable'"></span>
                        </div>
                    </div>

                    <!-- Side by Side Comparison -->
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-secondary-200 dark:divide-secondary-700">
                        <!-- Previous Inspection -->
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-3 h-3 rounded-full bg-secondary-400"></div>
                                <h3 class="font-medium text-secondary-900 dark:text-white">Previous Inspection</h3>
                            </div>
                            <div class="space-y-3 text-sm">
                                <p><span class="text-secondary-500 dark:text-secondary-400">Date:</span> <span class="text-secondary-900 dark:text-white" x-text="formatDate(comparisonData?.previous?.completed_at)"></span></p>
                                <p><span class="text-secondary-500 dark:text-secondary-400">Checker:</span> <span class="text-secondary-900 dark:text-white" x-text="comparisonData?.previous?.checker?.name || 'Unknown'"></span></p>
                                <p><span class="text-secondary-500 dark:text-secondary-400">Photos:</span> <span class="text-secondary-900 dark:text-white" x-text="comparisonData?.previous?.photo_count || 0"></span></p>
                                <p><span class="text-secondary-500 dark:text-secondary-400">Issues:</span> <span class="text-secondary-900 dark:text-white" x-text="comparisonData?.previous?.issues?.length || 0"></span></p>
                            </div>
                        </div>

                        <!-- Current Inspection -->
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-3 h-3 rounded-full bg-primary-500"></div>
                                <h3 class="font-medium text-secondary-900 dark:text-white">Current Inspection</h3>
                            </div>
                            <div class="space-y-3 text-sm">
                                <p><span class="text-secondary-500 dark:text-secondary-400">Date:</span> <span class="text-secondary-900 dark:text-white" x-text="formatDate(comparisonData?.current?.completed_at)"></span></p>
                                <p><span class="text-secondary-500 dark:text-secondary-400">Checker:</span> <span class="text-secondary-900 dark:text-white" x-text="comparisonData?.current?.checker?.name || 'Unknown'"></span></p>
                                <p><span class="text-secondary-500 dark:text-secondary-400">Photos:</span> <span class="text-secondary-900 dark:text-white" x-text="comparisonData?.current?.photo_count || 0"></span></p>
                                <p><span class="text-secondary-500 dark:text-secondary-400">Issues:</span> <span class="text-secondary-900 dark:text-white" x-text="comparisonData?.current?.issues?.length || 0"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Area Changes List -->
                    <div class="p-6 border-t border-secondary-200 dark:border-secondary-700">
                        <h3 class="font-medium text-secondary-900 dark:text-white mb-4">Area Changes</h3>
                        
                        <!-- Improved Areas -->
                        <template x-if="comparisonData?.changes?.improved?.length > 0">
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-success-600 dark:text-success-400 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Improved
                                </h4>
                                <div class="space-y-2">
                                    <template x-for="change in comparisonData.changes.improved" :key="change.area">
                                        <div class="flex items-center justify-between p-3 rounded-lg bg-success-50 dark:bg-success-900/20">
                                            <span class="text-sm text-secondary-900 dark:text-white" x-text="change.area"></span>
                                            <span class="text-xs text-success-600 dark:text-success-400">
                                                <span x-text="change.from"></span> → <span x-text="change.to"></span>
                                                <span class="ml-2" x-text="`+${change.score_change}`"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Declined Areas -->
                        <template x-if="comparisonData?.changes?.declined?.length > 0">
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-danger-600 dark:text-danger-400 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    </svg>
                                    Declined
                                </h4>
                                <div class="space-y-2">
                                    <template x-for="change in comparisonData.changes.declined" :key="change.area">
                                        <div class="flex items-center justify-between p-3 rounded-lg bg-danger-50 dark:bg-danger-900/20">
                                            <span class="text-sm text-secondary-900 dark:text-white" x-text="change.area"></span>
                                            <span class="text-xs text-danger-600 dark:text-danger-400">
                                                <span x-text="change.from"></span> → <span x-text="change.to"></span>
                                                <span class="ml-2" x-text="change.score_change"></span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- New Issues -->
                        <template x-if="comparisonData?.changes?.new_issues?.length > 0">
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-warning-600 dark:text-warning-400 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    New Issues
                                </h4>
                                <div class="space-y-2">
                                    <template x-for="issue in comparisonData.changes.new_issues" :key="issue.item">
                                        <div class="p-3 rounded-lg bg-warning-50 dark:bg-warning-900/20">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-secondary-900 dark:text-white" x-text="issue.item"></span>
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-warning-200 dark:bg-warning-800 text-warning-700 dark:text-warning-300" x-text="issue.severity"></span>
                                            </div>
                                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1" x-text="issue.description"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-800/50">
                    <button 
                        @click="close()"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-secondary-700 dark:text-secondary-300 hover:text-secondary-900 dark:hover:text-white transition-colors"
                    >
                        Close
                    </button>
                    <a 
                        x-show="comparisonData?.previous?.id"
                        :href="`/missions/${comparisonData?.previous?.id}`"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors"
                    >
                        View Previous Report
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('inspectionComparison', (config) => ({
            currentMissionId: config.currentMissionId,
            isOpen: false,
            loading: false,
            comparisonData: null,

            async open() {
                if (!this.currentMissionId) return;
                
                this.isOpen = true;
                this.loading = true;
                document.body.style.overflow = 'hidden';

                try {
                    const response = await fetch(`/api/v1/missions/${this.currentMissionId}/comparison`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.comparisonData = data.data;
                    }
                } catch (error) {
                    console.error('Error loading comparison:', error);
                    if (window.toast) {
                        window.toast.show({
                            message: 'Failed to load comparison data',
                            type: 'error',
                        });
                    }
                } finally {
                    this.loading = false;
                }
            },

            close() {
                this.isOpen = false;
                document.body.style.overflow = '';
            },

            formatDate(dateString) {
                if (!dateString) return 'N/A';
                return new Date(dateString).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }
        }));
    });
</script>
