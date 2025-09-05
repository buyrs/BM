@extends('layouts.app')

@section('title', 'Create Contract Template')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold">Create New Contract Template</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Create a new contract template for mission contracts
                    </p>
                </div>

                <form action="{{ route('admin.contract-templates.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Template Name *
                            </label>
                            <input type="text" name="name" id="name" required
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value="{{ old('name') }}">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Template Type *
                            </label>
                            <select name="type" id="type" required
                                class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" {{ old('type') == '' ? 'selected' : '' }}>Select Type</option>
                                <option value="entry" {{ old('type') == 'entry' ? 'selected' : '' }}>Entry Contract</option>
                                <option value="exit" {{ old('type') == 'exit' ? 'selected' : '' }}>Exit Contract</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Template Content *
                        </label>
                        <textarea name="content" id="content" rows="12" required
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                            placeholder="Enter your contract template content here...

Available placeholders:
{{tenant_name}} - Tenant's full name
{{tenant_email}} - Tenant's email
{{tenant_phone}} - Tenant's phone number
{{address}} - Property address
{{start_date}} - Contract start date
{{end_date}} - Contract end date
{{admin_name}} - Admin name
{{admin_signature_date}} - Admin signature date">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea name="description" id="description" rows="3"
                            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional description of this template...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('admin.contract-templates.index') }}" 
                           class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-400 dark:hover:bg-gray-500">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            Create Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection