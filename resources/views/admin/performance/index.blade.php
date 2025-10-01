<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Performance Monitoring') }}
            </h2>
            <div class="flex space-x-2">
                <button id="refresh-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Refresh
                </button>
                <button id="export-btn" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Export Report
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- System Health Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">System Health</h3>
                    <div id="system-health" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Health checks will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- HTTP Requests -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">HTTP Requests (24h)</h3>
                        <div id="http-metrics">
                            <!-- HTTP metrics will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Database Performance -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Database Performance (24h)</h3>
                        <div id="database-metrics">
                            <!-- Database metrics will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache and Queue Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Cache Operations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Cache Operations (24h)</h3>
                        <div id="cache-metrics">
                            <!-- Cache metrics will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Queue Jobs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-semibold mb-4">Queue Jobs (24h)</h3>
                        <div id="queue-metrics">
                            <!-- Queue metrics will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Analysis -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Database Query Analysis</h3>
                    <div id="database-analysis">
                        <!-- Database analysis will be populated here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let refreshInterval;

        document.addEventListener('DOMContentLoaded', function() {
            loadPerformanceData();
            startAutoRefresh();

            document.getElementById('refresh-btn').addEventListener('click', loadPerformanceData);
            document.getElementById('export-btn').addEventListener('click', exportReport);
        });

        function loadPerformanceData() {
            Promise.all([
                fetch('/admin/performance/health').then(r => r.json()),
                fetch('/admin/performance/metrics').then(r => r.json()),
                fetch('/admin/performance/database-analysis').then(r => r.json())
            ]).then(([health, metrics, dbAnalysis]) => {
                updateSystemHealth(health);
                updateHttpMetrics(metrics.http_requests);
                updateDatabaseMetrics(metrics.database_queries);
                updateCacheMetrics(metrics.cache_operations);
                updateQueueMetrics(metrics.queue_jobs);
                updateDatabaseAnalysis(dbAnalysis);
            }).catch(error => {
                console.error('Error loading performance data:', error);
            });
        }

        function updateSystemHealth(health) {
            const container = document.getElementById('system-health');
            container.innerHTML = '';

            Object.entries(health.checks).forEach(([component, check]) => {
                const statusColor = getStatusColor(check.status);
                const card = document.createElement('div');
                card.className = `p-4 rounded-lg border-l-4 ${statusColor.border} ${statusColor.bg}`;
                card.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 rounded-full ${statusColor.dot}"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 capitalize">${component}</p>
                            <p class="text-xs text-gray-500">${check.message || check.status}</p>
                            ${check.response_time ? `<p class="text-xs text-gray-400">${check.response_time}ms</p>` : ''}
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function updateHttpMetrics(metrics) {
            const container = document.getElementById('http-metrics');
            if (!metrics || metrics.total_requests === 0) {
                container.innerHTML = '<p class="text-gray-500">No HTTP request data available</p>';
                return;
            }

            container.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${metrics.total_requests.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Total Requests</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${metrics.avg_response_time}ms</div>
                        <div class="text-sm text-gray-500">Avg Response Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">${metrics.max_response_time}ms</div>
                        <div class="text-sm text-gray-500">Max Response Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">${formatBytes(metrics.avg_memory_usage)}</div>
                        <div class="text-sm text-gray-500">Avg Memory Usage</div>
                    </div>
                </div>
                ${metrics.status_codes ? `
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Status Codes</h4>
                        <div class="flex flex-wrap gap-2">
                            ${Object.entries(metrics.status_codes).map(([code, count]) => 
                                `<span class="px-2 py-1 text-xs rounded ${getStatusCodeColor(code)}">${code}: ${count}</span>`
                            ).join('')}
                        </div>
                    </div>
                ` : ''}
            `;
        }

        function updateDatabaseMetrics(metrics) {
            const container = document.getElementById('database-metrics');
            if (!metrics || metrics.total_queries === 0) {
                container.innerHTML = '<p class="text-gray-500">No database query data available</p>';
                return;
            }

            container.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${metrics.total_queries.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Total Queries</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${metrics.avg_execution_time}ms</div>
                        <div class="text-sm text-gray-500">Avg Execution Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">${metrics.max_execution_time}ms</div>
                        <div class="text-sm text-gray-500">Max Execution Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold ${metrics.slow_queries > 0 ? 'text-red-600' : 'text-green-600'}">${metrics.slow_queries}</div>
                        <div class="text-sm text-gray-500">Slow Queries</div>
                    </div>
                </div>
            `;
        }

        function updateCacheMetrics(metrics) {
            const container = document.getElementById('cache-metrics');
            if (!metrics || metrics.total_operations === 0) {
                container.innerHTML = '<p class="text-gray-500">No cache operation data available</p>';
                return;
            }

            container.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${metrics.total_operations.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Total Operations</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${metrics.hit_rate}%</div>
                        <div class="text-sm text-gray-500">Hit Rate</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${metrics.total_hits.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Cache Hits</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">${metrics.total_misses.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Cache Misses</div>
                    </div>
                </div>
            `;
        }

        function updateQueueMetrics(metrics) {
            const container = document.getElementById('queue-metrics');
            if (!metrics || metrics.total_jobs === 0) {
                container.innerHTML = '<p class="text-gray-500">No queue job data available</p>';
                return;
            }

            container.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${metrics.total_jobs.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Total Jobs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${metrics.success_rate}%</div>
                        <div class="text-sm text-gray-500">Success Rate</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">${metrics.completed_jobs.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Completed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">${metrics.failed_jobs.toLocaleString()}</div>
                        <div class="text-sm text-gray-500">Failed</div>
                    </div>
                </div>
            `;
        }

        function updateDatabaseAnalysis(analysis) {
            const container = document.getElementById('database-analysis');
            
            if (analysis.total_slow_queries === 0) {
                container.innerHTML = '<p class="text-green-600">No slow queries detected in the monitoring period.</p>';
                return;
            }

            let html = `
                <div class="mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600">${analysis.total_slow_queries}</div>
                            <div class="text-sm text-gray-500">Slow Queries</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">${analysis.average_execution_time}ms</div>
                            <div class="text-sm text-gray-500">Avg Execution Time</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">${analysis.suggestions.length}</div>
                            <div class="text-sm text-gray-500">Optimization Suggestions</div>
                        </div>
                    </div>
                </div>
            `;

            if (analysis.suggestions.length > 0) {
                html += `
                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Optimization Suggestions</h4>
                        <div class="space-y-2">
                            ${analysis.suggestions.slice(0, 5).map(suggestion => `
                                <div class="p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                                    <p class="text-sm text-yellow-800">${suggestion.message}</p>
                                    <p class="text-xs text-yellow-600 mt-1">Type: ${suggestion.type}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        function getStatusColor(status) {
            switch (status) {
                case 'healthy':
                    return {
                        border: 'border-green-400',
                        bg: 'bg-green-50',
                        dot: 'bg-green-400'
                    };
                case 'warning':
                    return {
                        border: 'border-yellow-400',
                        bg: 'bg-yellow-50',
                        dot: 'bg-yellow-400'
                    };
                case 'critical':
                    return {
                        border: 'border-red-400',
                        bg: 'bg-red-50',
                        dot: 'bg-red-400'
                    };
                default:
                    return {
                        border: 'border-gray-400',
                        bg: 'bg-gray-50',
                        dot: 'bg-gray-400'
                    };
            }
        }

        function getStatusCodeColor(code) {
            if (code.startsWith('2')) return 'bg-green-100 text-green-800';
            if (code.startsWith('3')) return 'bg-blue-100 text-blue-800';
            if (code.startsWith('4')) return 'bg-yellow-100 text-yellow-800';
            if (code.startsWith('5')) return 'bg-red-100 text-red-800';
            return 'bg-gray-100 text-gray-800';
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function startAutoRefresh() {
            refreshInterval = setInterval(loadPerformanceData, 30000); // Refresh every 30 seconds
        }

        function exportReport() {
            const hours = prompt('Enter number of hours for the report (1-168):', '24');
            if (hours && hours >= 1 && hours <= 168) {
                window.open(`/admin/performance/export?hours=${hours}&format=json`, '_blank');
            }
        }

        // Clean up interval when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</x-app-layout>