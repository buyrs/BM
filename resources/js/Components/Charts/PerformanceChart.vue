<template>
    <div class="performance-chart">
        <div class="chart-header">
            <h3 class="text-lg font-semibold text-text-primary mb-2">{{ title }}</h3>
            <div class="chart-controls flex items-center space-x-2 mb-4">
                <select 
                    v-model="chartType" 
                    @change="updateChart"
                    class="border border-gray-300 rounded-md px-3 py-1 text-sm"
                >
                    <option value="line">Line Chart</option>
                    <option value="bar">Bar Chart</option>
                    <option value="area">Area Chart</option>
                </select>
                <select 
                    v-model="timeRange" 
                    @change="updateChart"
                    class="border border-gray-300 rounded-md px-3 py-1 text-sm"
                >
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                    <option value="1y">Last Year</option>
                </select>
            </div>
        </div>
        
        <div class="chart-container" :style="{ height: height + 'px' }">
            <canvas ref="chartCanvas"></canvas>
        </div>
        
        <div v-if="showLegend" class="chart-legend flex justify-center space-x-4 mt-4">
            <div 
                v-for="(dataset, index) in chartData.datasets" 
                :key="index"
                class="flex items-center"
            >
                <div 
                    class="w-3 h-3 rounded mr-2" 
                    :style="{ backgroundColor: dataset.borderColor }"
                ></div>
                <span class="text-xs text-text-secondary">{{ dataset.label }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
    title: {
        type: String,
        default: 'Performance Chart'
    },
    data: {
        type: Object,
        required: true
    },
    height: {
        type: Number,
        default: 300
    },
    showLegend: {
        type: Boolean,
        default: true
    },
    type: {
        type: String,
        default: 'line'
    }
})

const emit = defineEmits(['timeRangeChanged', 'chartTypeChanged'])

const chartCanvas = ref(null)
const chartInstance = ref(null)
const chartType = ref(props.type)
const timeRange = ref('30d')

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
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || ''
                    if (label) {
                        label += ': '
                    }
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat().format(context.parsed.y)
                    }
                    return label
                }
            }
        }
    },
    scales: {
        x: {
            display: true,
            grid: {
                display: false
            },
            ticks: {
                color: '#6b7280'
            }
        },
        y: {
            display: true,
            beginAtZero: true,
            grid: {
                color: 'rgba(107, 114, 128, 0.1)'
            },
            ticks: {
                color: '#6b7280',
                callback: function(value) {
                    return new Intl.NumberFormat().format(value)
                }
            }
        }
    },
    interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
    },
    elements: {
        point: {
            radius: 4,
            hoverRadius: 6
        },
        line: {
            tension: 0.4
        }
    }
})

const updateChart = () => {
    emit('timeRangeChanged', timeRange.value)
    emit('chartTypeChanged', chartType.value)
    
    if (chartInstance.value) {
        chartInstance.value.destroy()
    }
    
    nextTick(() => {
        createChart()
    })
}

const createChart = () => {
    if (!chartCanvas.value) return

    const ctx = chartCanvas.value.getContext('2d')
    
    // Update chart options based on type
    const options = { ...chartOptions.value }
    
    if (chartType.value === 'area') {
        chartData.value.datasets.forEach(dataset => {
            dataset.fill = true
            dataset.backgroundColor = dataset.backgroundColor || 
                dataset.borderColor.replace('rgb', 'rgba').replace(')', ', 0.1)')
        })
    } else {
        chartData.value.datasets.forEach(dataset => {
            dataset.fill = false
        })
    }

    chartInstance.value = new Chart(ctx, {
        type: chartType.value === 'area' ? 'line' : chartType.value,
        data: chartData.value,
        options
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
            backgroundColor: dataset.backgroundColor || getDefaultColor(index, 0.1),
            borderWidth: 2,
            pointBackgroundColor: dataset.borderColor || getDefaultColor(index),
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            ...dataset
        })) || []
    }
}

const getDefaultColor = (index, alpha = 1) => {
    const colors = [
        `rgba(59, 130, 246, ${alpha})`,   // Blue
        `rgba(34, 197, 94, ${alpha})`,    // Green
        `rgba(239, 68, 68, ${alpha})`,    // Red
        `rgba(245, 158, 11, ${alpha})`,   // Yellow
        `rgba(168, 85, 247, ${alpha})`,   // Purple
        `rgba(20, 184, 166, ${alpha})`,   // Teal
        `rgba(251, 146, 60, ${alpha})`,   // Orange
        `rgba(236, 72, 153, ${alpha})`    // Pink
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

watch(chartType, () => {
    updateChart()
})
</script>

<style scoped>
.chart-container {
    position: relative;
}

.chart-controls select {
    font-size: 12px;
}

.chart-legend {
    flex-wrap: wrap;
}
</style>