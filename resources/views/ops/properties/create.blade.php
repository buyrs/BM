<x-modern-layout>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create New Property</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a new property to the system</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('ops.properties.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Properties
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Property Information</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the required information below.</p>
            </div>

            <form method="POST" action="{{ route('ops.properties.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Basic Property Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="internal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Internal Code</label>
                        <input type="text" name="internal_code" id="internal_code" value="{{ old('internal_code') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('internal_code') border-red-300 dark:border-red-600 @enderror" placeholder="e.g., Rosier, MainSt, etc.">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional short code to identify this property (letters, numbers, dash, underscore only)</p>
                        @error('internal_code')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="property_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Property Type <span class="text-red-500">*</span></label>
                        <select name="property_type" id="property_type" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('property_type') border-red-300 dark:border-red-600 @enderror" required>
                            <option value="">Select Property Type</option>
                            <option value="apartment" @selected(old('property_type')==='apartment')>Apartment</option>
                            <option value="house" @selected(old('property_type')==='house')>House</option>
                            <option value="condo" @selected(old('property_type')==='condo')>Condo</option>
                            <option value="townhouse" @selected(old('property_type')==='townhouse')>Townhouse</option>
                            <option value="studio" @selected(old('property_type')==='studio')>Studio</option>
                            <option value="commercial" @selected(old('property_type')==='commercial')>Commercial</option>
                        </select>
                        @error('property_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Property Address -->
                <div>
                    <label for="property_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Property Address <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" name="property_address" id="property_address" value="{{ old('property_address') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('property_address') border-red-300 dark:border-red-600 @enderror" placeholder="Start typing an address..." required autocomplete="off">
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <div id="address-help-google">Google Places will provide address suggestions as you type</div>
                            <div id="address-help-fallback" style="display: none;">Google Places API not configured - enter address manually</div>
                        </div>
                    </div>
                    @error('property_address')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Property Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="bedrooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bedrooms</label>
                        <input type="number" name="bedrooms" id="bedrooms" value="{{ old('bedrooms') }}" min="0" max="20" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0">
                        @error('bedrooms')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="bathrooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bathrooms</label>
                        <input type="number" name="bathrooms" id="bathrooms" value="{{ old('bathrooms') }}" min="0" max="20" step="0.5" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0">
                        @error('bathrooms')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="square_footage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Square Footage</label>
                        <input type="number" name="square_footage" id="square_footage" value="{{ old('square_footage') }}" min="0" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., 1200">
                        @error('square_footage')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Owner Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Owner Information</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="owner_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Owner Name <span class="text-red-500">*</span></label>
                            <input type="text" name="owner_name" id="owner_name" value="{{ old('owner_name') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('owner_name') border-red-300 dark:border-red-600 @enderror" placeholder="Enter the property owner's full name" required>
                            @error('owner_name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="owner_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Owner Email</label>
                            <input type="email" name="owner_email" id="owner_email" value="{{ old('owner_email') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="owner@example.com">
                            @error('owner_email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="owner_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Owner Phone</label>
                            <input type="tel" name="owner_phone" id="owner_phone" value="{{ old('owner_phone') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="(555) 123-4567">
                            @error('owner_phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="owner_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Owner Address</label>
                        <div class="relative">
                            <input type="text" name="owner_address" id="owner_address" value="{{ old('owner_address') }}" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('owner_address') border-red-300 dark:border-red-600 @enderror" placeholder="Start typing owner's address..." autocomplete="off">
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <div id="owner-address-help-google">Google Places will provide address suggestions as you type</div>
                                <div id="owner-address-help-fallback" style="display: none;">Google Places API not configured - enter address manually</div>
                            </div>
                        </div>
                        @error('owner_address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <textarea name="description" id="description" rows="4" class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Optional notes or description about this property">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('ops.properties.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</a>
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Property
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
let propertyAutocomplete;
let ownerAutocomplete;

// Initialize Google Places Autocomplete
function initAutocomplete() {
    const propertyInput = document.getElementById('property_address');
    const ownerInput = document.getElementById('owner_address');
    const helpGoogle = document.getElementById('address-help-google');
    const helpFallback = document.getElementById('address-help-fallback');
    const ownerHelpGoogle = document.getElementById('owner-address-help-google');
    const ownerHelpFallback = document.getElementById('owner-address-help-fallback');
    
    // Check if Google Places API key is configured
    const hasGoogleAPI = '{{ config("services.google.places_api_key") }}' !== '';
    
    if (hasGoogleAPI) {
        try {
            // Initialize Google Places Autocomplete for Property Address
            if (propertyInput) {
                propertyAutocomplete = new google.maps.places.Autocomplete(propertyInput, {
                    types: ['address'],
                    fields: ['formatted_address', 'geometry', 'name', 'address_components']
                });
                
                // Handle place selection for property address
                propertyAutocomplete.addListener('place_changed', function() {
                    const place = propertyAutocomplete.getPlace();
                    if (place.formatted_address) {
                        propertyInput.value = place.formatted_address;
                    }
                });
                
                // Show Google API help text
                if (helpGoogle && helpFallback) {
                    helpGoogle.style.display = 'block';
                    helpFallback.style.display = 'none';
                }
            }
            
            // Initialize Google Places Autocomplete for Owner Address
            if (ownerInput) {
                ownerAutocomplete = new google.maps.places.Autocomplete(ownerInput, {
                    types: ['address'],
                    fields: ['formatted_address', 'geometry', 'name', 'address_components']
                });
                
                // Handle place selection for owner address
                ownerAutocomplete.addListener('place_changed', function() {
                    const place = ownerAutocomplete.getPlace();
                    if (place.formatted_address) {
                        ownerInput.value = place.formatted_address;
                    }
                });
                
                // Show Google API help text
                if (ownerHelpGoogle && ownerHelpFallback) {
                    ownerHelpGoogle.style.display = 'block';
                    ownerHelpFallback.style.display = 'none';
                }
            }
            
        } catch (error) {
            console.error('Error initializing Google Places:', error);
            showFallbackMode();
        }
    } else {
        showFallbackMode();
    }
    
    function showFallbackMode() {
        if (helpGoogle && helpFallback) {
            helpGoogle.style.display = 'none';
            helpFallback.style.display = 'block';
        }
        if (ownerHelpGoogle && ownerHelpFallback) {
            ownerHelpGoogle.style.display = 'none';
            ownerHelpFallback.style.display = 'block';
        }
        if (propertyInput) {
            propertyInput.placeholder = 'Enter the complete property address';
        }
        if (ownerInput) {
            ownerInput.placeholder = 'Enter the complete owner address';
        }
    }
}

// Optional: Auto-fill additional fields based on place data
function fillAdditionalFields(place) {
    // This function could be expanded to auto-fill city, state, zip, etc.
    // if you have separate fields for those components
    
    if (place.address_components) {
        // Example: You could extract and use address components
        // const city = getAddressComponent(place, 'locality');
        // const state = getAddressComponent(place, 'administrative_area_level_1');
        // const zipCode = getAddressComponent(place, 'postal_code');
    }
}

// Helper function to extract address components
function getAddressComponent(place, type) {
    const component = place.address_components.find(component => 
        component.types.includes(type)
    );
    return component ? component.long_name : null;
}

// Initialize when DOM is loaded (fallback if Google API doesn't load)
document.addEventListener('DOMContentLoaded', function() {
    // If Google API is not loaded after 5 seconds, show fallback
    setTimeout(() => {
        if (typeof google === 'undefined') {
            const helpGoogle = document.getElementById('address-help-google');
            const helpFallback = document.getElementById('address-help-fallback');
            const ownerHelpGoogle = document.getElementById('owner-address-help-google');
            const ownerHelpFallback = document.getElementById('owner-address-help-fallback');
            
            if (helpGoogle && helpFallback) {
                helpGoogle.style.display = 'none';
                helpFallback.style.display = 'block';
            }
            
            if (ownerHelpGoogle && ownerHelpFallback) {
                ownerHelpGoogle.style.display = 'none';
                ownerHelpFallback.style.display = 'block';
            }
            
            const propertyInput = document.getElementById('property_address');
            if (propertyInput) {
                propertyInput.placeholder = 'Enter the complete property address';
            }
            
            const ownerInput = document.getElementById('owner_address');
            if (ownerInput) {
                ownerInput.placeholder = 'Enter the complete owner address';
            }
        }
    }, 5000);
});
</script>
@endpush
