@props([
    'refreshInterval' => 60000, // 1 minute
])

<!-- Client Dashboard Component -->
<div 
    x-data="clientDashboard({ refreshInterval: {{ $refreshInterval }} })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'space-y-6']) }}
>
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-primary-200 text-sm">Welcome back,</p>
                <h1 class="text-2xl font-bold" x-text="client.name || 'Client'"></h1>
                <p class="text-primary-200 mt-1" x-text="client.company || ''"></p>
            </div>
            <div class="text-right">
                <p class="text-primary-200 text-sm">Last updated</p>
                <p class="font-medium" x-text="lastUpdated"></p>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="stats.total_properties"></p>
                    <p class="text-xs text-secondary-500">Properties</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="stats.active_missions"></p>
                    <p class="text-xs text-secondary-500">In Progress</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="stats.completed_missions"></p>
                    <p class="text-xs text-secondary-500">Completed</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="stats.recent_inspections"></p>
                    <p class="text-xs text-secondary-500">Last 30 Days</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full" :class="stats.pending_issues > 0 ? 'bg-danger-100 dark:bg-danger-900/30' : 'bg-success-100 dark:bg-success-900/30'">
                    <svg class="w-5 h-5 mx-auto mt-2.5" :class="stats.pending_issues > 0 ? 'text-danger-500' : 'text-success-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold" :class="stats.pending_issues > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-secondary-900 dark:text-white'" x-text="stats.pending_issues"></p>
                    <p class="text-xs text-secondary-500">Open Issues</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Inspections -->
        <div class="lg:col-span-2 bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden">
            <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
                <h3 class="font-semibold text-secondary-900 dark:text-white">Recent Inspections</h3>
                <a href="/client/inspections" class="text-sm text-primary-500 hover:text-primary-600">View All →</a>
            </div>

            <div class="divide-y divide-secondary-100 dark:divide-secondary-700">
                <template x-for="inspection in recentInspections" :key="inspection.id">
                    <a :href="`/client/inspections/${inspection.id}`" class="block px-6 py-4 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-10 h-10 rounded-full flex items-center justify-center"
                                    :class="inspection.has_issues ? 'bg-warning-100 dark:bg-warning-900/30' : 'bg-success-100 dark:bg-success-900/30'"
                                >
                                    <svg x-show="!inspection.has_issues" class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <svg x-show="inspection.has_issues" class="w-5 h-5 text-warning-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-secondary-900 dark:text-white" x-text="inspection.title"></p>
                                    <p class="text-sm text-secondary-500" x-text="inspection.property"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-secondary-500" x-text="formatDate(inspection.completed_at)"></p>
                                <span 
                                    class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs"
                                    :class="inspection.has_issues ? 'bg-warning-100 text-warning-700' : 'bg-success-100 text-success-700'"
                                    x-text="inspection.has_issues ? 'Issues found' : 'All clear'"
                                ></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            <div x-show="recentInspections.length === 0" class="p-12 text-center">
                <p class="text-secondary-500">No completed inspections yet</p>
            </div>
        </div>

        <!-- Properties Quick List -->
        <div class="bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden">
            <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
                <h3 class="font-semibold text-secondary-900 dark:text-white">Your Properties</h3>
                <a href="/client/properties" class="text-sm text-primary-500 hover:text-primary-600">View All →</a>
            </div>

            <div class="divide-y divide-secondary-100 dark:divide-secondary-700 max-h-[400px] overflow-y-auto">
                <template x-for="property in properties" :key="property.id">
                    <a :href="`/client/properties/${property.id}`" class="block px-6 py-4 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-secondary-900 dark:text-white" x-text="property.name"></p>
                                <p class="text-sm text-secondary-500 truncate" x-text="property.address"></p>
                            </div>
                            <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                </template>
            </div>

            <div x-show="properties.length === 0" class="p-8 text-center">
                <p class="text-secondary-500">No properties assigned</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('clientDashboard', (config) => ({
            refreshInterval: config.refreshInterval,
            loading: false,
            client: {},
            stats: {
                total_properties: 0,
                active_missions: 0,
                completed_missions: 0,
                recent_inspections: 0,
                pending_issues: 0,
            },
            recentInspections: [],
            properties: [],
            lastUpdated: '',

            async init() {
                await this.loadDashboard();
                await this.loadProperties();

                setInterval(() => this.loadDashboard(), this.refreshInterval);
            },

            async loadDashboard() {
                try {
                    const response = await fetch('/api/v1/client/dashboard', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (data.success) {
                        this.client = data.data.client || {};
                        this.stats = data.data.stats || this.stats;
                        this.recentInspections = data.data.recent_inspections || [];
                        this.lastUpdated = new Date().toLocaleTimeString();
                    }
                } catch (e) {
                    console.error('Error loading dashboard:', e);
                }
            },

            async loadProperties() {
                try {
                    const response = await fetch('/api/v1/client/properties?per_page=10', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (data.success) {
                        this.properties = data.data || [];
                    }
                } catch (e) {
                    console.error('Error loading properties:', e);
                }
            },

            formatDate(date) {
                if (!date) return '';
                return new Date(date).toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            }
        }));
    });
</script>
