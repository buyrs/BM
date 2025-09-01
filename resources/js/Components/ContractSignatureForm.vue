<template>
    <div class="space-y-6">
        <!-- Step 1: Contract Preview -->
        <div v-if="currentStep === 'preview'">
            <ContractPreview
                :bail-mobilite="bailMobilite"
                :contract-template="contractTemplate"
                :signature-type="signatureType"
                @close="$emit('cancelled')"
                @proceed-to-sign="currentStep = 'signature'"
            />
        </div>

        <!-- Step 2: Signature -->
        <div v-else-if="currentStep === 'signature'">
            <BailMobiliteSignature
                :bail-mobilite="bailMobilite"
                :contract-template="contractTemplate"
                :signature-type="signatureType"
                :loading="submitting"
                @sign="handleSignature"
                @cancel="currentStep = 'preview'"
            />
        </div>

        <!-- Step 3: Success -->
        <div v-else-if="currentStep === 'success'" class="text-center py-8">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Contrat Signé avec Succès</h3>
            <p class="mt-1 text-sm text-gray-600">
                Le contrat de bail mobilité a été signé et le PDF a été généré automatiquement.
            </p>
            <div class="mt-6 flex justify-center space-x-3">
                <button
                    v-if="signatureResult"
                    @click="previewSignedContract"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Voir le Contrat Signé
                </button>
                <button
                    @click="$emit('signed', signatureResult)"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Continuer
                </button>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="currentStep === 'error'" class="text-center py-8">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Erreur lors de la Signature</h3>
            <p class="mt-1 text-sm text-gray-600">
                {{ errorMessage }}
            </p>
            <div class="mt-6 flex justify-center space-x-3">
                <button
                    @click="currentStep = 'signature'"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Réessayer
                </button>
                <button
                    @click="$emit('cancelled')"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                    Annuler
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import ContractPreview from './ContractPreview.vue'
import BailMobiliteSignature from './BailMobiliteSignature.vue'

const props = defineProps({
    mission: {
        type: Object,
        required: true
    },
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
    }
})

const emit = defineEmits(['signed', 'cancelled'])

const currentStep = ref('preview')
const submitting = ref(false)
const signatureResult = ref(null)
const errorMessage = ref('')

const handleSignature = async (signatureData) => {
    try {
        submitting.value = true
        
        const response = await axios.post(`/signatures/bail-mobilites/${props.bailMobilite.id}/sign`, {
            signature_type: signatureData.signatureType,
            signature_data: signatureData.signatureData,
            mission_id: props.mission.id,
            device_info: signatureData.metadata
        })

        if (response.data.success) {
            signatureResult.value = response.data.signature
            currentStep.value = 'success'
        } else {
            throw new Error(response.data.message || 'Erreur lors de la signature')
        }
    } catch (error) {
        console.error('Signature error:', error)
        errorMessage.value = error.response?.data?.message || error.message || 'Erreur inconnue lors de la signature'
        currentStep.value = 'error'
    } finally {
        submitting.value = false
    }
}

const previewSignedContract = () => {
    if (signatureResult.value) {
        window.open(`/signatures/${signatureResult.value.id}/preview`, '_blank')
    }
}
</script>