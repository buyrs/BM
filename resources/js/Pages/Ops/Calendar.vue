<template>
    <DashboardOps>
        <div class="calendar-container">
            <!-- Calendar Header -->
            <div class="calendar-header">
                <h1 class="text-2xl font-bold text-gray-900">Mission Calendar</h1>
                <p class="text-gray-600">View and manage all missions in calendar format</p>
            </div>

            <!-- Calendar Navigation -->
            <CalendarNavigation
                :current-date="currentDate"
                :view-mode="viewMode"
                @date-change="handleDateChange"
                @view-change="handleViewChange"
            />

            <!-- Calendar Filters -->
            <div class="calendar-filters mb-6">
                <div class="flex flex-wrap gap-4">
                    <select
                        v-model="filters.status"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="unassigned">Unassigned</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>

                    <select
                        v-model="filters.mission_type"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Types</option>
                        <option value="entry">Entry</option>
                        <option value="exit">Exit</option>
                    </select>

                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search by tenant or address..."
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1 min-w-64"
                    />

                    <button
                        @click="clearFilters"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading missions...</span>
            </div>

            <!-- Calendar Grid -->
            <CalendarGrid
                v-else
                :missions="filteredMissions"
                :current-date="currentDate"
                :view-mode="viewMode"
                :loading="loading"
                @mission-click="showMissionDetails"
                @date-click="showCreateMission"
            />

            <!-- Mission Details Modal -->
            <MissionDetailsModal
                :mission="selectedMission"
                :show="showDetailsModal"
                @close="closeDetailsModal"
                @update="handleMissionUpdate"
            />

            <!-- Create Mission Modal -->
            <CreateMissionModal
                :show="showCreateModal"
                :selected-date="selectedDate"
                @close="closeCreateModal"
                @create="handleMissionCreate"
            />
        </div>
    </DashboardOps>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'
import CalendarNavigation from '@/Components/Calendar/CalendarNavigation.vue'
import CalendarGrid from '@/Components/Calendar/CalendarGrid.vue'
import MissionDetailsModal from '@/Components/Calendar/MissionDetailsModal.vue'
import CreateMissionModal from '@/Components/Calendar/CreateMissionModal.vue'

// Props
const props = defineProps({
    missions: {
        type: Array,
        default: () => []
    },
    checkers: {
        type: Array,
        default: () => []
    }
})

// Reactive state
const currentDate = ref(new Date())
const viewMode = ref('month')
const loading = ref(false)
const showDetailsModal = ref(false)
const showCreateModal = ref(false)
const selectedMission = ref(null)
const selectedDate = ref(null)

// Filters
const filters = reactive({
    status: '',
    mission_type: '',
    checker_id: null,
    search: ''
})

// Computed properties
const filteredMissions = computed(() => {
    let filtered = props.missions

    if (filters.status) {
        filtered = filtered.filter(mission => mission.status === filters.status)
    }

    if (filters.mission_type) {
        filtered = filtered.filter(mission => mission.type === filters.mission_type)
    }

    if (filters.checker_id) {
        filtered = filtered.filter(mission => mission.agent?.id === filters.checker_id)
    }

    if (filters.search) {
        const searchTerm = filters.search.toLowerCase()
        filtered = filtered.filter(mission => 
            mission.tenant_name?.toLowerCase().includes(searchTerm) ||
            mission.address?.toLowerCase().includes(searchTerm)
        )
    }

    return filtered
})

// Methods
const handleDateChange = (newDate) => {
    currentDate.value = newDate
    loadMissions()
}

const handleViewChange = (newViewMode) => {
    viewMode.value = newViewMode
    loadMissions()
}

const showMissionDetails = (mission) => {
    selectedMission.value = mission
    showDetailsModal.value = true
}

const closeDetailsModal = () => {
    showDetailsModal.value = false
    selectedMission.value = null
}

const showCreateMission = (date) => {
    selectedDate.value = date
    showCreateModal.value = true
}

const closeCreateModal = () => {
    showCreateModal.value = false
    selectedDate.value = null
}

const handleMissionUpdate = (updatedMission) => {
    // Reload missions after update
    loadMissions()
    closeDetailsModal()
}

const handleMissionCreate = (newMission) => {
    // Reload missions after creation
    loadMissions()
    closeCreateModal()
}

const clearFilters = () => {
    filters.status = ''
    filters.mission_type = ''
    filters.checker_id = null
    filters.search = ''
}

const loadMissions = () => {
    loading.value = true
    
    const params = {
        date: currentDate.value.toISOString().split('T')[0],
        view: viewMode.value,
        ...filters
    }

    router.get(route('calendar.missions'), params, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            loading.value = false
        },
        onError: () => {
            loading.value = false
        }
    })
}

// Watch for filter changes
watch(filters, () => {
    loadMissions()
}, { deep: true })

// Load initial data
onMounted(() => {
    if (!props.missions.length) {
        loadMissions()
    }
})
</script>

<style scoped>
.calendar-container {
    @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6;
}

.calendar-header {
    @apply mb-6;
}

.calendar-filters {
    @apply bg-white p-4 rounded-lg shadow-sm border border-gray-200;
}

@media (max-width: 768px) {
    .calendar-filters .flex {
        @apply flex-col;
    }
    
    .calendar-filters select,
    .calendar-filters input {
        @apply w-full;
    }
}
</style>