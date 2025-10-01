<x-modern-layout>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create New Mission</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Define mission details and assign a checker</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('ops.missions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Missions
                </a>
            </div>
        </div>

        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="rounded-md bg-red-50 dark:bg-red-900/50 p-4 border border-red-200 dark:border-red-800">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">There were errors with your submission:</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <x-card>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Mission Details</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the required information below.</p>
            </div>

            <form method="POST" action="{{ route('ops.missions.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-300 dark:border-red-600 @enderror" placeholder="e.g. Check-in Inspection for Apt 12B" required>
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="checker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assign Checker <span class="text-red-500">*</span></label>
                        <select name="checker_id" id="checker_id" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('checker_id') border-red-300 dark:border-red-600 @enderror" required>
                            <option value="">Select Checker</option>
                            @foreach ($checkers as $checker)
                                <option value="{{ $checker->id }}" @selected(old('checker_id')==$checker->id)>{{ $checker->name }} ({{ $checker->email }})</option>
                            @endforeach
                        </select>
                        @error('checker_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optional notes for this mission">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Property Address with suggestions -->
                <div>
                    <label for="property_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Property Address <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="property_address" id="property_address" value="{{ old('property_address') }}" class="block w-full px-3 py-2.5 pr-28 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('property_address') border-red-300 dark:border-red-600 @enderror" placeholder="Start typing an address..." required autocomplete="off">
                        <div id="property-suggestions" class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden" style="max-height: 20rem;"></div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <div id="address-help-google" style="display: none;">Using Google Places for address suggestions</div>
                            <div id="address-help-existing">Searching existing properties by address, code, or owner</div>
                        </div>
                    </div>
                    @error('property_address')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="checkin_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Check-in Date <span class="text-red-500">*</span></label>
                        <input type="date" name="checkin_date" id="checkin_date" value="{{ old('checkin_date') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('checkin_date') border-red-300 dark:border-red-600 @enderror" required>
                        @error('checkin_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="checkout_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Check-out Date <span class="text-red-500">*</span></label>
                        <input type="date" name="checkout_date" id="checkout_date" value="{{ old('checkout_date') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('checkout_date') border-red-300 dark:border-red-600 @enderror" required>
                        @error('checkout_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3 pt-2">
                    <a href="{{ route('ops.missions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</a>
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Mission
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-modern-layout>

@push('scripts')
<!-- Google Places API -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places&callback=initAutocomplete"></script>

<script>
let autocomplete;
let existingPropertiesMode = false;

// Initialize Google Places Autocomplete
function initAutocomplete() {
    const propertyInput = document.getElementById('property_address');
    const suggestionsContainer = document.getElementById('property-suggestions');
    
    if (!propertyInput) return;
    
    // Check if Google Places API key is configured
    const hasGoogleAPI = '{{ config("services.google.places_api_key") }}' !== '';
    
    if (hasGoogleAPI) {
        // Initialize Google Places Autocomplete
        autocomplete = new google.maps.places.Autocomplete(propertyInput, {
            types: ['address'],
            fields: ['formatted_address', 'geometry', 'name']
        });
        
        // Handle place selection
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (place.formatted_address) {
                propertyInput.value = place.formatted_address;
            }
        });
    }
    
    // Add toggle button for existing properties search
    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.className = 'absolute right-2 top-2 px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-600 dark:text-gray-300 rounded';
    toggleButton.textContent = 'Search Existing';
    toggleButton.title = 'Search existing properties in database';
    
    // Insert toggle button
    const inputContainer = propertyInput.parentElement;
    inputContainer.style.position = 'relative';
    inputContainer.appendChild(toggleButton);
    
    // Toggle between Google Places and existing properties
    toggleButton.addEventListener('click', function() {
        existingPropertiesMode = !existingPropertiesMode;
        updateUI();
        
        if (existingPropertiesMode) {
            if (hasGoogleAPI && autocomplete) {
                // Disable Google Places temporarily
                google.maps.event.clearListeners(autocomplete, 'place_changed');
            }
            initExistingPropertiesSearch();
        } else {
            suggestionsContainer.classList.add('hidden');
            if (hasGoogleAPI && autocomplete) {
                // Re-enable Google Places
                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();
                    if (place.formatted_address) {
                        propertyInput.value = place.formatted_address;
                    }
                });
            }
        }
    });
    
    function updateUI() {
        const helpGoogle = document.getElementById('address-help-google');
        const helpExisting = document.getElementById('address-help-existing');
        
        if (existingPropertiesMode) {
            toggleButton.textContent = 'Use Google';
            toggleButton.title = 'Use Google Places for address search';
            toggleButton.className = 'absolute right-2 top-2 px-2 py-1 text-xs bg-green-100 hover:bg-green-200 dark:bg-green-600 dark:hover:bg-green-500 text-green-700 dark:text-green-200 rounded';
            if (helpGoogle) helpGoogle.style.display = 'none';
            if (helpExisting) helpExisting.style.display = 'block';
            propertyInput.placeholder = 'Search existing properties by address, code, or owner...';
        } else {
            toggleButton.textContent = 'Search Existing';
            toggleButton.title = 'Search existing properties in database';
            toggleButton.className = 'absolute right-2 top-2 px-2 py-1 text-xs bg-blue-100 hover:bg-blue-200 dark:bg-blue-600 dark:hover:bg-blue-500 text-blue-700 dark:text-blue-200 rounded';
            if (helpGoogle) helpGoogle.style.display = 'block';
            if (helpExisting) helpExisting.style.display = 'none';
            propertyInput.placeholder = 'Start typing an address...';
        }
    }
    
    // If no Google API key, default to existing properties mode
    if (!hasGoogleAPI) {
        existingPropertiesMode = true;
        toggleButton.textContent = 'Google API Not Configured';
        toggleButton.disabled = true;
        initExistingPropertiesSearch();
    }
}

