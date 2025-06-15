<template>
    <DashboardSuperAdmin>
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

                            <!-- Address -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Address
                                </label>
                                <textarea
                                    v-model="form.address"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                />
                                <div v-if="form.errors.address" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <!-- Tenant Information -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block font-medium text-sm text-gray-700">
                                        Tenant Name
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

                                <div>
                                    <label class="block font-medium text-sm text-gray-700">
                                        Tenant Phone
                                    </label>
                                    <input
                                        type="tel"
                                        v-model="form.tenant_phone"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                    <div v-if="form.errors.tenant_phone" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.tenant_phone }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700">
                                        Tenant Email
                                    </label>
                                    <input
                                        type="email"
                                        v-model="form.tenant_email"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                    <div v-if="form.errors.tenant_email" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.tenant_email }}
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Notes
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                />
                                <div v-if="form.errors.notes" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.notes }}
                                </div>
                            </div>

                            <!-- Assign Agent -->
                            <div>
                                <label class="block font-medium text-sm text-gray-700">
                                    Assign Agent (Optional)
                                </label>
                                <select
                                    v-model="form.agent_id"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                >
                                    <option value="">Select an agent</option>
                                    <option v-for="checker in checkers" :key="checker.id" :value="checker.id">
                                        {{ checker.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.agent_id" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.agent_id }}
                                </div>
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
    </DashboardSuperAdmin>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import DashboardSuperAdmin from '@/Layouts/DashboardSuperAdmin.vue'

const props = defineProps({
    checkers: Array
})

const form = useForm({
    type: 'checkin',
    scheduled_at: '',
    address: '',
    tenant_name: '',
    tenant_phone: '',
    tenant_email: '',
    notes: '',
    agent_id: ''
})

const submit = () => {
    form.post(route('missions.store'))
}
</script>