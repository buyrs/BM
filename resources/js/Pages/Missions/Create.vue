<template>
    <DashboardAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Mission
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Type -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Type
                                </label>
                                <select
                                    v-model="form.type"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                >
                                    <option value="checkin">Check-in</option>
                                    <option value="checkout">Check-out</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.type }}
                                </div>
                            </div>

                            <!-- Scheduled At -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Date and Time
                                </label>
                                <input
                                    type="datetime-local"
                                    v-model="form.scheduled_at"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                <div v-if="form.errors.scheduled_at" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.scheduled_at }}
                                </div>
                            </div>

                            <!-- Apartment Address -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Apartment Address
                                </label>
                                <input
                                    type="text"
                                    v-model="form.address"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                <div v-if="form.errors.address" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <!-- Guest Name -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Guest Name
                                </label>
                                <input
                                    type="text"
                                    v-model="form.tenant_name"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                <div v-if="form.errors.tenant_name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.tenant_name }}
                                </div>
                            </div>

                            <!-- Apartment Code -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Apartment Code
                                </label>
                                <input
                                    type="text"
                                    v-model="form.apartment_code"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                <div v-if="form.errors.apartment_code" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.apartment_code }}
                                </div>
                            </div>

                            <!-- Entry Date -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Entry Date
                                </label>
                                <input
                                    type="date"
                                    v-model="form.entry_date"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                <div v-if="form.errors.entry_date" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.entry_date }}
                                </div>
                            </div>

                            <!-- Duration of Rent (days) -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Duration of Rent (days)
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    v-model="form.duration"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                <div v-if="form.errors.duration" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.duration }}
                                </div>
                            </div>

                            <!-- Auto-assign Option -->
                            <div>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" v-model="form.auto_assign" class="form-checkbox h-5 w-5 text-indigo-600">
                                    <span class="ml-2 text-gray-700">Auto-assign checker</span>
                                </label>
                            </div>

                            <!-- Assign Checker (Searchable Dropdown) -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Assign Checker
                                </label>
                                <input
                                    type="text"
                                    v-model="checkerSearch"
                                    placeholder="Search checker by name or email..."
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mb-2"
                                    :disabled="form.auto_assign"
                                >
                                <select
                                    v-model="form.checker_id"
                                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    :disabled="form.auto_assign"
                                >
                                    <option value="">Select a checker</option>
                                    <option v-for="checker in filteredCheckers" :key="checker.id" :value="checker.id">
                                        {{ checker.name }} ({{ checker.email }})
                                    </option>
                                </select>
                                <div v-if="form.errors.checker_id" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.checker_id }}
                                </div>
                            </div>

                            <!-- CSV Upload Option -->
                            <div class="mt-6">
                                <label class="block font-medium text-sm text-gray-700 mb-2">
                                    Or upload CSV to create missions in bulk
                                </label>
                                <input type="file" accept=".csv" class="block w-full text-sm text-gray-500" disabled />
                                <p class="text-xs text-gray-400 mt-1">(CSV upload coming soon)</p>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :disabled="form.processing"
                                >
                                    Create Mission
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    checkers: Array
})

const checkerSearch = ref('')

const form = useForm({
    type: 'checkin',
    scheduled_at: '',
    address: '',
    tenant_name: '',
    tenant_phone: '',
    tenant_email: '',
    notes: '',
    agent_id: '',
    apartment_code: '',
    entry_date: '',
    duration: '',
    checker_id: '',
    auto_assign: false
})

const filteredCheckers = computed(() => {
    if (!checkerSearch.value) return props.checkers
    return props.checkers.filter(checker =>
        (checker.name && checker.name.toLowerCase().includes(checkerSearch.value.toLowerCase())) ||
        (checker.email && checker.email.toLowerCase().includes(checkerSearch.value.toLowerCase()))
    )
})

const submit = () => {
    form.post(route('missions.store'))
}
</script>