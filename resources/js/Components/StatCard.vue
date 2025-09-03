<template>
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-text-secondary">{{ title }}</p>
                <p class="text-2xl font-semibold text-text-primary">{{ formattedValue }}</p>
                
                <!-- Trend -->
                <div v-if="trend" class="flex items-center mt-2">
                    <span :class="getTrendClass()" class="text-xs font-medium flex items-center">
                        <svg
                            v-if="trend.direction === 'up'"
                            class="w-3 h-3 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"/>
                        </svg>
                        <svg
                            v-else-if="trend.direction === 'down'"
                            class="w-3 h-3 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"/>
                        </svg>
                        <span v-else class="w-3 h-3 mr-1 flex items-center justify-center">
                            <div class="w-2 h-0.5 bg-current rounded"></div>
                        </span>
                        {{ getTrendText() }}
                    </span>
                    <span class="text-xs text-text-secondary ml-1">{{ subtitle }}</span>
                </div>
                
                <!-- Subtitle without trend -->
                <div v-else-if="subtitle" class="mt-2">
                    <span class="text-xs text-text-secondary">{{ subtitle }}</span>
                </div>
            </div>
            
            <!-- Icon -->
            <div :class="getIconClass()" class="p-3 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        :d="getIconPath()"
                    />
                </svg>
            </div>
        </div>
        
        <!-- Details (for incidents card) -->
        <div v-if="details && Object.keys(details).length > 0" class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div v-for="(value, key) in details" :key="key" class="flex justify-between">
                    <span class="text-text-secondary">{{ key }}:</span>
                    <span class="font-medium" :class="getDetailValueClass(key, value)">{{ value }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
    title: {
        type: String,
        required: true
    },
    value: {
        type: [Number, String],
        required: true
    },
    icon: {
        type: String,
        required: true
    },
    color: {
        type: String,
        default: 'primary'
    },
    trend: {
        type: Object,
        default: null
    },
    subtitle: {
        type: String,
        default: ''
    },
    details: {
        type: Object,
        default: null
    }
})

const formattedValue = computed(() => {
    if (typeof props.value === 'number') {
        return props.value.toLocaleString('fr-FR')
    }
    return props.value
})

const getIconClass = () => {
    const classes = {
        warning: 'bg-warning-bg text-warning-text',
        info: 'bg-info-bg text-info-text',
        success: 'bg-success-bg text-success-text',
        error: 'bg-error-bg text-error-text',
        primary: 'bg-secondary text-primary'
    }
    return classes[props.color] || classes.primary
}

const getIconPath = () => {
    const paths = {
        clock: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        lightning: 'M13 10V3L4 14h7v7l9-11h-7z',
        'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'exclamation-triangle': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
        chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
    }
    return paths[props.icon] || paths.chart
}

const getTrendClass = () => {
    if (!props.trend) return ''
    
    const classes = {
        up: 'text-green-600',
        down: 'text-red-600',
        neutral: 'text-gray-600'
    }
    return classes[props.trend.direction] || classes.neutral
}

const getTrendText = () => {
    if (!props.trend) return ''
    
    const { value, percentage } = props.trend
    if (value > 0) return `+${value} (+${percentage}%)`
    if (value < 0) return `${value} (${percentage}%)`
    return '0 (0%)'
}

const getDetailValueClass = (key, value) => {
    if (key.toLowerCase().includes('critique') && value > 0) {
        return 'text-error-text'
    }
    if (key.toLowerCase().includes('ouvert') && value > 0) {
        return 'text-warning-text'
    }
    return 'text-text-primary'
}
</script>