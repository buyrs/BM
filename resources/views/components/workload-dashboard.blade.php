@props([
    'refreshInterval' => 30000, // 30 seconds
])

<!-- Workload Dashboard Component -->
<div 
    x-data="workloadDashboard({ refreshInterval: {{ $refreshInterval }} })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'space-y-6']) }}
>
    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Active -->
        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Active Checkers</span>
                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="summary.activeCheckers"></div>
        </div>

        <!-- Total Missions Today -->
        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Today's Missions</span>
                <div class="w-8 h-8 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="summary.todayMissions"></div>
        </div>

        <!-- Average Utilization -->
        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Avg Utilization</span>
                <div class="w-8 h-8 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-secondary-900 dark:text-white">
                <span x-text="summary.avgUtilization"></span>%
            </div>
        </div>

        <!-- Overdue Missions -->
        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center justify-between mb-2">
                <span class="text-secondary-500 dark:text-secondary-400 text-sm">Overdue</span>
                <div class="w-8 h-8 rounded-full bg-danger-100 dark:bg-danger-900/30 flex items-center justify-center">
                    <svg class="w-4 h-4 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold" :class="summary.overdue > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-secondary-900 dark:text-white'" x-text="summary.overdue"></div>
        </div>
    </div>

    <!-- Checker Workload List -->
    <div class="bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden">
        <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
            <h3 class="font-semibold text-secondary-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Checker Workloads
            </h3>
            <div class="flex items-center gap-2">
                <span class="text-xs text-secondary-500 dark:text-secondary-400">
                    Last updated: <span x-text="lastUpdated"></span>
                </span>
                <button 
                    @click="refresh()"
                    :disabled="loading"
                    class="p-1.5 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
                >
                    <svg class="w-4 h-4 text-secondary-500" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && checkers.length === 0" class="p-6">
            <div class="space-y-4">
                <template x-for="i in 3" :key="i">
                    <div class="animate-pulse flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-secondary-200 dark:bg-secondary-700"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-1/4 mb-2"></div>
                            <div class="h-2 bg-secondary-200 dark:bg-secondary-700 rounded w-1/2"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Checker List -->
        <div x-show="!loading || checkers.length > 0" class="divide-y divide-secondary-100 dark:divide-secondary-700">
            <template x-for="checker in checkers" :key="checker.id">
                <div class="px-6 py-4 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium" x-text="checker.name.charAt(0).toUpperCase()"></div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-secondary-900 dark:text-white truncate" x-text="checker.name"></span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400': checker.status === 'available',
                                        'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400': checker.status === 'moderate',
                                        'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': checker.status === 'busy',
                                        'bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400': checker.status === 'overloaded'
                                    }"
                                    x-text="checker.status"
                                ></span>
                            </div>

                            <!-- Utilization Bar -->
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-2 bg-secondary-200 dark:bg-secondary-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="{
                                            'bg-success-500': checker.utilization < 50,
                                            'bg-warning-500': checker.utilization >= 50 && checker.utilization < 80,
                                            'bg-orange-500': checker.utilization >= 80 && checker.utilization < 100,
                                            'bg-danger-500': checker.utilization >= 100
                                        }"
                                        :style="`width: ${Math.min(checker.utilization, 100)}%`"
                                    ></div>
                                </div>
                                <span class="text-xs text-secondary-500 dark:text-secondary-400 w-12 text-right" x-text="`${checker.utilization}%`"></span>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="flex items-center gap-4 text-sm">
                            <div class="text-center">
                                <div class="font-medium text-secondary-900 dark:text-white" x-text="checker.today"></div>
                                <div class="text-xs text-secondary-500 dark:text-secondary-400">Today</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium text-secondary-900 dark:text-white" x-text="checker.week"></div>
                                <div class="text-xs text-secondary-500 dark:text-secondary-400">Week</div>
                            </div>
                            <div class="text-center" x-show="checker.overdue > 0">
                                <div class="font-medium text-danger-600 dark:text-danger-400" x-text="checker.overdue"></div>
                                <div class="text-xs text-danger-500">Overdue</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button 
                                @click="assignToChecker(checker.id)"
                                class="p-2 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 text-primary-600 dark:text-primary-400 transition-colors"
                                title="Assign mission"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                            <a 
                                :href="`/users/${checker.id}`"
                                class="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 text-secondary-500 transition-colors"
                                title="View profile"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && checkers.length === 0" class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-secondary-100 dark:bg-secondary-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <p class="text-secondary-500 dark:text-secondary-400">No active checkers found</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('workloadDashboard', (config) => ({
            refreshInterval: config.refreshInterval || 30000,
            loading: false,
            checkers: [],
            lastUpdated: 'Never',
            refreshTimer: null,

            get summary() {
                const total = this.checkers.length;
                const todayMissions = this.checkers.reduce((sum, c) => sum + c.today, 0);
                const avgUtil = total > 0 
                    ? Math.round(this.checkers.reduce((sum, c) => sum + c.utilization, 0) / total)
                    : 0;
                const overdue = this.checkers.reduce((sum, c) => sum + c.overdue, 0);

                return {
                    activeCheckers: total,
                    todayMissions,
                    avgUtilization: avgUtil,
                    overdue,
                };
            },

            init() {
                this.refresh();
                this.startAutoRefresh();
            },

            startAutoRefresh() {
                if (this.refreshTimer) clearInterval(this.refreshTimer);
                this.refreshTimer = setInterval(() => this.refresh(), this.refreshInterval);
            },

            async refresh() {
                this.loading = true;

                try {
                    const response = await fetch('/api/v1/ops/workloads', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.checkers = data.data || [];
                        this.lastUpdated = new Date().toLocaleTimeString();
                    }
                } catch (error) {
                    console.error('Error loading workloads:', error);
                } finally {
                    this.loading = false;
                }
            },

            assignToChecker(checkerId) {
                // Dispatch event for assignment modal
                window.dispatchEvent(new CustomEvent('open-assignment-modal', {
                    detail: { checkerId }
                }));
            }
        }));
    });
</script>
