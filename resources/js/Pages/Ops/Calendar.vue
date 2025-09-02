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
                :loading="loading"
                :total-missions="filteredMissions.length"
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

            <!-- Bulk Operations Toolbar -->
            <div v-if="selectionMode || selectedMissionsForBulk.length > 0" 
                 class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-medium text-blue-900">
                                {{ selectedMissionsForBulk.length }} mission(s) selected
                            </span>
                        </div>
                        
                        <button
                            @click="selectAllVisibleMissions"
                            class="text-sm text-blue-700 hover:text-blue-900 underline"
                        >
                            Select All Visible
                        </button>
                        
                        <button
                            @click="clearSelection"
                            class="text-sm text-blue-700 hover:text-blue-900 underline"
                        >
                            Clear Selection
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button
                            @click="showBulkModal = true"
                            :disabled="selectedMissionsForBulk.length === 0"
                            class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Bulk Actions
                        </button>
                        
                        <button
                            @click="exitSelectionMode"
                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Exit Selection
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selection Mode Toggle -->
            <div v-else class="flex justify-end mb-4">
                <button
                    @click="enterSelectionMode"
                    class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Select Missions
                </button>
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
                :selection-mode="selectionMode"
                :selected-missions="selectedMissionsForBulk"
                @mission-click="handleMissionClick"
                @date-click="showCreateMission"
                @mission-select="handleMissionSelect"
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
                @delete="handleMissionDelete"
            />

            <!-- Create Mission Modal -->
            <CreateMissionModal
                :show="showCreateModal"
                :selected-date="selectedDate"
                :checkers="checkers"
                @close="closeCreateModal"
                @create="handleMissionCreate"
            />

            <!-- Edit Mission Modal -->
            <EditMissionModal
                :show="showEditModal"
                :mission="selectedMission"
                :checkers="checkers"
                @close="closeEditModal"
                @updated="handleMissionUpdated"
            />

            <!-- Assign Mission Modal -->
            <AssignMissionModal
                :show="showAssignModal"
                :mission="selectedMission"
                :checkers="checkers"
                @close="closeAssignModal"
                @assigned="handleMissionAssigned"
            />

            <!-- Bulk Operations Modal -->
            <BulkOperationsModal
                :show="showBulkModal"
                :selected-missions="selectedMissionsForBulk"
                :checkers="checkers"
                @close="closeBulkModal"
                @completed="handleBulkOperationCompleted"
            />
        </div>
    </DashboardOps>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'
import CalendarNavigation from '@/Components/Calendar/CalendarNavigation.vue'
import CalendarFilters from '@/Components/Calendar/CalendarFilters.vue'
import CalendarGrid from '@/Components/Calendar/CalendarGrid.vue'
import MissionDetailsModal from '@/Components/Calendar/MissionDetailsModal.vue'
import CreateMissionModal from '@/Components/Calendar/CreateMissionModal.vue'
import EditMissionModal from '@/Components/Calendar/EditMissionModal.vue'
import AssignMissionModal from '@/Components/Calendar/AssignMissionModal.vue'
import BulkOperationsModal from '@/Components/Calendar/BulkOperationsModal.vue'

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

// Initialize state from URL parameters or localStorage
const initializeCalendarState = () => {
    const urlParams = new URLSearchParams(window.location.search)
    const savedState = localStorage.getItem('calendar-state')
    const parsedSavedState = savedState ? JSON.parse(savedState) : {}
    
    return {
        currentDate: urlParams.get('date') || parsedSavedState.currentDate || new Date().toISOString().split('T')[0],
        viewMode: urlParams.get('view') || parsedSavedState.viewMode || 'month',
        filters: {
            status: urlParams.get('status') || parsedSavedState.filters?.status || props.initialFilters.status || '',
            mission_type: urlParams.get('mission_type') || parsedSavedState.filters?.mission_type || props.initialFilters.mission_type || '',
            checker_id: urlParams.get('checker_id') ? parseInt(urlParams.get('checker_id')) : (parsedSavedState.filters?.checker_id || props.initialFilters.checker_id || null),
            date_range: urlParams.get('date_range') || parsedSavedState.filters?.date_range || props.initialFilters.date_range || '',
            search: urlParams.get('search') || parsedSavedState.filters?.search || props.initialFilters.search || ''
        }
    }
}

const initialState = initializeCalendarState()

// Reactive state
const currentDate = ref(new Date(initialState.currentDate))
const viewMode = ref(initialState.viewMode)
const loading = ref(false)
const filtersLoading = ref(false)
const showDetailsModal = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAssignModal = ref(false)
const showBulkModal = ref(false)
const selectedMission = ref(null)
const selectedDate = ref(null)
const selectedMissionsForBulk = ref([])
const selectionMode = ref(false)

