<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Analytics Dashboard') }}
            </h2>
            <div class="flex space-x-2">
                <select id="dateRange" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">Select Date Range</option>
                </select>
                <button id="refreshData" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Refresh Data
                </button>
                <button id="clearCache" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Clear Cache
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                    <p class="mt-2 text-gray-600">Loading analytics data...</p>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div id="dashboardContent" class="space-y-6">
                <!-- Key Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Missions</p>
                                    <p id="totalMissions" class="text-2xl font-semibold text-gray-900">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Completion Rate</p>
                                    <p id="completionRate" class="text-2xl font-semibold text-gray-900">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Avg Completion Time</p>
                                    <p id="avgCompletionTime" class="text-2xl font-semibold text-gray-900">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Active Users</p>
                                    <p id="activeUsers" class="text-2xl font-semibold text-gray-900">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Mission Trends Chart -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Mission Trends</h3>
                            <div class="h-64">
                                <canvas id="missionTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Property Type Distribution -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Property Type Distribution</h3>
                            <div class="h-64">
                                <canvas id="propertyTypeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Performance and Maintenance Metrics -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- User Performance Table -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Checker Performance</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Missions</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody id="checkerPerformanceTable" class="bg-white divide-y divide-gray-200">
                                        <!-- Data will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Metrics -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Maintenance Requests</h3>
                            <div class="h-64">
                                <canvas id="maintenanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Metrics Tables -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Property Performance by Type</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Missions</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="propertyPerformanceTable" class="bg-white divide-y divide-gray-200">
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        class AnalyticsDashboard {
            constructor() {
                this.charts = {};
                this.currentDateRange = null;
                this.init();
            }

            init() {
                this.loadDateRanges();
                this.bindEvents();
                this.loadDashboardData();
            }

            bindEvents() {
                document.getElementById('dateRange').addEventListener('change', (e) => {
                    this.currentDateRange = e.target.value;
                    this.loadDashboardData();
                });

                document.getElementById('refreshData').addEventListener('click', () => {
                    this.loadDashboardData();
                });

                document.getElementById('clearCache').addEventListener('click', () => {
                    this.clearCache();
                });
            }

            async loadDateRanges() {
                try {
                    const response = await fetch('/admin/analytics/date-ranges');
                    const result = await response.json();
                    
                    if (result.success) {
                        const select = document.getElementById('dateRange');
                        Object.entries(result.data).forEach(([key, range]) => {
                            const option = document.createElement('option');
                            option.value = key;
                            option.textContent = range.label;
                            select.appendChild(option);
                        });
                        
                        // Set default to last 30 days
                        select.value = 'last_30_days';
                        this.currentDateRange = 'last_30_days';
                    }
                } catch (error) {
                    console.error('Failed to load date ranges:', error);
                }
            }

            async loadDashboardData() {
                this.showLoading();
                
                try {
                    let url = '/admin/analytics/dashboard-data';
                    if (this.currentDateRange) {
                        const dateRanges = await this.getDateRangeData();
                        const range = dateRanges[this.currentDateRange];
                        if (range) {
                            url += `?start_date=${range.start_date}&end_date=${range.end_date}`;
                        }
                    }

                    const response = await fetch(url);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.updateDashboard(result.data);
                    } else {
                        throw new Error('Failed to load dashboard data');
                    }
                } catch (error) {
                    console.error('Error loading dashboard data:', error);
                    this.showError('Failed to load dashboard data');
                } finally {
                    this.hideLoading();
                }
            }

            async getDateRangeData() {
                const response = await fetch('/admin/analytics/date-ranges');
                const result = await response.json();
                return result.success ? result.data : {};
            }

            updateDashboard(data) {
                // Update key metrics
                document.getElementById('totalMissions').textContent = data.mission_metrics.total_missions;
                document.getElementById('completionRate').textContent = data.mission_metrics.completion_rate + '%';
                document.getElementById('avgCompletionTime').textContent = data.mission_metrics.avg_completion_time_hours + 'h';
                document.getElementById('activeUsers').textContent = data.system_metrics.active_users;

                // Update charts
                this.updateMissionTrendsChart(data);
                this.updatePropertyTypeChart(data);
                this.updateMaintenanceChart(data);

                // Update tables
                this.updateCheckerPerformanceTable(data.user_performance.checker_performance);
                this.updatePropertyPerformanceTable(data.property_metrics.missions_by_property_type);
            }

            updateMissionTrendsChart(data) {
                const ctx = document.getElementById('missionTrendsChart').getContext('2d');
                
                if (this.charts.missionTrends) {
                    this.charts.missionTrends.destroy();
                }

                // For now, create sample trend data
                const labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                const createdData = [12, 19, 15, 22];
                const completedData = [8, 15, 12, 18];

                this.charts.missionTrends = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Created',
                            data: createdData,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.1
                        }, {
                            label: 'Completed',
                            data: completedData,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            updatePropertyTypeChart(data) {
                const ctx = document.getElementById('propertyTypeChart').getContext('2d');
                
                if (this.charts.propertyType) {
                    this.charts.propertyType.destroy();
                }

                const distribution = data.property_metrics.property_type_distribution;
                const labels = Object.keys(distribution);
                const values = Object.values(distribution);

                this.charts.propertyType = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                'rgb(59, 130, 246)',
                                'rgb(34, 197, 94)',
                                'rgb(251, 191, 36)',
                                'rgb(239, 68, 68)',
                                'rgb(168, 85, 247)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            updateMaintenanceChart(data) {
                const ctx = document.getElementById('maintenanceChart').getContext('2d');
                
                if (this.charts.maintenance) {
                    this.charts.maintenance.destroy();
                }

                const metrics = data.maintenance_metrics;
                const labels = ['Pending', 'In Progress', 'Completed'];
                const values = [metrics.pending_requests, metrics.in_progress_requests, metrics.completed_requests];

                this.charts.maintenance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Requests',
                            data: values,
                            backgroundColor: [
                                'rgb(251, 191, 36)',
                                'rgb(59, 130, 246)',
                                'rgb(34, 197, 94)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            updateCheckerPerformanceTable(checkerData) {
                const tbody = document.getElementById('checkerPerformanceTable');
                tbody.innerHTML = '';

                checkerData.forEach(checker => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${checker.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checker.total_missions}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checker.completed_missions}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checker.completion_rate}%</td>
                    `;
                    tbody.appendChild(row);
                });
            }

            updatePropertyPerformanceTable(propertyData) {
                const tbody = document.getElementById('propertyPerformanceTable');
                tbody.innerHTML = '';

                Object.entries(propertyData).forEach(([propertyType, metrics]) => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${propertyType}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${metrics.total_missions}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${metrics.completed_missions}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${metrics.completion_rate}%</td>
                    `;
                    tbody.appendChild(row);
                });
            }

            async clearCache() {
                try {
                    const response = await fetch('/admin/analytics/clear-cache', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        alert('Cache cleared successfully');
                        this.loadDashboardData();
                    }
                } catch (error) {
                    console.error('Failed to clear cache:', error);
                    alert('Failed to clear cache');
                }
            }

            showLoading() {
                document.getElementById('loadingIndicator').classList.remove('hidden');
                document.getElementById('dashboardContent').classList.add('opacity-50');
            }

            hideLoading() {
                document.getElementById('loadingIndicator').classList.add('hidden');
                document.getElementById('dashboardContent').classList.remove('opacity-50');
            }

            showError(message) {
                alert(message); // In a real app, use a proper notification system
            }
        }

        // Initialize dashboard when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new AnalyticsDashboard();
        });
    </script>
</x-app-layout>