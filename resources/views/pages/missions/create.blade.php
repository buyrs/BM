<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Nouvelle Mission') }}
            </h2>
            <a href="{{ route('missions.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Retour
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('missions.store') }}" x-data="missionForm()">
                        @csrf
                        
                        <!-- Mission Type -->
                        <div class="mb-6">
                            <label for="mission_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Type de mission <span class="text-red-500">*</span>
                            </label>
                            <select name="mission_type" 
                                    id="mission_type"
                                    x-model="form.mission_type"
                                    @change="updateFormFields()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Sélectionner un type</option>
                                @foreach($missionTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('mission_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bail Mobilité Selection -->
                        <div class="mb-6" x-show="form.mission_type">
                            <label for="bail_mobilite_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bail Mobilité (optionnel)
                            </label>
                            <select name="bail_mobilite_id" 
                                    id="bail_mobilite_id"
                                    x-model="form.bail_mobilite_id"
                                    @change="loadBailMobiliteData()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Sélectionner un bail mobilité</option>
                                @foreach($bailMobilites as $bm)
                                    <option value="{{ $bm->id }}">{{ $bm->tenant_name }} - {{ $bm->address }}</option>
                                @endforeach
                            </select>
                            @error('bail_mobilite_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Property Address -->
                            <div class="md:col-span-2">
                                <label for="property_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Adresse du bien <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="property_address" 
                                       id="property_address"
                                       x-model="form.property_address"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="123 Rue de la Paix, 75001 Paris">
                                @error('property_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tenant Name -->
                            <div>
                                <label for="tenant_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nom du locataire <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="tenant_name" 
                                       id="tenant_name"
                                       x-model="form.tenant_name"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="Jean Dupont">
                                @error('tenant_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tenant Phone -->
                            <div>
                                <label for="tenant_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Téléphone du locataire
                                </label>
                                <input type="tel" 
                                       name="tenant_phone" 
                                       id="tenant_phone"
                                       x-model="form.tenant_phone"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="01 23 45 67 89">
                                @error('tenant_phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tenant Email -->
                            <div>
                                <label for="tenant_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Email du locataire
                                </label>
                                <input type="email" 
                                       name="tenant_email" 
                                       id="tenant_email"
                                       x-model="form.tenant_email"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                       placeholder="jean.dupont@example.com">
                                @error('tenant_email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Scheduled Date -->
                            <div>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Date prévue <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="scheduled_at" 
                                       id="scheduled_at"
                                       x-model="form.scheduled_at"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('scheduled_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Scheduled Time -->
                            <div>
                                <label for="scheduled_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Heure prévue
                                </label>
                                <input type="time" 
                                       name="scheduled_time" 
                                       id="scheduled_time"
                                       x-model="form.scheduled_time"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('scheduled_time')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Agent Assignment -->
                            <div>
                                <label for="agent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Agent assigné
                                </label>
                                <select name="agent_id" 
                                        id="agent_id"
                                        x-model="form.agent_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Non assigné</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                                @error('agent_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Auto Assign -->
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="auto_assign" 
                                       id="auto_assign"
                                       x-model="form.auto_assign"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="auto_assign" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Assignation automatique
                                </label>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes
                            </label>
                            <textarea name="notes" 
                                      id="notes"
                                      x-model="form.notes"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Notes supplémentaires..."></textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('missions.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Annuler
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Créer la mission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function missionForm() {
            return {
                form: {
                    mission_type: '',
                    bail_mobilite_id: '',
                    property_address: '',
                    tenant_name: '',
                    tenant_phone: '',
                    tenant_email: '',
                    scheduled_at: '',
                    scheduled_time: '',
                    agent_id: '',
                    auto_assign: false,
                    notes: ''
                },
                
                bailMobilites: @json($bailMobilites),
                
                updateFormFields() {
                    // Reset form when mission type changes
                    this.form.bail_mobilite_id = '';
                    this.form.property_address = '';
                    this.form.tenant_name = '';
                    this.form.tenant_phone = '';
                    this.form.tenant_email = '';
                },
                
                loadBailMobiliteData() {
                    if (this.form.bail_mobilite_id) {
                        const bailMobilite = this.bailMobilites.find(bm => bm.id == this.form.bail_mobilite_id);
                        if (bailMobilite) {
                            this.form.property_address = bailMobilite.address;
                            this.form.tenant_name = bailMobilite.tenant_name;
                            // You can add more fields as needed
                        }
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