// Initialize existing properties search
function initExistingPropertiesSearch() {
    const propertyInput = document.getElementById('property_address');
    const suggestionsContainer = document.getElementById('property-suggestions');
    let debounceTimer;

    const inputHandler = function() {
        const query = this.value.trim();
        
        clearTimeout(debounceTimer);
        
        if (query.length < 1) {
            suggestionsContainer.classList.add('hidden');
            return;
        }
        
        debounceTimer = setTimeout(() => {
            searchExistingProperties(query);
        }, 300);
    };

    const focusHandler = function() {
        if (!existingPropertiesMode) return;
        const query = this.value.trim();
        if (query.length >= 1) {
            searchExistingProperties(query);
        }
    };

    const blurHandler = function() {
        setTimeout(() => {
            suggestionsContainer.classList.add('hidden');
        }, 200);
    };

    // Remove existing listeners and add new ones
    propertyInput.removeEventListener('input', inputHandler);
    propertyInput.removeEventListener('focus', focusHandler);
    propertyInput.removeEventListener('blur', blurHandler);
    
    propertyInput.addEventListener('input', inputHandler);
    propertyInput.addEventListener('focus', focusHandler);
    propertyInput.addEventListener('blur', blurHandler);

    async function searchExistingProperties(query) {
        if (!existingPropertiesMode) return;
        
        try {
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
                showExistingPropertySuggestions(data.data || []);
            }
        } catch (error) {
            console.error('Error searching existing properties:', error);
        }
    }

    function showExistingPropertySuggestions(properties) {
        if (!existingPropertiesMode || properties.length === 0) {
            suggestionsContainer.classList.add('hidden');
            return;
        }

        suggestionsContainer.innerHTML = '';
        
        // Add header
        const header = document.createElement('div');
        header.className = 'px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-600';
        header.textContent = 'Existing Properties';
        suggestionsContainer.appendChild(header);
        
        properties.forEach(property => {
            const div = document.createElement('div');
            div.className = 'cursor-pointer select-none relative px-3 py-2 hover:bg-blue-100 dark:hover:bg-blue-900';
            
            // Create structured display
            const nameDiv = document.createElement('div');
            nameDiv.className = 'text-sm font-medium text-gray-900 dark:text-white';
            nameDiv.textContent = property.name;
            
            const addressDiv = document.createElement('div');
            addressDiv.className = 'text-xs text-gray-500 dark:text-gray-400 mt-1';
            addressDiv.textContent = property.address;
            
            div.appendChild(nameDiv);
            if (property.address !== property.name) {
                div.appendChild(addressDiv);
            }
            
            div.addEventListener('click', function() {
                propertyInput.value = property.address; // Use the actual address
                suggestionsContainer.classList.add('hidden');
            });
            
            suggestionsContainer.appendChild(div);
        });

        suggestionsContainer.classList.remove('hidden');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // If Google API is not loaded yet, wait for it
    if (typeof google === 'undefined') {
        // Fallback to existing properties only
        existingPropertiesMode = true;
        initExistingPropertiesSearch();
    }
});
</script>
@endpush
