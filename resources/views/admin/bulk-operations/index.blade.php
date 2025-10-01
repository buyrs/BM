<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Bulk Operations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
                                <div class="text-sm font-medium text-gray-500">Total Missions</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_missions'] }}</div>
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
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Total Users</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] }}</div>
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
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Total Properties</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_properties'] }}</div>
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
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Pending Missions</div>
                                <div class="text-2xl font-bold text-gray-900">{{ $stats['pending_missions'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Operations Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="missions">
                            Mission Operations
                        </button>
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="users">
                            User Operations
                        </button>
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="properties">
                            Property Operations
                        </button>
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="notifications">
                            Notification Operations
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Mission Operations Tab -->
                    <div id="missions-tab" class="tab-content hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Mission Bulk Operations</h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Bulk Assignment -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">Bulk Assignment</h4>
                                <form id="bulk-assign-form">
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Mission IDs (comma-separated)</label>
                                            <input type="text" name="mission_ids" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="1,2,3">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Checker ID</label>
                                            <input type="number" name="checker_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Ops ID</label>
                                            <input type="number" name="ops_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        </div>
                                        <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            Assign Missions
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Bulk Status Update -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">Bulk Status Update</h4>
                                <form id="bulk-status-form">
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Mission IDs (comma-separated)</label>
                                            <input type="text" name="mission_ids" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="1,2,3">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Status</label>
                                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                <option value="">Select Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                            Update Status
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- User Operations Tab -->
                    <div id="users-tab" class="tab-content hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">User Bulk Operations</h3>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Bulk User Update</h4>
                            <form id="bulk-user-form">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">User IDs (comma-separated)</label>
                                        <input type="text" name="user_ids" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="1,2,3">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Role</label>
                                        <select name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Keep Current Role</option>
                                            <option value="admin">Admin</option>
                                            <option value="ops">Ops</option>
                                            <option value="checker">Checker</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Two-Factor Authentication</label>
                                        <select name="two_factor_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Keep Current Setting</option>
                                            <option value="1">Enable</option>
                                            <option value="0">Disable</option>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            Update Users
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Property Operations Tab -->
                    <div id="properties-tab" class="tab-content hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Property Bulk Operations</h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Bulk Import -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">Bulk Import Properties</h4>
                                <form id="bulk-import-form" enctype="multipart/form-data">
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">CSV File</label>
                                            <input type="file" name="file" accept=".csv" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <p class="mt-1 text-sm text-gray-500">CSV format: owner_name, owner_address, property_address, property_type, description</p>
                                        </div>
                                        <button type="submit" class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
                                            Import Properties
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Bulk Delete -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">Bulk Delete Properties</h4>
                                <form id="bulk-delete-form">
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Property IDs (comma-separated)</label>
                                            <input type="text" name="property_ids" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="1,2,3">
                                        </div>
                                        <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            Delete Properties
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Operations Tab -->
                    <div id="notifications-tab" class="tab-content hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Bulk Operations</h3>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Bulk Send Notifications</h4>
                            <form id="bulk-notification-form">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">User IDs (comma-separated)</label>
                                        <input type="text" name="user_ids" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="1,2,3">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Title</label>
                                        <input type="text" name="title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Message</label>
                                        <textarea name="message" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Type</label>
                                        <select name="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="info">Info</option>
                                            <option value="warning">Warning</option>
                                            <option value="error">Error</option>
                                            <option value="success">Success</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Channels</label>
                                        <div class="mt-2 space-y-2">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="channels[]" value="database" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-2">Database</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="channels[]" value="email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-2">Email</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="channels[]" value="websocket" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-2">WebSocket</span>
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="w-full bg-orange-600 text-white py-2 px-4 rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                        Send Notifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Display -->
            <div id="results-container" class="mt-6 hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Operation Results</h3>
                        <div id="results-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Show first tab by default
            if (tabButtons.length > 0) {
                tabButtons[0].classList.add('border-indigo-500', 'text-indigo-600');
                tabButtons[0].classList.remove('border-transparent', 'text-gray-500');
                document.getElementById(tabButtons[0].dataset.tab + '-tab').classList.remove('hidden');
            }

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active classes from all buttons
                    tabButtons.forEach(btn => {
                        btn.classList.remove('border-indigo-500', 'text-indigo-600');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    });

                    // Add active classes to clicked button
                    this.classList.add('border-indigo-500', 'text-indigo-600');
                    this.classList.remove('border-transparent', 'text-gray-500');

                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });

                    // Show selected tab content
                    document.getElementById(this.dataset.tab + '-tab').classList.remove('hidden');
                });
            });

            // Form submissions
            setupFormSubmission('bulk-assign-form', '/admin/bulk-operations/missions/assign');
            setupFormSubmission('bulk-status-form', '/admin/bulk-operations/missions/status');
            setupFormSubmission('bulk-user-form', '/admin/bulk-operations/users');
            setupFormSubmission('bulk-import-form', '/admin/bulk-operations/properties/import');
            setupFormSubmission('bulk-delete-form', '/admin/bulk-operations/properties/delete');
            setupFormSubmission('bulk-notification-form', '/admin/bulk-operations/notifications');
        });

        function setupFormSubmission(formId, url) {
            const form = document.getElementById(formId);
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = {};
                
                // Handle different form types
                if (formId === 'bulk-assign-form') {
                    data.mission_ids = formData.get('mission_ids').split(',').map(id => id.trim()).filter(id => id);
                    data.assignments = {};
                    if (formData.get('checker_id')) data.assignments.checker_id = formData.get('checker_id');
                    if (formData.get('ops_id')) data.assignments.ops_id = formData.get('ops_id');
                } else if (formId === 'bulk-status-form') {
                    data.mission_ids = formData.get('mission_ids').split(',').map(id => id.trim()).filter(id => id);
                    data.status = formData.get('status');
                } else if (formId === 'bulk-user-form') {
                    data.user_ids = formData.get('user_ids').split(',').map(id => id.trim()).filter(id => id);
                    data.updates = {};
                    if (formData.get('role')) data.updates.role = formData.get('role');
                    if (formData.get('two_factor_enabled')) data.updates.two_factor_enabled = formData.get('two_factor_enabled') === '1';
                } else if (formId === 'bulk-delete-form') {
                    data.property_ids = formData.get('property_ids').split(',').map(id => id.trim()).filter(id => id);
                } else if (formId === 'bulk-notification-form') {
                    data.user_ids = formData.get('user_ids').split(',').map(id => id.trim()).filter(id => id);
                    data.notification_data = {
                        title: formData.get('title'),
                        message: formData.get('message'),
                        type: formData.get('type'),
                        channels: formData.getAll('channels[]')
                    };
                }

                // Handle file upload for import
                if (formId === 'bulk-import-form') {
                    submitFormData(url, formData);
                } else {
                    submitJsonData(url, data);
                }
            });
        }

        function submitJsonData(url, data) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                showResults(result);
            })
            .catch(error => {
                showResults({
                    success: false,
                    message: 'An error occurred: ' + error.message
                });
            });
        }

        function submitFormData(url, formData) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                showResults(result);
            })
            .catch(error => {
                showResults({
                    success: false,
                    message: 'An error occurred: ' + error.message
                });
            });
        }

        function showResults(result) {
            const container = document.getElementById('results-container');
            const content = document.getElementById('results-content');
            
            let html = `<div class="p-4 rounded-md ${result.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}">`;
            html += `<div class="flex">`;
            html += `<div class="flex-shrink-0">`;
            html += result.success 
                ? `<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>`
                : `<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>`;
            html += `</div>`;
            html += `<div class="ml-3">`;
            html += `<h3 class="text-sm font-medium ${result.success ? 'text-green-800' : 'text-red-800'}">${result.success ? 'Success' : 'Error'}</h3>`;
            html += `<div class="mt-2 text-sm ${result.success ? 'text-green-700' : 'text-red-700'}">`;
            html += `<p>${result.message}</p>`;
            
            if (result.results && result.results.errors && result.results.errors.length > 0) {
                html += `<div class="mt-2"><strong>Errors:</strong><ul class="list-disc list-inside">`;
                result.results.errors.forEach(error => {
                    html += `<li>${error}</li>`;
                });
                html += `</ul></div>`;
            }
            
            html += `</div></div></div></div>`;
            
            content.innerHTML = html;
            container.classList.remove('hidden');
            
            // Scroll to results
            container.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</x-app-layout>