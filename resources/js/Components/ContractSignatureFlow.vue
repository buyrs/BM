<template>
    <div class="contract-signature-flow">
        <!-- Progress Indicator -->
        <div class="progress-indicator">
            <div class="flex items-center justify-between mb-8">
                <div 
                    v-for="(step, index) in steps" 
                    :key="step.key"
                    class="flex items-center"
                    :class="{ 'flex-1': index < steps.length - 1 }"
                >
                    <div class="flex items-center">
                        <div 
                            class="step-circle"
                            :class="{
                                'step-circle--completed': isStepCompleted(step.key),
                                'step-circle--current': currentStep === step.key,
                                'step-circle--pending': !isStepCompleted(step.key) && currentStep !== step.key
                            }"
                        >
                            <svg v-if="isStepCompleted(step.key)" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-700">{{ step.title }}</span>
                    </div>
                    <div v-if="index < steps.length - 1" class="flex-1 h-0.5 bg-gray-200 mx-4">
                        <div 
                            class="h-full bg-indigo-600 transition-all duration-300"
                            :style="{ width: getProgressWidth(index) }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step Content -->
        <div class="step-content">
            <!-- Step 1: Contract Review -->
            <div v-if="currentStep === 'review'" class="space-y-6">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">Contract Review</h2>
                    <p class="mt-2 text-gray-600">Please review the contract terms before signing</p>
                </div>
                
                <ContractPreview
                    :bail-mobilite="bailMobilite"
                    :contract-template="contractTemplate"
                    :signature-type="signatureType"
                    :show-actions="false"
                />
                
                <div class="flex justify-between">
                    <SecondaryButton @click="$emit('cancel')">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton @click="proceedToSignature">
                        I Agree - Proceed to Sign
                    </PrimaryButton>
                </div>
            </div>

            <!-- Step 2: Signature Capture -->
            <div v-else-if="currentStep === 'signature'" class="space-y-6">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">Digital Signature</h2>
                    <p class="mt-2 text-gray-600">Please provide your signature below</p>
                </div>

                <!-- Signer Information -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Signer Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input
                                v-model="signerInfo.name"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                :placeholder="bailMobilite.tenant_name"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                v-model="signerInfo.email"
                                type="email"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                :placeholder="bailMobilite.tenant_email"
                                required
                            />
                        </div>
                    </div>
                </div>

                <!-- Signature Pad -->
                <SignaturePad
                    v-model="tenantSignature"
                    title="Your Signature"
                    instructions="Please sign using your finger or stylus. This signature will be legally binding."
                    :show-preview="true"
                    :pen-width="3"
                    @validation-change="handleSignatureValidation"
                />

                <!-- Admin Signature Display -->
                <div v-if="contractTemplate.admin_signature" class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Host/Property Owner Signature</h3>
                    <div class="flex items-center space-x-4">
                        <img 
                            :src="contractTemplate.admin_signature" 
                            alt="Admin Signature" 
                            class="h-16 border border-gray-300 rounded bg-white p-2"
                        />
                        <div class="text-sm text-gray-600">
                            <p><strong>Signed:</strong> {{ formatDateTime(contractTemplate.admin_signed_at) }}</p>
                            <p><strong>By:</strong> {{ contractTemplate.creator?.name || 'Administrator' }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <SecondaryButton @click="currentStep = 'review'">
                        Back to Review
                    </SecondaryButton>
                    <PrimaryButton 
                        @click="proceedToConfirmation"
                        :disabled="!isSignatureValid || !signerInfo.name || !signerInfo.email"
                    >
                        Continue to Confirmation
                    </PrimaryButton>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div v-else-if="currentStep === 'confirmation'" class="space-y-6">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">Signature Confirmation</h2>
                    <p class="mt-2 text-gray-600">Please confirm your signature and contract details</p>
                </div>

                <!-- Contract Summary -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contract Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p><strong>Property:</strong> {{ bailMobilite.address }}</p>
                            <p><strong>Tenant:</strong> {{ signerInfo.name }}</p>
                            <p><strong>Type:</strong> {{ signatureType === 'entry' ? 'Entry Contract' : 'Exit Contract' }}</p>
                        </div>
                        <div>
                            <p><strong>Start Date:</strong> {{ formatDate(bailMobilite.start_date) }}</p>
                            <p><strong>End Date:</strong> {{ formatDate(bailMobilite.end_date) }}</p>
                            <p><strong>Signing Date:</strong> {{ formatDate(new Date()) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Signature Comparison -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Signatures</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Admin Signature -->
                        <div class="text-center">
                            <h4 class="font-medium text-gray-700 mb-2">Host/Property Owner</h4>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <img 
                                    :src="contractTemplate.admin_signature" 
                                    alt="Admin Signature" 
                                    class="max-h-16 mx-auto"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                {{ contractTemplate.creator?.name || 'Administrator' }}
                            </p>
                        </div>
                        
                        <!-- Tenant Signature -->
                        <div class="text-center">
                            <h4 class="font-medium text-gray-700 mb-2">Tenant</h4>
                            <div class="border border-gray-300 rounded-md p-4 bg-gray-50">
                                <img 
                                    :src="tenantSignature" 
                                    alt="Tenant Signature" 
                                    class="max-h-16 mx-auto"
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-2">{{ signerInfo.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Legal Notice -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Legal Notice</h3>
                            <p class="mt-1 text-sm text-yellow-700">
                                By clicking "Sign Contract", you acknowledge that you have read, understood, and agree to be bound by the terms of this contract. This electronic signature has the same legal effect as a handwritten signature.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <SecondaryButton @click="currentStep = 'signature'">
                        Back to Signature
                    </SecondaryButton>
                    <PrimaryButton 
                        @click="signContract"
                        :disabled="isSubmitting"
                        class="relative"
                    >
                        <span v-if="isSubmitting" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Signing Contract...
                        </span>
                        <span v-else>Sign Contract</span>
                    </PrimaryButton>
                </div>
            </div>

            <!-- Step 4: Success -->
            <div v-else-if="currentStep === 'success'" class="text-center space-y-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Contract Signed Successfully!</h2>
                    <p class="mt-2 text-gray-600">
                        Your contract has been signed and a PDF has been generated automatically.
                    </p>
                </div>

                <div v-if="signatureResult" class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-green-800 mb-2">Contract Details</h3>
                    <div class="text-sm text-green-700 space-y-1">
                        <p><strong>Contract ID:</strong> {{ signatureResult.id }}</p>
                        <p><strong>Signed At:</strong> {{ formatDateTime(signatureResult.signed_at) }}</p>
                        <p><strong>PDF Generated:</strong> {{ signatureResult.pdf_path ? 'Yes' : 'Processing...' }}</p>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <SecondaryButton 
                        v-if="signatureResult?.pdf_path"
                        @click="downloadContract"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Contract
                    </SecondaryButton>
                    <PrimaryButton @click="$emit('completed', signatureResult)">
                        Continue
                    </PrimaryButton>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="currentStep === 'error'" class="text-center space-y-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Signature Error</h2>
                    <p class="mt-2 text-gray-600">{{ errorMessage }}</p>
                </div>

                <div class="flex justify-center space-x-4">
                    <SecondaryButton @click="currentStep = 'signature'">
                        Try Again
                    </SecondaryButton>
                    <SecondaryButton @click="$emit('cancel')">
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import axios from 'axios'
import SignaturePad from './SignaturePad.vue'
import ContractPreview from './ContractPreview.vue'
import PrimaryButton from './PrimaryButton.vue'
import SecondaryButton from './SecondaryButton.vue'

const props = defineProps({
    bailMobilite: {
        type: Object,
        required: true
    },
    contractTemplate: {
        type: Object,
        required: true
    },
    signatureType: {
        type: String,
        required: true,
        validator: value => ['entry', 'exit'].includes(value)
    },
    mission: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['completed', 'cancel'])

// State
const currentStep = ref('review')
const tenantSignature = ref('')
const isSignatureValid = ref(false)
const isSubmitting = ref(false)
const signatureResult = ref(null)
const errorMessage = ref('')

const signerInfo = ref({
    name: props.bailMobilite.tenant_name || '',
    email: props.bailMobilite.tenant_email || ''
})

// Steps configuration
const steps = [
    { key: 'review', title: 'Review Contract' },
    { key: 'signature', title: 'Sign Document' },
    { key: 'confirmation', title: 'Confirm Details' },
    { key: 'success', title: 'Complete' }
]

// Computed
const isStepCompleted = (stepKey) => {
    const stepIndex = steps.findIndex(s => s.key === stepKey)
    const currentIndex = steps.findIndex(s => s.key === currentStep.value)
    return stepIndex < currentIndex || currentStep.value === 'success'
}

const getProgressWidth = (index) => {
    const currentIndex = steps.findIndex(s => s.key === currentStep.value)
    if (index < currentIndex) return '100%'
    if (index === currentIndex) return '50%'
    return '0%'
}

// Methods
const proceedToSignature = () => {
    currentStep.value = 'signature'
}

const proceedToConfirmation = () => {
    if (!isSignatureValid.value || !signerInfo.value.name || !signerInfo.value.email) {
        return
    }
    currentStep.value = 'confirmation'
}

const handleSignatureValidation = ({ isValid, message }) => {
    isSignatureValid.value = isValid
}

const signContract = async () => {
    if (isSubmitting.value) return
    
    try {
        isSubmitting.value = true
        
        const signatureData = {
            signature_type: props.signatureType,
            signature_data: tenantSignature.value,
            signer_name: signerInfo.value.name,
            signer_email: signerInfo.value.email,
            mission_id: props.mission?.id,
            device_info: {
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                screen: {
                    width: screen.width,
                    height: screen.height
                }
            }
        }

        const response = await axios.post(`/signatures/bail-mobilites/${props.bailMobilite.id}/sign`, signatureData)

        if (response.data.success) {
            signatureResult.value = response.data.signature
            currentStep.value = 'success'
        } else {
            throw new Error(response.data.message || 'Failed to sign contract')
        }
    } catch (error) {
        console.error('Signature error:', error)
        errorMessage.value = error.response?.data?.message || error.message || 'An unknown error occurred'
        currentStep.value = 'error'
    } finally {
        isSubmitting.value = false
    }
}

const downloadContract = () => {
    if (signatureResult.value?.pdf_path) {
        window.open(`/signatures/${signatureResult.value.id}/download`, '_blank')
    }
}

const formatDate = (date) => {
    return format(new Date(date), 'dd/MM/yyyy', { locale: fr })
}

const formatDateTime = (date) => {
    return format(new Date(date), 'dd/MM/yyyy à HH:mm', { locale: fr })
}
</script>

<style scoped>
.contract-signature-flow {
    @apply max-w-4xl mx-auto;
}

.progress-indicator {
    @apply mb-8;
}

.step-circle {
    @apply flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-200;
}

.step-circle--completed {
    @apply bg-indigo-600 border-indigo-600 text-white;
}

.step-circle--current {
    @apply bg-white border-indigo-600 text-indigo-600;
}

.step-circle--pending {
    @apply bg-white border-gray-300 text-gray-500;
}

.step-content {
    @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .progress-indicator .flex {
        @apply flex-col space-y-4;
    }
    
    .progress-indicator .flex-1 {
        @apply flex-none;
    }
    
    .step-content {
        @apply p-4;
    }
}
</style>