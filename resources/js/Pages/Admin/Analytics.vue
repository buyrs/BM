<template>
    <Head title="Analytics" />
    <DashboardAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Analytics</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <div class="flex flex-wrap gap-4 mb-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" v-model="filters.start" class="border rounded px-2 py-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" v-model="filters.end" class="border rounded px-2 py-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mission Type</label>
                            <select v-model="filters.type" class="border rounded px-2 py-1">
                                <option value="">All</option>
                                <option value="checkin">Check-in</option>
                                <option value="checkout">Check-out</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Checker</label>
                            <select v-model="filters.checker_id" class="border rounded px-2 py-1">
                                <option value="">All</option>
                                <option v-for="checker in checkerOptions" :key="checker.id" :value="checker.id">
                                    {{ checker.name }} ({{ checker.email }})
                                </option>
                            </select>
                        </div>
                        <button @click="fetchAnalytics" class="ml-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Apply</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-2">Missions Created/Completed (Last 30 Days)</h3>
                        <canvas ref="trendChart"></canvas>
                    </div>
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-2">Status Distribution</h3>
                        <canvas ref="statusChart"></canvas>
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-2">Checker Performance</h3>
                    <canvas ref="checkerChart"></canvas>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-2">Assignment Efficiency</h3>
                        <div v-if="analytics.assignmentEfficiency">
                            <p>Average time to complete: <span class="font-bold">{{ formatMinutes(analytics.assignmentEfficiency.avg_minutes_to_complete) }}</span></p>
                        </div>
                        <div v-else>
                            <p>No data available.</p>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-2">Incident Management</h3>
                        <div v-if="analytics.avgResolutionTime !== undefined">
                            <p>Average time to resolve incidents: <span class="font-bold">{{ analytics.avgResolutionTime }} hours</span></p>
                        </div>
                        <div v-else>
                            <p>No data available.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue';
import Chart from 'chart.js/auto';
import axios from 'axios';

const analytics = ref({});
const checkerOptions = ref([]);
const filters = ref({
    start: '',
    end: '',
    type: '',
    checker_id: ''
});

const trendChart = ref(null);
const statusChart = ref(null);
const checkerChart = ref(null);
let trendInstance, statusInstance, checkerInstance;

function formatMinutes(minutes) {
    if (!minutes) return 'N/A';
    const m = Math.round(minutes);
    if (m < 60) return `${m} min`;
    return `${Math.floor(m/60)}h ${m%60}m`;
}

async function fetchAnalytics() {
    const params = { ...filters.value };
    try {
        const { data } = await axios.get(route('admin.analytics.data'), { params });
        analytics.value = data;
        renderCharts();
    } catch (e) {
        // handle error
    }
}

function renderCharts() {
    // Trend Chart
    if (trendInstance) trendInstance.destroy();
    const created = analytics.value.missionsCreated || [];
    const completed = analytics.value.missionsCompleted || [];
    const labels = [...new Set([...created.map(d => d.date), ...completed.map(d => d.date)])].sort();
    const createdMap = Object.fromEntries(created.map(d => [d.date, d.count]));
    const completedMap = Object.fromEntries(completed.map(d => [d.date, d.count]));
    trendInstance = new Chart(trendChart.value, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Created', data: labels.map(l => createdMap[l] || 0), borderColor: '#6366f1', backgroundColor: '#6366f1', fill: false },
                { label: 'Completed', data: labels.map(l => completedMap[l] || 0), borderColor: '#10b981', backgroundColor: '#10b981', fill: false }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });

    // Status Chart
    if (statusInstance) statusInstance.destroy();
    const statuses = analytics.value.statusDistribution || [];
    statusInstance = new Chart(statusChart.value, {
        type: 'pie',
        data: {
            labels: statuses.map(s => s.status),
            datasets: [{ data: statuses.map(s => s.count), backgroundColor: ['#6366f1','#10b981','#f59e42','#ef4444','#a1a1aa'] }]
        },
        options: { responsive: true }
    });

    // Checker Performance
    if (checkerInstance) checkerInstance.destroy();
    const checkers = analytics.value.checkerPerformance || [];
    checkerInstance = new Chart(checkerChart.value, {
        type: 'bar',
        data: {
            labels: checkers.map(c => c.name || c.email),
            datasets: [
                { label: 'Completed', data: checkers.map(c => c.completed), backgroundColor: '#6366f1' },
                { label: 'Refusals', data: checkers.map(c => c.refusals), backgroundColor: '#f59e42' },
                { label: 'Downgraded', data: checkers.map(c => c.downgraded ? 1 : 0), backgroundColor: '#ef4444' }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });
}

async function fetchCheckers() {
    // Fetch all checkers for filter dropdown
    try {
        const { data } = await axios.get('/admin/checkers');
        checkerOptions.value = data.checkers || [];
    } catch (e) {}
}

onMounted(() => {
    fetchCheckers();
    fetchAnalytics();
});

watch(filters, fetchAnalytics);
</script> 