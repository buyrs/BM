<template>
    <div class="calendar-grid">
        <!-- Month View -->
        <div v-if="viewMode === 'month'" class="month-view">
            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-t-lg overflow-hidden">
                <div
                    v-for="day in daysOfWeek"
                    :key="day"
                    class="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700"
                >
                    {{ day }}
                </div>
            </div>

            <!-- Calendar Days -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-b-lg overflow-hidden">
                <div
                    v-for="(day, index) in calendarDays"
                    :key="`${day.date}-${index}`"
                    :class="[
                        'bg-white min-h-32 p-2 cursor-pointer hover:bg-gray-50 transition-colors',
                        day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                        day.isToday ? 'bg-blue-50 border-2 border-blue-200' : '',
                        day.isSelected ? 'bg-blue-100' : ''
                    ]"
                    @click="handleDateClick(day.date)"
                >
                    <!-- Date Number -->
                    <div class="flex justify-between items-start mb-2">
                        <span
                            :class="[
                                'text-sm font-medium',
                                day.isToday ? 'bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center' : ''
                            ]"
                        >
                            {{ day.date.getDate() }}
                        </span>
                        
                        <!-- Mission Count Badge -->
                        <span
                            v-if="day.missions.length > 0"
                            class="bg-blue-600 text-white text-xs rounded-full px-2 py-1 min-w-6 text-center"
                        >
                            {{ day.missions.length }}
                        </span>
                    </div>

                    <!-- Mission Events -->
                    <div class="space-y-1">
                        <MissionEvent
                            v-for="(mission, missionIndex) in day.missions.slice(0, 3)"
                            :key="mission.id"
                            :mission="mission"
                            :compact="true"
                            @click="handleMissionClick(mission, $event)"
                        />
                        
                        <!-- More missions indicator -->
                        <div
                            v-if="day.missions.length > 3"
                            class="text-xs text-gray-500 cursor-pointer hover:text-gray-700"
                            @click="handleDateClick(day.date)"
                        >
                            +{{ day.missions.length - 3 }} more
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Week View -->
        <div v-else-if="viewMode === 'week'" class="week-view">
            <div class="grid grid-cols-8 gap-px bg-gray-200 rounded-lg overflow-hidden">
                <!-- Time Column Header -->
                <div class="bg-gray-50 p-3 text-center text-sm font-medium text-gray-700">
                    Time
                </div>
                
                <!-- Day Headers -->
                <div
                    v-for="day in weekDays"
                    :key="day.date.toISOString()"
                    :class="[
                        'bg-gray-50 p-3 text-center text-sm font-medium',
                        day.isToday ? 'bg-blue-100 text-blue-800' : 'text-gray-700'
                    ]"
                >
                    <div>{{ day.dayName }}</div>
                    <div class="text-lg font-bold">{{ day.date.getDate() }}</div>
                </div>
            </div>

            <!-- Time Slots -->
            <div class="grid grid-cols-8 gap-px bg-gray-200">
                <div
                    v-for="hour in timeSlots"
                    :key="hour"
                    class="contents"
                >
                    <!-- Time Label -->
                    <div class="bg-white p-2 text-xs text-gray-500 text-center border-r">
                        {{ formatHour(hour) }}
                    </div>
                    
                    <!-- Day Columns -->
                    <div
                        v-for="day in weekDays"
                        :key="`${day.date.toISOString()}-${hour}`"
                        class="bg-white min-h-16 p-1 border-r border-b hover:bg-gray-50 cursor-pointer"
                        @click="handleTimeSlotClick(day.date, hour)"
                    >
                        <!-- Missions for this time slot -->
                        <MissionEvent
                            v-for="mission in getMissionsForTimeSlot(day.missions, hour)"
                            :key="mission.id"
                            :mission="mission"
                            :compact="false"
                            @click="handleMissionClick(mission, $event)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Day View -->
        <div v-else-if="viewMode === 'day'" class="day-view">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Day Header -->
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ formatDayHeader(currentDate) }}
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ dayMissions.length }} mission{{ dayMissions.length !== 1 ? 's' : '' }} scheduled
                    </p>
                </div>

                <!-- Time Slots -->
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="hour in timeSlots"
                        :key="hour"
                        class="flex hover:bg-gray-50"
                    >
                        <!-- Time Label -->
                        <div class="w-20 p-4 text-sm text-gray-500 text-right">
                            {{ formatHour(hour) }}
                        </div>
                        
                        <!-- Mission Slot -->
                        <div
                            class="flex-1 p-4 cursor-pointer"
                            @click="handleTimeSlotClick(currentDate, hour)"
                        >
                            <div class="space-y-2">
                                <MissionEvent
                                    v-for="mission in getMissionsForTimeSlot(dayMissions, hour)"
                                    :key="mission.id"
                                    :mission="mission"
                                    :compact="false"
                                    @click="handleMissionClick(mission, $event)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="!loading && missions.length === 0"
            class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200"
        >
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No missions found</h3>
            <p class="mt-1 text-sm text-gray-500">
                No missions scheduled for this {{ viewMode }}.
            </p>
            <div class="mt-6">
                <button
                    @click="handleDateClick(currentDate)"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Create Mission
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import MissionEvent from './MissionEvent.vue'

