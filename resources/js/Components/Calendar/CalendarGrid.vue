<template>
    <div 
        class="calendar-grid"
        @keydown="handleKeyboardNavigation"
        tabindex="0"
        ref="calendarGrid"
    >
        <!-- Mobile List View (for small screens) -->
        <div v-if="mobile && viewMode === 'month'" class="mobile-list-view md:hidden">
            <div
                v-for="day in calendarDays.filter(d => d.isCurrentMonth && d.missions.length > 0)"
                :key="day.date.toISOString()"
                class="mobile-list-item"
                @click="handleDateClick(day.date)"
            >
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ day.date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) }}
                    </h3>
                    <span class="bg-primary text-white text-xs rounded-full px-2 py-1">
                        {{ day.missions.length }} mission{{ day.missions.length !== 1 ? 's' : '' }}
                    </span>
                </div>
                
                <div class="space-y-2">
                    <MissionEvent
                        v-for="mission in day.missions"
                        :key="mission.id"
                        :mission="mission"
                        :compact="false"
                        :selection-mode="selectionMode"
                        :selected="selectedMissions.some(m => m.id === mission.id)"
                        @click="handleMissionClick(mission, $event)"
                    />
                </div>
            </div>
            
            <!-- Loading state for mobile list -->
            <div v-if="loading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="mobile-list-item animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="bg-gray-300 h-6 w-32 rounded"></div>
                        <div class="bg-gray-300 h-5 w-16 rounded-full"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="bg-gray-300 h-12 w-full rounded"></div>
                        <div class="bg-gray-300 h-12 w-3/4 rounded"></div>
                    </div>
                </div>
            </div>
            
            <!-- Empty state for mobile list -->
            <div v-else-if="calendarDays.filter(d => d.isCurrentMonth && d.missions.length > 0).length === 0" 
                 class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No missions this month</h3>
                <p class="mt-1 text-sm text-gray-500">Tap the + button to create a new mission.</p>
            </div>
        </div>

        <!-- Desktop Month View -->
        <div v-else-if="viewMode === 'month'" class="month-view hidden md:block">
            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-t-lg overflow-hidden">
                <div
                    v-for="day in daysOfWeek"
                    :key="day"
                    class="days-header bg-gray-50 p-3 text-center text-sm font-medium text-gray-700"
                    role="columnheader"
                    :aria-label="`${day} column`"
                >
                    <span class="sr-only">{{ day === 'Sun' ? 'Sunday' : day === 'Mon' ? 'Monday' : day === 'Tue' ? 'Tuesday' : day === 'Wed' ? 'Wednesday' : day === 'Thu' ? 'Thursday' : day === 'Fri' ? 'Friday' : 'Saturday' }}</span>
                    <span aria-hidden="true">{{ day }}</span>
                </div>
            </div>

            <!-- Calendar Days -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-b-lg overflow-hidden" role="grid">
                <div
                    v-for="(day, index) in calendarDays"
                    :key="`${day.date}-${index}`"
                    :class="[
                        'bg-white min-h-32 p-2 cursor-pointer hover:bg-gray-50 transition-colors touch-feedback',
                        day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400',
                        day.isToday ? 'bg-secondary border-2 border-primary' : '',
                        day.isSelected ? 'keyboard-selected' : ''
                    ]"
                    role="gridcell"
                    :tabindex="day.isCurrentMonth ? 0 : -1"
                    :aria-label="getDateAriaLabel(day)"
                    :aria-selected="day.isSelected"
                    @click="handleDateClick(day.date)"
                    @keydown="handleDateKeydown($event, day.date)"
                >
                    <!-- Date Number -->
                    <div class="flex justify-between items-start mb-2">
                        <span
                            :class="[
                                'text-sm font-medium',
                                day.isToday ? 'bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center' : ''
                            ]"
                        >
                            {{ day.date.getDate() }}
                        </span>
                        
                        <!-- Mission Count Badge -->
                        <span
                            v-if="day.missions.length > 0"
                            class="bg-primary text-white text-xs rounded-full px-2 py-1 min-w-6 text-center"
                        >
                            {{ day.missions.length }}
                        </span>
                    </div>

                    <!-- Mission Events with Drag and Drop -->
                    <div 
                        class="space-y-1 min-h-[60px]"
                        @drop="handleDrop($event, day.date)"
                        @dragover="handleDragOver"
                        @dragenter="handleDragEnter"
                        @dragleave="handleDragLeave"
                        :class="{ 'drag-over': isDragOver && dragOverDate === day.date.toDateString() }"
                    >
                        <!-- Priority missions (first 2) -->
                        <MissionEvent
                            v-for="(mission, missionIndex) in day.missions.slice(0, 2)"
                            :key="mission.id"
                            :mission="mission"
                            :compact="true"
                            :selection-mode="selectionMode"
                            :selected="selectedMissions.some(m => m.id === mission.id)"
                            :draggable="canDragMission(mission)"
                            @click="handleMissionClick(mission, $event)"
                            @dragstart="handleDragStart($event, mission)"
                            @dragend="handleDragEnd"
                        />
                        
                        <!-- Stacked missions indicator -->
                        <div
                            v-if="day.missions.length > 2"
                            class="relative"
                        >
                            <!-- Third mission (partially visible) -->
                            <div
                                v-if="day.missions.length > 2"
                                class="relative z-10 transform translate-y-0.5"
                            >
                                <MissionEvent
                                    :key="day.missions[2].id"
                                    :mission="day.missions[2]"
                                    :compact="true"
                                    :selection-mode="selectionMode"
                                    :selected="selectedMissions.some(m => m.id === day.missions[2].id)"
                                    :draggable="canDragMission(day.missions[2])"
                                    @click="handleMissionClick(day.missions[2], $event)"
                                    @dragstart="handleDragStart($event, day.missions[2])"
                                    @dragend="handleDragEnd"
                                />
                            </div>
                            
                            <!-- Stack indicator for additional missions -->
                            <div
                                v-if="day.missions.length > 3"
                                class="absolute inset-0 z-0 bg-gray-200 rounded transform translate-y-1 opacity-50"
                            ></div>
                            <div
                                v-if="day.missions.length > 4"
                                class="absolute inset-0 z-0 bg-gray-300 rounded transform translate-y-2 opacity-30"
                            ></div>
                            
                            <!-- More missions indicator -->
                            <div
                                v-if="day.missions.length > 3"
                                class="absolute bottom-0 right-0 z-20 bg-primary text-white text-xs rounded-full px-2 py-1 cursor-pointer hover:bg-accent transform translate-y-1"
                                @click="handleDateClick(day.date)"
                                :title="`${day.missions.length - 3} more missions`"
                            >
                                +{{ day.missions.length - 3 }}
                            </div>
                        </div>
                        
                        <!-- Drop zone indicator -->
                        <div
                            v-if="isDragOver && dragOverDate === day.date.toDateString() && day.missions.length === 0"
                            class="border-2 border-dashed border-blue-400 bg-blue-50 rounded p-2 text-center text-xs text-blue-600"
                        >
                            Drop mission here
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
                        day.isToday ? 'bg-secondary text-primary' : 'text-text-secondary'
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
                    
                    <!-- Day Columns with Drop Zones -->
                    <div
                        v-for="day in weekDays"
                        :key="`${day.date.toISOString()}-${hour}`"
                        class="bg-white min-h-16 p-1 border-r border-b hover:bg-gray-50 cursor-pointer"
                        @click="handleTimeSlotClick(day.date, hour)"
                        @drop="handleTimeSlotDrop($event, day.date, hour)"
                        @dragover="handleDragOver"
                        @dragenter="handleDragEnter"
                        @dragleave="handleDragLeave"
                        :class="{ 'drag-over': isDragOver && dragOverTimeSlot === `${day.date.toDateString()}-${hour}` }"
                    >
                        <!-- Missions for this time slot -->
                        <MissionEvent
                            v-for="mission in getMissionsForTimeSlot(day.missions, hour)"
                            :key="mission.id"
                            :mission="mission"
                            :compact="false"
                            :selection-mode="selectionMode"
                            :selected="selectedMissions.some(m => m.id === mission.id)"
                            :draggable="canDragMission(mission)"
                            @click="handleMissionClick(mission, $event)"
                            @dragstart="handleDragStart($event, mission)"
                            @dragend="handleDragEnd"
                        />
                        
                        <!-- Drop zone indicator for empty time slots -->
                        <div
                            v-if="isDragOver && dragOverTimeSlot === `${day.date.toDateString()}-${hour}` && getMissionsForTimeSlot(day.missions, hour).length === 0"
                            class="border-2 border-dashed border-blue-400 bg-blue-50 rounded p-1 text-center text-xs text-blue-600"
                        >
                            Drop here
                        </div>
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
                        
                        <!-- Mission Slot with Drop Zone -->
                        <div
                            class="flex-1 p-4 cursor-pointer min-h-[60px]"
                            @click="handleTimeSlotClick(currentDate, hour)"
                            @drop="handleTimeSlotDrop($event, currentDate, hour)"
                            @dragover="handleDragOver"
                            @dragenter="handleDragEnter"
                            @dragleave="handleDragLeave"
                            :class="{ 'drag-over': isDragOver && dragOverTimeSlot === `${currentDate.toDateString()}-${hour}` }"
                        >
                            <div class="space-y-2">
                                <MissionEvent
                                    v-for="mission in getMissionsForTimeSlot(dayMissions, hour)"
                                    :key="mission.id"
                                    :mission="mission"
                                    :compact="false"
                                    :selection-mode="selectionMode"
                                    :selected="selectedMissions.some(m => m.id === mission.id)"
                                    :draggable="canDragMission(mission)"
                                    @click="handleMissionClick(mission, $event)"
                                    @dragstart="handleDragStart($event, mission)"
                                    @dragend="handleDragEnd"
                                />
                                
                                <!-- Drop zone indicator for empty time slots -->
                                <div
                                    v-if="isDragOver && dragOverTimeSlot === `${currentDate.toDateString()}-${hour}` && getMissionsForTimeSlot(dayMissions, hour).length === 0"
                                    class="border-2 border-dashed border-blue-400 bg-blue-50 rounded p-2 text-center text-xs text-blue-600"
                                >
                                    Drop mission at {{ formatHour(hour) }}
                                </div>
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
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
                >
                    Create Mission
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
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
    },
    selectionMode: {
        type: Boolean,
        default: false
    },
    selectedMissions: {
        type: Array,
        default: () => []
    },
    mobile: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['mission-click', 'date-click', 'mission-select', 'bulk-select', 'mission-reschedule', 'conflict-detected'])

