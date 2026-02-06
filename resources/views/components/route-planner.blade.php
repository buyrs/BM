@props([
    'missionIds' => [],
    'checkerId' => null,
])

<!-- Route Planner Component -->
<div 
    x-data="routePlanner({ 
        initialMissionIds: {{ json_encode($missionIds) }},
        checkerId: {{ $checkerId ?? 'null' }}
    })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden']) }}
>
    <!-- Header -->
    <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
        <h3 class="font-semibold text-secondary-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Route Planner
        </h3>
        <div class="flex items-center gap-2">
            <button 
                @click="optimizeRoute()"
                :disabled="loading || missions.length < 2"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Optimizing...' : 'Optimize Route'"></span>
            </button>
        </div>
    </div>

    <!-- Savings Banner -->
    <div 
        x-show="comparison?.savings"
        x-transition
        class="px-6 py-3 bg-success-50 dark:bg-success-900/20 border-b border-success-200 dark:border-success-800"
    >
        <div class="flex items-center justify-center gap-4 text-sm">
            <div class="flex items-center gap-2 text-success-700 dark:text-success-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Route optimized!</span>
            </div>
            <div class="flex items-center gap-4 text-success-600 dark:text-success-300">
                <span>Save <strong x-text="`${comparison.savings.distance_km} km`"></strong></span>
                <span>•</span>
                <span>Save <strong x-text="`${comparison.savings.time_minutes} min`"></strong></span>
                <span>•</span>
                <span><strong x-text="`${comparison.savings.percentage}%`"></strong> shorter</span>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-secondary-200 dark:divide-secondary-700">
        <!-- Route List -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-medium text-secondary-900 dark:text-white">
                    <span x-text="optimizedRoute.length > 0 ? 'Optimized Route' : 'Missions'"></span>
                    <span class="text-secondary-500 dark:text-secondary-400 font-normal" x-text="`(${missions.length})`"></span>
                </h4>
                <span class="text-xs text-secondary-500" x-show="metrics">
                    Total: <span x-text="`${metrics?.total_distance_km || 0} km`"></span> • 
                    <span x-text="`${metrics?.total_estimated_hours || 0}h`"></span>
                </span>
            </div>

            <!-- Route Steps -->
            <div class="space-y-2" x-show="optimizedRoute.length > 0">
                <template x-for="(stop, index) in optimizedRoute" :key="index">
                    <div class="flex items-start gap-3">
                        <!-- Timeline -->
                        <div class="flex flex-col items-center">
                            <div 
                                class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                                :class="{
                                    'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400': stop.type === 'start',
                                    'bg-success-100 dark:bg-success-900/30 text-success-600 dark:text-success-400': stop.type === 'mission'
                                }"
                                x-text="stop.type === 'start' ? '📍' : index"
                            ></div>
                            <div x-show="index < optimizedRoute.length - 1" class="w-0.5 h-8 bg-secondary-200 dark:bg-secondary-700"></div>
                        </div>

                        <!-- Stop Info -->
                        <div class="flex-1 pb-4">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-secondary-900 dark:text-white text-sm" x-text="stop.mission_title || stop.address"></p>
                                <span class="text-xs text-secondary-500" x-text="stop.arrival_time || ''"></span>
                            </div>
                            <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5" x-text="stop.address"></p>
                            <div class="flex items-center gap-2 mt-1 text-xs text-secondary-400" x-show="stop.travel_minutes">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span x-text="`${stop.travel_minutes} min travel`"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="optimizedRoute.length === 0 && !loading" class="text-center py-8">
                <p class="text-secondary-500 dark:text-secondary-400 text-sm">
                    Add missions or click "Optimize Route" to plan your day
                </p>
            </div>
        </div>

        <!-- Map Placeholder / Schedule -->
        <div class="p-6">
            <h4 class="font-medium text-secondary-900 dark:text-white mb-4">Schedule</h4>
            
            <div class="space-y-3" x-show="schedule.length > 0">
                <template x-for="(item, index) in schedule" :key="index">
                    <div 
                        class="flex items-center gap-3 p-3 rounded-lg"
                        :class="item.type === 'start' ? 'bg-secondary-50 dark:bg-secondary-700/50' : 'bg-primary-50 dark:bg-primary-900/20'"
                    >
                        <div class="text-center min-w-[60px]">
                            <div class="text-sm font-medium text-secondary-900 dark:text-white" x-text="item.arrival_time"></div>
                            <div x-show="item.departure_time" class="text-xs text-secondary-500" x-text="`→ ${item.departure_time}`"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-secondary-900 dark:text-white" x-text="item.mission_title || 'Start'"></p>
                            <p class="text-xs text-secondary-500 truncate" x-text="item.address"></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Metrics Summary -->
            <div class="mt-6 grid grid-cols-2 gap-3" x-show="metrics">
                <div class="p-3 rounded-lg bg-secondary-50 dark:bg-secondary-700/50 text-center">
                    <div class="text-lg font-bold text-secondary-900 dark:text-white" x-text="`${metrics?.total_distance_km || 0} km`"></div>
                    <div class="text-xs text-secondary-500">Total Distance</div>
                </div>
                <div class="p-3 rounded-lg bg-secondary-50 dark:bg-secondary-700/50 text-center">
                    <div class="text-lg font-bold text-secondary-900 dark:text-white" x-text="`${metrics?.total_estimated_hours || 0}h`"></div>
                    <div class="text-xs text-secondary-500">Estimated Time</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('routePlanner', (config) => ({
            missionIds: config.initialMissionIds || [],
            checkerId: config.checkerId,
            loading: false,
            missions: [],
            optimizedRoute: [],
            schedule: [],
            metrics: null,
            comparison: null,

            async init() {
                if (this.checkerId) {
                    await this.loadDailyRoute();
                } else if (this.missionIds.length > 0) {
                    await this.optimizeRoute();
                }
            },

            async loadDailyRoute() {
                this.loading = true;

                try {
                    const response = await fetch(`/api/v1/ops/daily-route/${this.checkerId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const data = await response.json();

                    if (data.success && data.data) {
                        this.optimizedRoute = data.data.route || [];
                        this.schedule = data.data.schedule || [];
                        this.metrics = data.data.metrics || null;
                    }
                } catch (error) {
                    console.error('Error loading daily route:', error);
                } finally {
                    this.loading = false;
                }
            },

            async optimizeRoute() {
                if (this.missionIds.length < 2) return;
                
                this.loading = true;

                try {
                    const response = await fetch('/api/v1/ops/compare-routes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            mission_ids: this.missionIds,
                        }),
                    });

                    const data = await response.json();

                    if (data.success && data.data) {
                        this.comparison = data.data;
                        this.optimizedRoute = data.data.route || [];
                        this.schedule = data.data.schedule || [];
                        this.metrics = data.data.optimized || null;

                        // Haptic feedback
                        if (window.haptics) {
                            window.haptics.success();
                        }

                        if (window.toast) {
                            window.toast.show({
                                message: `Route optimized! Save ${data.data.savings?.percentage || 0}%`,
                                type: 'success',
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error optimizing route:', error);
                    if (window.toast) {
                        window.toast.show({
                            message: 'Failed to optimize route',
                            type: 'error',
                        });
                    }
                } finally {
                    this.loading = false;
                }
            },

            addMission(missionId) {
                if (!this.missionIds.includes(missionId)) {
                    this.missionIds.push(missionId);
                }
            },

            removeMission(missionId) {
                this.missionIds = this.missionIds.filter(id => id !== missionId);
            }
        }));
    });
</script>
