<template>
    <div class="donut-chart">
        <div class="chart-header">
            <h3 class="text-lg font-semibold text-text-primary mb-4">{{ title }}</h3>
        </div>
        
        <div class="chart-container" :style="{ height: height + 'px' }">
            <canvas ref="chartCanvas"></canvas>
        </div>
        
        <div v-if="showStats" class="chart-stats mt-4">
            <div class="grid grid-cols-2 gap-4">
                <div 
                    v-for="(item, index) in chartStats" 
                    :key="index"
                    class="stat-item text-center"
                >
                    <div 
                        class="stat-color w-4 h-4 rounded mx-auto mb-1"
                        :style="{ backgroundColor: item.color }"
                    ></div>
                    <div class="stat-label text-xs text-text-secondary">{{ item.label }}</div>
                    <div class="stat-value text-lg font-semibold text-text-primary">{{ item.value }}</div>
                    <div class="stat-percentage text-xs text-text-secondary">{{ item.percentage }}%</div>
                </div>
            </div>
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
        default: 'Distribution Chart'
    },
    data: {
        type: Object,
        required: true
    },
    height: {
        type: Number,
        default: 300
    },
    showStats: {
        type: Boolean,
        default: true
    },
    colors: {
        type: Array,
        default: () => [
            '#3b82f6', // Blue
            '#22c55e', // Green
            '#ef4444', // Red
            '#f59e0b', // Yellow
            '#a855f7', // Purple
            '#14b8a6', // Teal
            '#fb923c', // Orange
            '#ec4899'  // Pink
        ]
    }
})

const chartCanvas = ref(null)
const chartInstance = ref(null)

const chartData = ref({
    labels: [],
    datasets: [{
        data: [],
        backgroundColor: [],
        borderColor: '#ffffff',
        borderWidth: 2
    }]
})

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: 'white',
            bodyColor: 'white',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
                label: function(context) {
                    const label = context.label || ''
                    const value = context.parsed
                    const total = context.dataset.data.reduce((a, b) => a + b, 0)
                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0
                    return `${label}: ${value} (${percentage}%)`
                }
            }
        }
    },
    cutout: '60%',
    elements: {
        arc: {
            borderWidth: 2
        }
    }
})

const chartStats = computed(() => {
    if (!chartData.value.labels.length) return []
    
    const total = chartData.value.datasets[0].data.reduce((a, b) => a + b, 0)
    
    return chartData.value.labels.map((label, index) => {
        const value = chartData.value.datasets[0].data[index]
        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0
        
        return {
            label,
            value,
            percentage,
            color: chartData.value.datasets[0].backgroundColor[index]
        }
    })
})

const createChart = () => {
    if (!chartCanvas.value) return

    const ctx = chartCanvas.value.getContext('2d')
    
    chartInstance.value = new Chart(ctx, {
        type: 'doughnut',
        data: chartData.value,
        options: chartOptions.value
    })
}

const processData = (rawData) => {
    if (!rawData) return

    const labels = rawData.labels || []
    const data = rawData.data || []
    const colors = rawData.colors || props.colors

    chartData.value = {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: labels.map((_, index) => colors[index % colors.length]),
            borderColor: '#ffffff',
            borderWidth: 2
        }]
    }
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
</script>

<style scoped>
.stat-item {
    padding: 8px;
    border-radius: 6px;
    background-color: #f8f9fa;
}

.stat-color {
    border: 1px solid rgba(0, 0, 0, 0.1);
}
</style>