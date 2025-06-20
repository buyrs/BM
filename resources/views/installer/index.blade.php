<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Wizard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100">
    <div id="app" class="min-h-screen">
        <div class="max-w-4xl mx-auto py-8 px-4">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6">Installation Wizard</h1>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="(step, index) in steps" :key="index" 
                             class="flex items-center"
                             :class="{ 'text-blue-600': currentStep >= index + 1, 'text-gray-400': currentStep < index + 1 }">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center border-2"
                                     :class="currentStep >= index + 1 ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300'">
                                    @{{ index + 1 }}
                                </div>
                                <span class="ml-2">@{{ step }}</span>
                            </div>
                            <div v-if="index < steps.length - 1" class="w-24 h-0.5 mx-4"
                                 :class="currentStep > index + 1 ? 'bg-blue-600' : 'bg-gray-300'"></div>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Requirements -->
                <div v-if="currentStep === 1">
                    <h2 class="text-xl font-semibold mb-4">System Requirements</h2>
                    
                    <!-- PHP Version -->
                    <div class="mb-4">
                        <h3 class="font-medium mb-2">PHP Version</h3>
                        <div class="flex items-center">
                            <span class="mr-2">Required: 8.1.0</span>
                            <span class="mr-2">Current: {{ $requirements['php']['current'] }}</span>
                            <span :class="$requirements['php']['status'] ? 'text-green-600' : 'text-red-600'">
                                {{ $requirements['php']['status'] ? '✓' : '✗' }}
                            </span>
                        </div>
                    </div>

                    <!-- PHP Extensions -->
                    <div class="mb-4">
                        <h3 class="font-medium mb-2">PHP Extensions</h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($requirements['extensions'] as $extension => $installed)
                                <div class="flex items-center">
                                    <span class="mr-2">{{ $extension }}</span>
                                    <span :class="$installed ? 'text-green-600' : 'text-red-600'">
                                        {{ $installed ? '✓' : '✗' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Directory Permissions -->
                    <div class="mb-4">
                        <h3 class="font-medium mb-2">Directory Permissions</h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($permissions as $path => $permission)
                                <div class="flex items-center">
                                    <span class="mr-2">{{ $path }}</span>
                                    <span :class="$permission['writable'] ? 'text-green-600' : 'text-red-600'">
                                        {{ $permission['writable'] ? '✓' : '✗' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button @click="nextStep" 
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                                :disabled="!canProceed">
                            Next Step
                        </button>
                    </div>
                </div>

                <!-- Step 2: Database Configuration -->
                <div v-if="currentStep === 2">
                    <h2 class="text-xl font-semibold mb-4">Database Configuration</h2>
                    
                    <form @submit.prevent="testDatabase" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Host</label>
                            <input type="text" v-model="dbConfig.host" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Port</label>
                            <input type="text" v-model="dbConfig.port" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Name</label>
                            <input type="text" v-model="dbConfig.database" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Username</label>
                            <input type="text" v-model="dbConfig.username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Database Password</label>
                            <input type="password" v-model="dbConfig.password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        </div>

                        <div class="flex justify-between mt-6">
                            <button type="button" @click="prevStep" 
                                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                Previous
                            </button>
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Test Connection
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Installation -->
                <div v-if="currentStep === 3">
                    <h2 class="text-xl font-semibold mb-4">Installation</h2>
                    
                    <div class="space-y-4">
                        <div v-for="(step, index) in installationSteps" :key="index"
                             class="flex items-center">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mr-2"
                                 :class="getStepClass(index)">
                                @{{ index < currentInstallationStep ? '✓' : (index === currentInstallationStep ? '⟳' : '○') }}
                            </div>
                            <span>@{{ step }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button @click="prevStep" 
                                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
                                :disabled="isInstalling">
                            Previous
                        </button>
                        <button @click="startInstallation" 
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                                :disabled="isInstalling">
                            Start Installation
                        </button>
                    </div>
                </div>

                <!-- Step 4: Completion -->
                <div v-if="currentStep === 4">
                    <div class="text-center py-8">
                        <div class="text-green-600 text-5xl mb-4">✓</div>
                        <h2 class="text-2xl font-bold mb-4">Installation Completed!</h2>
                        <p class="text-gray-600 mb-6">Your application has been successfully installed.</p>
                        <a href="/" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue

        createApp({
            data() {
                return {
                    currentStep: 1,
                    steps: ['Requirements', 'Database', 'Installation', 'Complete'],
                    dbConfig: {
                        host: 'localhost',
                        port: '3306',
                        database: '',
                        username: '',
                        password: ''
                    },
                    isInstalling: false,
                    currentInstallationStep: 0,
                    installationSteps: [
                        'Running migrations...',
                        'Seeding database...',
                        'Generating application key...',
                        'Optimizing application...',
                        'Finalizing installation...'
                    ]
                }
            },
            computed: {
                canProceed() {
                    if (this.currentStep === 1) {
                        return {{ $requirements['php']['status'] }} && 
                               Object.values({{ json_encode($requirements['extensions']) }}).every(v => v) &&
                               Object.values({{ json_encode($permissions) }}).every(p => p.writable);
                    }
                    return true;
                }
            },
            methods: {
                nextStep() {
                    if (this.currentStep < this.steps.length) {
                        this.currentStep++;
                    }
                },
                prevStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                    }
                },
                async testDatabase() {
                    try {
                        const response = await axios.post('/install/database', this.dbConfig);
                        alert('Database connection successful!');
                        this.nextStep();
                    } catch (error) {
                        alert(error.response?.data?.error || 'Database connection failed');
                    }
                },
                getStepClass(index) {
                    if (index < this.currentInstallationStep) {
                        return 'bg-green-600 text-white';
                    } else if (index === this.currentInstallationStep) {
                        return 'bg-blue-600 text-white animate-spin';
                    }
                    return 'bg-gray-200 text-gray-600';
                },
                async startInstallation() {
                    this.isInstalling = true;
                    try {
                        for (let i = 0; i < this.installationSteps.length; i++) {
                            this.currentInstallationStep = i;
                            await new Promise(resolve => setTimeout(resolve, 1000));
                        }
                        
                        const response = await axios.post('/install/install');
                        this.nextStep();
                    } catch (error) {
                        alert(error.response?.data?.error || 'Installation failed');
                    } finally {
                        this.isInstalling = false;
                    }
                }
            }
        }).mount('#app')
    </script>
</body>
</html> 