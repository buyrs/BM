<template>
    <div class="calendar-navigation">
        <div class="flex items-center justify-between mb-6">
            <!-- Date Navigation -->
            <div class="flex items-center space-x-4">
                <button
                    @click="navigatePrevious"
                    class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="loading"
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
                        class="px-3 py-1 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md"
                        :disabled="isCurrentPeriod"
                    >
                        Today
                    </button>
                </div>

                <button
                    @click="navigateNext"
                    class="p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="loading"
                >
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- View Mode Selector -->
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">View:</span>
                <div class="flex rounded-md shadow-sm">
                    <button
                        v-for="mode in viewModes"
                        :key="mode.value"
                        @click="changeViewMode(mode.value)"
                        :class="[
                            'px-3 py-2 text-sm font-medium border focus:outline-none focus:ring-2 focus:ring-blue-500',
                            viewMode === mode.value
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                            mode.value === 'month' ? 'rounded-l-md' : '',
                            mode.value === 'day' ? 'rounded-r-md -ml-px' : '',
                            mode.value === 'week' ? '-ml-px' : ''
                        ]"
                        :disabled="loading"
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
                        class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="loading"
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
                        class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :disabled="loading"
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
            </div>

            <!-- Calendar Stats -->
            <div class="text-sm text-gray-600">
                <span v-if="totalMissions > 0">
                    {{ totalMissions }} mission{{ totalMissions !== 1 ? 's' : '' }} in {{ formattedCurrentPeriod.toLowerCase() }}
                </span>
                <span v-else>
                    No missions in {{ formattedCurrentPeriod.toLowerCase() }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

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
    }
})

// Emits
const emit = defineEmits(['date-change', 'view-change'])

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
    for (let year = currentYear - 2; year <= currentYear + 2; year++) {
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

// Methods
const navigatePrevious = () => {
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
    
    emit('date-change', newDate)
}

const navigateNext = () => {
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
    
    emit('date-change', newDate)
}

const goToToday = () => {
    emit('date-change', new Date())
}

const changeViewMode = (mode) => {
    emit('view-change', mode)
}

const handleMonthChange = () => {
    const newDate = new Date(props.currentDate)
    newDate.setMonth(selectedMonth.value)
    emit('date-change', newDate)
}

const handleYearChange = () => {
    const newDate = new Date(props.currentDate)
    newDate.setFullYear(selectedYear.value)
    emit('date-change', newDate)
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