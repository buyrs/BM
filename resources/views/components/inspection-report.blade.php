@props([
    'missionId' => null,
])

<!-- Inspection Report Component -->
<div 
    x-data="inspectionReport({ missionId: {{ $missionId ?? 'null' }} })"
    x-init="init()"
    {{ $attributes->merge(['class' => 'space-y-6']) }}
>
    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    <!-- Report Content -->
    <div x-show="!loading && report">
        <!-- Header -->
        <div class="bg-white dark:bg-secondary-800 rounded-xl shadow-soft p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-secondary-900 dark:text-white" x-text="report.title"></h1>
                    <div class="flex items-center gap-4 mt-2">
                        <div class="flex items-center gap-2 text-secondary-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span x-text="report.property?.name"></span>
                        </div>
                        <div class="flex items-center gap-2 text-secondary-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span x-text="report.inspector"></span>
                        </div>
                        <div class="flex items-center gap-2 text-secondary-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="formatDate(report.completed_at)"></span>
                        </div>
                    </div>
                </div>
                <div>
                    <span 
                        class="px-4 py-2 rounded-full text-sm font-medium"
                        :class="report.has_issues ? 'bg-warning-100 text-warning-700' : 'bg-success-100 text-success-700'"
                        x-text="report.has_issues ? '⚠️ Issues Found' : '✓ All Clear'"
                    ></span>
                </div>
            </div>

            <!-- Property Address -->
            <div class="mt-4 p-4 bg-secondary-50 dark:bg-secondary-700/50 rounded-lg">
                <p class="text-sm text-secondary-500">Property Address</p>
                <p class="font-medium text-secondary-900 dark:text-white" x-text="report.property?.address"></p>
            </div>

            <!-- Summary -->
            <div x-show="report.summary" class="mt-4">
                <p class="text-sm text-secondary-500 mb-2">Inspector Notes</p>
                <p class="text-secondary-700 dark:text-secondary-300" x-text="report.summary"></p>
            </div>
        </div>

        <!-- Inspection Areas -->
        <template x-for="(area, areaIndex) in report.areas" :key="areaIndex">
            <div class="bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden mb-4">
                <button 
                    @click="activeArea = activeArea === areaIndex ? null : areaIndex"
                    class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                            <span class="text-primary-600 dark:text-primary-400 font-semibold" x-text="areaIndex + 1"></span>
                        </div>
                        <h3 class="font-semibold text-secondary-900 dark:text-white" x-text="area.name"></h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-secondary-500" x-text="`${area.items.length} items`"></span>
                        <svg 
                            class="w-5 h-5 text-secondary-400 transform transition-transform"
                            :class="{ 'rotate-180': activeArea === areaIndex }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                <div x-show="activeArea === areaIndex" x-collapse>
                    <div class="px-6 pb-4 divide-y divide-secondary-100 dark:divide-secondary-700">
                        <template x-for="(item, itemIndex) in area.items" :key="itemIndex">
                            <div class="py-4">
                                <div class="flex items-start gap-3">
                                    <div 
                                        class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                                        :class="item.status === 'checked' ? 'bg-success-100 dark:bg-success-900/30' : 'bg-secondary-100 dark:bg-secondary-700'"
                                    >
                                        <svg x-show="item.status === 'checked'" class="w-4 h-4 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg x-show="item.status !== 'checked'" class="w-4 h-4 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-secondary-900 dark:text-white" x-text="item.description"></p>
                                        
                                        <!-- Condition Badge -->
                                        <div class="flex items-center gap-2 mt-2">
                                            <template x-if="item.condition">
                                                <span 
                                                    class="px-2 py-0.5 rounded-full text-xs"
                                                    :class="{
                                                        'bg-success-100 text-success-700': item.condition === 'good',
                                                        'bg-warning-100 text-warning-700': item.condition === 'fair',
                                                        'bg-danger-100 text-danger-700': item.condition === 'poor'
                                                    }"
                                                    x-text="item.condition"
                                                ></span>
                                            </template>
                                            <span x-show="item.has_photo" class="flex items-center gap-1 text-xs text-secondary-500">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                Photo attached
                                            </span>
                                        </div>

                                        <!-- Notes -->
                                        <p x-show="item.notes" class="text-sm text-secondary-500 mt-2" x-text="item.notes"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <!-- Actions -->
        <div class="flex items-center justify-between bg-white dark:bg-secondary-800 rounded-xl shadow-soft p-4">
            <a href="/client/inspections" class="text-primary-500 hover:text-primary-600 font-medium text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Inspections
            </a>
            <button 
                @click="downloadReport()"
                class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white font-medium text-sm rounded-lg flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </button>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="!loading && error" class="text-center py-12">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-danger-100 dark:bg-danger-900/30 flex items-center justify-center">
            <svg class="w-8 h-8 text-danger-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <p class="text-secondary-900 dark:text-white font-medium" x-text="error"></p>
        <a href="/client/inspections" class="text-primary-500 hover:underline mt-2 inline-block">Back to Inspections</a>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('inspectionReport', (config) => ({
            missionId: config.missionId,
            loading: true,
            report: null,
            error: null,
            activeArea: 0,

            async init() {
                if (this.missionId) {
                    await this.loadReport();
                }
            },

            async loadReport() {
                this.loading = true;
                this.error = null;

                try {
                    const response = await fetch(`/api/v1/client/inspections/${this.missionId}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.report = data.data;
                    } else {
                        this.error = data.message || 'Failed to load report';
                    }
                } catch (e) {
                    console.error('Error loading report:', e);
                    this.error = 'Failed to load inspection report';
                } finally {
                    this.loading = false;
                }
            },

            formatDate(date) {
                if (!date) return '';
                return new Date(date).toLocaleDateString('fr-FR', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                });
            },

            downloadReport() {
                // Would call a PDF generation endpoint
                window.open(`/api/v1/client/inspections/${this.missionId}/pdf`, '_blank');
            }
        }));
    });
</script>
