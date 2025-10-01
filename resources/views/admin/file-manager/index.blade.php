<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('File Manager') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- File Manager Interface -->
                    <div id="file-manager" class="space-y-6">
                        <!-- Toolbar -->
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex flex-wrap items-center gap-4">
                                <!-- Upload Button -->
                                <button id="upload-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Upload Files
                                </button>
                                
                                <!-- Bulk Actions -->
                                <div class="flex items-center gap-2">
                                    <button id="bulk-delete-btn" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50" disabled>
                                        Delete Selected
                                    </button>
                                    <button id="bulk-move-btn" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50" disabled>
                                        Move Selected
                                    </button>
                                </div>
                            </div>

                            <!-- View Toggle -->
                            <div class="flex items-center gap-2">
                                <button id="grid-view-btn" class="p-2 rounded bg-blue-500 text-white">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                </button>
                                <button id="list-view-btn" class="p-2 rounded bg-gray-300 text-gray-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" id="search-input" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Search files...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Property</label>
                                <select id="property-filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Properties</option>
                                    @foreach($properties as $property)
                                        <option value="{{ $property->id }}">{{ $property->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mission</label>
                                <select id="mission-filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Missions</option>
                                    @foreach($missions as $mission)
                                        <option value="{{ $mission->id }}" data-property="{{ $mission->property_id }}">{{ $mission->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                                <select id="type-filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Types</option>
                                    <option value="image">Images</option>
                                    <option value="application/pdf">PDF Documents</option>
                                    <option value="video">Videos</option>
                                    <option value="text">Text Files</option>
                                </select>
                            </div>
                        </div>

                        <!-- File Grid/List -->
                        <div id="files-container" class="min-h-96">
                            <div id="files-grid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                <!-- Files will be loaded here -->
                            </div>
                            <div id="files-list" class="hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input type="checkbox" id="select-all" class="rounded border-gray-300">
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="files-table-body" class="bg-white divide-y divide-gray-200">
                                        <!-- Files will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div id="pagination" class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span id="showing-from">0</span> to <span id="showing-to">0</span> of <span id="total-files">0</span> files
                            </div>
                            <div class="flex items-center space-x-2">
                                <button id="prev-page" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50" disabled>
                                    Previous
                                </button>
                                <span id="page-info" class="px-3 py-2 text-sm font-medium text-gray-700">
                                    Page 1 of 1
                                </span>
                                <button id="next-page" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50" disabled>
                                    Next
                                </button>
                            </div>
                        </div>

                        <!-- Loading Indicator -->
                        <div id="loading" class="hidden text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                            <p class="mt-2 text-gray-600">Loading files...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Upload Files</h3>
                <form id="upload-form" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Files</label>
                        <input type="file" id="file-input" name="files[]" multiple class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Property</label>
                        <select name="property_id" id="upload-property" class="w-full rounded-md border-gray-300">
                            <option value="">Select Property</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}">{{ $property->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mission</label>
                        <select name="mission_id" id="upload-mission" class="w-full rounded-md border-gray-300">
                            <option value="">Select Mission</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" id="cancel-upload" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- File Details Modal -->
    <div id="details-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-2/3 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">File Details</h3>
                    <button id="close-details" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="file-details-content">
                    <!-- File details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // File Manager JavaScript
        class FileManager {
            constructor() {
                this.currentPage = 1;
                this.perPage = 20;
                this.currentView = 'grid';
                this.selectedFiles = new Set();
                this.filters = {};
                
                this.initializeEventListeners();
                this.loadFiles();
            }

            initializeEventListeners() {
                // View toggle
                document.getElementById('grid-view-btn').addEventListener('click', () => this.switchView('grid'));
                document.getElementById('list-view-btn').addEventListener('click', () => this.switchView('list'));
                
                // Upload modal
                document.getElementById('upload-btn').addEventListener('click', () => this.showUploadModal());
                document.getElementById('cancel-upload').addEventListener('click', () => this.hideUploadModal());
                document.getElementById('upload-form').addEventListener('submit', (e) => this.handleUpload(e));
                
                // Filters
                document.getElementById('search-input').addEventListener('input', (e) => this.handleSearch(e));
                document.getElementById('property-filter').addEventListener('change', (e) => this.handleFilter('property_id', e.target.value));
                document.getElementById('mission-filter').addEventListener('change', (e) => this.handleFilter('mission_id', e.target.value));
                document.getElementById('type-filter').addEventListener('change', (e) => this.handleFilter('mime_type', e.target.value));
                
                // Bulk actions
                document.getElementById('bulk-delete-btn').addEventListener('click', () => this.bulkDelete());
                document.getElementById('bulk-move-btn').addEventListener('click', () => this.bulkMove());
                
                // Pagination
                document.getElementById('prev-page').addEventListener('click', () => this.previousPage());
                document.getElementById('next-page').addEventListener('click', () => this.nextPage());
                
                // Select all
                document.getElementById('select-all').addEventListener('change', (e) => this.selectAll(e.target.checked));
                
                // Property change updates missions
                document.getElementById('property-filter').addEventListener('change', (e) => this.updateMissionFilter(e.target.value));
                document.getElementById('upload-property').addEventListener('change', (e) => this.updateUploadMissions(e.target.value));
            }

            async loadFiles() {
                this.showLoading();
                
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        per_page: this.perPage,
                        ...this.filters
                    });
                    
                    const response = await fetch(`/admin/file-manager/files?${params}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.renderFiles(data.data);
                        this.updatePagination(data.pagination);
                    }
                } catch (error) {
                    console.error('Error loading files:', error);
                } finally {
                    this.hideLoading();
                }
            }

            renderFiles(files) {
                if (this.currentView === 'grid') {
                    this.renderGridView(files);
                } else {
                    this.renderListView(files);
                }
            }

            renderGridView(files) {
                const container = document.getElementById('files-grid');
                container.innerHTML = '';
                
                files.forEach(file => {
                    const fileElement = this.createFileGridItem(file);
                    container.appendChild(fileElement);
                });
            }

            renderListView(files) {
                const tbody = document.getElementById('files-table-body');
                tbody.innerHTML = '';
                
                files.forEach(file => {
                    const row = this.createFileListItem(file);
                    tbody.appendChild(row);
                });
            }

            createFileGridItem(file) {
                const div = document.createElement('div');
                div.className = 'relative bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md cursor-pointer';
                div.dataset.fileId = file.id;
                
                const isImage = file.is_image;
                const thumbnail = isImage ? (file.metadata?.thumbnails?.medium?.url || file.url) : null;
                
                div.innerHTML = `
                    <div class="absolute top-2 left-2">
                        <input type="checkbox" class="file-checkbox rounded border-gray-300" data-file-id="${file.id}">
                    </div>
                    <div class="text-center">
                        ${isImage && thumbnail ? 
                            `<img src="${thumbnail}" alt="${file.original_name}" class="w-16 h-16 mx-auto object-cover rounded">` :
                            `<div class="w-16 h-16 mx-auto bg-gray-200 rounded flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                            </div>`
                        }
                        <p class="mt-2 text-sm font-medium text-gray-900 truncate">${file.original_name}</p>
                        <p class="text-xs text-gray-500">${file.size}</p>
                        <p class="text-xs text-gray-500">${file.property?.name || 'No Property'}</p>
                    </div>
                `;
                
                div.addEventListener('click', (e) => {
                    if (!e.target.classList.contains('file-checkbox')) {
                        this.showFileDetails(file.id);
                    }
                });
                
                div.querySelector('.file-checkbox').addEventListener('change', (e) => {
                    this.toggleFileSelection(file.id, e.target.checked);
                });
                
                return div;
            }

            createFileListItem(file) {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.dataset.fileId = file.id;
                
                tr.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="file-checkbox rounded border-gray-300" data-file-id="${file.id}">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            ${file.is_image ? 
                                `<img src="${file.metadata?.thumbnails?.small?.url || file.url}" alt="${file.original_name}" class="w-8 h-8 object-cover rounded mr-3">` :
                                `<div class="w-8 h-8 bg-gray-200 rounded mr-3 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>`
                            }
                            <div>
                                <div class="text-sm font-medium text-gray-900">${file.original_name}</div>
                                <div class="text-sm text-gray-500">${file.filename}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${file.size}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${file.mime_type}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${file.property?.name || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(file.created_at).toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button class="text-indigo-600 hover:text-indigo-900 mr-3" onclick="fileManager.showFileDetails(${file.id})">View</button>
                        <a href="/admin/file-manager/download/${file.id}" class="text-green-600 hover:text-green-900 mr-3">Download</a>
                        <button class="text-red-600 hover:text-red-900" onclick="fileManager.deleteFile(${file.id})">Delete</button>
                    </td>
                `;
                
                tr.querySelector('.file-checkbox').addEventListener('change', (e) => {
                    this.toggleFileSelection(file.id, e.target.checked);
                });
                
                return tr;
            }

            switchView(view) {
                this.currentView = view;
                
                if (view === 'grid') {
                    document.getElementById('files-grid').classList.remove('hidden');
                    document.getElementById('files-list').classList.add('hidden');
                    document.getElementById('grid-view-btn').classList.add('bg-blue-500', 'text-white');
                    document.getElementById('grid-view-btn').classList.remove('bg-gray-300', 'text-gray-700');
                    document.getElementById('list-view-btn').classList.add('bg-gray-300', 'text-gray-700');
                    document.getElementById('list-view-btn').classList.remove('bg-blue-500', 'text-white');
                } else {
                    document.getElementById('files-grid').classList.add('hidden');
                    document.getElementById('files-list').classList.remove('hidden');
                    document.getElementById('list-view-btn').classList.add('bg-blue-500', 'text-white');
                    document.getElementById('list-view-btn').classList.remove('bg-gray-300', 'text-gray-700');
                    document.getElementById('grid-view-btn').classList.add('bg-gray-300', 'text-gray-700');
                    document.getElementById('grid-view-btn').classList.remove('bg-blue-500', 'text-white');
                }
                
                this.loadFiles();
            }

            toggleFileSelection(fileId, selected) {
                if (selected) {
                    this.selectedFiles.add(fileId);
                } else {
                    this.selectedFiles.delete(fileId);
                }
                
                this.updateBulkActionButtons();
            }

            selectAll(selected) {
                const checkboxes = document.querySelectorAll('.file-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selected;
                    const fileId = parseInt(checkbox.dataset.fileId);
                    if (selected) {
                        this.selectedFiles.add(fileId);
                    } else {
                        this.selectedFiles.delete(fileId);
                    }
                });
                
                this.updateBulkActionButtons();
            }

            updateBulkActionButtons() {
                const hasSelection = this.selectedFiles.size > 0;
                document.getElementById('bulk-delete-btn').disabled = !hasSelection;
                document.getElementById('bulk-move-btn').disabled = !hasSelection;
            }

            handleSearch(e) {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.filters.search = e.target.value;
                    this.currentPage = 1;
                    this.loadFiles();
                }, 500);
            }

            handleFilter(key, value) {
                if (value) {
                    this.filters[key] = value;
                } else {
                    delete this.filters[key];
                }
                this.currentPage = 1;
                this.loadFiles();
            }

            updateMissionFilter(propertyId) {
                const missionSelect = document.getElementById('mission-filter');
                const missions = missionSelect.querySelectorAll('option[data-property]');
                
                missions.forEach(option => {
                    if (!propertyId || option.dataset.property === propertyId) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                missionSelect.value = '';
                delete this.filters.mission_id;
                this.loadFiles();
            }

            updateUploadMissions(propertyId) {
                const missionSelect = document.getElementById('upload-mission');
                const missions = @json($missions);
                
                missionSelect.innerHTML = '<option value="">Select Mission</option>';
                
                if (propertyId) {
                    missions.filter(m => m.property_id == propertyId).forEach(mission => {
                        const option = document.createElement('option');
                        option.value = mission.id;
                        option.textContent = mission.title;
                        missionSelect.appendChild(option);
                    });
                }
            }

            showUploadModal() {
                document.getElementById('upload-modal').classList.remove('hidden');
            }

            hideUploadModal() {
                document.getElementById('upload-modal').classList.add('hidden');
                document.getElementById('upload-form').reset();
            }

            async handleUpload(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const files = document.getElementById('file-input').files;
                
                if (files.length === 0) {
                    alert('Please select files to upload');
                    return;
                }
                
                try {
                    for (let i = 0; i < files.length; i++) {
                        const uploadData = new FormData();
                        uploadData.append('file', files[i]);
                        uploadData.append('property_id', formData.get('property_id'));
                        uploadData.append('mission_id', formData.get('mission_id'));
                        uploadData.append('_token', formData.get('_token'));
                        
                        const response = await fetch('/admin/file-manager/upload', {
                            method: 'POST',
                            body: uploadData
                        });
                        
                        const result = await response.json();
                        if (!result.success) {
                            throw new Error(result.message);
                        }
                    }
                    
                    this.hideUploadModal();
                    this.loadFiles();
                    alert('Files uploaded successfully');
                } catch (error) {
                    alert('Upload failed: ' + error.message);
                }
            }

            async showFileDetails(fileId) {
                try {
                    const response = await fetch(`/admin/file-manager/files/${fileId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.renderFileDetails(data.data);
                        document.getElementById('details-modal').classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error loading file details:', error);
                }
            }

            renderFileDetails(file) {
                const container = document.getElementById('file-details-content');
                
                container.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            ${file.is_image ? 
                                `<img src="${file.url}" alt="${file.original_name}" class="w-full h-64 object-cover rounded-lg">` :
                                `<div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>`
                            }
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-medium text-gray-900">File Information</h4>
                                <dl class="mt-2 space-y-1">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Name:</dt>
                                        <dd class="text-sm text-gray-900">${file.original_name}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Size:</dt>
                                        <dd class="text-sm text-gray-900">${file.size}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Type:</dt>
                                        <dd class="text-sm text-gray-900">${file.mime_type}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Property:</dt>
                                        <dd class="text-sm text-gray-900">${file.property?.name || 'N/A'}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Mission:</dt>
                                        <dd class="text-sm text-gray-900">${file.mission?.title || 'N/A'}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Uploaded by:</dt>
                                        <dd class="text-sm text-gray-900">${file.uploaded_by?.name || 'N/A'}</dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-500">Upload date:</dt>
                                        <dd class="text-sm text-gray-900">${new Date(file.created_at).toLocaleString()}</dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div class="flex space-x-3">
                                <a href="/admin/file-manager/download/${file.id}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Download
                                </a>
                                <button onclick="fileManager.deleteFile(${file.id})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('close-details').addEventListener('click', () => {
                    document.getElementById('details-modal').classList.add('hidden');
                });
            }

            async deleteFile(fileId) {
                if (!confirm('Are you sure you want to delete this file?')) {
                    return;
                }
                
                try {
                    const response = await fetch(`/admin/file-manager/files/${fileId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        this.loadFiles();
                        document.getElementById('details-modal').classList.add('hidden');
                        alert('File deleted successfully');
                    } else {
                        alert('Delete failed: ' + result.message);
                    }
                } catch (error) {
                    alert('Delete failed: ' + error.message);
                }
            }

            async bulkDelete() {
                if (this.selectedFiles.size === 0) return;
                
                if (!confirm(`Are you sure you want to delete ${this.selectedFiles.size} files?`)) {
                    return;
                }
                
                try {
                    const response = await fetch('/admin/file-manager/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            file_ids: Array.from(this.selectedFiles)
                        })
                    });
                    
                    const result = await response.json();
                    alert(result.message);
                    
                    this.selectedFiles.clear();
                    this.loadFiles();
                } catch (error) {
                    alert('Bulk delete failed: ' + error.message);
                }
            }

            async bulkMove() {
                // Implementation for bulk move would go here
                alert('Bulk move functionality not implemented yet');
            }

            updatePagination(pagination) {
                document.getElementById('showing-from').textContent = ((pagination.current_page - 1) * pagination.per_page) + 1;
                document.getElementById('showing-to').textContent = Math.min(pagination.current_page * pagination.per_page, pagination.total);
                document.getElementById('total-files').textContent = pagination.total;
                document.getElementById('page-info').textContent = `Page ${pagination.current_page} of ${pagination.last_page}`;
                
                document.getElementById('prev-page').disabled = pagination.current_page <= 1;
                document.getElementById('next-page').disabled = pagination.current_page >= pagination.last_page;
            }

            previousPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.loadFiles();
                }
            }

            nextPage() {
                this.currentPage++;
                this.loadFiles();
            }

            showLoading() {
                document.getElementById('loading').classList.remove('hidden');
                document.getElementById('files-container').classList.add('opacity-50');
            }

            hideLoading() {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('files-container').classList.remove('opacity-50');
            }
        }

        // Initialize file manager when page loads
        let fileManager;
        document.addEventListener('DOMContentLoaded', function() {
            fileManager = new FileManager();
        });
    </script>
    @endpush
</x-app-layout>