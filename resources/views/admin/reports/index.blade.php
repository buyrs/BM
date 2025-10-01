<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Reports') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Report Generation -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Generate New Report</h3>
                        
                        <form id="reportForm" class="space-y-4">
                            <div>
                                <label for="reportType" class="block text-sm font-medium text-gray-700">Report Type</label>
                                <select id="reportType" name="report_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Report Type</option>
                                    <option value="analytics">Analytics Report</option>
                                    <option value="missions">Mission Report</option>
                                    <option value="user_performance">User Performance Report</option>
                                    <option value="maintenance">Maintenance Report</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" id="startDate" name="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" id="endDate" name="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </div>
                            </div>

                            <div>
                                <label for="format" class="block text-sm font-medium text-gray-700">Format</label>
                                <select id="format" name="format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="pdf">PDF</option>
                                    <option value="excel">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>

                            <!-- Dynamic filters will be populated here -->
                            <div id="dynamicFilters"></div>

                            <div class="flex justify-end">
                                <button type="submit" id="generateBtn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Generated Reports -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Generated Reports</h3>
                            <button id="refreshReports" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Refresh
                            </button>
                        </div>
                        
                        <div id="reportsList" class="space-y-2">
                            <!-- Reports will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading Modal -->
            <div id="loadingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 mx-auto"></div>
                        <h3 class="text-lg font-medium text-gray-900 mt-4">Generating Report</h3>
                        <p class="text-sm text-gray-500 mt-2">Please wait while your report is being generated...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        class ReportsManager {
            constructor() {
                this.reportTypes = {};
                this.init();
            }

            async init() {
                await this.loadReportTypes();
                this.bindEvents();
                this.loadReports();
                this.setDefaultDates();
            }

            bindEvents() {
                document.getElementById('reportType').addEventListener('change', (e) => {
                    this.updateDynamicFilters(e.target.value);
                });

                document.getElementById('reportForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.generateReport();
                });

                document.getElementById('refreshReports').addEventListener('click', () => {
                    this.loadReports();
                });
            }

            setDefaultDates() {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - 30);

                document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
                document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
            }

            async loadReportTypes() {
                try {
                    const response = await fetch('/admin/reports/types');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.reportTypes = result.data;
                    }
                } catch (error) {
                    console.error('Failed to load report types:', error);
                }
            }

            updateDynamicFilters(reportType) {
                const container = document.getElementById('dynamicFilters');
                container.innerHTML = '';

                if (!reportType || !this.reportTypes[reportType]) {
                    return;
                }

                const config = this.reportTypes[reportType];

                // Add sections for analytics report
                if (reportType === 'analytics' && config.sections) {
                    const sectionsDiv = document.createElement('div');
                    sectionsDiv.innerHTML = `
                        <label class="block text-sm font-medium text-gray-700 mb-2">Report Sections</label>
                        <div class="space-y-2">
                            ${Object.entries(config.sections).map(([key, description]) => `
                                <label class="flex items-center">
                                    <input type="checkbox" name="sections[]" value="${key}" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-600">${description}</span>
                                </label>
                            `).join('')}
                        </div>
                    `;
                    container.appendChild(sectionsDiv);
                }

                // Add filters for other report types
                if (config.filters) {
                    Object.entries(config.filters).forEach(([filterName, filterOptions]) => {
                        const filterDiv = document.createElement('div');
                        
                        if (Array.isArray(filterOptions)) {
                            filterDiv.innerHTML = `
                                <label for="${filterName}" class="block text-sm font-medium text-gray-700">${this.formatLabel(filterName)}</label>
                                <select id="${filterName}" name="${filterName}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">All</option>
                                    ${filterOptions.map(option => `<option value="${option}">${this.formatLabel(option)}</option>`).join('')}
                                </select>
                            `;
                        } else {
                            filterDiv.innerHTML = `
                                <label for="${filterName}" class="block text-sm font-medium text-gray-700">${this.formatLabel(filterName)}</label>
                                <input type="text" id="${filterName}" name="${filterName}" placeholder="${filterOptions}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            `;
                        }
                        
                        container.appendChild(filterDiv);
                    });
                }
            }

            formatLabel(text) {
                return text.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }

            async generateReport() {
                const formData = new FormData(document.getElementById('reportForm'));
                const reportType = formData.get('report_type');
                
                if (!reportType) {
                    alert('Please select a report type');
                    return;
                }

                this.showLoading();

                try {
                    const data = {};
                    for (let [key, value] of formData.entries()) {
                        if (key === 'sections[]') {
                            if (!data.sections) data.sections = [];
                            data.sections.push(value);
                        } else {
                            data[key] = value;
                        }
                    }

                    const response = await fetch(`/admin/reports/generate/${reportType}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('Report generated successfully!');
                        this.loadReports();
                        
                        // Auto-download the report
                        if (result.data.download_url) {
                            window.open(result.data.download_url, '_blank');
                        }
                    } else {
                        alert('Failed to generate report: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error generating report:', error);
                    alert('Failed to generate report');
                } finally {
                    this.hideLoading();
                }
            }

            async loadReports() {
                try {
                    const response = await fetch('/admin/reports');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.displayReports(result.data);
                    }
                } catch (error) {
                    console.error('Failed to load reports:', error);
                }
            }

            displayReports(reports) {
                const container = document.getElementById('reportsList');
                
                if (reports.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center">No reports generated yet</p>';
                    return;
                }

                container.innerHTML = reports.map(report => `
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">${report.filename}</p>
                            <p class="text-sm text-gray-500">${report.size_formatted} • ${report.created_at}</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="${report.download_url}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Download
                            </a>
                            <button onclick="reportsManager.deleteReport('${report.filename}')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Delete
                            </button>
                        </div>
                    </div>
                `).join('');
            }

            async deleteReport(filename) {
                if (!confirm('Are you sure you want to delete this report?')) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/reports/${filename}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.loadReports();
                    } else {
                        alert('Failed to delete report: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error deleting report:', error);
                    alert('Failed to delete report');
                }
            }

            showLoading() {
                document.getElementById('loadingModal').classList.remove('hidden');
            }

            hideLoading() {
                document.getElementById('loadingModal').classList.add('hidden');
            }
        }

        // Initialize when DOM is loaded
        let reportsManager;
        document.addEventListener('DOMContentLoaded', () => {
            reportsManager = new ReportsManager();
        });
    </script>
</x-app-layout>