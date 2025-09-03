<template>
    <DashboardOps>
        <ErrorBoundary
            ref="errorBoundary"
            :show-details="false"
            @retry="handleGlobalRetry"
            @reset="handleGlobalReset"
        >
            <div class="calendar-container touch-enabled" ref="calendarContainer">
                <!-- Mobile Header -->
                <div class="calendar-header">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Mission Calendar</h1>
                            <p class="text-gray-600 text-sm md:text-base">View and manage all missions in calendar format</p>
                        </div>
                        
                        <!-- Mobile Menu Toggle -->
                        <button
                            @click="toggleMobileMenu"
                            class="md:hidden p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :aria-expanded="showMobileMenu"
                            aria-label="Toggle mobile menu"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>

            <!-- Mobile Menu Overlay -->
            <div
                v-if="showMobileMenu"
                class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"
                @click="closeMobileMenu"
            ></div>

            <!-- Mobile Slide-out Menu -->
            <div
                :class="[
                    'fixed top-0 right-0 h-full w-80 bg-white shadow-xl transform transition-transform duration-300 ease-in-out z-50 md:hidden',
                    showMobileMenu ? 'translate-x-0' : 'translate-x-full'
                ]"
            >
                <div class="p-4 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Calendar Options</h2>
                        <button
                            @click="closeMobileMenu"
                            class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Close mobile menu"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-4 space-y-4 overflow-y-auto">
                    <!-- Mobile Navigation -->
                    <CalendarNavigation
                        :current-date="currentDate"
                        :view-mode="viewMode"
                        :loading="loading"
                        :total-missions="filteredMissions.length"
                        :mobile="true"
                        @date-change="handleDateChange"
                        @view-change="handleViewChange"
                    />

                    <!-- Mobile Filters -->
                    <CalendarFilters
                        :filters="filters"
                        :checkers="checkers"
                        :loading="filtersLoading"
                        :mobile="true"
                        @filter-change="handleFilterChange"
                        @clear-filters="handleClearFilters"
                    />
                </div>
            </div>

            <!-- Desktop Navigation and Filters -->
            <div class="hidden md:block">
                <CalendarNavigation
                    :current-date="currentDate"
                    :view-mode="viewMode"
                    :loading="loading"
                    :total-missions="filteredMissions.length"
                    @date-change="handleDateChange"
                    @view-change="handleViewChange"
                />

                <CalendarFilters
                    :filters="filters"
                    :checkers="checkers"
                    :loading="filtersLoading"
                    @filter-change="handleFilterChange"
                    @clear-filters="handleClearFilters"
                />
            </div>

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

            <!-- Loading States -->
            <div v-if="initialLoading">
                <LoadingSkeleton type="navigation" />
                <LoadingSkeleton type="filters" />
                <LoadingSkeleton type="grid" />
            </div>
            
            <div v-else-if="loading && !hasData" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading missions...</span>
            </div>

            <!-- Mobile Quick Actions Bar -->
            <div class="md:hidden bg-white border-b border-gray-200 p-3 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <button
                            @click="handlePreviousPeriod"
                            class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 touch-feedback"
                            :disabled="loading"
                            :aria-label="getPreviousButtonLabel()"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        
                        <div class="text-center">
                            <h2 class="text-lg font-semibold text-gray-900">{{ formattedCurrentPeriod }}</h2>
                            <p class="text-xs text-gray-600">{{ filteredMissions.length }} missions</p>
                        </div>
                        
                        <button
                            @click="handleNextPeriod"
                            class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 touch-feedback"
                            :disabled="loading"
                            :aria-label="getNextButtonLabel()"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-1">
                        <button
                            v-for="mode in viewModes"
                            :key="mode.value"
                            @click="handleViewChange(mode.value)"
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded touch-feedback',
                                viewMode === mode.value
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                            :disabled="loading"
                            :aria-pressed="viewMode === mode.value"
                            :aria-label="`Switch to ${mode.label.toLowerCase()} view`"
                        >
                            {{ mode.short }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Swipe Container for Touch Gestures -->
            <div
                class="swipe-container relative"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
                ref="swipeContainer"
            >
                <!-- Swipe Indicators -->
                <div class="swipe-indicator left" ref="leftIndicator">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </div>
                <div class="swipe-indicator right" ref="rightIndicator">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>

                <!-- Calendar Grid -->
                <CalendarGrid
                    v-if="!initialLoading && !criticalError"
                    :missions="filteredMissions"
                    :current-date="currentDate"
                    :view-mode="viewMode"
                    :loading="loading"
                    :selection-mode="selectionMode"
                    :selected-missions="selectedMissionsForBulk"
                    :mobile="isMobile"
                    @mission-click="handleMissionClick"
                    @date-click="showCreateMission"
                    @mission-select="handleMissionSelect"
                    @mission-reschedule="handleMissionReschedule"
                    @conflict-detected="handleConflictDetected"
                />
                
                <!-- Empty State -->
                <EmptyState
                    v-else-if="!initialLoading && !loading && !criticalError && filteredMissions.length === 0"
                    :type="getEmptyStateType()"
                    @primary-action="handleEmptyStateAction"
                    @secondary-action="handleEmptyStateSecondaryAction"
                />
                
                <!-- Critical Error State -->
                <EmptyState
                    v-else-if="criticalError"
                    type="loading-failed"
                    :title="criticalError.title"
                    :description="criticalError.message"
                    primary-action="Try Again"
                    secondary-action="Refresh Page"
                    @primary-action="handleCriticalErrorRetry"
                    @secondary-action="handlePageRefresh"
                />
            </div>

            <!-- Mobile Floating Action Button -->
            <button
                v-if="isMobile && !selectionMode"
                @click="showCreateMissionFab"
                class="mobile-fab touch-feedback"
                aria-label="Create new mission"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>

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

            <!-- Conflict Resolution Modal -->
            <ConflictResolutionModal
                :show="showConflictModal"
                :conflict-data="conflictData"
                @close="closeConflictModal"
                @resolve="handleConflictResolution"
            />
            </div>
            
            <!-- Toast Notifications -->
            <ToastContainer />
        </ErrorBoundary>
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
import ConflictResolutionModal from '@/Components/Calendar/ConflictResolutionModal.vue'
import ErrorBoundary from '@/Components/Calendar/ErrorBoundary.vue'
import LoadingSkeleton from '@/Components/Calendar/LoadingSkeleton.vue'
import EmptyState from '@/Components/Calendar/EmptyState.vue'
import ToastContainer from '@/Components/Calendar/ToastContainer.vue'
import calendarErrorService from '@/Services/CalendarErrorService.js'
import toastService from '@/Services/ToastService.js'

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
const initialLoading = ref(true)
const filtersLoading = ref(false)
const criticalError = ref(null)
const hasData = ref(false)
const showDetailsModal = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAssignModal = ref(false)
const showBulkModal = ref(false)
const showConflictModal = ref(false)
const selectedMission = ref(null)
const selectedDate = ref(null)
const selectedMissionsForBulk = ref([])
const selectionMode = ref(false)
const conflictData = ref(null)

// Mobile-specific state
const showMobileMenu = ref(false)
const isMobile = ref(false)
const touchStartX = ref(0)
const touchStartY = ref(0)
const touchEndX = ref(0)
const touchEndY = ref(0)
const isSwipeGesture = ref(false)

// Template refs
const calendarContainer = ref(null)
const swipeContainer = ref(null)
const leftIndicator = ref(null)
const rightIndicator = ref(null)
const errorBoundary = ref(null)

// Mission cache for efficient loading
const missionCache = reactive(new Map())
const lastLoadedRange = ref(null)

// Filters
const filters = reactive(initialState.filters)

// View modes configuration
const viewModes = [
    { value: 'month', label: 'Month', short: 'M' },
    { value: 'week', label: 'Week', short: 'W' },
    { value: 'day', label: 'Day', short: 'D' }
]

// Computed properties
const formattedCurrentPeriod = computed(() => {
    const date = currentDate.value
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ]
    
    switch (viewMode.value) {
        case 'month':
            return `${months[date.getMonth()]} ${date.getFullYear()}`
        case 'week':
            const startOfWeek = getStartOfWeek(date)
            const endOfWeek = getEndOfWeek(date)
            return `${formatShortDate(startOfWeek)} - ${formatShortDate(endOfWeek)}`
        case 'day':
            return formatLongDate(date)
        default:
            return ''
    }
})

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

