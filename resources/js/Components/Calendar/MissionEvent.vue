<template>
    <div
        :class="[
            'mission-event cursor-pointer transition-all duration-200 hover:shadow-md relative touch-feedback',
            eventClasses,
            compact ? 'p-1 rounded text-xs' : 'p-2 rounded-md text-sm',
            selectionMode ? 'hover:ring-2 hover:ring-info-border' : '',
            selected ? 'ring-2 ring-primary bg-secondary' : '',
            mission.type === 'entry' ? 'colorblind-pattern-entry' : 'colorblind-pattern-exit',
            draggable ? 'draggable-mission' : ''
        ]"
        role="button"
        :tabindex="0"
        :draggable="draggable"
        :aria-label="getMissionAriaLabel()"
        :aria-pressed="selected"
        :aria-describedby="compact ? null : `mission-tooltip-${mission.id}`"
        @click="handleClick"
        @keydown="handleKeydown"
        @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave"
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
    >
        <!-- Selection Checkbox -->
        <div v-if="selectionMode" class="absolute -top-1 -right-1 z-10">
            <div :class="[
                'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                selected ? 'bg-primary border-primary' : 'bg-white border-gray-300'
            ]">
                <svg v-if="selected" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
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

        <!-- Enhanced Tooltip -->
        <Teleport to="body">
            <div
                v-if="showTooltip && !compact"
                ref="tooltip"
                class="absolute z-50 bg-gray-900 text-white text-xs rounded-md px-3 py-2 shadow-lg pointer-events-none max-w-xs"
                :style="tooltipStyle"
            >
                <div class="space-y-1">
                    <div class="font-medium text-white">{{ mission.tenant_name || 'No tenant name' }}</div>
                    <div class="text-gray-300">{{ mission.address || 'No address' }}</div>
                    <div class="flex items-center space-x-2">
                        <span :class="[
                            'px-2 py-0.5 rounded text-xs font-medium',
                            mission.type === 'entry' ? 'bg-info-border text-white' : 'bg-warning-border text-white'
                        ]">
                            {{ missionTypeLabel }}
                        </span>
                        <span :class="[
                            'px-2 py-0.5 rounded text-xs font-medium',
                            getTooltipStatusClasses(mission.status)
                        ]">
                            {{ formattedStatus }}
                        </span>
                    </div>
                    <div v-if="mission.agent" class="text-gray-300">
                        <span class="text-gray-400">Assigned to:</span> {{ mission.agent.name }}
                    </div>
                    <div v-if="mission.scheduled_time" class="text-gray-300">
                        <span class="text-gray-400">Time:</span> {{ formattedTime }}
                    </div>
                    <div v-if="mission.bail_mobilite" class="text-gray-300 text-xs">
                        <span class="text-gray-400">BM Duration:</span> {{ mission.bail_mobilite.duration_days }} days
                    </div>
                    <div v-if="mission.conflicts && mission.conflicts.length > 0" class="text-red-300 text-xs">
                        <span class="text-red-400">⚠️ Conflicts:</span> {{ mission.conflicts.length }}
                    </div>
                </div>
                <!-- Tooltip Arrow -->
                <div class="absolute top-full left-1/2 transform -translate-x-1/2">
                    <div class="border-4 border-transparent border-t-gray-900"></div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'

// Props
const props = defineProps({
    mission: {
        type: Object,
        required: true
    },
    compact: {
        type: Boolean,
        default: false
    },
    selectionMode: {
        type: Boolean,
        default: false
    },
    selected: {
        type: Boolean,
        default: false
    },
    draggable: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['click', 'dragstart', 'dragend'])

// Reactive state
const showTooltip = ref(false)
const tooltip = ref(null)
const tooltipStyle = ref({})

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
        'entry': 'bg-info-bg border-info-border text-info-text',
        'exit': 'bg-warning-bg border-warning-border text-warning-text'
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
        ? 'text-info-text' 
        : 'text-warning-text'
})

const statusIndicatorClasses = computed(() => {
    const baseClasses = 'w-2 h-2 rounded-full flex-shrink-0'
    
    const statusColors = {
        'unassigned': 'bg-gray-400',
        'assigned': 'bg-warning-border',
        'in_progress': 'bg-info-border animate-pulse',
        'completed': 'bg-success-border',
        'cancelled': 'bg-error-border'
    }
    
    return [
        baseClasses,
        statusColors[props.mission.status] || statusColors.unassigned
    ].join(' ')
})

// Methods
const getTooltipStatusClasses = (status) => {
    const statusClasses = {
        'unassigned': 'bg-gray-600 text-gray-100',
        'assigned': 'bg-warning-border text-white',
        'in_progress': 'bg-info-border text-white',
        'completed': 'bg-success-border text-white',
        'cancelled': 'bg-error-border text-white'
    }
    
    return statusClasses[status] || statusClasses.unassigned
}

const updateTooltipPosition = async (event) => {
    if (!showTooltip.value) return
    
    await nextTick()
    
    if (tooltip.value) {
        const rect = event.target.getBoundingClientRect()
        const tooltipRect = tooltip.value.getBoundingClientRect()
        
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2)
        let top = rect.bottom + 8
        
        // Adjust if tooltip goes off screen
        if (left < 8) left = 8
        if (left + tooltipRect.width > window.innerWidth - 8) {
            left = window.innerWidth - tooltipRect.width - 8
        }
        
        if (top + tooltipRect.height > window.innerHeight - 8) {
            top = rect.top - tooltipRect.height - 8
        }
        
        tooltipStyle.value = {
            left: `${left}px`,
            top: `${top}px`
        }
    }
}

const handleMouseEnter = (event) => {
    showTooltip.value = true
    updateTooltipPosition(event)
}

const handleMouseLeave = () => {
    showTooltip.value = false
}

const handleClick = (event) => {
    event.stopPropagation()
    emit('click', props.mission)
}

const handleKeydown = (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        event.stopPropagation()
        emit('click', props.mission)
    }
}

const handleDragStart = (event) => {
    if (!props.draggable) {
        event.preventDefault()
        return
    }
    
    emit('dragstart', event, props.mission)
}

const handleDragEnd = (event) => {
    if (!props.draggable) return
    
    emit('dragend', event, props.mission)
}

const getMissionAriaLabel = () => {
    let label = `${missionTypeLabel.value} mission`
    
    if (props.mission.tenant_name) {
        label += ` for ${props.mission.tenant_name}`
    }
    
    if (props.mission.address) {
        label += ` at ${props.mission.address}`
    }
    
    if (props.mission.scheduled_time) {
        label += ` scheduled for ${formattedTime.value}`
    }
    
    label += `, status: ${formattedStatus.value}`
    
    if (props.mission.agent) {
        label += `, assigned to ${props.mission.agent.name}`
    }
    
    if (props.mission.conflicts && props.mission.conflicts.length > 0) {
        label += `, has scheduling conflicts`
    }
    
    if (props.selectionMode) {
        label += props.selected ? ', selected' : ', not selected'
    }
    
    return label
}
</script>

<style scoped>
.mission-event {
    position: relative;
}

.mission-event:hover {
    transform: translateY(-1px);
}

.draggable-mission {
    cursor: move;
}

.draggable-mission:hover {
    @apply shadow-lg;
    transform: translateY(-2px) scale(1.02);
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