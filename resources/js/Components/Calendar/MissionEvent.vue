<template>
    <div
        :class="[
            'mission-event cursor-pointer transition-all duration-200 hover:shadow-md',
            eventClasses,
            compact ? 'p-1 rounded text-xs' : 'p-2 rounded-md text-sm'
        ]"
        @click="handleClick"
        @mouseenter="showTooltip = true"
        @mouseleave="showTooltip = false"
    >
        <!-- Mission Content -->
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <!-- Mission Type & Time -->
                <div class="flex items-center space-x-1">
                    <span :class="typeIconClasses">
                        <svg v-if="mission.type === 'entry'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-3.707-8.293l3-3a1 1 0 011.414 1.414L9.414 9H13a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    
                    <span class="font-medium">
                        {{ missionTypeLabel }}
                    </span>
                    
                    <span v-if="mission.scheduled_time && !compact" class="text-xs opacity-75">
                        {{ formattedTime }}
                    </span>
                </div>

                <!-- Tenant Name -->
                <div v-if="mission.tenant_name" :class="compact ? 'truncate' : ''">
                    {{ mission.tenant_name }}
                </div>

                <!-- Address (only in non-compact mode) -->
                <div v-if="!compact && mission.address" class="text-xs opacity-75 truncate">
                    {{ mission.address }}
                </div>

                <!-- Checker Assignment -->
                <div v-if="mission.agent && !compact" class="text-xs opacity-75">
                    Assigned to: {{ mission.agent.name }}
                </div>
            </div>

            <!-- Status Indicator -->
            <div :class="statusIndicatorClasses"></div>
        </div>

        <!-- Conflicts Warning -->
        <div v-if="mission.conflicts && mission.conflicts.length > 0" class="mt-1">
            <div class="flex items-center text-xs text-red-600">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                Conflict
            </div>
        </div>

        <!-- Tooltip -->
        <div
            v-if="showTooltip && !compact"
            class="absolute z-50 bg-gray-900 text-white text-xs rounded-md px-2 py-1 mt-1 shadow-lg pointer-events-none"
            style="transform: translateY(100%)"
        >
            <div class="font-medium">{{ mission.tenant_name }}</div>
            <div>{{ mission.address }}</div>
            <div>{{ missionTypeLabel }} - {{ formattedStatus }}</div>
            <div v-if="mission.agent">Assigned to: {{ mission.agent.name }}</div>
            <div v-if="mission.scheduled_time">Time: {{ formattedTime }}</div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
    mission: {
        type: Object,
        required: true
    },
    compact: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['click'])

// Reactive state
const showTooltip = ref(false)

// Computed properties
const missionTypeLabel = computed(() => {
    return props.mission.type === 'entry' ? 'Entry' : 'Exit'
})

const formattedTime = computed(() => {
    if (!props.mission.scheduled_time) return ''
    
    const [hours, minutes] = props.mission.scheduled_time.split(':')
    const hour = parseInt(hours)
    const period = hour >= 12 ? 'PM' : 'AM'
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
    
    return `${displayHour}:${minutes} ${period}`
})

const formattedStatus = computed(() => {
    const statusMap = {
        'unassigned': 'Unassigned',
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    }
    
    return statusMap[props.mission.status] || props.mission.status
})

const eventClasses = computed(() => {
    const baseClasses = 'border-l-4'
    const typeClasses = {
        'entry': 'bg-blue-50 border-blue-500 text-blue-900',
        'exit': 'bg-orange-50 border-orange-500 text-orange-900'
    }
    
    const statusClasses = {
        'unassigned': 'opacity-75',
        'assigned': '',
        'in_progress': 'ring-2 ring-green-200',
        'completed': 'opacity-60',
        'cancelled': 'opacity-40 line-through'
    }
    
    return [
        baseClasses,
        typeClasses[props.mission.type] || typeClasses.entry,
        statusClasses[props.mission.status] || ''
    ].join(' ')
})

const typeIconClasses = computed(() => {
    return props.mission.type === 'entry' 
        ? 'text-blue-600' 
        : 'text-orange-600'
})

const statusIndicatorClasses = computed(() => {
    const baseClasses = 'w-2 h-2 rounded-full flex-shrink-0'
    
    const statusColors = {
        'unassigned': 'bg-gray-400',
        'assigned': 'bg-blue-400',
        'in_progress': 'bg-green-400 animate-pulse',
        'completed': 'bg-green-600',
        'cancelled': 'bg-red-400'
    }
    
    return [
        baseClasses,
        statusColors[props.mission.status] || statusColors.unassigned
    ].join(' ')
})

// Methods
const handleClick = (event) => {
    event.stopPropagation()
    emit('click', props.mission)
}
</script>

<style scoped>
.mission-event {
    position: relative;
}

.mission-event:hover {
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .mission-event {
        @apply text-xs;
    }
    
    .mission-event .w-3 {
        @apply w-2 h-2;
    }
}
</style>