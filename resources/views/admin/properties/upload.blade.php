<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Upload Properties CSV') }}
            </h2>
            <a href="{{ route('admin.properties.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Properties
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Import Results -->
            @if (session('import_result'))
                @php $result = session('import_result'); @endphp
                <div class="bg-white border-l-4 @if($result['errors'] > 0) border-red-500 @else border-green-500 @endif rounded-lg shadow-md px-6 py-4 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if($result['errors'] > 0)
                                <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="font-semibold text-gray-900 mb-3 text-lg">Import Results</h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                                <div class="bg-blue-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-blue-600">{{ $result['total'] }}</p>
                                    <p class="text-xs text-blue-700 mt-1">Total Rows</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-green-600">{{ $result['created'] }}</p>
                                    <p class="text-xs text-green-700 mt-1">Created</p>
                                </div>
                                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-yellow-600">{{ $result['updated'] }}</p>
                                    <p class="text-xs text-yellow-700 mt-1">Updated</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-gray-600">{{ $result['skipped'] }}</p>
                                    <p class="text-xs text-gray-700 mt-1">Skipped</p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-red-600">{{ $result['errors'] }}</p>
                                    <p class="text-xs text-red-700 mt-1">Errors</p>
                                </div>
                            </div>
                            
                            @if($result['dry_run'])
                                <div class="bg-yellow-100 border border-yellow-300 rounded-lg px-4 py-3 mb-3">
                                    <p class="font-semibold text-yellow-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        This was a dry run - no data was actually saved.
                                    </p>
                                </div>
                            @endif
                            
                            @if (!empty($result['error_messages']))
                                <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-red-900 mb-2 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        Error Details
                                    </h4>
                                    <ul class="list-disc list-inside mt-2 space-y-1 text-sm max-h-60 overflow-y-auto">
                                        @foreach ($result['error_messages'] as $error)
                                            <li class="text-red-700">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Instructions -->
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-800 mb-2">Upload Instructions</h3>
                        <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                            <li>Upload a CSV file with property data</li>
                            <li>Required columns: <code>property_address</code></li>
                            <li>Optional columns: <code>owner_name</code>, <code>owner_address</code></li>
                            <li>Maximum file size: 10MB</li>
                            <li>Use "Dry Run" to test your file without importing data</li>
                            <li>Existing properties (same address) will be updated</li>
                        </ul>
                        <div class="mt-3">
                            <a href="{{ route('admin.properties.template') }}" 
                               class="text-blue-600 hover:text-blue-800 underline">
                                Download CSV Template
                            </a>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form action="{{ route('admin.properties.upload') }}" 
                          method="POST" 
                          enctype="multipart/form-data" 
                          class="space-y-6">
                        @csrf

                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700">CSV File *</label>
                            <input type="file" 
                                   name="file" 
                                   id="file" 
                                   accept=".csv,.txt"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="dry_run" 
                                   id="dry_run" 
                                   value="1"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="dry_run" class="ml-2 block text-sm text-gray-900">
                                Dry Run (validate file without importing data)
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.properties.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Upload CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>