<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Advanced Search') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Global Search Bar -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="text" id="global-search" placeholder="Search across all data..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <button id="global-search-btn" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Search
                        </button>
                    </div>
                    <div id="global-results" class="mt-4 hidden"></div>
                </div>
            </div>

            <!-- Search Tabs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button class="search-tab border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="missions">
                            Mission Search
                        </button>
                        <button class="search-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="users">
                            User Search
                        </button>
                        <button class="search-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="properties">
                            Property Search
                        </button>
                        <button class="search-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="saved">
                            Saved Searches
                        </button>
                        <button class="search-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm" data-tab="analytics">
                            Analytics
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Mission Search Tab -->
                    <div id="missions-search" class="search-content">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                            <!-- Filters -->
                            <div class="lg:col-span-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Mission Filters</h3>
                                <form id="mission-search-form" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Search Text</label>
                                        <input type="text" name="search" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Title, description, address...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Status</label>
                                        <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">All Statuses</option>
                                            <option value="pending">Pending</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Date From</label>
                                        <input type="date" name="date_from" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Date To</label>
                                        <input type="date" name="date_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sort By</label>
                                        <select name="sort_by" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="created_at">Created Date</option>
                                            <option value="title">Title</option>
                                            <option value="status">Status</option>
                                            <option value="checkin_date">Check-in Date</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                                        <select name="sort_order" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="desc">Descending</option>
                                            <option value="asc">Ascending</option>
                                        </select>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            Search
                                        </button>
                                        <button type="button" class="save-search-btn bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" data-type="missions">
                                            Save
                                        </button>
                                    </div>
                                    <button type="button" class="export-btn w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500" data-type="missions">
                                        Export Results
                                    </button>
                                </form>
                            </div>

                            <!-- Results -->
                            <div class="lg:col-span-3">
                                <div id="mission-results" class="hidden">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                                        <div id="mission-stats" class="text-sm text-gray-600"></div>
                                    </div>
                                    <div id="mission-results-content"></div>
                                    <div id="mission-pagination" class="mt-4"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Search Tab -->
                    <div id="users-search" class="search-content hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                            <!-- Filters -->
                            <div class="lg:col-span-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">User Filters</h3>
                                <form id="user-search-form" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Search Text</label>
                                        <input type="text" name="search" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Name, email...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Role</label>
                                        <select name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">All Roles</option>
                                            <option value="admin">Admin</option>
                                            <option value="ops">Ops</option>
                                            <option value="checker">Checker</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Two-Factor Enabled</label>
                                        <select name="two_factor_enabled" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">All Users</option>
                                            <option value="true">Enabled</option>
                                            <option value="false">Disabled</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Sort By</label>
                                        <select name="sort_by" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="created_at">Created Date</option>
                                            <option value="name">Name</option>
                                            <option value="email">Email</option>
                                            <option value="last_login_at">Last Login</option>
                                        </select>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            Search
                                        </button>
                                        <button type="button" class="save-search-btn bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" data-type="users">
                                            Save
                                        </button>
                                    </div>
                                    <button type="button" class="export-btn w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500" data-type="users">
                                        Export Results
                                    </button>
                                </form>
                            </div>

                            <!-- Results -->
                            <div class="lg:col-span-3">
                                <div id="user-results" class="hidden">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                                        <div id="user-stats" class="text-sm text-gray-600"></div>
                                    </div>
                                    <div id="user-results-content"></div>
                                    <div id="user-pagination" class="mt-4"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Property Search Tab -->
                    <div id="properties-search" class="search-content hidden">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                            <!-- Filters -->
                            <div class="lg:col-span-1">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Property Filters</h3>
                                <form id="property-search-form" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Search Text</label>
                                        <input type="text" name="search" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Address, owner name...">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Property Type</label>
                                        <input type="text" name="property_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g., Apartment, House">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Owner Name</label>
                                        <input type="text" name="owner_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Has Missions</label>
                                        <select name="has_missions" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">All Properties</option>
                                            <option value="true">With Missions</option>
                                            <option value="false">Without Missions</option>
                                        </select>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            Search
                                        </button>
                                        <button type="button" class="save-search-btn bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" data-type="properties">
                                            Save
                                        </button>
                                    </div>
                                    <button type="button" class="export-btn w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500" data-type="properties">
                                        Export Results
                                    </button>
                                </form>
                            </div>

                            <!-- Results -->
                            <div class="lg:col-span-3">
                                <div id="property-results" class="hidden">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                                        <div id="property-stats" class="text-sm text-gray-600"></div>
                                    </div>
                                    <div id="property-results-content"></div>
                                    <div id="property-pagination" class="mt-4"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Saved Searches Tab -->
                    <div id="saved-search" class="search-content hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Saved Searches</h3>
                        <div id="saved-searches-content">
                            @if(count($savedSearches) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($savedSearches as $search)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <h4 class="font-medium text-gray-900">{{ $search['name'] }}</h4>
                                                <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">{{ ucfirst($search['type']) }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-3">
                                                Created: {{ \Carbon\Carbon::parse($search['created_at'])->format('M j, Y g:i A') }}
                                            </p>
                                            <div class="flex space-x-2">
                                                <button class="load-search-btn flex-1 bg-indigo-600 text-white py-1 px-3 rounded text-sm hover:bg-indigo-700" 
                                                        data-name="{{ $search['name'] }}" 
                                                        data-type="{{ $search['type'] }}" 
                                                        data-filters="{{ json_encode($search['filters']) }}">
                                                    Load
                                                </button>
                                                <button class="delete-search-btn bg-red-600 text-white py-1 px-3 rounded text-sm hover:bg-red-700" 
                                                        data-name="{{ $search['name'] }}" 
                                                        data-type="{{ $search['type'] }}">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No saved searches found.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    <div id="analytics-search" class="search-content hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Search Analytics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Total Searches</h4>
                                <p class="text-2xl font-bold text-indigo-600">{{ $analytics['total_searches'] ?? 0 }}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Most Searched Type</h4>
                                <p class="text-lg font-semibold text-green-600">
                                    @if(isset($analytics['searches_by_type']) && count($analytics['searches_by_type']) > 0)
                                        {{ ucfirst(array_keys($analytics['searches_by_type'], max($analytics['searches_by_type']))[0] ?? 'None') }}
                                    @else
                                        None
                                    @endif
                                </p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Popular Filters</h4>
                                <div class="space-y-1">
                                    @if(isset($analytics['popular_filters']) && count($analytics['popular_filters']) > 0)
                                        @foreach(array_slice($analytics['popular_filters'], 0, 3, true) as $filter => $count)
                                            <div class="text-sm">
                                                <span class="font-medium">{{ $filter }}</span>
                                                <span class="text-gray-500">({{ $count }})</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-sm text-gray-500">No data available</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Search Modal -->
    <div id="save-search-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Save Search</h3>
                <form id="save-search-form">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Search Name</label>
                        <input type="text" id="search-name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancel-save" class="bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentSearchType = 'missions';
        let currentFilters = {};

        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            setupTabs();
            
            // Global search
            setupGlobalSearch();
            
            // Search forms
            setupSearchForms();
            
            // Save search functionality
            setupSaveSearch();
            
            // Load saved searches
            setupLoadSearch();
            
            // Export functionality
            setupExport();
        });

        function setupTabs() {
            const tabButtons = document.querySelectorAll('.search-tab');
            const tabContents = document.querySelectorAll('.search-content');

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
                    const tabId = this.dataset.tab + '-search';
                    document.getElementById(tabId).classList.remove('hidden');
                    
                    currentSearchType = this.dataset.tab;
                });
            });
        }

        function setupGlobalSearch() {
            const searchInput = document.getElementById('global-search');
            const searchBtn = document.getElementById('global-search-btn');
            const resultsDiv = document.getElementById('global-results');

            function performGlobalSearch() {
                const query = searchInput.value.trim();
                if (query.length < 2) return;

                fetch('/admin/advanced-search/global', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ query: query })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayGlobalResults(data.data);
                    } else {
                        showError('Global search failed: ' + data.message);
                    }
                })
                .catch(error => {
                    showError('Global search error: ' + error.message);
                });
            }

            searchBtn.addEventListener('click', performGlobalSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performGlobalSearch();
                }
            });
        }

        function displayGlobalResults(data) {
            const resultsDiv = document.getElementById('global-results');
            
            if (data.results.length === 0) {
                resultsDiv.innerHTML = '<p class="text-gray-500">No results found.</p>';
                resultsDiv.classList.remove('hidden');
                return;
            }

            let html = '<div class="space-y-2">';
            data.results.forEach(result => {
                html += `
                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div>
                            <div class="font-medium text-gray-900">${result.title}</div>
                            <div class="text-sm text-gray-500">${result.subtitle}</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">${result.type}</span>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${result.status}</span>
                            <a href="${result.url}" class="text-indigo-600 hover:text-indigo-900 text-sm">View</a>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
        }

        function setupSearchForms() {
            ['mission', 'user', 'property'].forEach(type => {
                const form = document.getElementById(`${type}-search-form`);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        performSearch(type);
                    });
                }
            });
        }

        function performSearch(type) {
            const form = document.getElementById(`${type}-search-form`);
            const formData = new FormData(form);
            const filters = {};
            
            for (let [key, value] of formData.entries()) {
                if (value) filters[key] = value;
            }

            currentFilters = filters;

            fetch(`/admin/advanced-search/${type}s`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(filters)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySearchResults(type, data.data);
                } else {
                    showError(`${type} search failed: ` + data.message);
                }
            })
            .catch(error => {
                showError(`${type} search error: ` + error.message);
            });
        }

        function displaySearchResults(type, data) {
            const resultsDiv = document.getElementById(`${type}-results`);
            const contentDiv = document.getElementById(`${type}-results-content`);
            const statsDiv = document.getElementById(`${type}-stats`);
            const paginationDiv = document.getElementById(`${type}-pagination`);

            // Show stats
            if (data.search_stats) {
                statsDiv.innerHTML = `Found ${data.search_stats.total_found} results`;
            }

            // Display results
            if (data.data.length === 0) {
                contentDiv.innerHTML = '<p class="text-gray-500">No results found.</p>';
            } else {
                let html = '<div class="space-y-4">';
                data.data.forEach(item => {
                    html += formatResultItem(type, item);
                });
                html += '</div>';
                contentDiv.innerHTML = html;
            }

            // Display pagination
            if (data.pagination) {
                paginationDiv.innerHTML = formatPagination(data.pagination);
            }

            resultsDiv.classList.remove('hidden');
        }

        function formatResultItem(type, item) {
            switch(type) {
                case 'mission':
                    return `
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">${item.title}</h4>
                                    <p class="text-sm text-gray-600">${item.property_address}</p>
                                    <p class="text-xs text-gray-500">Checker: ${item.checker?.name || 'Unassigned'}</p>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${item.status}</span>
                            </div>
                        </div>
                    `;
                case 'user':
                    return `
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">${item.name}</h4>
                                    <p class="text-sm text-gray-600">${item.email}</p>
                                </div>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">${item.role}</span>
                            </div>
                        </div>
                    `;
                case 'property':
                    return `
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">${item.property_address}</h4>
                                    <p class="text-sm text-gray-600">Owner: ${item.owner_name}</p>
                                </div>
                                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">${item.property_type}</span>
                            </div>
                        </div>
                    `;
                default:
                    return '<div>Unknown item type</div>';
            }
        }

        function formatPagination(pagination) {
            let html = '<div class="flex justify-between items-center">';
            html += `<div class="text-sm text-gray-700">Showing ${pagination.from} to ${pagination.to} of ${pagination.total} results</div>`;
            html += '<div class="flex space-x-2">';
            
            if (pagination.current_page > 1) {
                html += '<button class="px-3 py-1 border border-gray-300 rounded text-sm">Previous</button>';
            }
            
            if (pagination.current_page < pagination.last_page) {
                html += '<button class="px-3 py-1 border border-gray-300 rounded text-sm">Next</button>';
            }
            
            html += '</div></div>';
            return html;
        }

        function setupSaveSearch() {
            const saveButtons = document.querySelectorAll('.save-search-btn');
            const modal = document.getElementById('save-search-modal');
            const form = document.getElementById('save-search-form');
            const cancelBtn = document.getElementById('cancel-save');

            saveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    currentSearchType = this.dataset.type;
                    modal.classList.remove('hidden');
                });
            });

            cancelBtn.addEventListener('click', function() {
                modal.classList.add('hidden');
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const name = document.getElementById('search-name').value;
                
                fetch('/admin/advanced-search/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        name: name,
                        type: currentSearchType,
                        filters: currentFilters
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Search saved successfully');
                        modal.classList.add('hidden');
                        form.reset();
                    } else {
                        showError('Failed to save search: ' + data.message);
                    }
                })
                .catch(error => {
                    showError('Save search error: ' + error.message);
                });
            });
        }

        function setupLoadSearch() {
            const loadButtons = document.querySelectorAll('.load-search-btn');
            const deleteButtons = document.querySelectorAll('.delete-search-btn');

            loadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.dataset.type;
                    const filters = JSON.parse(this.dataset.filters);
                    
                    // Switch to appropriate tab
                    const tabButton = document.querySelector(`[data-tab="${type}"]`);
                    if (tabButton) {
                        tabButton.click();
                    }
                    
                    // Load filters into form
                    const form = document.getElementById(`${type.slice(0, -1)}-search-form`);
                    if (form) {
                        Object.keys(filters).forEach(key => {
                            const input = form.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.value = filters[key];
                            }
                        });
                        
                        // Trigger search
                        form.dispatchEvent(new Event('submit'));
                    }
                });
            });

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this saved search?')) {
                        const name = this.dataset.name;
                        const type = this.dataset.type;
                        
                        fetch('/admin/advanced-search/saved/delete', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ name: name, type: type })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showSuccess('Search deleted successfully');
                                location.reload(); // Refresh to update saved searches
                            } else {
                                showError('Failed to delete search: ' + data.message);
                            }
                        })
                        .catch(error => {
                            showError('Delete search error: ' + error.message);
                        });
                    }
                });
            });
        }

        function setupExport() {
            const exportButtons = document.querySelectorAll('.export-btn');

            exportButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.dataset.type;
                    const format = prompt('Export format (csv or json):', 'csv');
                    
                    if (format && ['csv', 'json'].includes(format.toLowerCase())) {
                        fetch('/admin/advanced-search/export', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                type: type,
                                format: format.toLowerCase(),
                                filters: currentFilters
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showSuccess('Export completed. Download will start shortly.');
                                // Trigger download
                                window.location.href = data.data.download_url;
                            } else {
                                showError('Export failed: ' + data.message);
                            }
                        })
                        .catch(error => {
                            showError('Export error: ' + error.message);
                        });
                    }
                });
            });
        }

        function showSuccess(message) {
            // Simple success notification - you can enhance this
            alert('Success: ' + message);
        }

        function showError(message) {
            // Simple error notification - you can enhance this
            alert('Error: ' + message);
        }
    </script>
</x-app-layout>