// Error handling methods
const handleGlobalRetry = () => {
    criticalError.value = null
    loadMissionsIfNeeded()
}

const handleGlobalReset = () => {
    criticalError.value = null
    // Reset to initial state
    const state = initializeCalendarState()
    currentDate.value = new Date(state.currentDate)
    viewMode.value = state.viewMode
    Object.assign(filters, state.filters)
    missionCache.clear()
    loadMissionsIfNeeded()
}

const handleCriticalError = (error, operation) => {
    console.error(`Critical calendar error in ${operation}:`, error)
    
    const errorInfo = calendarErrorService.categorizeError(error)
    criticalError.value = {
        title: `${operation} Failed`,
        message: errorInfo.userMessage,
        canRetry: errorInfo.canRetry,
        originalError: error
    }
    
    initialLoading.value = false
    loading.value = false
}

const handleCriticalErrorRetry = () => {
    criticalError.value = null
    loadMissionsIfNeeded()
}

const handlePageRefresh = () => {
    window.location.reload()
}

const getEmptyStateType = () => {
    // Check if filters are applied
    const hasFilters = Object.values(filters).some(value => 
        value !== '' && value !== null && value !== undefined
    )
    
    if (hasFilters) {
        return 'no-results'
    }
    
    return 'no-missions'
}

const handleEmptyStateAction = () => {
    const emptyType = getEmptyStateType()
    
    if (emptyType === 'no-results') {
        handleClearFilters()
    } else {
        // Show create mission modal for today
        showCreateMission(new Date())
    }
}

const handleEmptyStateSecondaryAction = () => {
    const emptyType = getEmptyStateType()
    
    if (emptyType === 'no-results') {
        // Reset search
        filters.search = ''
        handleFilterChange(filters)
    }
}

// Wrapped API methods with error handling
const safeApiCall = async (operation, apiCall, options = {}) => {
    try {
        const result = await calendarErrorService.wrapApiCall(operation, apiCall, {
            context: 'calendar',
            showNotification: true,
            ...options
        })()
        
        if (result.error) {
            throw new Error(result.userMessage)
        }
        
        return result
    } catch (error) {
        if (options.critical) {
            handleCriticalError(error, operation)
        } else {
            toastService.error(error.message || 'An error occurred', {
                title: `${operation} Failed`
            })
        }
        throw error
    }
}

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
    
    // Close mobile menu after view change
    if (isMobile.value) {
        closeMobileMenu()
    }
}

// Mobile-specific methods
const toggleMobileMenu = () => {
    showMobileMenu.value = !showMobileMenu.value
}

const closeMobileMenu = () => {
    showMobileMenu.value = false
}

const checkMobileDevice = () => {
    isMobile.value = window.innerWidth < 768
}

const handlePreviousPeriod = () => {
    const newDate = new Date(currentDate.value)
    
    switch (viewMode.value) {
        case 'month':
            newDate.setMonth(newDate.getMonth() - 1)
            break
        case 'week':
            newDate.setDate(newDate.getDate() - 7)
            break
        case 'day':
            newDate.setDate(newDate.getDate() - 1)
            break
    }
    
    handleDateChange(newDate)
}

const handleNextPeriod = () => {
    const newDate = new Date(currentDate.value)
    
    switch (viewMode.value) {
        case 'month':
            newDate.setMonth(newDate.getMonth() + 1)
            break
        case 'week':
            newDate.setDate(newDate.getDate() + 7)
            break
        case 'day':
            newDate.setDate(newDate.getDate() + 1)
            break
    }
    
    handleDateChange(newDate)
}

const getPreviousButtonLabel = () => {
    switch (viewMode.value) {
        case 'month':
            return 'Previous month'
        case 'week':
            return 'Previous week'
        case 'day':
            return 'Previous day'
        default:
            return 'Previous'
    }
}

const getNextButtonLabel = () => {
    switch (viewMode.value) {
        case 'month':
            return 'Next month'
        case 'week':
            return 'Next week'
        case 'day':
            return 'Next day'
        default:
            return 'Next'
    }
}

