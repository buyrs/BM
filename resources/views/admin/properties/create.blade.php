<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Property') }}
            </h2>
            <a href="{{ route('admin.properties.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Properties
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.properties.store') }}" method="POST">
                        @csrf
                        
                        @include('properties._form')

                        <div class="flex justify-end space-x-3 mt-6">
                            <a href="{{ route('admin.properties.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Create Property
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
        const propertyInput = document.getElementById('property_address');
        const ownerInput = document.getElementById('owner_address');
        const helpGoogle = document.getElementById('address-help-google');
        const helpFallback = document.getElementById('address-help-fallback');
        const ownerHelpGoogle = document.getElementById('owner-address-help-google');
        const ownerHelpFallback = document.getElementById('owner-address-help-fallback');
        
        const hasGoogleAPI = '{{ config("services.google.places_api_key") }}' !== '';
        
        if (hasGoogleAPI && typeof google !== 'undefined' && google.maps && google.maps.places) {
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
</x-app-layout>