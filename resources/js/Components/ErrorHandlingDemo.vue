<template>
    <div class="max-w-4xl mx-auto p-6 space-y-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Error Handling & User Feedback Demo</h2>
            
            <!-- Toast Notifications Demo -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Toast Notifications</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <button
                        @click="showSuccessToast"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                    >
                        Success Toast
                    </button>
                    <button
                        @click="showErrorToast"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                    >
                        Error Toast
                    </button>
                    <button
                        @click="showWarningToast"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors"
                    >
                        Warning Toast
                    </button>
                    <button
                        @click="showInfoToast"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Info Toast
                    </button>
                </div>
            </div>

            <!-- API Error Simulation -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">API Error Simulation</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <button
                        @click="simulateNetworkError"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Network Error
                    </button>
                    <button
                        @click="simulateServerError"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Server Error
                    </button>
                    <button
                        @click="simulateValidationError"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Validation Error
                    </button>
                    <button
                        @click="simulateAuthError"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Auth Error
                    </button>
                    <button
                        @click="simulatePermissionError"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Permission Error
                    </button>
                    <button
                        @click="simulateTimeoutError"
                        class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                    >
                        Timeout Error
                    </button>
                </div>
            </div>

            <!-- Form Validation Demo -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Form Validation Demo</h3>
                <form @submit.prevent="handleFormSubmit" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name (Required)</label>
                        <input
                            id="name"
                            v-model="form.name"
                            @blur="touchField('name')"
                            type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': hasFieldError('name') && isFieldTouched('name') }"
                        />
                        <InputError :message="getFieldError('name')" />
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email (Required)</label>
                        <input
                            id="email"
                            v-model="form.email"
                            @blur="touchField('email')"
                            type="email"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': hasFieldError('email') && isFieldTouched('email') }"
                        />
                        <InputError :message="getFieldError('email')" />
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            @blur="touchField('phone')"
                            type="tel"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': hasFieldError('phone') && isFieldTouched('phone') }"
                        />
                        <InputError :message="getFieldError('phone')" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password (Min 8 chars)</label>
                        <input
                            id="password"
                            v-model="form.password"
                            @blur="touchField('password')"
                            type="password"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': hasFieldError('password') && isFieldTouched('password') }"
                        />
                        <InputError :message="getFieldError('password')" />
                    </div>

                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input
                            id="confirmPassword"
                            v-model="form.confirmPassword"
                            @blur="touchField('confirmPassword')"
                            type="password"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': hasFieldError('confirmPassword') && isFieldTouched('confirmPassword') }"
                        />
                        <InputError :message="getFieldError('confirmPassword')" />
                    </div>

                    <div class="flex items-center justify-between">
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span v-if="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Submitting...
                            </span>
                            <span v-else>Submit Form</span>
                        </button>
                        
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                        >
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Loading States Demo -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Loading States Demo</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-2">Small Spinner</h4>
                        <LoadingSpinner size="sm" message="Loading..." />
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-2">Medium Spinner</h4>
                        <LoadingSpinner size="md" message="Processing..." />
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium mb-2">Large Spinner</h4>
                        <LoadingSpinner size="lg" message="Please wait..." />
                    </div>
                </div>
                
                <div class="mt-6">
                    <h4 class="font-medium mb-2">Progress Loading</h4>
                    <div class="border rounded-lg p-4">
                        <LoadingSpinner 
                            size="md" 
                            message="Uploading file..." 
                            :show-progress="true" 
                            :progress="uploadProgress" 
                        />
                        <button
                            @click="simulateUpload"
                            :disabled="isUploading"
                            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {{ isUploading ? 'Uploading...' : 'Start Upload' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Statistics -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Error Statistics</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <pre class="text-sm">{{ JSON.stringify(errorStats, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useFormValidation } from '@/Composables/useFormValidation.js'
import InputError from '@/Components/InputError.vue'
import LoadingSpinner from '@/Components/LoadingSpinner.vue'
import errorMessageService from '@/Services/ErrorMessageService.js'
import toastService from '@/Services/ToastService.js'
import errorHandler from '@/utils/errorHandler.js'

// Form validation setup
const {
    form,
    errors,
    touched,
    isSubmitting,
    isValid,
    validateField,
    validateAll,
    clearFieldError,
    clearErrors,
    touchField,
    isFieldTouched,
    hasFieldError,
    getFieldError,
    reset,
    handleSubmit,
    enableRealTimeValidation
} = useFormValidation(
    {
        name: '',
        email: '',
        phone: '',
        password: '',
        confirmPassword: ''
    },
    {
        name: ['required'],
        email: ['required', 'email'],
        phone: ['phone'],
        password: [
            'required',
            { type: 'min', params: [8], message: 'Password must be at least 8 characters' }
        ],
        confirmPassword: [
            { type: 'match', params: ['password'], message: 'Passwords must match' }
        ]
    }
)

// Enable real-time validation
enableRealTimeValidation()

// Upload simulation
const uploadProgress = ref(0)
const isUploading = ref(false)

// Error statistics
const errorStats = computed(() => errorHandler.getErrorStats())

// Toast notification methods
const showSuccessToast = () => {
    toastService.success('Operation completed successfully!', {
        title: 'Success'
    })
}

const showErrorToast = () => {
    toastService.error('Something went wrong. Please try again.', {
        title: 'Error',
        action: {
            label: 'Retry',
            onClick: () => console.log('Retry clicked')
        }
    })
}

const showWarningToast = () => {
    toastService.warning('This action cannot be undone.', {
        title: 'Warning'
    })
}

const showInfoToast = () => {
    toastService.info('New features are available!', {
        title: 'Information'
    })
}

// API error simulation methods
const simulateNetworkError = () => {
    const error = {
        code: 'NETWORK_ERROR',
        message: 'Network Error'
    }
    errorMessageService.showError(error, 'missions', 'create')
}

const simulateServerError = () => {
    const error = {
        response: {
            status: 500,
            data: { message: 'Internal server error' }
        }
    }
    errorMessageService.showError(error, 'missions', 'create', () => {
        console.log('Retrying server request...')
    })
}

const simulateValidationError = () => {
    const error = {
        response: {
            status: 422,
            data: {
                errors: {
                    name: ['The name field is required.'],
                    email: ['The email field must be a valid email address.']
                }
            }
        }
    }
    errorMessageService.showError(error, 'users', 'create')
}

const simulateAuthError = () => {
    const error = {
        response: {
            status: 401,
            data: { message: 'Unauthorized' }
        }
    }
    errorMessageService.showError(error, 'missions', 'update')
}

const simulatePermissionError = () => {
    const error = {
        response: {
            status: 403,
            data: { message: 'Forbidden' }
        }
    }
    errorMessageService.showError(error, 'users', 'delete')
}

const simulateTimeoutError = () => {
    const error = {
        code: 'TIMEOUT_ERROR',
        message: 'Request timeout'
    }
    errorMessageService.showError(error, 'checklists', 'submit', () => {
        console.log('Retrying timeout request...')
    })
}

// Form submission
const handleFormSubmit = async () => {
    try {
        await handleSubmit(
            async (formData) => {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 2000))
                console.log('Form submitted:', formData)
                return { success: true }
            },
            {
                context: 'users',
                operation: 'create'
            }
        )
    } catch (error) {
        console.error('Form submission failed:', error)
    }
}

const resetForm = () => {
    reset()
}

// Upload simulation
const simulateUpload = async () => {
    if (isUploading.value) return
    
    isUploading.value = true
    uploadProgress.value = 0
    
    const interval = setInterval(() => {
        uploadProgress.value += Math.random() * 15
        
        if (uploadProgress.value >= 100) {
            uploadProgress.value = 100
            clearInterval(interval)
            
            setTimeout(() => {
                isUploading.value = false
                uploadProgress.value = 0
                toastService.success('File uploaded successfully!')
            }, 500)
        }
    }, 200)
}

onMounted(() => {
    console.log('Error Handling Demo loaded')
})
</script>