@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Checklist for Mission: {{ $checklist->mission->title }} ({{ ucfirst($checklist->type) }})</h1>

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

        <div class="space-y-6">
            @foreach ($checklist->checklistItems as $item)
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">{{ $item->amenity->amenityType->name }} - {{ $item->amenity->name }}</h2>

                    <div class="mb-4">
                        <label for="state_{{ $item->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">State</label>
                        <select name="items[{{ $item->id }}][state]" id="state_{{ $item->id }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            <option value="">Select State</option>
                            <option value="bad" {{ $item->state === 'bad' ? 'selected' : '' }}>Bad</option>
                            <option value="average" {{ $item->state === 'average' ? 'selected' : '' }}>Average</option>
                            <option value="good" {{ $item->state === 'good' ? 'selected' : '' }}>Good</option>
                            <option value="excellent" {{ $item->state === 'excellent' ? 'selected' : '' }}>Excellent</option>
                            <option value="need_a_fix" {{ $item->state === 'need_a_fix' ? 'selected' : '' }}>Need a Fix</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="comment_{{ $item->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comment</label>
                        <textarea name="items[{{ $item->id }}][comment]" id="comment_{{ $item->id }}" rows="3" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $item->comment }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="photo_{{ $item->id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Photo</label>
                        <input type="file" name="items[{{ $item->id }}][photo]" id="photo_{{ $item->id }}" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @if ($item->photo_path)
                            <img src="{{ asset('storage/' . $item->photo_path) }}" alt="Item Photo" class="mt-2 w-32 h-32 object-cover rounded-md">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Tenant Signature</h2>
            <div x-data="signaturePadComponent()" x-init="initSignaturePad()">
                <canvas x-ref="signatureCanvas" class="signature-pad-canvas border border-gray-300 dark:border-gray-600 rounded-md w-full h-48"></canvas>
                <input type="hidden" name="signature_data" x-model="savedSignature">
                <div class="mt-4 space-x-2">
                    <button type="button" @click="clearSignature()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300">Clear Signature</button>
                    <button type="button" @click="saveSignature()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">Save Signature</button>
                </div>
                <p x-show="signaturePad.isEmpty()" class="text-red-500 text-sm mt-2">Please provide a signature.</p>
            </div>
        </div>

        <div class="mt-6 flex justify-between">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">Save Checklist</button>
            <button type="button" onclick="document.getElementById('submit-checklist-form').submit();" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300">Submit Checklist</button>
        </div>
    </form>

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
