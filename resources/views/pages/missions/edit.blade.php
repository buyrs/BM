<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Modifier Mission #{{ $mission->id }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('missions.show', $mission) }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Retour
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('missions.update', $mission) }}" x-data="missionForm()">
                        @csrf
                        @method('PUT')
                        
                        <!-- Mission Type -->
                        <div class="mb-6">
                            <label for="mission_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Type de mission <span class="text-red-500">*</span>
                            </label>
                            <select name="mission_type" 
                                    id="mission_type"
                                    x-model="form.mission_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach($missionTypes as $value => $label)
                                    <option value="{{ $value }}" {{ $mission->mission_type === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('mission_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bail Mobilité Selection -->
                        <div class="mb-6">
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
                                    <option value="{{ $bm->id }}" {{ $mission->bail_mobilite_id == $bm->id ? 'selected' : '' }}>
                                        {{ $bm->tenant_name }} - {{ $bm->address }}
                                    </option>
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
                                       value="{{ old('property_address', $mission->address) }}"
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
                                       value="{{ old('tenant_name', $mission->tenant_name) }}"
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
                                       value="{{ old('tenant_phone', $mission->tenant_phone) }}"
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
                                       value="{{ old('tenant_email', $mission->tenant_email) }}"
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
                                       value="{{ old('scheduled_at', $mission->scheduled_at?->format('Y-m-d')) }}"
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
                                       value="{{ old('scheduled_time', $mission->scheduled_at?->format('H:i')) }}"
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
                                        <option value="{{ $agent->id }}" {{ $mission->agent_id == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('agent_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Statut <span class="text-red-500">*</span>
                                </label>
                                <select name="status" 
                                        id="status"
                                        x-model="form.status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="unassigned" {{ $mission->status === 'unassigned' ? 'selected' : '' }}>Non assigné</option>
                                    <option value="assigned" {{ $mission->status === 'assigned' ? 'selected' : '' }}>Assigné</option>
                                    <option value="in_progress" {{ $mission->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                    <option value="completed" {{ $mission->status === 'completed' ? 'selected' : '' }}>Terminé</option>
                                    <option value="cancelled" {{ $mission->status === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
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
                                      placeholder="Notes supplémentaires...">{{ old('notes', $mission->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('missions.show', $mission) }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Annuler
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Mettre à jour
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
                    mission_type: '{{ $mission->mission_type }}',
                    bail_mobilite_id: '{{ $mission->bail_mobilite_id }}',
                    property_address: '{{ $mission->address }}',
                    tenant_name: '{{ $mission->tenant_name }}',
                    tenant_phone: '{{ $mission->tenant_phone }}',
                    tenant_email: '{{ $mission->tenant_email }}',
                    scheduled_at: '{{ $mission->scheduled_at?->format('Y-m-d') }}',
                    scheduled_time: '{{ $mission->scheduled_at?->format('H:i') }}',
                    agent_id: '{{ $mission->agent_id }}',
                    status: '{{ $mission->status }}',
                    notes: '{{ $mission->notes }}'
                },
                
                bailMobilites: @json($bailMobilites),
                
                loadBailMobiliteData() {
                    if (this.form.bail_mobilite_id) {
                        const bailMobilite = this.bailMobilites.find(bm => bm.id == this.form.bail_mobilite_id);
                        if (bailMobilite) {
                            this.form.property_address = bailMobilite.address;
                            this.form.tenant_name = bailMobilite.tenant_name;
                        }
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
