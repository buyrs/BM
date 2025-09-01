<template>
    <div>
        <Head title="Nouveau Bail Mobilité" />

        <DashboardOps>
            <template #header>
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('ops.bail-mobilites.index')"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                        Nouveau Bail Mobilité
                    </h2>
                </div>
            </template>

            <div class="py-12">
                <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <form @submit.prevent="submit">
                            <div class="space-y-6">
                                <!-- Dates Section -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Dates du Séjour</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                                Date de Début *
                                            </label>
                                            <input
                                                id="start_date"
                                                v-model="form.start_date"
                                                type="date"
                                                :min="today"
                                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                                :class="{ 'border-red-300': form.errors.start_date }"
                                                required
                                            />
                                            <div v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                                                {{ form.errors.start_date }}
                                            </div>
                                        </div>

                                        <div>
                                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                                Date de Fin *
                                            </label>
                                            <input
                                                id="end_date"
                                                v-model="form.end_date"
                                                type="date"
                                                :min="form.start_date || today"
                                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                                :class="{ 'border-red-300': form.errors.end_date }"
                                                required
                                            />
                                            <div v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                                {{ form.errors.end_date }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="durationDays > 0" class="mt-2 text-sm text-gray-600">
                                        Durée du séjour: {{ durationDays }} jour{{ durationDays > 1 ? 's' : '' }}
                                    </div>
                                </div>

                                <!-- Property Information -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du Logement</h3>
                                    <div>
                                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                            Adresse Complète *
                                        </label>
                                        <textarea
                                            id="address"
                                            v-model="form.address"
                                            rows="3"
                                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                            :class="{ 'border-red-300': form.errors.address }"
                                            placeholder="Adresse complète du logement..."
                                            required
                                        ></textarea>
                                        <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.address }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Tenant Information -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du Locataire</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="tenant_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                Nom Complet *
                                            </label>
                                            <input
                                                id="tenant_name"
                                                v-model="form.tenant_name"
                                                type="text"
                                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                                :class="{ 'border-red-300': form.errors.tenant_name }"
                                                placeholder="Nom et prénom du locataire"
                                                required
                                            />
                                            <div v-if="form.errors.tenant_name" class="mt-1 text-sm text-red-600">
                                                {{ form.errors.tenant_name }}
                                            </div>
                                        </div>

                                        <div>
                                            <label for="tenant_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                                Téléphone
                                            </label>
                                            <input
                                                id="tenant_phone"
                                                v-model="form.tenant_phone"
                                                type="tel"
                                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                                :class="{ 'border-red-300': form.errors.tenant_phone }"
                                                placeholder="+33 6 12 34 56 78"
                                            />
                                            <div v-if="form.errors.tenant_phone" class="mt-1 text-sm text-red-600">
                                                {{ form.errors.tenant_phone }}
                                            </div>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label for="tenant_email" class="block text-sm font-medium text-gray-700 mb-2">
                                                Email
                                            </label>
                                            <input
                                                id="tenant_email"
                                                v-model="form.tenant_email"
                                                type="email"
                                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                                :class="{ 'border-red-300': form.errors.tenant_email }"
                                                placeholder="email@exemple.com"
                                            />
                                            <div v-if="form.errors.tenant_email" class="mt-1 text-sm text-red-600">
                                                {{ form.errors.tenant_email }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Notes -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Notes Additionnelles</h3>
                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                            Notes et Instructions Spéciales
                                        </label>
                                        <textarea
                                            id="notes"
                                            v-model="form.notes"
                                            rows="4"
                                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                            :class="{ 'border-red-300': form.errors.notes }"
                                            placeholder="Instructions spéciales, codes d'accès, informations importantes..."
                                        ></textarea>
                                        <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                                            {{ form.errors.notes }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary -->
                                <div v-if="form.start_date && form.end_date && form.tenant_name" class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">Résumé</h4>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <p><strong>Locataire:</strong> {{ form.tenant_name }}</p>
                                        <p><strong>Période:</strong> {{ formatDate(form.start_date) }} - {{ formatDate(form.end_date) }}</p>
                                        <p><strong>Durée:</strong> {{ durationDays }} jour{{ durationDays > 1 ? 's' : '' }}</p>
                                        <p class="text-xs text-gray-500 mt-2">
                                            Les missions d'entrée et de sortie seront automatiquement créées.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                                <Link
                                    :href="route('ops.bail-mobilites.index')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    Annuler
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span v-if="form.processing">Création en cours...</span>
                                    <span v-else>Créer le Bail Mobilité</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </DashboardOps>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'

const form = useForm({
    start_date: '',
    end_date: '',
    address: '',
    tenant_name: '',
    tenant_phone: '',
    tenant_email: '',
    notes: ''
})

const today = new Date().toISOString().split('T')[0]

const durationDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0
    const start = new Date(form.start_date)
    const end = new Date(form.end_date)
    const diffTime = end - start
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return Math.max(0, diffDays)
})

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR')
}

const submit = () => {
    form.post(route('ops.bail-mobilites.store'))
}
</script>