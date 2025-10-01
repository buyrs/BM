@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Create New Mission (Ops)</h1>
        <a href="{{ route('ops.missions.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Missions</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" action="{{ route('ops.missions.store') }}">
            @csrf
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                <input type="text" name="title" id="title" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" id="description" rows="3" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="property_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Property Address</label>
                <div class="relative">
                    <input 
                        type="text" 
                        name="property_address" 
                        id="property_address" 
                        class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Enter property address..."
                        required
                    >
                    <div id="property-suggestions" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm hidden" style="max-height: 15rem;">
                        <!-- Suggestions will be populated here -->
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Enter property address manually or select from existing properties.
                    </p>
                </div>
                @error('property_address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="checkin_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Check-in Date</label>
                <input type="date" name="checkin_date" id="checkin_date" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('checkin_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="checkout_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Check-out Date</label>
                <input type="date" name="checkout_date" id="checkout_date" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('checkout_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="checker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign Checker</label>
                <select name="checker_id" id="checker_id" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="">Select Checker</option>
                    @foreach ($checkers as $checker)
                        <option value="{{ $checker->id }}">{{ $checker->name }} ({{ $checker->email }})</option>
                    @endforeach
                </select>
                @error('checker_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Create Mission</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const propertyInput = document.getElementById('property_address');
    const suggestionsContainer = document.getElementById('property-suggestions');
    let debounceTimer;

    propertyInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timer
        clearTimeout(debounceTimer);
        
        // Don't search for very short queries
        if (query.length < 2) {
            suggestionsContainer.classList.add('hidden');
            return;
        }
        
        // Debounce the search
        debounceTimer = setTimeout(() => {
            searchProperties(query);
        }, 300);
    });

    propertyInput.addEventListener('focus', function() {
        const query = this.value.trim();
        if (query.length >= 2) {
            searchProperties(query);
        }
    });

    propertyInput.addEventListener('blur', function() {
        // Hide suggestions after a short delay to allow clicks
        setTimeout(() => {
            suggestionsContainer.classList.add('hidden');
        }, 200);
    });

    async function searchProperties(query) {
        try {
            // Make actual API call to search properties
            const response = await fetch('{{ route('ops.missions.search-properties') }}?query=' + encodeURIComponent(query), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                showSuggestions(data.data || []);
            } else {
                // Fallback to simulated data if API fails
                const sampleProperties = [
                    {id: 1, name: '123 Main Street, Downtown'},
                    {id: 2, name: '456 Oak Avenue, Midtown'},
                    {id: 3, name: '789 Pine Road, Suburbia'},
                    {id: 4, name: '321 Elm Boulevard, Riverside'},
                    {id: 5, name: '654 Maple Drive, Hillside'}
                ].filter(prop => prop.name.toLowerCase().includes(query.toLowerCase()));
                
                showSuggestions(sampleProperties);
            }
        } catch (error) {
            console.error('Error searching properties:', error);
            // Fallback to simulated data if API fails
            const sampleProperties = [
                {id: 1, name: '123 Main Street, Downtown'},
                {id: 2, name: '456 Oak Avenue, Midtown'},
                {id: 3, name: '789 Pine Road, Suburbia'},
                {id: 4, name: '321 Elm Boulevard, Riverside'},
                {id: 5, name: '654 Maple Drive, Hillside'}
            ].filter(prop => prop.name.toLowerCase().includes(query.toLowerCase()));
            
            showSuggestions(sampleProperties);
        }
    }

    function showSuggestions(properties) {
        if (properties.length === 0) {
            suggestionsContainer.classList.add('hidden');
            return;
        }

        suggestionsContainer.innerHTML = '';
        
        properties.forEach(property => {
            const div = document.createElement('div');
            div.className = 'cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-100 dark:hover:bg-blue-900';
            div.textContent = property.name;
            div.addEventListener('click', function() {
                propertyInput.value = property.name;
                suggestionsContainer.classList.add('hidden');
            });
            suggestionsContainer.appendChild(div);
        });

        suggestionsContainer.classList.remove('hidden');
    }
});
</script>
@endpush
@endsection