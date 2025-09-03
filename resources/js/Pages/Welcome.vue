<template>
    <div class="min-h-screen bg-background">
        <!-- Hero Section -->
        <div class="relative isolate px-6 pt-14 lg:px-8">
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-primary to-secondary opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
            </div>
            
            <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-text-primary sm:text-6xl">
                        Bail Mobilité
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-text-secondary">
                        Streamline your property management process with our comprehensive checklist system. Monitor, manage, and maintain your properties efficiently.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a
                            v-if="canLogin"
                            href="/admin/login"
                            class="rounded-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            Log in as Admin
                        </a>
                        <a
                            v-if="canLogin"
                            href="/ops/login"
                            class="rounded-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            Log in as Ops
                        </a>
                        <a
                            v-if="canLogin"
                            href="/checker/login"
                            class="rounded-md bg-primary px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            Log in as Checker
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white">
            <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
                <div class="flex justify-center space-x-6 md:order-2">
                    <a href="#" class="text-text-secondary hover:text-primary">
                        <span class="sr-only">Privacy Policy</span>
                        Privacy
                    </a>
                    <a href="#" class="text-text-secondary hover:text-primary">
                        <span class="sr-only">Terms of Service</span>
                        Terms
                    </a>
                </div>
                <div class="mt-8 md:order-1 md:mt-0">
                    <p class="text-center text-xs leading-5 text-text-secondary">
                        &copy; 2024 Bail Mobilité. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script> 