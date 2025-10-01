@extends('layouts.app')

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 dark:text-white mb-4 break-words">Checklist for Mission: {{ $checklist->mission->title }} ({{ ucfirst($checklist->type) }})</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('checklists.update', $checklist->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-4 sm:space-y-6">
            @foreach ($checklist->checklistItems as $item)
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-white mb-3 sm:mb-4 break-words">{{ $item->amenity->amenityType->name }} - {{ $item->amenity->name }}</h2>

                    <div class="mb-3 sm:mb-4">
                        <label for="state_{{ $item->id }}" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">State</label>
                        <select name="items[{{ $item->id }}][state]" id="state_{{ $item->id }}" class="block w-full px-2 sm:px-3 py-2 sm:py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm sm:text-base" required>
                            <option value="">Select State</option>
                            <option value="bad" {{ $item->state === 'bad' ? 'selected' : '' }}>Bad</option>
                            <option value="average" {{ $item->state === 'average' ? 'selected' : '' }}>Average</option>
                            <option value="good" {{ $item->state === 'good' ? 'selected' : '' }}>Good</option>
                            <option value="excellent" {{ $item->state === 'excellent' ? 'selected' : '' }}>Excellent</option>
                            <option value="need_a_fix" {{ $item->state === 'need_a_fix' ? 'selected' : '' }}>Need a Fix</option>
                        </select>
                    </div>

                    <div class="mb-3 sm:mb-4">
                        <label for="comment_{{ $item->id }}" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment</label>
                        <textarea name="items[{{ $item->id }}][comment]" id="comment_{{ $item->id }}" rows="2" class="block w-full px-2 sm:px-3 py-1 sm:py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm sm:text-base">{{ $item->comment }}</textarea>
                    </div>

                    <div class="mb-3 sm:mb-4">
                        <label for="photo_{{ $item->id }}" class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Photo</label>
                        <input type="file" name="items[{{ $item->id }}][photo]" id="photo_{{ $item->id }}" class="block w-full text-xs sm:text-sm text-gray-500 file:mr-2 sm:file:mr-4 file:py-1 sm:file:py-2 file:px-2 sm:file:px-3 file:rounded file:border-0 file:text-xs sm:file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @if ($item->photo_path)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $item->photo_path) }}" alt="Item Photo" class="w-20 h-20 sm:w-32 sm:h-32 object-cover rounded-md">
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 sm:mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-800 dark:text-white mb-3 sm:mb-4">Tenant Signature</h2>
            <div x-data="signaturePadComponent()" x-init="initSignaturePad()">
                <canvas x-ref="signatureCanvas" class="signature-pad-canvas border border-gray-300 dark:border-gray-600 rounded-md w-full h-32 sm:h-48"></canvas>
                <input type="hidden" name="signature_data" x-model="savedSignature">
                <div class="mt-3 sm:mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="clearSignature()" class="px-3 py-2 sm:px-4 sm:py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300 text-sm">Clear Signature</button>
                    <button type="button" @click="saveSignature()" class="px-3 py-2 sm:px-4 sm:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 text-sm">Save Signature</button>
                </div>
                <p x-show="signaturePad.isEmpty()" class="text-red-500 text-xs sm:text-sm mt-2">Please provide a signature.</p>
            </div>
        </div>

        <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row justify-between gap-3 sm:gap-0">
            <!-- Save for later (with offline capability) -->
            <button type="button" onclick="saveChecklistOffline()" class="px-4 py-2 sm:px-6 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 w-full sm:w-auto">
                <span id="save-btn-text">Save Checklist</span>
                <span id="save-indicator" class="ml-2 hidden">💾</span>
            </button>
            
            <!-- Submit form (with offline queuing if needed) -->
            <button type="button" onclick="submitChecklist()" class="px-4 py-2 sm:px-6 sm:py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300 w-full sm:w-auto">
                <span id="submit-btn-text">Submit Checklist</span>
                <span id="submit-indicator" class="ml-2 hidden">📤</span>
            </button>
        </div>
    </form>
    
    <script>
        // Function to save checklist data for offline use
        function saveChecklistOffline() {
            const formData = new FormData(document.querySelector('form'));
            const checklistData = {};
            
            // Extract form data
            for (let [key, value] of formData.entries()) {
                checklistData[key] = value;
            }
            
            // Save to local storage with a timestamp
            const saveData = {
                id: {{ $checklist->id }},
                checklistId: {{ $checklist->id }},
                data: checklistData,
                savedAt: new Date().toISOString(),
                missionTitle: "{{ $checklist->mission->title }}",
                checklistType: "{{ $checklist->type }}"
            };
            
            localStorage.setItem('offline-checklist-{{ $checklist->id }}', JSON.stringify(saveData));
            
            // Update UI to show saved status
            document.getElementById('save-btn-text').textContent = 'Saved Offline!';
            document.getElementById('save-indicator').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('save-btn-text').textContent = 'Save Checklist';
                document.getElementById('save-indicator').classList.add('hidden');
            }, 2000);
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
            alertDiv.innerHTML = '<span>Checklist saved offline and will sync when online.</span>';
            document.querySelector('form').prepend(alertDiv);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }
        
        // Function to submit checklist with offline capability
        function submitChecklist() {
            if (navigator.onLine) {
                // If online, submit normally
                document.getElementById('submit-indicator').classList.remove('hidden');
                document.getElementById('submit-btn-text').textContent = 'Submitting...';
                
                // Try to submit the form
                try {
                    document.getElementById('submit-checklist-form').submit();
                } catch (e) {
                    // If submission fails, store for later sync
                    queueChecklistSubmission();
                }
            } else {
                // If offline, queue for later submission
                queueChecklistSubmission();
            }
        }
        
        // Function to queue checklist for later submission
        function queueChecklistSubmission() {
            const formData = new FormData(document.querySelector('form'));
            const checklistData = {};
            
            // Extract form data
            for (let [key, value] of formData.entries()) {
                checklistData[key] = value;
            }
            
            // Queue the submission
            const submissionData = {
                id: Date.now(),
                url: "{{ route('checklists.submit', $checklist->id) }}",
                method: 'POST',
                csrfToken: document.querySelector('input[name="_token"]').value,
                data: checklistData,
                queuedAt: new Date().toISOString(),
                missionTitle: "{{ $checklist->mission->title }}",
                checklistType: "{{ $checklist->type }}"
            };
            
            // Add to submission queue
            const queue = JSON.parse(localStorage.getItem('checklist-submission-queue') || '[]');
            queue.push(submissionData);
            localStorage.setItem('checklist-submission-queue', JSON.stringify(queue));
            
            // Update UI
            document.getElementById('submit-btn-text').textContent = 'Queued!';
            document.getElementById('submit-indicator').classList.remove('hidden');
            
            setTimeout(() => {
                document.getElementById('submit-btn-text').textContent = 'Submit Checklist';
                document.getElementById('submit-indicator').classList.add('hidden');
            }, 2000);
            
            // Show queued message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4';
            alertDiv.innerHTML = '<span>Checklist queued for submission. Will submit when online.</span>';
            document.querySelector('form').prepend(alertDiv);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
            
            // Trigger service worker to sync when back online
            if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'SYNC_DATA' });
            }
        }
        
        // Auto-sync when page loads and user comes online
        window.addEventListener('load', function() {
            if (navigator.onLine) {
                // Check for any queued submissions
                const queue = JSON.parse(localStorage.getItem('checklist-submission-queue') || '[]');
                if (queue.length > 0) {
                    // Trigger service worker to process queued items
                    if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                        navigator.serviceWorker.controller.postMessage({ type: 'SYNC_DATA' });
                    }
                }
            }
        });
    </script>

    <form id="submit-checklist-form" action="{{ route('checklists.submit', $checklist->id) }}" method="POST" class="hidden">
        @csrf
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@5.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    function signaturePadComponent() {
        return {
            signaturePad: null,
            savedSignature: '{{ $checklist->signature_path ? asset('storage/' . $checklist->signature_path) : '' }}',

            initSignaturePad() {
                const canvas = this.$refs.signatureCanvas;

                // Adjust canvas size for high DPI screens and responsiveness
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                this.signaturePad = new SignaturePad(canvas);

                if (this.savedSignature) {
                    this.signaturePad.fromDataURL(this.savedSignature);
                }

                // Optional: Handle canvas resize to clear the signature
                window.addEventListener('resize', () => {
                    this.signaturePad.clear();
                });
            },

            clearSignature() {
                this.signaturePad.clear();
                this.savedSignature = null;
            },

            saveSignature() {
                if (this.signaturePad.isEmpty()) {
                    alert("Please provide a signature first.");
                } else {
                    this.savedSignature = this.signaturePad.toDataURL();
                }
            }
        }
    }
</script>
@endpush
