@extends('layouts.app')

@section('title', 'View Contract Template')

@section('content')
<div class="py-12" x-data="{ copied: false }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ $template->name }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            {{ ucfirst($template->type) }} Contract Template
                        </p>
                        <div class="flex items-center mt-2 space-x-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                {{ $template->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($template->isSignedByAdmin())
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    Signed
                                </span>
                            @endif
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Created {{ $template->created_at->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        @if(!$template->isSignedByAdmin())
                            <a href="{{ route('admin.contract-templates.edit', $template) }}" 
                               class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('admin.contract-templates.index') }}" 
                           class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Info Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Template Information
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Name
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $template->name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Type
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ ucfirst($template->type) }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Status
                            </label>
                            <p class="mt-1">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                @if($template->isSignedByAdmin())
                                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        Signed
                                    </span>
                                @endif
                            </p>
                        </div>
                        
                        @if($template->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Description
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $template->description }}</p>
                        </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Created At
                            </label>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                {{ $template->created_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Updated At
                            </label>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                {{ $template->updated_at->format('M d, Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Content -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Template Content
                        </h3>
                        <button onclick="copyToClipboard()" 
                                class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm"
                                :class="{ 'bg-green-600': copied }">
                            <span x-show="!copied">Copy Content</span>
                            <span x-show="copied" x-transition>Copied!</span>
                        </button>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono">{{ $template->content }}</pre>
                    </div>
                    
                    <!-- Preview Button -->
                    <div class="mt-4">
                        <button onclick="previewTemplate()" 
                                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                            Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Template Usage
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg text-center">
                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Total Signatures</h4>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ $template->signatures()->count() }}</p>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg text-center">
                        <h4 class="text-sm font-medium text-green-800 dark:text-green-200">Created By</h4>
                        <p class="text-lg font-bold text-green-600 dark:text-green-300">{{ $template->creator->name }}</p>
                    </div>
                    
                    <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg text-center">
                        <h4 class="text-sm font-medium text-purple-800 dark:text-purple-200">Last Updated</h4>
                        <p class="text-lg font-bold text-purple-600 dark:text-purple-300">
                            {{ $template->updated_at->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const content = `{{ $template->content }}`;
    navigator.clipboard.writeText(content).then(() => {
        // Set copied state for Alpine.js
        if (typeof Alpine !== 'undefined') {
            Alpine.$data.copied = true;
            setTimeout(() => Alpine.$data.copied = false, 2000);
        }
        alert('Template content copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

function previewTemplate() {
    // This would open a modal or new page with the template preview
    // For now, just show an alert
    alert('Preview functionality would show the template with sample data');
}
</script>
@endsection