const showCreateMissionFab = () => {
    const today = new Date()
    showCreateMission(today)
}

// Touch gesture handling
const handleTouchStart = (event) => {
    if (!isMobile.value) return
    
    const touch = event.touches[0]
    touchStartX.value = touch.clientX
    touchStartY.value = touch.clientY
    isSwipeGesture.value = false
}

const handleTouchMove = (event) => {
    if (!isMobile.value) return
    
    const touch = event.touches[0]
    const deltaX = touch.clientX - touchStartX.value
    const deltaY = touch.clientY - touchStartY.value
    
    // Check if this is a horizontal swipe gesture
    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
        isSwipeGesture.value = true
        
        // Show swipe indicators
        if (deltaX > 0 && leftIndicator.value) {
            leftIndicator.value.classList.add('show')
            rightIndicator.value?.classList.remove('show')
        } else if (deltaX < 0 && rightIndicator.value) {
            rightIndicator.value.classList.add('show')
            leftIndicator.value?.classList.remove('show')
        }
        
        // Prevent default scrolling during swipe
        event.preventDefault()
    }
}

const handleTouchEnd = (event) => {
    if (!isMobile.value || !isSwipeGesture.value) return
    
    const touch = event.changedTouches[0]
    touchEndX.value = touch.clientX
    touchEndY.value = touch.clientY
    
    const deltaX = touchEndX.value - touchStartX.value
    const deltaY = touchEndY.value - touchStartY.value
    
    // Hide swipe indicators
    leftIndicator.value?.classList.remove('show')
    rightIndicator.value?.classList.remove('show')
    
    // Check if this is a valid swipe gesture
    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 100) {
        if (deltaX > 0) {
            // Swipe right - go to previous period
            handlePreviousPeriod()
        } else {
            // Swipe left - go to next period
            handleNextPeriod()
        }
    }
    
    isSwipeGesture.value = false
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

const closeConflictModal = () => {
    showConflictModal.value = false
    conflictData.value = null
}

const handleMissionReschedule = async (rescheduleData) => {
    try {
        await safeApiCall('Reschedule Mission', async () => {
            const response = await fetch(route('ops.calendar.missions.update', rescheduleData.mission.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    scheduled_at: rescheduleData.newDate,
                    scheduled_time: rescheduleData.newTime || rescheduleData.mission.scheduled_time
                })
            })

            const data = await response.json()

            if (data.success) {
                // Reload missions to reflect changes
                await loadMissions()
                toastService.success('Mission rescheduled successfully')
            } else {
                throw new Error(data.message || 'Failed to reschedule mission')
            }
        })
    } catch (error) {
        console.error('Error rescheduling mission:', error)
        toastService.error('Failed to reschedule mission')
    }
}

const handleConflictDetected = (conflictInfo) => {
    conflictData.value = conflictInfo
    showConflictModal.value = true
}

const handleConflictResolution = async (resolution) => {
    try {
        switch (resolution.action) {
            case 'proceed':
                // Proceed with the original reschedule despite conflicts
                await handleMissionReschedule({
                    mission: resolution.conflictData.mission,
                    newDate: resolution.conflictData.newDate,
                    newTime: resolution.conflictData.newTime
                })
                toastService.warning('Mission rescheduled with conflicts', {
                    title: 'Conflicts Remain',
                    description: 'Please review and resolve scheduling conflicts manually.'
                })
                break
                
            case 'suggest':
                // Use the selected alternative time slot
                if (resolution.newTime) {
                    await handleMissionReschedule({
                        mission: resolution.conflictData.mission,
                        newDate: resolution.newDate,
                        newTime: resolution.newTime
                    })
                    toastService.success('Mission rescheduled to conflict-free time slot')
                }
                break
                
            case 'cancel':
                // Do nothing, just close the modal
                toastService.info('Mission reschedule cancelled')
                break
        }
    } catch (error) {
        console.error('Error resolving conflict:', error)
        toastService.error('Failed to resolve scheduling conflict')
    } finally {
        closeConflictModal()
    }
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
    toastService.success('Mission created successfully')
}

