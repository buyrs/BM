@props(['action' => '', 'method' => 'POST', 'title' => '', 'submitText' => 'Submit', 'cancelUrl' => null])

@php
    $mobileService = app(\App\Services\MobileResponsivenessService::class);
    $formConfig = $mobileService->getMobileFormConfig();
    $buttonConfig = $mobileService->getTouchButtonConfig();
@endphp

<div class="max-w-2xl mx-auto">
    @if($title)
        <div class="mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $title }}</h2>
        </div>
    @endif

    <form action="{{ $action }}" method="{{ $method }}" class="space-y-6" enctype="multipart/form-data">
        @csrf
        @if($method !== 'GET' && $method !== 'POST')
            @method($method)
        @endif

        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-4 py-6 sm:p-6 space-y-6">
                {{ $slot }}
            </div>

            <!-- Form Actions -->
            <div class="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200 rounded-b-lg">
                <div class="flex flex-col sm:flex-row sm:justify-end space-y-3 sm:space-y-0 sm:space-x-3">
                    @if($cancelUrl)
                        <a href="{{ $cancelUrl }}" 
                           class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            Cancel
                        </a>
                    @endif
                    <button type="submit" 
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        {{ $submitText }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    /* Mobile-optimized form styles */
    .mobile-form input[type="text"],
    .mobile-form input[type="email"],
    .mobile-form input[type="password"],
    .mobile-form input[type="number"],
    .mobile-form input[type="tel"],
    .mobile-form input[type="url"],
    .mobile-form input[type="date"],
    .mobile-form input[type="datetime-local"],
    .mobile-form input[type="time"],
    .mobile-form select,
    .mobile-form textarea {
        min-height: {{ $formConfig['input_height'] }};
        font-size: {{ $formConfig['input_font_size'] }};
        padding: {{ $formConfig['touch_padding'] }};
        border-radius: {{ $formConfig['border_radius'] }};
    }

    .mobile-form label {
        font-size: {{ $formConfig['label_font_size'] }};
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .mobile-form .form-group {
        margin-bottom: {{ $formConfig['spacing'] }};
    }

    /* Touch-friendly buttons */
    .mobile-form button,
    .mobile-form .btn {
        min-height: {{ $buttonConfig['min_height'] }};
        min-width: {{ $buttonConfig['min_width'] }};
        padding: {{ $buttonConfig['padding'] }};
        font-size: {{ $buttonConfig['font_size'] }};
        border-radius: {{ $buttonConfig['border_radius'] }};
        touch-action: manipulation;
    }

    /* Prevent zoom on iOS */
    @media screen and (-webkit-min-device-pixel-ratio: 0) {
        select,
        textarea,
        input[type="text"],
        input[type="password"],
        input[type="datetime"],
        input[type="datetime-local"],
        input[type="date"],
        input[type="month"],
        input[type="time"],
        input[type="week"],
        input[type="number"],
        input[type="email"],
        input[type="url"],
        input[type="search"],
        input[type="tel"],
        input[type="color"] {
            font-size: 16px !important;
        }
    }

    /* Focus states for better accessibility */
    .mobile-form input:focus,
    .mobile-form select:focus,
    .mobile-form textarea:focus {
        outline: none;
        ring: {{ $formConfig['focus_ring'] }};
        ring-color: #4f46e5;
        border-color: #4f46e5;
    }

    /* File input styling */
    .mobile-form input[type="file"] {
        padding: 0.75rem;
        border: 2px dashed #d1d5db;
        border-radius: {{ $formConfig['border_radius'] }};
        background-color: #f9fafb;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .mobile-form input[type="file"]:hover {
        border-color: #9ca3af;
        background-color: #f3f4f6;
    }

    .mobile-form input[type="file"]:focus {
        border-color: #4f46e5;
        background-color: #eef2ff;
    }

    /* Checkbox and radio styling */
    .mobile-form input[type="checkbox"],
    .mobile-form input[type="radio"] {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
    }

    /* Error states */
    .mobile-form .error input,
    .mobile-form .error select,
    .mobile-form .error textarea {
        border-color: #ef4444;
        ring-color: #ef4444;
    }

    .mobile-form .error-message {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    /* Success states */
    .mobile-form .success input,
    .mobile-form .success select,
    .mobile-form .success textarea {
        border-color: #10b981;
        ring-color: #10b981;
    }

    /* Loading states */
    .mobile-form .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .mobile-form .loading button {
        position: relative;
    }

    .mobile-form .loading button::after {
        content: '';
        position: absolute;
        width: 1rem;
        height: 1rem;
        top: 50%;
        left: 50%;
        margin-left: -0.5rem;
        margin-top: -0.5rem;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        .mobile-form {
            padding: 1rem;
        }
        
        .mobile-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .mobile-form input,
        .mobile-form select,
        .mobile-form textarea {
            font-size: 16px; /* Prevent zoom on iOS */
        }
    }
</style>