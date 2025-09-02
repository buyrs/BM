<template>
    <div 
        :class="[
            'calendar-navigation',
            mobile ? 'mobile-navigation' : ''
        ]"
        @keydown="handleKeyboardNavigation"
        tabindex="0"
        ref="navigationContainer"
    >
        <div class="flex items-center justify-between mb-6">
            <!-- Date Navigation -->
            <div class="flex items-center space-x-4">
                <button
                    @click="navigatePrevious"
                    class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="loading || isAtMinBoundary"
                    :title="getPreviousButtonTitle()"
                    :aria-label="getPreviousButtonTitle()"
                >
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <div class="flex items-center space-x-2">
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ formattedCurrentPeriod }}
                    </h2>
                    <button
                        @click="goToToday"
                        class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isCurrentPeriod || loading"
                        title="Go to current date"
                        aria-label="Go to current date"
                    >
                        Today
                    </button>
                </div>

                <button
                    @click="navigateNext"
                    class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="loading || isAtMaxBoundary"
                    :title="getNextButtonTitle()"
                    :aria-label="getNextButtonTitle()"
                >
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- View Mode Selector -->
            <div v-if="!mobile" class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">View:</span>
                <div class="view-mode-selector flex rounded-md shadow-sm" role="group" aria-label="Calendar view modes">
                    <button
                        v-for="mode in viewModes"
                        :key="mode.value"
                        @click="changeViewMode(mode.value)"
                        :class="[
                            'px-3 py-2 text-sm font-medium border focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed touch-feedback',
                            viewMode === mode.value
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            mode.value === 'month' ? 'rounded-l-md' : '',
                            mode.value === 'day' ? 'rounded-r-md -ml-px' : '',
                            mode.value === 'week' ? '-ml-px' : ''
                        ]"
                        :disabled="loading"
                        :aria-pressed="viewMode === mode.value"
                        :title="`Switch to ${mode.label.toLowerCase()} view`"
                        :aria-label="`Switch to ${mode.label.toLowerCase()} view`"
                    >
                        {{ mode.label }}
                    </button>
                </div>
            </div>
            
            <!-- Mobile View Mode Selector -->
            <div v-else class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">View Mode</label>
                <div class="view-mode-selector flex space-x-1">
                    <button
                        v-for="mode in viewModes"
                        :key="mode.value"
                        @click="changeViewMode(mode.value)"
                        :class="[
                            'flex-1 px-3 py-2 text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 touch-feedback',
                            viewMode === mode.value
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                        :disabled="loading"
                        :aria-pressed="viewMode === mode.value"
                        :aria-label="`Switch to ${mode.label.toLowerCase()} view`"
                    >
                        {{ mode.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Quick Date Picker -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-4">
                <!-- Month/Year Selector -->
                <div class="flex items-center space-x-2">
                    <select
                        v-model="selectedMonth"
                        @change="handleMonthChange"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="loading"
                        aria-label="Select month"
                    >
                        <option
                            v-for="(month, index) in months"
                            :key="index"
                            :value="index"
                        >
                            {{ month }}
                        </option>
                    </select>

                    <select
                        v-model="selectedYear"
                        @change="handleYearChange"
                        class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="loading"
                        aria-label="Select year"
                    >
                        <option
                            v-for="year in availableYears"
                            :key="year"
                            :value="year"
                        >
                            {{ year }}
                        </option>
                    </select>
                </div>

                <!-- Quick Navigation Buttons -->
                <div class="flex items-center space-x-2">
                    <button
                        @click="jumpToDate('start_of_year')"
                        class="px-2 py-1 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded"
                        :disabled="loading"
                        title="Go to start of year"
                        aria-label="Go to start of year"
                    >
                        Year Start
                    </button>
                    <button
                        @click="jumpToDate('end_of_year')"
                        class="px-2 py-1 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded"
                        :disabled="loading"
                        title="Go to end of year"
                        aria-label="Go to end of year"
                    >
                        Year End
                    </button>
                </div>
            </div>

            <!-- Calendar Stats and Date Range Info -->
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-600">
                    <span v-if="totalMissions > 0">
                        {{ totalMissions }} mission{{ totalMissions !== 1 ? 's' : '' }} in {{ formattedCurrentPeriod.toLowerCase() }}
                    </span>
                    <span v-else>
                        No missions in {{ formattedCurrentPeriod.toLowerCase() }}
                    </span>
                </div>
                
                <!-- Date Range Display -->
                <div class="text-xs text-gray-500" v-if="dateRange">
                    {{ dateRange.start }} - {{ dateRange.end }}
                </div>
            </div>
        </div>

        <!-- Keyboard Navigation Help -->
        <div class="text-xs text-gray-500 mb-2" v-if="showKeyboardHelp">
            <span class="font-medium">Keyboard shortcuts:</span>
            ← → Navigate periods | ↑ ↓ Change view | T Today | M Month | W Week | D Day | ? Toggle help
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
    currentDate: {
        type: Date,
        required: true
    },
    viewMode: {
        type: String,
        default: 'month',
        validator: (value) => ['month', 'week', 'day'].includes(value)
    },
    loading: {
        type: Boolean,
        default: false
    },
    totalMissions: {
        type: Number,
        default: 0
    },
    mobile: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['date-change', 'view-change'])

// Template refs
const navigationContainer = ref(null)

// View modes configuration
const viewModes = [
    { value: 'month', label: 'Month' },
    { value: 'week', label: 'Week' },
    { value: 'day', label: 'Day' }
]

// Month names
const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

// Reactive state
const selectedMonth = ref(props.currentDate.getMonth())
const selectedYear = ref(props.currentDate.getFullYear())
const showKeyboardHelp = ref(false)

// Date boundaries (configurable limits)
const minDate = new Date(2020, 0, 1) // January 1, 2020
const maxDate = new Date(2030, 11, 31) // December 31, 2030

// Computed properties
const formattedCurrentPeriod = computed(() => {
    const date = props.currentDate
    
    switch (props.viewMode) {
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

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    const years = []
    const startYear = Math.max(minDate.getFullYear(), currentYear - 5)
    const endYear = Math.min(maxDate.getFullYear(), currentYear + 5)
    
    for (let year = startYear; year <= endYear; year++) {
        years.push(year)
    }
    return years
})

const isCurrentPeriod = computed(() => {
    const today = new Date()
    const current = props.currentDate
    
    switch (props.viewMode) {
        case 'month':
            return today.getMonth() === current.getMonth() && 
                   today.getFullYear() === current.getFullYear()
        case 'week':
            const todayWeekStart = getStartOfWeek(today)
            const currentWeekStart = getStartOfWeek(current)
            return todayWeekStart.getTime() === currentWeekStart.getTime()
        case 'day':
            return today.toDateString() === current.toDateString()
        default:
            return false
    }
})

const isAtMinBoundary = computed(() => {
    const current = props.currentDate
    
    switch (props.viewMode) {
        case 'month':
            return current.getFullYear() <= minDate.getFullYear() && 
                   current.getMonth() <= minDate.getMonth()
        case 'week':
            const weekStart = getStartOfWeek(current)
            return weekStart <= minDate
        case 'day':
            return current <= minDate
        default:
            return false
    }
})

const isAtMaxBoundary = computed(() => {
    const current = props.currentDate
    
    switch (props.viewMode) {
        case 'month':
            return current.getFullYear() >= maxDate.getFullYear() && 
                   current.getMonth() >= maxDate.getMonth()
        case 'week':
            const weekEnd = getEndOfWeek(current)
            return weekEnd >= maxDate
        case 'day':
            return current >= maxDate
        default:
            return false
    }
})

const dateRange = computed(() => {
    const current = props.currentDate
    
    switch (props.viewMode) {
        case 'month':
            const startOfMonth = new Date(current.getFullYear(), current.getMonth(), 1)
            const endOfMonth = new Date(current.getFullYear(), current.getMonth() + 1, 0)
            return {
                start: startOfMonth.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                end: endOfMonth.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
            }
        case 'week':
            const startOfWeek = getStartOfWeek(current)
            const endOfWeek = getEndOfWeek(current)
            return {
                start: startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                end: endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
            }
        case 'day':
            return {
                start: current.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                end: current.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
            }
        default:
            return null
    }
})

// Methods
const navigatePrevious = () => {
    if (isAtMinBoundary.value) return
    
    const newDate = new Date(props.currentDate)
    
    switch (props.viewMode) {
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
    
    // Ensure we don't go below minimum date
    if (newDate < minDate) {
        newDate.setTime(minDate.getTime())
    }
    
    emit('date-change', newDate)
}

const navigateNext = () => {
    if (isAtMaxBoundary.value) return
    
    const newDate = new Date(props.currentDate)
    
    switch (props.viewMode) {
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
    
    // Ensure we don't go above maximum date
    if (newDate > maxDate) {
        newDate.setTime(maxDate.getTime())
    }
    
    emit('date-change', newDate)
}

const goToToday = () => {
    const today = new Date()
    // Ensure today is within boundaries
    if (today < minDate) {
        emit('date-change', new Date(minDate))
    } else if (today > maxDate) {
        emit('date-change', new Date(maxDate))
    } else {
        emit('date-change', today)
    }
}

const changeViewMode = (mode) => {
    emit('view-change', mode)
}

const handleMonthChange = () => {
    const newDate = new Date(props.currentDate)
    newDate.setMonth(selectedMonth.value)
    
    // Ensure the new date is within boundaries
    if (newDate < minDate) {
        newDate.setTime(minDate.getTime())
    } else if (newDate > maxDate) {
        newDate.setTime(maxDate.getTime())
    }
    
    emit('date-change', newDate)
}

const handleYearChange = () => {
    const newDate = new Date(props.currentDate)
    newDate.setFullYear(selectedYear.value)
    
    // Ensure the new date is within boundaries
    if (newDate < minDate) {
        newDate.setTime(minDate.getTime())
    } else if (newDate > maxDate) {
        newDate.setTime(maxDate.getTime())
    }
    
    emit('date-change', newDate)
}

const jumpToDate = (target) => {
    const current = props.currentDate
    let newDate
    
    switch (target) {
        case 'start_of_year':
            newDate = new Date(current.getFullYear(), 0, 1)
            break
        case 'end_of_year':
            newDate = new Date(current.getFullYear(), 11, 31)
            break
        default:
            return
    }
    
    // Ensure the new date is within boundaries
    if (newDate < minDate) {
        newDate.setTime(minDate.getTime())
    } else if (newDate > maxDate) {
        newDate.setTime(maxDate.getTime())
    }
    
    emit('date-change', newDate)
}

const getPreviousButtonTitle = () => {
    switch (props.viewMode) {
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

const getNextButtonTitle = () => {
    switch (props.viewMode) {
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

// Keyboard navigation
const handleKeyboardNavigation = (event) => {
    // Don't handle keyboard events if user is typing in an input
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT') {
        return
    }
    
    switch (event.key) {
        case 'ArrowLeft':
            event.preventDefault()
            navigatePrevious()
            break
        case 'ArrowRight':
            event.preventDefault()
            navigateNext()
            break
        case 'ArrowUp':
            event.preventDefault()
            // Cycle through view modes (day -> week -> month)
            const currentIndex = viewModes.findIndex(mode => mode.value === props.viewMode)
            const nextIndex = (currentIndex + 1) % viewModes.length
            changeViewMode(viewModes[nextIndex].value)
            break
        case 'ArrowDown':
            event.preventDefault()
            // Cycle through view modes (month -> week -> day)
            const currentIndexDown = viewModes.findIndex(mode => mode.value === props.viewMode)
            const prevIndex = currentIndexDown === 0 ? viewModes.length - 1 : currentIndexDown - 1
            changeViewMode(viewModes[prevIndex].value)
            break
        case 't':
        case 'T':
            event.preventDefault()
            goToToday()
            break
        case 'm':
        case 'M':
            event.preventDefault()
            changeViewMode('month')
            break
        case 'w':
        case 'W':
            event.preventDefault()
            changeViewMode('week')
            break
        case 'd':
        case 'D':
            event.preventDefault()
            changeViewMode('day')
            break
        case '?':
            event.preventDefault()
            showKeyboardHelp.value = !showKeyboardHelp.value
            break
    }
}

// Utility functions
const getStartOfWeek = (date) => {
    const start = new Date(date)
    const day = start.getDay()
    const diff = start.getDate() - day + (day === 0 ? -6 : 1) // Adjust when day is Sunday
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

// Lifecycle hooks
onMounted(() => {
    // Focus the navigation container for keyboard events
    if (navigationContainer.value) {
        navigationContainer.value.focus()
    }
})

// Watch for prop changes
watch(() => props.currentDate, (newDate) => {
    selectedMonth.value = newDate.getMonth()
    selectedYear.value = newDate.getFullYear()
})
</script>

<style scoped>
.calendar-navigation {
    @apply bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6;
}

@media (max-width: 768px) {
    .calendar-navigation .flex {
        @apply flex-col space-y-4;
    }
    
    .calendar-navigation .justify-between {
        @apply justify-start;
    }
    
    .calendar-navigation .space-x-4 {
        @apply space-x-2;
    }
}
</style>