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
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                    <h3 class="font-semibold mb-2">Import Results:</h3>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Total rows processed: {{ $result['total'] }}</li>
                        <li>Properties created: {{ $result['created'] }}</li>
                        <li>Properties updated: {{ $result['updated'] }}</li>
                        <li>Rows skipped: {{ $result['skipped'] }}</li>
                        <li>Errors: {{ $result['errors'] }}</li>
                        @if($result['dry_run'])
                            <li class="font-semibold">This was a dry run - no data was actually saved.</li>
                        @endif
                    </ul>
                    
                    @if (!empty($result['error_messages']))
                        <div class="mt-4">
                            <h4 class="font-semibold">Error Details:</h4>
                            <ul class="list-disc list-inside mt-2 space-y-1">
                                @foreach ($result['error_messages'] as $error)
                                    <li class="text-red-700">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
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