// Props
const props = defineProps({
    missions: {
        type: Array,
        default: () => []
    },
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
    }
})

// Emits
const emit = defineEmits(['mission-click', 'date-click'])

// Constants
const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const timeSlots = Array.from({ length: 24 }, (_, i) => i) // 0-23 hours

// Computed properties
const calendarDays = computed(() => {
    const year = props.currentDate.getFullYear()
    const month = props.currentDate.getMonth()
    
    // Get first day of month and calculate start of calendar
    const firstDay = new Date(year, month, 1)
    const startDate = new Date(firstDay)
    startDate.setDate(startDate.getDate() - firstDay.getDay())
    
    // Generate 42 days (6 weeks)
    const days = []
    const today = new Date()
    
    for (let i = 0; i < 42; i++) {
        const date = new Date(startDate)
        date.setDate(startDate.getDate() + i)
        
        const dayMissions = getMissionsForDate(date)
        
        days.push({
            date,
            isCurrentMonth: date.getMonth() === month,
            isToday: date.toDateString() === today.toDateString(),
            isSelected: false,
            missions: dayMissions
        })
    }
    
    return days
})

const weekDays = computed(() => {
    const startOfWeek = getStartOfWeek(props.currentDate)
    const days = []
    const today = new Date()
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(startOfWeek)
        date.setDate(startOfWeek.getDate() + i)
        
        days.push({
            date,
            dayName: daysOfWeek[date.getDay()],
            isToday: date.toDateString() === today.toDateString(),
            missions: getMissionsForDate(date)
        })
    }
    
    return days
})

const dayMissions = computed(() => {
    return getMissionsForDate(props.currentDate)
})

// Methods
const getMissionsForDate = (date) => {
    return props.missions.filter(mission => {
        const missionDate = new Date(mission.scheduled_at)
        return missionDate.toDateString() === date.toDateString()
    })
}

const getMissionsForTimeSlot = (missions, hour) => {
    return missions.filter(mission => {
        if (!mission.scheduled_time) return hour === 9 // Default to 9 AM if no time specified
        
        const [missionHour] = mission.scheduled_time.split(':').map(Number)
        return missionHour === hour
    })
}

const getStartOfWeek = (date) => {
    const start = new Date(date)
    const day = start.getDay()
    const diff = start.getDate() - day
    start.setDate(diff)
    return start
}

const formatHour = (hour) => {
    const period = hour >= 12 ? 'PM' : 'AM'
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
    return `${displayHour}:00 ${period}`
}

const formatDayHeader = (date) => {
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const handleDateClick = (date) => {
    emit('date-click', date)
}

const handleMissionClick = (mission, event) => {
    event.stopPropagation()
    emit('mission-click', mission)
}

const handleTimeSlotClick = (date, hour) => {
    const dateTime = new Date(date)
    dateTime.setHours(hour, 0, 0, 0)
    emit('date-click', dateTime)
}
</script>

<style scoped>
.calendar-grid {
    @apply bg-white rounded-lg shadow-sm border border-gray-200;
}

.month-view .grid {
    @apply min-h-96;
}

.week-view,
.day-view {
    @apply overflow-x-auto;
}

@media (max-width: 768px) {
    .month-view .min-h-32 {
        @apply min-h-24;
    }
    
    .month-view .p-2 {
        @apply p-1;
    }
    
    .week-view {
        @apply text-sm;
    }
    
    .day-view .w-20 {
        @apply w-16;
    }
}
</style>