// Template refs
const calendarGrid = ref(null)

// Selected date for keyboard navigation
const selectedDateIndex = ref(0)

// Drag and drop state
const isDragOver = ref(false)
const dragOverDate = ref(null)
const dragOverTimeSlot = ref(null)
const draggedMission = ref(null)
const dragStartPosition = ref(null)

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
    if (props.selectionMode) {
        emit('mission-select', mission)
    } else {
        emit('mission-click', mission)
    }
}

const handleTimeSlotClick = (date, hour) => {
    const dateTime = new Date(date)
    dateTime.setHours(hour, 0, 0, 0)
    emit('date-click', dateTime)
}

const handleDateKeydown = (event, date) => {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        handleDateClick(date)
    }
}

const getDateAriaLabel = (day) => {
    const dateStr = day.date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        month: 'long', 
        day: 'numeric',
        year: 'numeric'
    })
    
    let label = dateStr
    
    if (day.isToday) {
        label += ', today'
    }
    
    if (!day.isCurrentMonth) {
        label += ', outside current month'
    }
    
    if (day.missions.length > 0) {
        label += `, ${day.missions.length} mission${day.missions.length !== 1 ? 's' : ''}`
    } else {
        label += ', no missions'
    }
    
    return label
}

// Keyboard navigation for calendar grid
const handleKeyboardNavigation = (event) => {
    // Don't handle keyboard events if user is typing in an input
    if (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT') {
        return
    }
    
    if (props.viewMode === 'month') {
        const totalDays = calendarDays.value.length
        
        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault()
                selectedDateIndex.value = Math.max(0, selectedDateIndex.value - 1)
                highlightSelectedDate()
                break
            case 'ArrowRight':
                event.preventDefault()
                selectedDateIndex.value = Math.min(totalDays - 1, selectedDateIndex.value + 1)
                highlightSelectedDate()
                break
            case 'ArrowUp':
                event.preventDefault()
                selectedDateIndex.value = Math.max(0, selectedDateIndex.value - 7)
                highlightSelectedDate()
                break
            case 'ArrowDown':
                event.preventDefault()
                selectedDateIndex.value = Math.min(totalDays - 1, selectedDateIndex.value + 7)
                highlightSelectedDate()
                break
            case 'Enter':
            case ' ':
                event.preventDefault()
                if (calendarDays.value[selectedDateIndex.value]) {
                    handleDateClick(calendarDays.value[selectedDateIndex.value].date)
                }
                break
            case 'Home':
                event.preventDefault()
                selectedDateIndex.value = 0
                highlightSelectedDate()
                break
            case 'End':
                event.preventDefault()
                selectedDateIndex.value = totalDays - 1
                highlightSelectedDate()
                break
        }
    }
}

