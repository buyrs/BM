<template>
    <Head title="Analytics & Reporting" />
    <DashboardAdmin>
        <template #header>
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-primary">Analytics & Reporting</h1>
                <p class="text-secondary mt-1">Gain insights into your Bail Mobilité operations.</p>
            </div>
        </template>
        
        <div class="p-6 lg:p-8">
            <!-- Key Metrics Cards -->
            <div class="mb-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="metric-card rounded-lg bg-card p-6 shadow-sm border border-custom relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-green-400/20 to-green-600/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-secondary">Mission Completion Rate</p>
                                <div class="p-2 rounded-full bg-green-100 dark:bg-green-900/50">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-primary mt-1">{{ metrics.completionRate }}%</p>
                            <div class="flex items-center gap-1 text-sm mt-2">
                                <span :class="metrics.completionTrend >= 0 ? 'text-green-500' : 'text-red-500'" class="flex items-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path v-if="metrics.completionTrend >= 0" d="M12 19V5"></path>
                                        <path v-if="metrics.completionTrend >= 0" d="M5 12l7-7 7 7"></path>
                                        <path v-else d="M12 5v14"></path>
                                        <path v-else d="M19 12l-7 7-7-7"></path>
                                    </svg>
                                    {{ Math.abs(metrics.completionTrend) }}%
                                </span>
                                <span class="text-secondary">vs last 30 days</span>
                            </div>
                            <div class="mt-3 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div 
                                    class="bg-green-500 h-2 rounded-full transition-all duration-1000 ease-out" 
                                    :style="{ width: metrics.completionRate + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card rounded-lg bg-card p-6 shadow-sm border border-custom relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-blue-400/20 to-blue-600/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-secondary">Average Inspection Time</p>
                                <div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-primary mt-1">{{ metrics.avgInspectionTime }}</p>
                            <div class="flex items-center gap-1 text-sm mt-2">
                                <span :class="metrics.inspectionTrend >= 0 ? 'text-red-500' : 'text-green-500'" class="flex items-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path v-if="metrics.inspectionTrend >= 0" d="M12 5v14"></path>
                                        <path v-if="metrics.inspectionTrend >= 0" d="M19 12l-7 7-7-7"></path>
                                        <path v-else d="M12 19V5"></path>
                                        <path v-else d="M5 12l7-7 7 7"></path>
                                    </svg>
                                    {{ Math.abs(metrics.inspectionTrend) }}%
                                </span>
                                <span class="text-secondary">vs last 30 days</span>
                            </div>
                            <div class="mt-3 flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div 
                                        class="bg-blue-500 h-2 rounded-full transition-all duration-1000 ease-out" 
                                        :style="{ width: '75%' }"
                                    ></div>
                                </div>
                                <span class="text-xs text-secondary">Target: 2h</span>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card rounded-lg bg-card p-6 shadow-sm border border-custom relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-red-400/20 to-red-600/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-secondary">Total Incidents</p>
                                <div class="p-2 rounded-full bg-red-100 dark:bg-red-900/50">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-primary mt-1">{{ metrics.totalIncidents }}</p>
                            <div class="flex items-center gap-1 text-sm mt-2">
                                <span :class="metrics.incidentTrend <= 0 ? 'text-green-500' : 'text-red-500'" class="flex items-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path v-if="metrics.incidentTrend <= 0" d="M12 19V5"></path>
                                        <path v-if="metrics.incidentTrend <= 0" d="M5 12l7-7 7 7"></path>
                                        <path v-else d="M12 5v14"></path>
                                        <path v-else d="M19 12l-7 7-7-7"></path>
                                    </svg>
                                    {{ Math.abs(metrics.incidentTrend) }}%
                                </span>
                                <span class="text-secondary">vs last 30 days</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div class="text-center">
                                    <div class="text-red-500 font-semibold">{{ Math.floor(metrics.totalIncidents * 0.3) }}</div>
                                    <div class="text-secondary">High</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-yellow-500 font-semibold">{{ Math.floor(metrics.totalIncidents * 0.5) }}</div>
                                    <div class="text-secondary">Medium</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-green-500 font-semibold">{{ Math.floor(metrics.totalIncidents * 0.2) }}</div>
                                    <div class="text-secondary">Low</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="metric-card rounded-lg bg-card p-6 shadow-sm border border-custom relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-yellow-400/20 to-yellow-600/20 rounded-full -mr-10 -mt-10"></div>
                        <div class="relative">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-sm font-medium text-secondary">Avg. Checker Rating</p>
                                <div class="p-2 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-3xl font-bold text-primary mt-1">{{ metrics.avgRating }}/5</p>
                            <div class="flex items-center gap-1 text-sm mt-2">
                                <span :class="metrics.ratingTrend >= 0 ? 'text-green-500' : 'text-red-500'" class="flex items-center">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path v-if="metrics.ratingTrend >= 0" d="M12 19V5"></path>
                                        <path v-if="metrics.ratingTrend >= 0" d="M5 12l7-7 7 7"></path>
                                        <path v-else d="M12 5v14"></path>
                                        <path v-else d="M19 12l-7 7-7-7"></path>
                                    </svg>
                                    {{ Math.abs(metrics.ratingTrend) }}%
                                </span>
                                <span class="text-secondary">vs last 30 days</span>
                            </div>
                            <div class="mt-3 flex items-center space-x-1">
                                <div v-for="i in 5" :key="i" class="flex-1">
                                    <svg 
                                        class="w-4 h-4 transition-colors duration-300" 
                                        :class="i <= Math.floor(metrics.avgRating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'"
                                        fill="currentColor" 
                                        viewBox="0 0 24 24"
                                    >
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5 mb-8">
                <div class="lg:col-span-3 rounded-lg bg-card p-6 shadow-sm border border-custom">
                    <h3 class="text-lg font-semibold text-primary mb-4">Incident Trends</h3>
                    <div class="chart-container">
                        <canvas ref="incidentTrendsChart"></canvas>
                    </div>
                </div>
                <div class="lg:col-span-2 rounded-lg bg-card p-6 shadow-sm border border-custom">
                    <h3 class="text-lg font-semibold text-primary mb-4">Mission Status</h3>
                    <div class="chart-container">
                        <canvas ref="missionStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Contract Expiration Tracking -->
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-primary mb-4">Contract Expiration Tracking</h2>
                <div class="rounded-lg bg-card shadow-sm border border-custom">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[var(--border-color)]">
                            <thead class="bg-[var(--background-color)]">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Property</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Tenant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Expiration Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-secondary uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border-color)]">
                                <tr v-for="contract in expiringContracts" :key="contract.id" class="hover:bg-table-hover">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary">{{ contract.property }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary">{{ contract.tenant }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-secondary">{{ formatDate(contract.expiration_date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="getStatusClass(contract.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ contract.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button 
                                            @click="sendReminder(contract.id)"
                                            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--accent-color)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-[var(--primary-color)] transition-colors duration-200"
                                        >
                                            <span>Send Reminder</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Custom Reports -->
            <div>
                <h2 class="text-2xl font-semibold text-primary mb-4">Custom Reports</h2>
                <div class="rounded-lg bg-card p-6 shadow-sm border border-custom">
                    <form @submit.prevent="generateReport" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 items-end">
                        <div class="sm:col-span-2 lg:col-span-1">
                            <label class="block text-sm font-medium text-secondary mb-1" for="report-type">Report Type</label>
                            <select 
                                v-model="reportFilters.type" 
                                class="form-input form-select w-full bg-card border border-custom text-primary rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent"
                                id="report-type"
                            >
                                <option value="mission-summary">Mission Summary</option>
                                <option value="checker-performance">Checker Performance</option>
                                <option value="incident-analysis">Incident Analysis</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1" for="start-date">Start Date</label>
                            <input 
                                v-model="reportFilters.startDate"
                                class="form-input w-full bg-card border border-custom text-primary rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent"
                                id="start-date"
                                type="date"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1" for="end-date">End Date</label>
                            <input 
                                v-model="reportFilters.endDate"
                                class="form-input w-full bg-card border border-custom text-primary rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent"
                                id="end-date"
                                type="date"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1" for="property">Property</label>
                            <select 
                                v-model="reportFilters.property"
                                class="form-input form-select w-full bg-card border border-custom text-primary rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent"
                                id="property"
                            >
                                <option value="">All Properties</option>
                                <option v-for="property in propertyOptions" :key="property.id" :value="property.id">
                                    {{ property.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex gap-4">
                            <button 
                                @click="exportReport"
                                type="button"
                                class="w-full px-4 py-2 text-sm font-medium rounded-md text-primary bg-card border border-custom hover:bg-[var(--background-color)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-[var(--primary-color)] transition-colors duration-200"
                            >
                                Export
                            </button>
                            <button 
                                type="submit"
                                class="w-full px-4 py-2 text-sm font-medium rounded-md text-white bg-[var(--primary-color)] hover:bg-[var(--accent-color)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-card focus:ring-[var(--primary-color)] transition-colors duration-200"
                            >
                                Generate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue';
import Chart from 'chart.js/auto';
import axios from 'axios';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();

// Data refs
const analytics = ref({});
const checkerOptions = ref([]);
const propertyOptions = ref([]);
const expiringContracts = ref([
    {
        id: 1,
        property: '123 Rue de Paris, 75001 Paris',
        tenant: 'Jean Dupont',
        expiration_date: '2024-08-15',
        status: 'Expiring Soon'
    },
    {
        id: 2,
        property: '45 Avenue des Champs-Élysées, 75008 Paris',
        tenant: 'Marie Dubois',
        expiration_date: '2024-09-01',
        status: 'Upcoming'
    }
]);

// Chart refs
const incidentTrendsChart = ref(null);
const missionStatusChart = ref(null);
let incidentTrendsInstance, missionStatusInstance;

// Report filters
const reportFilters = ref({
    type: 'mission-summary',
    startDate: '',
    endDate: '',
    property: ''
});

// Computed metrics
const metrics = computed(() => ({
    completionRate: analytics.value.completionRate || 95,
    completionTrend: analytics.value.completionTrend || 5,
    avgInspectionTime: analytics.value.avgInspectionTime || '2.5 hrs',
    inspectionTrend: analytics.value.inspectionTrend || -10,
    totalIncidents: analytics.value.totalIncidents || 15,
    incidentTrend: analytics.value.incidentTrend || -20,
    avgRating: analytics.value.avgRating || 4.8,
    ratingTrend: analytics.value.ratingTrend || 2
}));

// Chart data
const missionStatusData = {
    labels: ['Completed', 'Pending', 'Failed'],
    datasets: [{
        label: 'Missions',
        data: [190, 8, 2],
        backgroundColor: [
            'rgba(42, 157, 143, 0.7)',
            'rgba(233, 196, 106, 0.7)',
            'rgba(231, 111, 81, 0.7)'
        ],
        borderColor: [
            '#2a9d8f',
            '#e9c46a',
            '#e76f51'
        ],
        borderWidth: 1
    }]
};

const incidentTrendsData = {
    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    datasets: [{
        label: 'Type A',
        data: [2, 4, 3, 5],
        borderColor: '#137fec',
        backgroundColor: 'rgba(19, 127, 236, 0.1)',
        tension: 0.4,
        fill: true
    }, {
        label: 'Type B',
        data: [1, 2, 1, 3],
        borderColor: '#2a9d8f',
        backgroundColor: 'rgba(42, 157, 143, 0.1)',
        tension: 0.4,
        fill: true
    }]
};

// Helper functions
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function getStatusClass(status) {
    const baseClasses = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
    switch (status) {
        case 'Expiring Soon':
            return `${baseClasses} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100`;
        case 'Upcoming':
            return `${baseClasses} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100`;
        default:
            return `${baseClasses} bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100`;
    }
}

function getChartOptions(isDarkMode) {
    const textColor = isDarkMode ? '#a0a0a0' : '#64748b';
    const gridColor = isDarkMode ? '#444444' : '#e2e8f0';
    
    return {
        missionStatusConfig: {
            type: 'doughnut',
            data: missionStatusData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: textColor
                        }
                    }
                }
            }
        },
        incidentTrendsConfig: {
            type: 'line',
            data: incidentTrendsData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: gridColor
                        },
                        ticks: {
                            color: textColor
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: textColor
                        }
                    }
                }
            }
        }
    };
}

