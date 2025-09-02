<template>
    <div class="calendar-filters bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
        <!-- Filter Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Filters</h3>
            <button
                @click="clearAllFilters"
                :disabled="!hasActiveFilters"
                class="text-sm text-gray-500 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Clear All
            </button>
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Status Filter -->
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select
                    v-model="localFilters.status"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loading"
                >
                    <option value="">All Statuses</option>
                    <option value="unassigned">Unassigned</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Mission Type Filter -->
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700">Mission Type</label>
                <select
                    v-model="localFilters.mission_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loading"
                >
                    <option value="">All Types</option>
                    <option value="entry">Entry</option>
                    <option value="exit">Exit</option>
                </select>
            </div>

            <!-- Checker Filter -->
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700">Checker</label>
                <select
                    v-model="localFilters.checker_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loading"
                >
                    <option :value="null">All Checkers</option>
                    <option
                        v-for="checker in checkers"
                        :key="checker.id"
                        :value="checker.id"
                    >
                        {{ checker.name }}
                    </option>
                </select>
            </div>

            <!-- Date Range Filter -->
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700">Date Range</label>
                <select
                    v-model="localFilters.date_range"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loading"
                >
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="tomorrow">Tomorrow</option>
                    <option value="this_week">This Week</option>
                    <option value="next_week">Next Week</option>
                    <option value="this_month">This Month</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    v-model="localFilters.search"
                    type="text"
                    placeholder="Search by tenant name, address, or mission ID..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :disabled="loading"
                />
                <div
                    v-if="localFilters.search && !loading"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                    <button
                        @click="localFilters.search = ''"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <span class="text-sm text-gray-600">Active filters:</span>
                
                <!-- Status Filter Tag -->
                <span
                    v-if="localFilters.status"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                    Status: {{ getStatusLabel(localFilters.status) }}
                    <button
                        @click="localFilters.status = ''"
                        class="ml-1 text-blue-600 hover:text-blue-800"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>

                <!-- Mission Type Filter Tag -->
                <span
                    v-if="localFilters.mission_type"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                    Type: {{ getMissionTypeLabel(localFilters.mission_type) }}
                    <button
                        @click="localFilters.mission_type = ''"
                        class="ml-1 text-green-600 hover:text-green-800"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>

                <!-- Checker Filter Tag -->
                <span
                    v-if="localFilters.checker_id"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                >
                    Checker: {{ getCheckerName(localFilters.checker_id) }}
                    <button
                        @click="localFilters.checker_id = null"
                        class="ml-1 text-purple-600 hover:text-purple-800"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>

                <!-- Date Range Filter Tag -->
                <span
                    v-if="localFilters.date_range"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                >
                    Date: {{ getDateRangeLabel(localFilters.date_range) }}
                    <button
                        @click="localFilters.date_range = ''"
                        class="ml-1 text-yellow-600 hover:text-yellow-800"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>

                <!-- Search Filter Tag -->
                <span
                    v-if="localFilters.search"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                    Search: "{{ localFilters.search }}"
                    <button
                        @click="localFilters.search = ''"
                        class="ml-1 text-gray-600 hover:text-gray-800"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="loading" class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center text-sm text-gray-600">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                Applying filters...
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
    filters: {
        type: Object,
        required: true
    },
    checkers: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['filter-change', 'clear-filters'])

// Local reactive copy of filters
const localFilters = reactive({
    status: props.filters.status || '',
    mission_type: props.filters.mission_type || '',
    checker_id: props.filters.checker_id || null,
    date_range: props.filters.date_range || '',
    search: props.filters.search || ''
})

// Computed properties
const hasActiveFilters = computed(() => {
    return localFilters.status ||
           localFilters.mission_type ||
           localFilters.checker_id ||
           localFilters.date_range ||
           localFilters.search
})

// Methods
const clearAllFilters = () => {
    localFilters.status = ''
    localFilters.mission_type = ''
    localFilters.checker_id = null
    localFilters.date_range = ''
    localFilters.search = ''
    emit('clear-filters')
}

const getStatusLabel = (status) => {
    const labels = {
        unassigned: 'Unassigned',
        assigned: 'Assigned',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled'
    }
    return labels[status] || status
}

const getMissionTypeLabel = (type) => {
    const labels = {
        entry: 'Entry',
        exit: 'Exit'
    }
    return labels[type] || type
}

const getCheckerName = (checkerId) => {
    const checker = props.checkers.find(c => c.id === checkerId)
    return checker ? checker.name : 'Unknown'
}

const getDateRangeLabel = (range) => {
    const labels = {
        today: 'Today',
        tomorrow: 'Tomorrow',
        this_week: 'This Week',
        next_week: 'Next Week',
        this_month: 'This Month',
        overdue: 'Overdue'
    }
    return labels[range] || range
}

// Debounced filter change for search
let searchTimeout = null
const debouncedSearchChange = (searchValue) => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        emit('filter-change', { ...localFilters, search: searchValue })
    }, 300) // 300ms debounce for search
}

// Watch for changes in local filters and emit to parent
watch(localFilters, (newFilters, oldFilters) => {
    // Handle search with debouncing
    if (newFilters.search !== oldFilters.search) {
        debouncedSearchChange(newFilters.search)
    } else {
        // Emit immediately for non-search filters
        emit('filter-change', { ...newFilters })
    }
}, { deep: true })

// Watch for changes in props.filters to sync local state
watch(() => props.filters, (newFilters) => {
    Object.assign(localFilters, {
        status: newFilters.status || '',
        mission_type: newFilters.mission_type || '',
        checker_id: newFilters.checker_id || null,
        date_range: newFilters.date_range || '',
        search: newFilters.search || ''
    })
}, { deep: true })

// Initialize local filters on mount
onMounted(() => {
    Object.assign(localFilters, {
        status: props.filters.status || '',
        mission_type: props.filters.mission_type || '',
        checker_id: props.filters.checker_id || null,
        date_range: props.filters.date_range || '',
        search: props.filters.search || ''
    })
})
</script>

<style scoped>
/* Custom styles for the filter component */
.calendar-filters {
    transition: all 0.2s ease-in-out;
}

.calendar-filters:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Loading state styles */
.calendar-filters select:disabled,
.calendar-filters input:disabled {
    @apply bg-gray-50 cursor-not-allowed;
}

/* Filter tag animations */
.inline-flex {
    transition: all 0.2s ease-in-out;
}

.inline-flex:hover {
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
        @apply grid-cols-1;
    }
}

@media (min-width: 768px) and (max-width: 1024px) {
    .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-4 {
        @apply grid-cols-2;
    }
}
</style>