@props([
    'propertyId' => null,
    'propertyAddress' => null,
    'limit' => 5,
])

<!-- Mission History Sidebar Component -->
<div 
    x-data="missionHistory({ 
        propertyId: {{ $propertyId ?? 'null' }},
        propertyAddress: '{{ $propertyAddress }}',
        limit: {{ $limit }}
    })"
    x-init="loadHistory()"
    {{ $attributes->merge(['class' => 'bg-white dark:bg-secondary-800 rounded-xl shadow-soft overflow-hidden']) }}
>
    <!-- Header -->
    <div class="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700 flex items-center justify-between">
        <h3 class="font-semibold text-secondary-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Inspection History
        </h3>
        <button 
            @click="refresh()"
            :disabled="loading"
            class="p-1.5 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-700 transition-colors"
        >
            <svg 
                class="w-4 h-4 text-secondary-500 dark:text-secondary-400"
                :class="{ 'animate-spin': loading }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>

    <!-- Loading State -->
    <div x-show="loading && missions.length === 0" class="p-4 space-y-3">
        <template x-for="i in 3" :key="i">
            <div class="animate-pulse flex gap-3">
                <div class="w-12 h-12 bg-secondary-200 dark:bg-secondary-700 rounded-lg"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-4 bg-secondary-200 dark:bg-secondary-700 rounded w-3/4"></div>
                    <div class="h-3 bg-secondary-200 dark:bg-secondary-700 rounded w-1/2"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div 
        x-show="!loading && missions.length === 0"
        class="p-8 text-center"
    >
        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center">
            <svg class="w-6 h-6 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-secondary-500 dark:text-secondary-400 text-sm">
            No previous inspections
        </p>
    </div>

    <!-- Mission List -->
    <div x-show="missions.length > 0" class="divide-y divide-secondary-100 dark:divide-secondary-700">
        <template x-for="mission in missions" :key="mission.id">
            <a 
                :href="`/missions/${mission.id}`"
                class="block p-4 hover:bg-secondary-50 dark:hover:bg-secondary-700/50 transition-colors group"
            >
                <div class="flex gap-3">
                    <!-- Status Badge -->
                    <div 
                        class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
                        :class="{
                            'bg-success-100 dark:bg-success-900/30': mission.status === 'completed',
                            'bg-warning-100 dark:bg-warning-900/30': mission.status === 'in_progress',
                            'bg-secondary-100 dark:bg-secondary-700': mission.status === 'pending'
                        }"
                    >
                        <!-- Completed Icon -->
                        <svg 
                            x-show="mission.status === 'completed'"
                            class="w-6 h-6 text-success-600 dark:text-success-400" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <!-- In Progress Icon -->
                        <svg 
                            x-show="mission.status === 'in_progress'"
                            class="w-6 h-6 text-warning-600 dark:text-warning-400" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <!-- Pending Icon -->
                        <svg 
                            x-show="mission.status === 'pending'"
                            class="w-6 h-6 text-secondary-500 dark:text-secondary-400" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-medium text-secondary-900 dark:text-white truncate" x-text="mission.title"></p>
                            <svg class="w-4 h-4 text-secondary-400 group-hover:text-primary-500 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-2 mt-1 text-sm text-secondary-500 dark:text-secondary-400">
                            <span x-text="formatDate(mission.completed_at || mission.created_at)"></span>
                            <span class="w-1 h-1 bg-secondary-300 dark:bg-secondary-600 rounded-full"></span>
                            <span x-text="mission.checker_name || 'Unassigned'"></span>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="flex items-center gap-3 mt-2">
                            <span 
                                x-show="mission.photo_count > 0"
                                class="inline-flex items-center gap-1 text-xs text-secondary-500 dark:text-secondary-400"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="`${mission.photo_count} photos`"></span>
                            </span>
                            <span 
                                x-show="mission.issue_count > 0"
                                class="inline-flex items-center gap-1 text-xs text-warning-600 dark:text-warning-400"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span x-text="`${mission.issue_count} issues`"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </template>
    </div>

    <!-- View All Link -->
    <div 
        x-show="hasMore"
        class="px-4 py-3 border-t border-secondary-200 dark:border-secondary-700 text-center"
    >
        <a 
            :href="`/properties/${propertyId}/missions`"
            class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
        >
            View all inspections →
        </a>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('missionHistory', (config) => ({
            propertyId: config.propertyId,
            propertyAddress: config.propertyAddress,
            limit: config.limit || 5,
            missions: [],
            loading: false,
            hasMore: false,

            async loadHistory() {
                if (!this.propertyId && !this.propertyAddress) return;
                
                this.loading = true;

                try {
                    const params = new URLSearchParams({
                        limit: this.limit + 1, // Request one extra to check if there are more
                    });

                    if (this.propertyId) {
                        params.append('property_id', this.propertyId);
                    } else if (this.propertyAddress) {
                        params.append('property_address', this.propertyAddress);
                    }

                    const response = await fetch(`/api/v1/missions?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    });

                    const data = await response.json();

                    if (data.success && data.data) {
                        // Check if there are more items
                        this.hasMore = data.data.length > this.limit;
                        // Only show up to limit
                        this.missions = data.data.slice(0, this.limit);
                    }
                } catch (error) {
                    console.error('Error loading mission history:', error);
                    
                    // Try loading from cache
                    this.loadFromCache();
                } finally {
                    this.loading = false;
                }
            },

            loadFromCache() {
                try {
                    const cached = localStorage.getItem(`missionHistory_${this.propertyId || this.propertyAddress}`);
                    if (cached) {
                        this.missions = JSON.parse(cached);
                    }
                } catch (e) {
                    console.error('Error loading from cache:', e);
                }
            },

            refresh() {
                this.loadHistory();
            },

            formatDate(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;

                // Today
                if (diff < 24 * 60 * 60 * 1000 && date.getDate() === now.getDate()) {
                    return 'Today';
                }
                // Yesterday
                if (diff < 48 * 60 * 60 * 1000) {
                    const yesterday = new Date(now);
                    yesterday.setDate(yesterday.getDate() - 1);
                    if (date.getDate() === yesterday.getDate()) {
                        return 'Yesterday';
                    }
                }
                // Within a week
                if (diff < 7 * 24 * 60 * 60 * 1000) {
                    return date.toLocaleDateString('en-US', { weekday: 'long' });
                }
                // Older
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric',
                    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
                });
            }
        }));
    });
</script>
