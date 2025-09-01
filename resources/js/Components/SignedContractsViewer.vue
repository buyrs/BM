<template>
    <div class="signed-contracts-viewer">
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900">Contrats Signés</h3>
            <p class="mt-1 text-sm text-gray-600">
                Consultez et téléchargez les contrats signés pour ce Bail Mobilité.
            </p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>

        <!-- No Signatures -->
        <div v-else-if="!signatures || signatures.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun contrat signé</h3>
            <p class="mt-1 text-sm text-gray-500">
                Les contrats signés apparaîtront ici une fois les signatures complétées.
            </p>
        </div>

        <!-- Signatures List -->
        <div v-else class="space-y-4">
            <div
                v-for="signatureData in signatures"
                :key="signatureData.signature.id"
                class="bg-white border border-gray-200 rounded-lg shadow-sm"
            >
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">
                                    Contrat {{ signatureData.signature.signature_type === 'entry' ? 'd\'Entrée' : 'de Sortie' }}
                                </h4>
                                <p class="text-sm text-gray-600">
                                    {{ signatureData.signature.contract_template?.name }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="flex items-center space-x-2">
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    signatureData.validation.is_valid
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ signatureData.validation.is_valid ? 'Valide' : 'Invalide' }}
                            </span>
                        </div>
                    </div>

                    <!-- Signature Details -->
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h5 class="text-sm font-medium text-gray-900 mb-2">Informations de Signature</h5>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Signé le:</strong> {{ formatDateTime(signatureData.signature.tenant_signed_at) }}</p>
                                <p><strong>Locataire:</strong> {{ bailMobilite.tenant_name }}</p>
                                <p v-if="signatureData.signature.signature_metadata?.ip_address">
                                    <strong>Adresse IP:</strong> {{ signatureData.signature.signature_metadata.ip_address }}
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <h5 class="text-sm font-medium text-gray-900 mb-2">Validation</h5>
                            <div class="space-y-1">
                                <div class="flex items-center text-sm">
                                    <svg
                                        :class="[
                                            'h-4 w-4 mr-2',
                                            signatureData.signature.tenant_signature ? 'text-green-500' : 'text-red-500'
                                        ]"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            v-if="signatureData.signature.tenant_signature"
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                        <path
                                            v-else
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span :class="signatureData.signature.tenant_signature ? 'text-green-700' : 'text-red-700'">
                                        Signature locataire
                                    </span>
                                </div>
                                
                                <div class="flex items-center text-sm">
                                    <svg
                                        :class="[
                                            'h-4 w-4 mr-2',
                                            signatureData.signature.contract_template?.admin_signature ? 'text-green-500' : 'text-red-500'
                                        ]"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            v-if="signatureData.signature.contract_template?.admin_signature"
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                        <path
                                            v-else
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span :class="signatureData.signature.contract_template?.admin_signature ? 'text-green-700' : 'text-red-700'">
                                        Signature administrateur
                                    </span>
                                </div>
                                
                                <div class="flex items-center text-sm">
                                    <svg
                                        :class="[
                                            'h-4 w-4 mr-2',
                                            signatureData.signature.contract_pdf_path ? 'text-green-500' : 'text-red-500'
                                        ]"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            v-if="signatureData.signature.contract_pdf_path"
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                        <path
                                            v-else
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span :class="signatureData.signature.contract_pdf_path ? 'text-green-700' : 'text-red-700'">
                                        PDF généré
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Errors -->
                    <div v-if="signatureData.validation.errors && signatureData.validation.errors.length > 0" class="mt-4">
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Erreurs de validation</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li v-for="error in signatureData.validation.errors" :key="error">
                                                {{ error }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex justify-end space-x-3">
                        <button
                            v-if="signatureData.signature.contract_pdf_path"
                            @click="previewContract(signatureData.signature)"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Prévisualiser
                        </button>
                        
                        <button
                            v-if="signatureData.signature.contract_pdf_path"
                            @click="downloadContract(signatureData.signature)"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Télécharger
                        </button>
                        
                        <button
                            @click="validateSignature(signatureData.signature)"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Valider
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archive Action -->
        <div v-if="signatures && signatures.length > 0" class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h4 class="text-sm font-medium text-gray-900">Archivage</h4>
                    <p class="text-sm text-gray-600">
                        Archiver les signatures pour conservation légale.
                    </p>
                </div>
                <button
                    @click="archiveSignatures"
                    :disabled="archiving"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50"
                >
                    <svg v-if="archiving" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6" />
                    </svg>
                    {{ archiving ? 'Archivage...' : 'Archiver' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import axios from 'axios'

const props = defineProps({
    bailMobilite: {
        type: Object,
        required: true
    }
})

const signatures = ref([])
const loading = ref(true)
const archiving = ref(false)

onMounted(() => {
    loadSignatures()
})

const loadSignatures = async () => {
    try {
        loading.value = true
        const response = await axios.get(`/signatures/bail-mobilites/${props.bailMobilite.id}/signatures`)
        signatures.value = response.data.signatures
    } catch (error) {
        console.error('Error loading signatures:', error)
    } finally {
        loading.value = false
    }
}

const previewContract = (signature) => {
    window.open(`/signatures/${signature.id}/preview`, '_blank')
}

const downloadContract = (signature) => {
    window.location.href = `/signatures/${signature.id}/download`
}

const validateSignature = async (signature) => {
    try {
        const response = await axios.get(`/signatures/${signature.id}/validate`)
        console.log('Validation result:', response.data)
        // Refresh signatures to get updated validation
        await loadSignatures()
    } catch (error) {
        console.error('Error validating signature:', error)
    }
}

const archiveSignatures = async () => {
    try {
        archiving.value = true
        await axios.post(`/signatures/bail-mobilites/${props.bailMobilite.id}/archive`)
        // Show success message or notification
        console.log('Signatures archived successfully')
    } catch (error) {
        console.error('Error archiving signatures:', error)
    } finally {
        archiving.value = false
    }
}

const formatDateTime = (date) => {
    if (!date) return 'Non défini'
    return format(new Date(date), 'dd/MM/yyyy à HH:mm', { locale: fr })
}
</script>

<style scoped>
.signed-contracts-viewer {
    max-width: 4xl;
}
</style>