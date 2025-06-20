<template>
    <Head title="Manage Checkers" />
    <DashboardAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Checkers</h2>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Create Checker Form -->
                <form @submit.prevent="createChecker" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-8">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="checkerEmail">
                            Checker Email
                        </label>
                        <input v-model="checkerEmail" id="checkerEmail" type="email" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" />
                    </div>
                    <div class="flex items-center justify-between">
                        <PrimaryButton type="submit">Create Checker</PrimaryButton>
                    </div>
                    <div v-if="generatedPassword" class="mt-4 text-green-600">
                        Generated Password: <span class="font-mono">{{ generatedPassword }}</span>
                    </div>
                </form>

                <!-- Checkers List -->
                <div class="bg-white shadow-md rounded px-8 pt-6 pb-8">
                    <h3 class="text-lg font-bold mb-4">Checker List</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="checker in checkers" :key="checker.id" class="bg-white">
                                <td class="px-6 py-4 whitespace-nowrap">{{ checker.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="checker.enabled ? 'text-green-600' : 'text-red-600'">
                                        {{ checker.enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <PrimaryButton v-if="!checker.enabled" @click="enableChecker(checker.id)">Enable</PrimaryButton>
                                    <PrimaryButton v-else @click="disableChecker(checker.id)" class="bg-red-500 hover:bg-red-700">Disable</PrimaryButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Placeholder data and logic
const checkerEmail = ref('');
const generatedPassword = ref('');
const checkers = ref([
    { id: 1, email: 'checker1@example.com', enabled: true },
    { id: 2, email: 'checker2@example.com', enabled: false },
]);

function generatePassword(length = 10) {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let pass = '';
    for (let i = 0; i < length; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return pass;
}

function createChecker() {
    // Placeholder: Replace with API call
    const password = generatePassword();
    generatedPassword.value = password;
    checkers.value.push({
        id: Date.now(),
        email: checkerEmail.value,
        enabled: true,
    });
    checkerEmail.value = '';
    // In real app, send email and password to backend
}

function enableChecker(id) {
    // Placeholder: Replace with API call
    const checker = checkers.value.find(c => c.id === id);
    if (checker) checker.enabled = true;
}

function disableChecker(id) {
    // Placeholder: Replace with API call
    const checker = checkers.value.find(c => c.id === id);
    if (checker) checker.enabled = false;
}
</script> 