const handleMissionAssign = (mission) => {
    selectedMission.value = mission
    showAssignModal.value = true
    closeDetailsModal()
}

const handleMissionStatusChange = async ({ mission, status }) => {
    try {
        await safeApiCall('Update Mission Status', async () => {
            const response = await fetch(route('ops.calendar.missions.update-status', mission.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status })
            })

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`)
            }

            const data = await response.json()

            if (!data.success) {
                throw new Error(data.message || 'Failed to update mission status')
            }

            return data
        })

        loadMissions()
        closeDetailsModal()
        toastService.success('Mission status updated successfully')
    } catch (error) {
        // Error already handled by safeApiCall
        console.error('Error updating mission status:', error)
    }
}

const handleMissionDuplicate = async (mission) => {
    try {
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
        
        await safeApiCall('Duplicate Mission', async () => {
            return new Promise((resolve, reject) => {
                router.post(route('ops.calendar.missions.create'), duplicateData, {
                    onSuccess: (page) => {
                        resolve(page)
                    },
                    onError: (errors) => {
                        reject(new Error(errors.message || 'Failed to duplicate mission'))
                    }
                })
            })
        })
        
        loadMissions()
        closeDetailsModal()
        toastService.success('Mission duplicated successfully')
    } catch (error) {
        // Error already handled by safeApiCall
        console.error('Error duplicating mission:', error)
    }
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
        await safeApiCall('Delete Mission', async () => {
            const response = await fetch(route('ops.calendar.missions.delete', mission.id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`)
            }

            const data = await response.json()

            if (!data.success) {
                throw new Error(data.message || 'Failed to delete mission')
            }

            return data
        })

        loadMissions()
        closeDetailsModal()
        toastService.success('Mission deleted successfully')
    } catch (error) {
        // Error already handled by safeApiCall
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

const loadMissions = async (startDate = null, endDate = null, cacheKey = null) => {
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

    try {
        await safeApiCall('Load Missions', async () => {
            return new Promise((resolve, reject) => {
                router.get(route('ops.calendar.missions'), params, {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: (page) => {
                        try {
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
                                
                                hasData.value = true
                            }
                            
                            lastLoadedRange.value = { startDate, endDate, cacheKey }
                            resolve(page)
                        } catch (error) {
                            reject(error)
                        }
                    },
                    onError: (errors) => {
                        reject(new Error(errors.message || 'Failed to load missions'))
                    }
                })
            })
        }, { critical: initialLoading.value })
        
        criticalError.value = null
    } catch (error) {
        console.error('Failed to load missions:', error)
    } finally {
        loading.value = false
        filtersLoading.value = false
        initialLoading.value = false
    }
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

const formatShortDate = (date) => {
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
    })
}

const formatLongDate = (date) => {
    return date.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric',
        month: 'long', 
        day: 'numeric' 
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

// Handle browser back/forward navigation
const handlePopState = () => {
    const state = initializeCalendarState()
    currentDate.value = new Date(state.currentDate)
    viewMode.value = state.viewMode
    Object.assign(filters, state.filters)
    loadMissionsIfNeeded()
}

// Load initial data
onMounted(async () => {
    try {
        // Set up browser navigation handling
        window.addEventListener('popstate', handlePopState)
        
        // Set up mobile detection
        checkMobileDevice()
        window.addEventListener('resize', checkMobileDevice)
        
        // Set up mobile menu close on outside click
        document.addEventListener('click', (event) => {
            if (showMobileMenu.value && !event.target.closest('.mobile-menu')) {
                closeMobileMenu()
            }
        })
        
        // Set up error service callbacks
        calendarErrorService.onError('Load Missions', () => {
            loadMissionsIfNeeded()
        })
        
        // Load missions if not already provided or if state changed
        if (!props.missions.length || 
            currentDate.value.toISOString().split('T')[0] !== new Date().toISOString().split('T')[0]) {
            await loadMissionsIfNeeded()
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
            
            hasData.value = props.missions.length > 0
            initialLoading.value = false
        }
    } catch (error) {
        console.error('Error during calendar initialization:', error)
        handleCriticalError(error, 'Calendar Initialization')
    }
})

// Cleanup
onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState)
    window.removeEventListener('resize', checkMobileDevice)
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