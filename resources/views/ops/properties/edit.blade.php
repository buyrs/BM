@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Edit Property (Ops)</h1>
        <a href="{{ route('ops.properties.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Properties</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" action="{{ route('ops.properties.update', $property->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="internal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Internal Code</label>
                <input type="text" name="internal_code" id="internal_code" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('internal_code', $property->internal_code) }}" placeholder="e.g., Rosier, MainSt, etc.">
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Optional short code to identify this property (letters, numbers, dash, underscore only)</p>
                @error('internal_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="owner_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner Name</label>
                <input type="text" name="owner_name" id="owner_name" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="{{ old('owner_name', $property->owner_name) }}" required>
                @error('owner_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="owner_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner Address</label>
                <div class="relative">
                    <input type="text" name="owner_address" id="owner_address" value="{{ old('owner_address', $property->owner_address) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Start typing owner's address..." autocomplete="off">
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <div id="owner-address-help-google">Google Places will provide address suggestions</div>
                        <div id="owner-address-help-fallback" style="display: none;">Enter address manually</div>
                    </div>
                </div>
                @error('owner_address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="property_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Property Address</label>
                <div class="relative">
                    <input type="text" name="property_address" id="property_address" value="{{ old('property_address', $property->property_address) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Start typing property address..." required autocomplete="off">
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <div id="address-help-google">Google Places will provide address suggestions</div>
                        <div id="address-help-fallback" style="display: none;">Enter address manually</div>
                    </div>
                </div>
                @error('property_address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="property_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Property Type</label>
                <select name="property_type" id="property_type" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="">Select Property Type</option>
                    <option value="apartment" {{ old('property_type', $property->property_type) == 'apartment' ? 'selected' : '' }}>Apartment</option>
                    <option value="house" {{ old('property_type', $property->property_type) == 'house' ? 'selected' : '' }}>House</option>
                    <option value="commercial" {{ old('property_type', $property->property_type) == 'commercial' ? 'selected' : '' }}>Commercial</option>
                    <option value="condo" {{ old('property_type', $property->property_type) == 'condo' ? 'selected' : '' }}>Condo</option>
                    <option value="townhouse" {{ old('property_type', $property->property_type) == 'townhouse' ? 'selected' : '' }}>Townhouse</option>
                </select>
                @error('property_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" id="description" rows="3" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $property->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Update Property</button>
        </form>
    </div>
</div>

@push('scripts')
<!-- Google Places API -->
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places&callback=initAutocomplete"></script>

<style>
/* Ensure Google Places dropdown is visible above all other elements */
.pac-container {
    z-index: 10000 !important;
    background-color: white !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    margin-top: 4px !important;
}
.pac-item {
    padding: 8px 12px !important;
    cursor: pointer !important;
}
.pac-item:hover {
    background-color: #f3f4f6 !important;
}
.pac-item-query {
    font-weight: 500 !important;
}
</style>

<script>
let propertyAutocomplete;
let ownerAutocomplete;

function initAutocomplete() {
    console.log('🔍 Google Places: initAutocomplete called');
    console.log('🔍 Google Maps object:', typeof google !== 'undefined' ? 'loaded' : 'NOT loaded');
    console.log('🔍 Places library:', typeof google !== 'undefined' && google.maps && google.maps.places ? 'loaded' : 'NOT loaded');
    const propertyInput = document.getElementById('property_address');
    const ownerInput = document.getElementById('owner_address');
    const helpGoogle = document.getElementById('address-help-google');
    const helpFallback = document.getElementById('address-help-fallback');
    const ownerHelpGoogle = document.getElementById('owner-address-help-google');
    const ownerHelpFallback = document.getElementById('owner-address-help-fallback');
    
    const hasGoogleAPI = '{{ config("services.google.places_api_key") }}' !== '';
    
    if (hasGoogleAPI) {
        try {
            if (propertyInput) {
                propertyAutocomplete = new google.maps.places.Autocomplete(propertyInput, {
                    types: ['address'],
                    fields: ['formatted_address', 'geometry', 'name', 'address_components']
                });
                
                propertyAutocomplete.addListener('place_changed', function() {
                    const place = propertyAutocomplete.getPlace();
                    if (place.formatted_address) {
                        propertyInput.value = place.formatted_address;
                    }
                });
                
                if (helpGoogle && helpFallback) {
                    helpGoogle.style.display = 'block';
                    helpFallback.style.display = 'none';
                }
            }
            
            if (ownerInput) {
                ownerAutocomplete = new google.maps.places.Autocomplete(ownerInput, {
                    types: ['address'],
                    fields: ['formatted_address', 'geometry', 'name', 'address_components']
                });
                
                ownerAutocomplete.addListener('place_changed', function() {
                    const place = ownerAutocomplete.getPlace();
                    if (place.formatted_address) {
                        ownerInput.value = place.formatted_address;
                    }
                });
                
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
        if (propertyInput) propertyInput.placeholder = 'Enter the complete property address';
        if (ownerInput) ownerInput.placeholder = 'Enter the complete owner address';
    }
}

document.addEventListener('DOMContentLoaded', function() {
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
            const ownerInput = document.getElementById('owner_address');
            if (propertyInput) propertyInput.placeholder = 'Enter the complete property address';
            if (ownerInput) ownerInput.placeholder = 'Enter the complete owner address';
        }
    }, 5000);
});
</script>
@endpush
@endsection