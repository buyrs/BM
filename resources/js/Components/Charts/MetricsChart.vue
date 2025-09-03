<template>
    <div class="metrics-chart">
        <div class="chart-header flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-text-primary">{{ title }}</h3>
            <div class="chart-actions flex items-center space-x-2">
                <button
                    v-for="period in availablePeriods"
                    :key="period.value"
                    @click="selectedPeriod = period.value"
                    :class="[
                        'px-3 py-1 text-xs font-medium rounded-md transition-colors',
                        selectedPeriod === period.value
                            ? 'bg-primary text-white'
                            : 'text-text-secondary hover:text-primary hover:bg-secondary'
                    ]"
                >
                    {{ period.label }}
                </button>
            </div>
        </div>
        
        <div class="metrics-grid grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div 
                v-for="metric in displayMetrics" 
                :key="metric.key"
                class="metric-card bg-white rounded-lg p-4 border border-gray-200"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary">{{ metric.label }}</p>
                        <p class="text-2xl font-bold text-text-primary">{{ formatValue(metric.value, metric.type) }}</p>
                        <div v-if="metric.trend" class="flex items-center mt-1">
                            <svg 
                                :class="[
                                    'w-4 h-4 mr-1',
                                    metric.trend > 0 ? 'text-success-text' : 'text-error-text'
                                ]"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    stroke-width="2" 
                                    :d="metric.trend > 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'"
                                />
                            </svg>
                            <span 
                                :class="[
                                    'text-xs font-medium',
                                    metric.trend > 0 ? 'text-success-text' : 'text-error-text'
                                ]"
                            >
                                {{ Math.abs(metric.trend) }}%
                            </span>
                        </div>
                    </div>
                    <div 
                        :class="[
                            'p-3 rounded-full',
                            metric.color === 'blue' ? 'bg-info-bg' : '',
                            metric.color === 'green' ? 'bg-success-bg' : '',
                            metric.color === 'red' ? 'bg-error-bg' : '',
                            metric.color === 'yellow' ? 'bg-warning-bg' : ''
                        ]"
                    >
                        <svg 
                            :class="[
                                'w-6 h-6',
                                metric.color === 'blue' ? 'text-info-text' : '',
                                metric.color === 'green' ? 'text-success-text' : '',
                                metric.color === 'red' ? 'text-error-text' : '',
                                metric.color === 'yellow' ? 'text-warning-text' : ''
                            ]"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path 
                                stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                :d="metric.icon"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chart-container" :style="{ height: height + 'px' }">
            <canvas ref="chartCanvas"></canvas>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick, computed } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
    title: {
        type: String,
        default: 'Metrics Overview'
    },
    data: {
        type: Object,
        required: true
    },
    metrics: {
        type: Array,
        default: () => []
    },
    height: {
        type: Number,
        default: 250
    }
})

const emit = defineEmits(['periodChanged'])

const chartCanvas = ref(null)
const chartInstance = ref(null)
const selectedPeriod = ref('30d')

const availablePeriods = [
    { value: '7d', label: '7D' },
    { value: '30d', label: '30D' },
    { value: '90d', label: '90D' },
    { value: '1y', label: '1Y' }
]

const displayMetrics = computed(() => {
    return props.metrics.map(metric => ({
        ...metric,
        icon: getIconPath(metric.type),
        color: getColorForMetric(metric.type)
    }))
})

const chartData = ref({
    labels: [],
    datasets: []
})

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: 'white',
            bodyColor: 'white',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            cornerRadius: 8
        }
    },
    scales: {
        x: {
            display: true,
            grid: {
                display: false
            },
            ticks: {
                color: '#6b7280',
                maxTicksLimit: 8
            }
        },
        y: {
            display: true,
            beginAtZero: true,
            grid: {
                color: 'rgba(107, 114, 128, 0.1)'
            },
            ticks: {
                color: '#6b7280'
            }
        }
    },
    elements: {
        point: {
            radius: 3,
            hoverRadius: 5
        },
        line: {
            tension: 0.4
        }
    }
})

const getIconPath = (type) => {
    const icons = {
        'missions': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'completed': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'incidents': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        'checkers': 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'duration': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'rate': 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'
    }
    return icons[type] || icons['missions']
}

const getColorForMetric = (type) => {
    const colors = {
        'missions': 'blue',
        'completed': 'green',
        'incidents': 'red',
        'checkers': 'yellow',
        'duration': 'blue',
        'rate': 'green'
    }
    return colors[type] || 'blue'
}

const formatValue = (value, type) => {
    if (type === 'percentage' || type === 'rate') {
        return `${value}%`
    }
    if (type === 'duration') {
        return `${value}h`
    }
    if (type === 'currency') {
        return new Intl.NumberFormat('fr-FR', { 
            style: 'currency', 
            currency: 'EUR' 
        }).format(value)
    }
    return new Intl.NumberFormat().format(value)
}

const createChart = () => {
    if (!chartCanvas.value) return

    const ctx = chartCanvas.value.getContext('2d')
    
    chartInstance.value = new Chart(ctx, {
        type: 'line',
        data: chartData.value,
        options: chartOptions.value
    })
}

const processData = (rawData) => {
    if (!rawData) return

    chartData.value = {
        labels: rawData.labels || [],
        datasets: rawData.datasets?.map((dataset, index) => ({
            label: dataset.label,
            data: dataset.data,
            borderColor: dataset.borderColor || getDefaultColor(index),
            backgroundColor: 'transparent',
            borderWidth: 2,
            pointBackgroundColor: dataset.borderColor || getDefaultColor(index),
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            ...dataset
        })) || []
    }
}

const getDefaultColor = (index) => {
    const colors = [
        '#3b82f6', // Blue
        '#22c55e', // Green
        '#ef4444', // Red
        '#f59e0b', // Yellow
        '#a855f7', // Purple
        '#14b8a6', // Teal
    ]
    return colors[index % colors.length]
}

onMounted(() => {
    processData(props.data)
    nextTick(() => {
        createChart()
    })
})

watch(() => props.data, (newData) => {
    processData(newData)
    if (chartInstance.value) {
        chartInstance.value.data = chartData.value
        chartInstance.value.update('none')
    }
}, { deep: true })

watch(selectedPeriod, (newPeriod) => {
    emit('periodChanged', newPeriod)
})
</script>

<style scoped>
.metric-card {
    transition: all 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>