<template>
    <div class="bail-mobilite-signature">
        <!-- Contract Display -->
        <div v-if="contractTemplate" class="mb-6">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Contrat de Bail Mobilité - {{ signatureType === 'entry' ? 'Entrée' : 'Sortie' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ contractTemplate.name }}
                    </p>
                </div>
                
                <!-- Contract Content -->
                <div class="px-6 py-4">
                    <div class="prose max-w-none">
                        <div class="whitespace-pre-wrap text-sm text-gray-700">
                            {{ contractTemplate.content }}
                        </div>
                    </div>
                </div>

                <!-- Property and Tenant Info -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Informations du Logement</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Adresse:</strong> {{ bailMobilite.address }}</p>
                                <p><strong>Début:</strong> {{ formatDate(bailMobilite.start_date) }}</p>
                                <p><strong>Fin:</strong> {{ formatDate(bailMobilite.end_date) }}</p>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Informations du Locataire</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Nom:</strong> {{ bailMobilite.tenant_name }}</p>
                                <p v-if="bailMobilite.tenant_email"><strong>Email:</strong> {{ bailMobilite.tenant_email }}</p>
                                <p v-if="bailMobilite.tenant_phone"><strong>Téléphone:</strong> {{ bailMobilite.tenant_phone }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Signature Display -->
                <div v-if="contractTemplate.admin_signature" class="px-6 py-4 border-t border-gray-200">
                    <h4 class="font-medium text-gray-900 mb-2">Signature de l'Hôte/Propriétaire</h4>
                    <div class="flex items-center space-x-4">
                        <img 
                            :src="contractTemplate.admin_signature" 
                            alt="Signature Hôte" 
                            class="h-16 border border-gray-300 rounded"
                        >
                        <div class="text-sm text-gray-600">
                            <p><strong>Signé le:</strong> {{ formatDateTime(contractTemplate.admin_signed_at) }}</p>
                            <p><strong>Par:</strong> {{ contractTemplate.creator?.name || 'Administrateur' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Signature du Locataire</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Veuillez signer ci-dessous pour confirmer votre accord avec les termes du contrat.
                </p>
            </div>

            <div class="px-6 py-4">
                <!-- Enhanced Signature Pad -->
                <SignaturePad
                    v-model="signatureData"
                    title="Signature électronique"
                    instructions="Veuillez signer ci-dessous pour confirmer votre accord avec les termes du contrat."
                    :show-preview="true"
                    :pen-width="3"
                    @validation-change="handleSignatureValidation"
                />

                <!-- Signature Actions -->
                <div class="flex justify-between items-center mt-6">
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="$emit('cancel')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Annuler
                        </button>
                        <button
                            type="button"
                            @click="saveSignature"
                            :disabled="!isSignatureValid || loading"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="loading">Enregistrement...</span>
                            <span v-else>Signer le Contrat</span>
                        </button>
                    </div>
                </div>

                <!-- Signature Status -->
                <div v-if="signatureValidation.message" class="mt-4 p-4 rounded-md" :class="signatureValidation.isValid ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg v-if="signatureValidation.isValid" class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <svg v-else class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium" :class="signatureValidation.isValid ? 'text-green-800' : 'text-yellow-800'">
                                {{ signatureValidation.message }}
                            </p>
                            <p v-if="signatureValidation.isValid" class="mt-1 text-sm text-green-700">
                                Cliquez sur "Signer le Contrat" pour finaliser la signature.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legal Notice -->
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-800">
                        <strong>Notice légale:</strong> En signant ce document électroniquement, vous acceptez les termes et conditions du contrat de bail mobilité. Cette signature a la même valeur légale qu'une signature manuscrite.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import SignaturePad from './SignaturePad.vue'

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
    loading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['sign', 'cancel'])

const signatureData = ref('')
const signatureValidation = ref({ isValid: false, message: '' })

const isSignatureValid = computed(() => signatureValidation.value.isValid)

const handleSignatureValidation = ({ isValid, message }) => {
    signatureValidation.value = { isValid, message }
}

const saveSignature = () => {
    if (!isSignatureValid.value) return
    
    emit('sign', {
        signatureData: signatureData.value,
        signatureType: props.signatureType,
        metadata: {
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            screen: {
                width: screen.width,
                height: screen.height
            }
        }
    })
}

const formatDate = (date) => {
    return format(new Date(date), 'dd/MM/yyyy', { locale: fr })
}

const formatDateTime = (date) => {
    return format(new Date(date), 'dd/MM/yyyy à HH:mm', { locale: fr })
}
</script>

<style scoped>
.bail-mobilite-signature {
    max-width: 4xl;
}

.prose {
    max-width: none;
}
</style>