// Mission cache for efficient loading
const missionCache = reactive(new Map())
const lastLoadedRange = ref(null)

// Filters
const filters = reactive(initialState.filters)

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
    persistCalendarState()
    loadMissionsIfNeeded()
}

const handleViewChange = (newViewMode) => {
    viewMode.value = newViewMode
    persistCalendarState()
    loadMissionsIfNeeded()
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

const closeEditModal = () => {
    showEditModal.value = false
    selectedMission.value = null
}

const closeAssignModal = () => {
    showAssignModal.value = false
    selectedMission.value = null
}

const closeBulkModal = () => {
    showBulkModal.value = false
    selectedMissionsForBulk.value = []
}

const handleMissionUpdate = (mission) => {
    selectedMission.value = mission
    showEditModal.value = true
    closeDetailsModal()
}

const handleMissionCreate = (newMission) => {
    // Reload missions after creation
    loadMissions()
    closeCreateModal()
}

const handleMissionAssign = (mission) => {
    selectedMission.value = mission
    showAssignModal.value = true
    closeDetailsModal()
}

const handleMissionStatusChange = async ({ mission, status }) => {
    try {
        const response = await fetch(route('ops.calendar.missions.update-status', mission.id), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status })
        })

        const data = await response.json()

        if (data.success) {
            loadMissions()
            closeDetailsModal()
        } else {
            console.error('Failed to update mission status:', data.message)
        }
    } catch (error) {
        console.error('Error updating mission status:', error)
    }
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

const handleMissionUpdated = (updatedMission) => {
    // Reload missions after update
    loadMissions()
    closeEditModal()
}

const handleMissionAssigned = (assignedMission) => {
    // Reload missions after assignment
    loadMissions()
    closeAssignModal()
}

const handleBulkOperationCompleted = (results) => {
    // Reload missions after bulk operation
    loadMissions()
    // Modal will close automatically after showing results
}

const handleMissionClick = (mission) => {
    if (selectionMode.value) {
        handleMissionSelect(mission)
    } else {
        showMissionDetails(mission)
    }
}

const handleMissionSelect = (mission) => {
    const index = selectedMissionsForBulk.value.findIndex(m => m.id === mission.id)
    if (index > -1) {
        selectedMissionsForBulk.value.splice(index, 1)
    } else {
        selectedMissionsForBulk.value.push(mission)
    }
}

const enterSelectionMode = () => {
    selectionMode.value = true
    selectedMissionsForBulk.value = []
}

const exitSelectionMode = () => {
    selectionMode.value = false
    selectedMissionsForBulk.value = []
}

const selectAllVisibleMissions = () => {
    selectedMissionsForBulk.value = [...filteredMissions.value]
}

const clearSelection = () => {
    selectedMissionsForBulk.value = []
}

const handleMissionDelete = async (mission) => {
    try {
        const response = await fetch(route('ops.calendar.missions.delete', mission.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })

        const data = await response.json()

        if (data.success) {
            loadMissions()
            closeDetailsModal()
        } else {
            console.error('Failed to delete mission:', data.message)
        }
    } catch (error) {
        console.error('Error deleting mission:', error)
    }
}

