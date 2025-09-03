<template>
    <Head title="Manage Checkers" />
    <DashboardAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-text-primary leading-tight">Manage Checkers</h2>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Create Checker Form -->
                <form @submit.prevent="createChecker" class="bg-white shadow-md rounded-lg p-6 mb-8">
                    <div class="mb-4">
                        <InputLabel for="checkerEmail" value="Checker Email" />
                        <TextInput v-model="checkerEmail" id="checkerEmail" type="email" required class="mt-1 block w-full" />
                    </div>
                    <div class="flex items-center justify-between">
                        <PrimaryButton type="submit">Create Checker</PrimaryButton>
                    </div>
                    <div v-if="generatedPassword" class="mt-4 text-success-text">
                        Generated Password: <span class="font-mono">{{ generatedPassword }}</span>
                    </div>
                </form>

                <!-- Checkers List -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4 text-text-primary">Checker List</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-secondary uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="checker in checkers" :key="checker.id" class="bg-white">
                                <td class="px-6 py-4 whitespace-nowrap text-text-primary">{{ checker.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="checker.enabled ? 'text-success-text' : 'text-error-text'">
                                        {{ checker.enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <PrimaryButton v-if="!checker.enabled" @click="enableChecker(checker.id)">Enable</PrimaryButton>
                                    <DangerButton v-else @click="disableChecker(checker.id)">Disable</DangerButton>
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
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

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