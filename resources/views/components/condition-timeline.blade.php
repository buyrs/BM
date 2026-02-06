@props([
    'propertyId' => null,
])

<!-- Condition Timeline Component -->
<div 
    x-data="conditionTimeline({ propertyId: {{ $propertyId ?? 'null' }} })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden']) }}
>
    <!-- Header -->
    <div class="px-6 py-4 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
        <h3 class="font-semibold text-secondary-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Condition History
        </h3>
        <div class="flex items-center gap-2">
            <select 
                x-model="filter.area"
                @change="loadHistory()"
                class="text-sm px-3 py-1.5 rounded-lg border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900"
            >
                <option value="">All Areas</option>
                <template x-for="area in areas" :key="area">
                    <option :value="area" x-text="area"></option>
                </template>
            </select>
        </div>
    </div>

    <!-- Score Summary -->
    <div class="px-6 py-4 bg-secondary-50 dark:bg-secondary-700/50 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div 
                class="w-16 h-16 rounded-full flex items-center justify-center"
                :class="scoreClass"
            >
                <span class="text-2xl font-bold" x-text="score ? `${score}%` : '—'"></span>
            </div>
            <div>
                <p class="font-medium text-secondary-900 dark:text-white">Overall Condition</p>
                <p class="text-sm text-secondary-500" x-text="`${totalItems} items tracked`"></p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <template x-for="(count, condition) in byCondition" :key="condition">
                <div class="text-center">
                    <div 
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                        :class="getConditionClass(condition)"
                        x-text="count"
                    ></div>
                    <p class="text-xs text-secondary-500 mt-1 capitalize" x-text="condition"></p>
                </div>
            </template>
        </div>
    </div>

    <!-- Timeline -->
    <div class="px-6 py-4 max-h-[500px] overflow-y-auto">
        <div class="relative">
            <!-- Timeline line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-secondary-200 dark:bg-secondary-700"></div>

            <div class="space-y-4">
                <template x-for="(entry, index) in history" :key="index">
                    <div class="relative flex gap-4">
                        <!-- Timeline dot -->
                        <div 
                            class="w-8 h-8 rounded-full flex items-center justify-center z-10 flex-shrink-0"
                            :class="getConditionClass(entry.condition)"
                        >
                            <svg x-show="entry.hasDegraded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                            <svg x-show="!entry.hasDegraded" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 pb-4">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-secondary-900 dark:text-white" x-text="entry.item"></span>
                                <span 
                                    class="px-2 py-0.5 rounded-full text-xs capitalize"
                                    :class="getConditionBadgeClass(entry.condition)"
                                    x-text="entry.condition"
                                ></span>
                                <span 
                                    x-show="entry.previous_condition && entry.previous_condition !== entry.condition"
                                    class="text-xs text-secondary-400"
                                    x-text="`← ${entry.previous_condition}`"
                                ></span>
                            </div>
                            <p class="text-sm text-secondary-500" x-text="entry.area"></p>
                            <p x-show="entry.notes" class="text-sm text-secondary-600 dark:text-secondary-400 mt-1" x-text="entry.notes"></p>
                            <div class="flex items-center gap-4 mt-2 text-xs text-secondary-400">
                                <span x-text="formatDate(entry.recorded_at)"></span>
                                <span x-show="entry.mission" x-text="`Mission: ${entry.mission?.title || entry.mission_id}`"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="history.length === 0 && !loading" class="text-center py-8">
                <p class="text-secondary-500">No condition history yet</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('conditionTimeline', (config) => ({
            propertyId: config.propertyId,
            loading: false,
            history: [],
            areas: [],
            score: null,
            totalItems: 0,
            byCondition: {},
            filter: { area: '' },

            async init() {
                if (this.propertyId) {
                    await this.loadHistory();
                    await this.loadScore();
                }
            },

            async loadHistory() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.filter.area) params.append('area', this.filter.area);

                    const response = await fetch(`/api/v1/properties/${this.propertyId}/conditions?${params}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (data.success) {
                        this.history = data.data || [];
                        this.areas = [...new Set(this.history.map(h => h.area))];
                    }
                } catch (e) {
                    console.error('Error loading history:', e);
                } finally {
                    this.loading = false;
                }
            },

            async loadScore() {
                try {
                    const response = await fetch(`/api/v1/properties/${this.propertyId}/condition-score`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (data.success) {
                        this.score = data.data.score;
                        this.totalItems = data.data.total_items;
                        this.byCondition = data.data.by_condition;
                    }
                } catch (e) {
                    console.error('Error loading score:', e);
                }
            },

            get scoreClass() {
                if (!this.score) return 'bg-secondary-100 dark:bg-secondary-700 text-secondary-500';
                if (this.score >= 80) return 'bg-success-100 dark:bg-success-900/30 text-success-600';
                if (this.score >= 60) return 'bg-warning-100 dark:bg-warning-900/30 text-warning-600';
                return 'bg-danger-100 dark:bg-danger-900/30 text-danger-600';
            },

            getConditionClass(condition) {
                const classes = {
                    excellent: 'bg-success-100 dark:bg-success-900/30 text-success-600',
                    good: 'bg-success-100 dark:bg-success-900/30 text-success-600',
                    fair: 'bg-warning-100 dark:bg-warning-900/30 text-warning-600',
                    poor: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
                    critical: 'bg-danger-100 dark:bg-danger-900/30 text-danger-600',
                };
                return classes[condition] || 'bg-secondary-100 dark:bg-secondary-700 text-secondary-500';
            },

            getConditionBadgeClass(condition) {
                const classes = {
                    excellent: 'bg-success-100 text-success-700',
                    good: 'bg-success-100 text-success-700',
                    fair: 'bg-warning-100 text-warning-700',
                    poor: 'bg-orange-100 text-orange-700',
                    critical: 'bg-danger-100 text-danger-700',
                };
                return classes[condition] || 'bg-secondary-100 text-secondary-700';
            },

            formatDate(date) {
                return new Date(date).toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                });
            }
        }));
    });
</script>