// Filter management methods
const updateURLParams = (newParams) => {
    const url = new URL(window.location)
    const params = url.searchParams

    // Clear existing params
    params.delete('date')
    params.delete('view')
    params.delete('status')
    params.delete('mission_type')
    params.delete('checker_id')
    params.delete('date_range')
    params.delete('search')

    // Add new params
    Object.entries(newParams).forEach(([key, value]) => {
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
    
    // Clear cache since filters changed
    missionCache.clear()
    
    // Persist state and load missions
    persistCalendarState()
    loadMissionsIfNeeded()
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
    
    // Clear cache since filters changed
    missionCache.clear()
    
    // Persist state and load missions
    persistCalendarState()
    loadMissionsIfNeeded()
}

// State persistence
const persistCalendarState = () => {
    const state = {
        currentDate: currentDate.value.toISOString().split('T')[0],
        viewMode: viewMode.value,
        filters: { ...filters }
    }
    
    localStorage.setItem('calendar-state', JSON.stringify(state))
    updateURLParams({
        date: state.currentDate,
        view: state.viewMode,
        ...state.filters
    })
}

// Efficient mission loading with caching
const getDateRangeForView = (date, view) => {
    const current = new Date(date)
    let startDate, endDate
    
    switch (view) {
        case 'month':
            startDate = new Date(current.getFullYear(), current.getMonth(), 1)
            endDate = new Date(current.getFullYear(), current.getMonth() + 1, 0)
            // Extend to show full weeks
            startDate.setDate(startDate.getDate() - startDate.getDay())
            endDate.setDate(endDate.getDate() + (6 - endDate.getDay()))
            break
        case 'week':
            startDate = getStartOfWeek(current)
            endDate = getEndOfWeek(current)
            break
        case 'day':
            startDate = new Date(current)
            endDate = new Date(current)
            break
        default:
            startDate = new Date(current.getFullYear(), current.getMonth(), 1)
            endDate = new Date(current.getFullYear(), current.getMonth() + 1, 0)
    }
    
    return { startDate, endDate }
}

const getCacheKey = (startDate, endDate, filters) => {
    const filterString = Object.entries(filters)
        .filter(([key, value]) => value !== '' && value !== null && value !== undefined)
        .sort()
        .map(([key, value]) => `${key}:${value}`)
        .join('|')
    
    return `${startDate.toISOString().split('T')[0]}_${endDate.toISOString().split('T')[0]}_${filterString}`
}

const loadMissionsIfNeeded = () => {
    const { startDate, endDate } = getDateRangeForView(currentDate.value, viewMode.value)
    const cacheKey = getCacheKey(startDate, endDate, filters)
    
    // Check if we already have this data cached
    if (missionCache.has(cacheKey)) {
        return
    }
    
    // Check if we need to load a larger range to avoid frequent API calls
    const shouldLoadExtendedRange = viewMode.value === 'day' || viewMode.value === 'week'
    
    if (shouldLoadExtendedRange) {
        // For day/week views, load the entire month to reduce API calls
        const monthStart = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1)
        const monthEnd = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0)
        loadMissions(monthStart, monthEnd, cacheKey)
    } else {
        loadMissions(startDate, endDate, cacheKey)
    }
}

const loadMissions = (startDate = null, endDate = null, cacheKey = null) => {
    if (!startDate || !endDate) {
        const range = getDateRangeForView(currentDate.value, viewMode.value)
        startDate = range.startDate
        endDate = range.endDate
    }
    
    if (!cacheKey) {
        cacheKey = getCacheKey(startDate, endDate, filters)
    }
    
    loading.value = true
    filtersLoading.value = true
    
    const params = {
        start_date: startDate.toISOString().split('T')[0],
        end_date: endDate.toISOString().split('T')[0],
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
        onSuccess: (page) => {
            // Cache the loaded missions
            if (page.props.missions) {
                missionCache.set(cacheKey, {
                    missions: page.props.missions,
                    timestamp: Date.now(),
                    startDate: startDate.toISOString().split('T')[0],
                    endDate: endDate.toISOString().split('T')[0]
                })
                
                // Clean old cache entries (keep last 10)
                if (missionCache.size > 10) {
                    const oldestKey = Array.from(missionCache.keys())[0]
                    missionCache.delete(oldestKey)
                }
            }
            
            lastLoadedRange.value = { startDate, endDate, cacheKey }
            loading.value = false
            filtersLoading.value = false
        },
        onError: () => {
            loading.value = false
            filtersLoading.value = false
        }
    })
}

// Utility functions for date handling
const getStartOfWeek = (date) => {
    const start = new Date(date)
    const day = start.getDay()
    const diff = start.getDate() - day + (day === 0 ? -6 : 1)
    start.setDate(diff)
    return start
}

const getEndOfWeek = (date) => {
    const end = getStartOfWeek(date)
    end.setDate(end.getDate() + 6)
    return end
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

// Handle browser back/forward navigation
const handlePopState = () => {
    const state = initializeCalendarState()
    currentDate.value = new Date(state.currentDate)
    viewMode.value = state.viewMode
    Object.assign(filters, state.filters)
    loadMissionsIfNeeded()
}

// Load initial data
onMounted(() => {
    // Set up browser navigation handling
    window.addEventListener('popstate', handlePopState)
    
    // Load missions if not already provided or if state changed
    if (!props.missions.length || 
        currentDate.value.toISOString().split('T')[0] !== new Date().toISOString().split('T')[0]) {
        loadMissionsIfNeeded()
    } else {
        // Cache the initial missions
        const { startDate, endDate } = getDateRangeForView(currentDate.value, viewMode.value)
        const cacheKey = getCacheKey(startDate, endDate, filters)
        missionCache.set(cacheKey, {
            missions: props.missions,
            timestamp: Date.now(),
            startDate: startDate.toISOString().split('T')[0],
            endDate: endDate.toISOString().split('T')[0]
        })
    }
})

// Cleanup
onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState)
    clearTimeout(filterTimeout)
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