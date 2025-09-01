<template>
    <div class="contract-preview">
        <!-- Header -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900">
                Aperçu du Contrat - {{ signatureType === 'entry' ? 'Entrée' : 'Sortie' }}
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                Prévisualisation du contrat avant signature
            </p>
        </div>

        <!-- Contract Document -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <!-- Document Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="text-center">
                    <h1 class="text-xl font-bold text-gray-900">CONTRAT DE BAIL MOBILITÉ</h1>
                    <h2 class="text-lg text-gray-700 mt-1">
                        {{ signatureType === 'entry' ? 'Document d\'Entrée' : 'Document de Sortie' }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-2">
                        Référence: BM-{{ bailMobilite.id }}-{{ signatureType.toUpperCase() }}
                    </p>
                </div>
            </div>

            <!-- Property Information -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Informations du Logement</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Adresse:</span>
                        <p class="text-gray-900">{{ bailMobilite.address }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Date de début:</span>
                        <p class="text-gray-900">{{ formatDate(bailMobilite.start_date) }}</p>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Date de fin:</span>
                        <p class="text-gray-900">{{ formatDate(bailMobilite.end_date) }}</p>
                    </div>
                </div>
            </div>

            <!-- Tenant Information -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Informations du Locataire</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Nom:</span>
                        <p class="text-gray-900">{{ bailMobilite.tenant_name }}</p>
                    </div>
                    <div v-if="bailMobilite.tenant_email">
                        <span class="font-medium text-gray-700">Email:</span>
                        <p class="text-gray-900">{{ bailMobilite.tenant_email }}</p>
                    </div>
                    <div v-if="bailMobilite.tenant_phone">
                        <span class="font-medium text-gray-700">Téléphone:</span>
                        <p class="text-gray-900">{{ bailMobilite.tenant_phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Contract Content -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Contenu du Contrat</h3>
                <div class="bg-gray-50 p-4 rounded-md">
                    <div class="whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">
                        {{ contractTemplate.content }}
                    </div>
                </div>
            </div>

            <!-- Special Notes -->
            <div v-if="bailMobilite.notes" class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Notes Spécifiques</h3>
                <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200">
                    <div class="whitespace-pre-wrap text-sm text-gray-700">
                        {{ bailMobilite.notes }}
                    </div>
                </div>
            </div>

            <!-- Signature Section Preview -->
            <div class="px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Signatures</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Admin Signature -->
                    <div class="border border-gray-300 rounded-md p-4 text-center">
                        <h4 class="font-medium text-gray-900 mb-3">Signature de l'Hôte/Propriétaire</h4>
                        <div v-if="contractTemplate.admin_signature" class="mb-3">
                            <img 
                                :src="contractTemplate.admin_signature" 
                                alt="Signature Hôte" 
                                class="max-h-16 mx-auto border border-gray-300 rounded"
                            >
                        </div>
                        <div v-else class="mb-3 h-16 bg-gray-100 rounded flex items-center justify-center">
                            <span class="text-gray-500 text-sm">Signature requise</span>
                        </div>
                        <div class="text-xs text-gray-600">
                            <p><strong>Nom:</strong> {{ contractTemplate.creator?.name || 'Administrateur' }}</p>
                            <p v-if="contractTemplate.admin_signed_at">
                                <strong>Date:</strong> {{ formatDateTime(contractTemplate.admin_signed_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Tenant Signature -->
                    <div class="border border-gray-300 rounded-md p-4 text-center">
                        <h4 class="font-medium text-gray-900 mb-3">Signature du Locataire</h4>
                        <div class="mb-3 h-16 bg-gray-100 rounded flex items-center justify-center">
                            <span class="text-gray-500 text-sm">À signer</span>
                        </div>
                        <div class="text-xs text-gray-600">
                            <p><strong>Nom:</strong> {{ bailMobilite.tenant_name }}</p>
                            <p><strong>Date:</strong> À définir</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-600">
                    Document généré électroniquement le {{ formatDateTime(new Date()) }}
                </p>
                <p class="text-xs text-gray-600 mt-1">
                    Ce document constitue un contrat légalement contraignant entre les parties signataires.
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <button
                @click="$emit('close')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Fermer
            </button>
            
            <button
                v-if="canProceedToSign"
                @click="$emit('proceed-to-sign')"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Procéder à la Signature
            </button>
        </div>

        <!-- Validation Warnings -->
        <div v-if="!canProceedToSign" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Signature non disponible</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Le contrat ne peut pas être signé pour les raisons suivantes:</p>
                        <ul class="list-disc pl-5 mt-1 space-y-1">
                            <li v-if="!contractTemplate.admin_signature">
                                La signature de l'administrateur est manquante sur le modèle de contrat
                            </li>
                            <li v-if="!contractTemplate.is_active">
                                Le modèle de contrat n'est pas actif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

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
    }
})

const emit = defineEmits(['close', 'proceed-to-sign'])

const canProceedToSign = computed(() => {
    return props.contractTemplate.admin_signature && 
           props.contractTemplate.is_active
})

const formatDate = (date) => {
    return format(new Date(date), 'dd/MM/yyyy', { locale: fr })
}

const formatDateTime = (date) => {
    return format(new Date(date), 'dd/MM/yyyy à HH:mm', { locale: fr })
}
</script>

<style scoped>
.contract-preview {
    max-width: 4xl;
}

.prose {
    max-width: none;
}
</style>