const highlightSelectedDate = () => {
    // Update the selected state in calendar days
    calendarDays.value.forEach((day, index) => {
        day.isSelected = index === selectedDateIndex.value
    })
}

// Drag and Drop Methods
const canDragMission = (mission) => {
    // Only allow dragging if mission is not completed or cancelled
    return mission.status !== 'completed' && mission.status !== 'cancelled'
}

const handleDragStart = (event, mission) => {
    if (!canDragMission(mission)) {
        event.preventDefault()
        return
    }
    
    draggedMission.value = mission
    dragStartPosition.value = {
        date: mission.scheduled_at,
        time: mission.scheduled_time
    }
    
    // Set drag data
    event.dataTransfer.setData('text/plain', JSON.stringify({
        missionId: mission.id,
        type: 'mission'
    }))
    
    event.dataTransfer.effectAllowed = 'move'
    
    // Add visual feedback
    event.target.style.opacity = '0.5'
}

const handleDragEnd = (event) => {
    // Reset visual feedback
    event.target.style.opacity = '1'
    
    // Reset drag state
    isDragOver.value = false
    dragOverDate.value = null
    dragOverTimeSlot.value = null
    draggedMission.value = null
    dragStartPosition.value = null
}

const handleDragOver = (event) => {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
}

const handleDragEnter = (event) => {
    event.preventDefault()
    isDragOver.value = true
}