function renderCharts(isDarkMode) {
    if (missionStatusInstance) missionStatusInstance.destroy();
    if (incidentTrendsInstance) incidentTrendsInstance.destroy();
    
    const options = getChartOptions(isDarkMode);
    
    if (missionStatusChart.value) {
        missionStatusInstance = new Chart(missionStatusChart.value, options.missionStatusConfig);
    }
    
    if (incidentTrendsChart.value) {
        incidentTrendsInstance = new Chart(incidentTrendsChart.value, options.incidentTrendsConfig);
    }
}

async function fetchAnalytics() {
    try {
        const { data } = await axios.get(route('admin.analytics.data'));
        analytics.value = data;
    } catch (e) {
        console.error('Error fetching analytics:', e);
    }
}

async function fetchProperties() {
    try {
        const { data } = await axios.get('/admin/properties');
        propertyOptions.value = data.properties || [];
    } catch (e) {
        console.error('Error fetching properties:', e);
    }
}

async function sendReminder(contractId) {
    try {
        await axios.post(`/admin/contracts/${contractId}/reminder`);
        // Show success message
    } catch (e) {
        console.error('Error sending reminder:', e);
    }
}

function generateReport() {
    console.log('Generating report with filters:', reportFilters.value);
    // Implement report generation logic
}

function exportReport() {
    console.log('Exporting report with filters:', reportFilters.value);
    // Implement export logic
}

onMounted(() => {
    fetchAnalytics();
    fetchProperties();
    
    // Initial chart render
    setTimeout(() => {
        renderCharts(isDark.value);
    }, 100);
});

// Watch for theme changes and re-render charts
watch(isDark, (newValue) => {
    renderCharts(newValue);
});
</script> 