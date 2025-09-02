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
            <CalendarFilters
                :filters="filters"
                :checkers="checkers"
                :loading="filtersLoading"
                @filter-change="handleFilterChange"
                @clear-filters="handleClearFilters"
            />

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
                @assign="handleMissionAssign"
                @status-change="handleMissionStatusChange"
                @duplicate="handleMissionDuplicate"
                @view-bail-mobilite="handleViewBailMobilite"
            />

            <!-- Create Mission Modal -->
            <CreateMissionModal
                :show="showCreateModal"
                :selected-date="selectedDate"
                :checkers="checkers"
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
import CalendarFilters from '@/Components/Calendar/CalendarFilters.vue'
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
    },
    initialFilters: {
        type: Object,
        default: () => ({})
    }
})

// Reactive state
const currentDate = ref(new Date())
const viewMode = ref('month')
const loading = ref(false)
const filtersLoading = ref(false)
const showDetailsModal = ref(false)
const showCreateModal = ref(false)
const selectedMission = ref(null)
const selectedDate = ref(null)

// Initialize filters from URL parameters or props
const initializeFilters = () => {
    const urlParams = new URLSearchParams(window.location.search)
    return {
        status: urlParams.get('status') || props.initialFilters.status || '',
        mission_type: urlParams.get('mission_type') || props.initialFilters.mission_type || '',
        checker_id: urlParams.get('checker_id') ? parseInt(urlParams.get('checker_id')) : (props.initialFilters.checker_id || null),
        date_range: urlParams.get('date_range') || props.initialFilters.date_range || '',
        search: urlParams.get('search') || props.initialFilters.search || ''
    }
}

// Filters
const filters = reactive(initializeFilters())

// Computed properties
const filteredMissions = computed(() => {
    let filtered = props.missions

    // Status filter
    if (filters.status) {
        filtered = filtered.filter(mission => mission.status === filters.status)
    }

    // Mission type filter
    if (filters.mission_type) {
        filtered = filtered.filter(mission => mission.type === filters.mission_type)
    }

    // Checker filter
    if (filters.checker_id) {
        filtered = filtered.filter(mission => mission.agent?.id === filters.checker_id)
    }

    // Date range filter
    if (filters.date_range) {
        const now = new Date()
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
        
        filtered = filtered.filter(mission => {
            const missionDate = new Date(mission.scheduled_at)
            const missionDay = new Date(missionDate.getFullYear(), missionDate.getMonth(), missionDate.getDate())
            
            switch (filters.date_range) {
                case 'today':
                    return missionDay.getTime() === today.getTime()
                case 'tomorrow':
                    const tomorrow = new Date(today)
                    tomorrow.setDate(tomorrow.getDate() + 1)
                    return missionDay.getTime() === tomorrow.getTime()
                case 'this_week':
                    const startOfWeek = new Date(today)
                    startOfWeek.setDate(today.getDate() - today.getDay())
                    const endOfWeek = new Date(startOfWeek)
                    endOfWeek.setDate(startOfWeek.getDate() + 6)
                    return missionDay >= startOfWeek && missionDay <= endOfWeek
                case 'next_week':
                    const nextWeekStart = new Date(today)
                    nextWeekStart.setDate(today.getDate() + (7 - today.getDay()))
                    const nextWeekEnd = new Date(nextWeekStart)
                    nextWeekEnd.setDate(nextWeekStart.getDate() + 6)
                    return missionDay >= nextWeekStart && missionDay <= nextWeekEnd
                case 'this_month':
                    return missionDate.getMonth() === now.getMonth() && missionDate.getFullYear() === now.getFullYear()
                case 'overdue':
                    return missionDay < today && mission.status !== 'completed' && mission.status !== 'cancelled'
                default:
                    return true
            }
        })
    }

    // Search filter
    if (filters.search) {
        const searchTerm = filters.search.toLowerCase()
        filtered = filtered.filter(mission => 
            mission.tenant_name?.toLowerCase().includes(searchTerm) ||
            mission.address?.toLowerCase().includes(searchTerm) ||
            mission.id?.toString().includes(searchTerm) ||
            mission.agent?.name?.toLowerCase().includes(searchTerm)
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

const handleMissionAssign = (mission) => {
    // Handle mission assignment - use assign-to-checker route for ops users
    router.post(route('missions.assign-to-checker', mission.id), {}, {
        onSuccess: () => {
            loadMissions()
            closeDetailsModal()
        }
    })
}

const handleMissionStatusChange = ({ mission, status }) => {
    // Handle mission status change
    router.patch(route('missions.update-status', mission.id), {
        status: status
    }, {
        onSuccess: () => {
            loadMissions()
            closeDetailsModal()
        },
        onError: (errors) => {
            console.error('Failed to update mission status:', errors)
        }
    })
}

const handleMissionDuplicate = (mission) => {
    // Handle mission duplication - create a new mission with similar data
    const duplicateData = {
        start_date: new Date(mission.scheduled_at).toISOString().split('T')[0],
        end_date: mission.bail_mobilite?.end_date || new Date(mission.scheduled_at).toISOString().split('T')[0],
        address: mission.address,
        tenant_name: mission.tenant_name,
        tenant_phone: mission.tenant_phone,
        tenant_email: mission.tenant_email,
        notes: mission.notes + ' (Duplicated)',
        entry_scheduled_time: mission.scheduled_time,
        exit_scheduled_time: mission.scheduled_time,
    }
    
    router.post(route('ops.calendar.missions.create'), duplicateData, {
        onSuccess: () => {
            loadMissions()
            closeDetailsModal()
        },
        onError: (errors) => {
            console.error('Failed to duplicate mission:', errors)
        }
    })
}

const handleViewBailMobilite = (bailMobilite) => {
    // Navigate to bail mobilite details
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id))
}

// Filter management methods
const updateURLParams = (newFilters) => {
    const url = new URL(window.location)
    const params = url.searchParams

    // Clear existing filter params
    params.delete('status')
    params.delete('mission_type')
    params.delete('checker_id')
    params.delete('date_range')
    params.delete('search')

    // Add new filter params
    Object.entries(newFilters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.set(key, value)
        }
    })

    // Update URL without page reload
    window.history.replaceState({}, '', url.toString())
}

const handleFilterChange = (newFilters) => {
    // Update local filters
    Object.assign(filters, newFilters)
    
    // Update URL parameters
    updateURLParams(newFilters)
    
    // Load missions with new filters
    loadMissions()
}

const handleClearFilters = () => {
    const clearedFilters = {
        status: '',
        mission_type: '',
        checker_id: null,
        date_range: '',
        search: ''
    }
    
    // Update local filters
    Object.assign(filters, clearedFilters)
    
    // Update URL parameters
    updateURLParams(clearedFilters)
    
    // Load missions without filters
    loadMissions()
}

const loadMissions = () => {
    loading.value = true
    filtersLoading.value = true
    
    const params = {
        date: currentDate.value.toISOString().split('T')[0],
        view: viewMode.value,
        ...filters
    }

    // Remove empty/null values from params
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
        }
    })

    router.get(route('ops.calendar.missions'), params, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            loading.value = false
            filtersLoading.value = false
        },
        onError: () => {
            loading.value = false
            filtersLoading.value = false
        }
    })
}

// Debounced filter change handler for search
let filterTimeout = null
const debouncedFilterChange = (newFilters) => {
    clearTimeout(filterTimeout)
    filtersLoading.value = true
    
    filterTimeout = setTimeout(() => {
        handleFilterChange(newFilters)
    }, 300) // 300ms debounce for search
}

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
</style>