const handleDragLeave = (event) => {
    // Only hide drag indicator if we're actually leaving the drop zone
    if (!event.currentTarget.contains(event.relatedTarget)) {
        isDragOver.value = false
        dragOverDate.value = null
        dragOverTimeSlot.value = null
    }
}

const handleDrop = (event, date) => {
    event.preventDefault()
    
    try {
        const dragData = JSON.parse(event.dataTransfer.getData('text/plain'))
        
        if (dragData.type === 'mission' && draggedMission.value) {
            const mission = draggedMission.value
            const newDate = date.toISOString().split('T')[0]
            
            // Check if the date actually changed
            if (mission.scheduled_at === newDate) {
                return
            }
            
            // Emit reschedule event
            emit('mission-reschedule', {
                mission,
                newDate,
                oldDate: mission.scheduled_at,
                newTime: mission.scheduled_time // Keep same time
            })
        }
    } catch (error) {
        console.error('Error handling drop:', error)
    } finally {
        // Reset drag state
        isDragOver.value = false
        dragOverDate.value = null
        dragOverTimeSlot.value = null
    }
}

const handleTimeSlotDrop = (event, date, hour) => {
    event.preventDefault()
    event.stopPropagation()
    
    try {
        const dragData = JSON.parse(event.dataTransfer.getData('text/plain'))
        
        if (dragData.type === 'mission' && draggedMission.value) {
            const mission = draggedMission.value
            const newDate = date.toISOString().split('T')[0]
            const newTime = `${hour.toString().padStart(2, '0')}:00`
            
            // Check if anything actually changed
            if (mission.scheduled_at === newDate && mission.scheduled_time === newTime) {
                return
            }
            
            // Check for conflicts before rescheduling
            checkTimeSlotConflicts(date, hour, mission).then(conflicts => {
                if (conflicts.length > 0) {
                    emit('conflict-detected', {
                        mission,
                        newDate,
                        newTime,
                        conflicts
                    })
                } else {
                    // Emit reschedule event
                    emit('mission-reschedule', {
                        mission,
                        newDate,
                        newTime,
                        oldDate: mission.scheduled_at,
                        oldTime: mission.scheduled_time
                    })
                }
            })
        }
    } catch (error) {
        console.error('Error handling time slot drop:', error)
    } finally {
        // Reset drag state
        isDragOver.value = false
        dragOverDate.value = null
        dragOverTimeSlot.value = null
    }
}

