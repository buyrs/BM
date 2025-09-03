<template>
    <div class="advanced-filters">
        <div class="filters-header flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-text-primary">Filters & Search</h3>
            <div class="filter-actions flex items-center space-x-2">
                <SecondaryButton @click="resetFilters" :disabled="!hasActiveFilters">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset
                </SecondaryButton>
                <PrimaryButton @click="applyFilters">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.293.707l-2 2A1 1 0 0110 21v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Apply Filters
                </PrimaryButton>
            </div>
        </div>

        <div class="filters-content bg-white rounded-xl shadow-md p-6">
            <!-- Search Bar -->
            <div class="search-section mb-6">
                <label class="block text-sm font-medium text-text-secondary mb-2">
                    Search
                </label>
                <div class="relative">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by name, address, email, or notes..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
                        @input="debouncedSearch"
                    />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Date Range -->
            <div class="date-range-section mb-6">
                <label class="block text-sm font-medium text-text-secondary mb-2">
                    Date Range
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-text-secondary mb-1">Quick Select</label>
                        <select 
                            v-model="selectedDateRange" 
                            @change="applyDateRange"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">Custom Range</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="last7days">Last 7 Days</option>
                            <option value="last30days">Last 30 Days</option>
                            <option value="last90days">Last 90 Days</option>
                            <option value="thismonth">This Month</option>
                            <option value="lastmonth">Last Month</option>
                            <option value="thisyear">This Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-text-secondary mb-1">From Date</label>
                        <input
                            v-model="filters.date_from"
                            type="date"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-text-secondary mb-1">To Date</label>
                        <input
                            v-model="filters.date_to"
                            type="date"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                        />
                    </div>
                </div>
            </div>

            <!-- Status Filters -->
            <div class="status-section mb-6">
                <label class="block text-sm font-medium text-text-secondary mb-2">
                    Status
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <label 
                        v-for="status in availableStatuses" 
                        :key="status.value"
                        class="flex items-center p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer"
                    >
                        <input
                            v-model="filters.statuses"
                            :value="status.value"
                            type="checkbox"
                            class="mr-2 text-primary focus:ring-primary border-gray-300 rounded"
                        />
                        <span class="text-sm">{{ status.label }}</span>
                        <span 
                            v-if="status.count !== undefined"
                            class="ml-auto text-xs text-text-secondary bg-gray-100 px-2 py-1 rounded-full"
                        >
                            {{ status.count }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- User Filters -->
            <div class="user-section mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-2">
                            Ops User
                        </label>
                        <select 
                            v-model="filters.ops_user_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">All Ops Users</option>
                            <option 
                                v-for="user in opsUsers" 
                                :key="user.id" 
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-2">
                            Checker
                        </label>
                        <select 
                            v-model="filters.checker_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">All Checkers</option>
                            <option 
                                v-for="checker in checkers" 
                                :key="checker.id" 
                                :value="checker.id"
                            >
                                {{ checker.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Advanced Options -->
            <div class="advanced-section">
                <button
                    @click="showAdvanced = !showAdvanced"
                    class="flex items-center text-sm font-medium text-primary hover:text-primary-dark mb-3"
                >
                    <svg 
                        :class="['w-4 h-4 mr-2 transition-transform', showAdvanced ? 'rotate-90' : '']"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Advanced Options
                </button>

                <div v-show="showAdvanced" class="advanced-options space-y-4">
                    <!-- Priority Filter -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-2">
                            Priority
                        </label>
                        <div class="flex space-x-2">
                            <label 
                                v-for="priority in availablePriorities" 
                                :key="priority.value"
                                class="flex items-center p-2 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer"
                            >
                                <input
                                    v-model="filters.priorities"
                                    :value="priority.value"
                                    type="checkbox"
                                    class="mr-2 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <span class="text-sm">{{ priority.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Special Filters -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-2">
                            Special Filters
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    v-model="filters.incident_only"
                                    type="checkbox"
                                    class="mr-2 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <span class="text-sm">Incidents Only</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="filters.ending_soon"
                                    type="checkbox"
                                    class="mr-2 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <span class="text-sm">Ending Soon (within 10 days)</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="filters.unassigned_only"
                                    type="checkbox"
                                    class="mr-2 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <span class="text-sm">Unassigned Only</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="filters.overdue_only"
                                    type="checkbox"
                                    class="mr-2 text-primary focus:ring-primary border-gray-300 rounded"
                                />
                                <span class="text-sm">Overdue Only</span>
                            </label>
                        </div>
                    </div>

                    <!-- Sorting -->
                    <div>
                        <label class="block text-sm font-medium text-text-secondary mb-2">
                            Sort By
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <select 
                                v-model="filters.sort_by"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                            >
                                <option value="created_at">Created Date</option>
                                <option value="updated_at">Updated Date</option>
                                <option value="start_date">Start Date</option>
                                <option value="end_date">End Date</option>
                                <option value="tenant_name">Tenant Name</option>
                                <option value="address">Address</option>
                            </select>
                            <select 
                                v-model="filters.sort_direction"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-primary focus:border-primary"
                            >
                                <option value="desc">Newest First</option>
                                <option value="asc">Oldest First</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="hasActiveFilters" class="active-filters mt-4">
            <div class="flex items-center flex-wrap gap-2">
                <span class="text-sm font-medium text-text-secondary">Active filters:</span>
                
                <span 
                    v-if="filters.search"
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-primary text-white"
                >
                    Search: "{{ filters.search }}"
                    <button @click="filters.search = ''" class="ml-2 hover:text-gray-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>

                <span 
                    v-if="filters.date_from || filters.date_to"
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-info-bg text-info-text"
                >
                    Date: {{ formatDateRange() }}
                    <button @click="clearDateRange" class="ml-2 hover:text-info-text-dark">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>

                <span 
                    v-for="status in filters.statuses" 
                    :key="status"
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-secondary text-primary"
                >
                    {{ getStatusLabel(status) }}
                    <button @click="removeStatus(status)" class="ml-2 hover:text-primary-dark">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { debounce } from 'lodash'
import PrimaryButton from '../PrimaryButton.vue'
import SecondaryButton from '../SecondaryButton.vue'

const props = defineProps({
    availableStatuses: {
        type: Array,
        default: () => [
            { value: 'assigned', label: 'Assigned' },
            { value: 'in_progress', label: 'In Progress' },
            { value: 'completed', label: 'Completed' },
            { value: 'incident', label: 'Incident' }
        ]
    },
    availablePriorities: {
        type: Array,
        default: () => [
            { value: 'low', label: 'Low' },
            { value: 'normal', label: 'Normal' },
            { value: 'high', label: 'High' },
            { value: 'urgent', label: 'Urgent' }
        ]
    },
    opsUsers: {
        type: Array,
        default: () => []
    },
    checkers: {
        type: Array,
        default: () => []
    },
    initialFilters: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['filtersChanged', 'search'])

const showAdvanced = ref(false)
const selectedDateRange = ref('')

const filters = ref({
    search: '',
    date_from: '',
    date_to: '',
    statuses: [],
    priorities: [],
    ops_user_id: '',
    checker_id: '',
    incident_only: false,
    ending_soon: false,
    unassigned_only: false,
    overdue_only: false,
    sort_by: 'created_at',
    sort_direction: 'desc',
    ...props.initialFilters
})

const hasActiveFilters = computed(() => {
    return filters.value.search ||
           filters.value.date_from ||
           filters.value.date_to ||
           filters.value.statuses.length > 0 ||
           filters.value.priorities.length > 0 ||
           filters.value.ops_user_id ||
           filters.value.checker_id ||
           filters.value.incident_only ||
           filters.value.ending_soon ||
           filters.value.unassigned_only ||
           filters.value.overdue_only
})

const debouncedSearch = debounce(() => {
    emit('search', filters.value.search)
}, 300)

const applyDateRange = () => {
    const today = new Date()
    const ranges = {
        today: {
            from: today.toISOString().split('T')[0],
            to: today.toISOString().split('T')[0]
        },
        yesterday: {
            from: new Date(today.getTime() - 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            to: new Date(today.getTime() - 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        },
        last7days: {
            from: new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            to: today.toISOString().split('T')[0]
        },
        last30days: {
            from: new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            to: today.toISOString().split('T')[0]
        },
        last90days: {
            from: new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
            to: today.toISOString().split('T')[0]
        },
        thismonth: {
            from: new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0],
            to: today.toISOString().split('T')[0]
        },
        lastmonth: {
            from: new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString().split('T')[0],
            to: new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0]
        },
        thisyear: {
            from: new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0],
            to: today.toISOString().split('T')[0]
        }
    }

    if (ranges[selectedDateRange.value]) {
        filters.value.date_from = ranges[selectedDateRange.value].from
        filters.value.date_to = ranges[selectedDateRange.value].to
    }
}

const resetFilters = () => {
    filters.value = {
        search: '',
        date_from: '',
        date_to: '',
        statuses: [],
        priorities: [],
        ops_user_id: '',
        checker_id: '',
        incident_only: false,
        ending_soon: false,
        unassigned_only: false,
        overdue_only: false,
        sort_by: 'created_at',
        sort_direction: 'desc'
    }
    selectedDateRange.value = ''
    applyFilters()
}

const applyFilters = () => {
    emit('filtersChanged', { ...filters.value })
}

const clearDateRange = () => {
    filters.value.date_from = ''
    filters.value.date_to = ''
    selectedDateRange.value = ''
}

const removeStatus = (status) => {
    filters.value.statuses = filters.value.statuses.filter(s => s !== status)
}

const getStatusLabel = (status) => {
    const statusObj = props.availableStatuses.find(s => s.value === status)
    return statusObj ? statusObj.label : status
}

const formatDateRange = () => {
    if (filters.value.date_from && filters.value.date_to) {
        return `${filters.value.date_from} to ${filters.value.date_to}`
    } else if (filters.value.date_from) {
        return `From ${filters.value.date_from}`
    } else if (filters.value.date_to) {
        return `Until ${filters.value.date_to}`
    }
    return ''
}

// Watch for filter changes and auto-apply
watch(filters, () => {
    if (hasActiveFilters.value) {
        applyFilters()
    }
}, { deep: true })
</script>

<style scoped>
.advanced-options {
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
}

.active-filters {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>