const checkTimeSlotConflicts = async (date, hour, mission) => {
    const conflicts = []
    const newTime = `${hour.toString().padStart(2, '0')}:00`
    
    // Check for other missions at the same time
    const conflictingMissions = props.missions.filter(m => {
        if (m.id === mission.id) return false
        if (!m.scheduled_at || !m.scheduled_time) return false
        
        const missionDate = new Date(m.scheduled_at).toDateString()
        const targetDate = date.toDateString()
        const missionTime = m.scheduled_time.substring(0, 5)
        const targetTime = newTime.substring(0, 5)
        
        return missionDate === targetDate && missionTime === targetTime && m.agent?.id === mission.agent?.id
    })
    
    if (conflictingMissions.length > 0) {
        conflicts.push(`Checker already has ${conflictingMissions.length} mission(s) at this time`)
    }
    
    // Check business hours
    if (hour < 9 || hour >= 19) {
        conflicts.push('Outside business hours (9 AM - 7 PM)')
    }
    
    // Check if it's a weekend
    if (date.getDay() === 0 || date.getDay() === 6) {
        conflicts.push('Weekend scheduling')
    }
    
    return conflicts
}

// Lifecycle hooks
onMounted(() => {
    // Set initial selected date to today or current date
    const today = new Date()
    const todayIndex = calendarDays.value.findIndex(day => 
        day.date.toDateString() === today.toDateString()
    )
    
    if (todayIndex !== -1) {
        selectedDateIndex.value = todayIndex
    } else {
        // Find the current month's first day
        const currentMonthIndex = calendarDays.value.findIndex(day => day.isCurrentMonth)
        if (currentMonthIndex !== -1) {
            selectedDateIndex.value = currentMonthIndex
        }
    }
    
    highlightSelectedDate()
})
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

/* Drag and Drop Styles */
.drag-over {
    @apply bg-blue-50 border-2 border-dashed border-blue-400;
}

.mission-event[draggable="true"] {
    cursor: move;
}

.mission-event[draggable="true"]:hover {
    @apply shadow-md transform scale-105;
    transition: all 0.2s ease;
}

.mission-event.dragging {
    @apply opacity-50;
}

/* Drop zone indicators */
.drop-zone-indicator {
    @apply border-2 border-dashed border-blue-400 bg-blue-50 rounded p-2 text-center text-xs text-blue-600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Conflict indicators */
.conflict-warning {
    @apply bg-yellow-50 border-yellow-200 text-yellow-800;
}

.conflict-error {
    @apply bg-red-50 border-red-200 text-red-800;
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
    
    /* Disable drag on mobile for better touch experience */
    .mission-event[draggable="true"] {
        cursor: pointer;
    }
    
    .mission-event[draggable="true"]:hover {
        transform: none;
        @apply shadow-sm;
    }
}

/* Touch-friendly drag indicators */
@media (hover: none) and (pointer: coarse) {
    .mission-event[draggable="true"] {
        cursor: pointer;
    }
    
    .drag-over {
        @apply bg-blue-100;
    }
}
</style>