@extends('layouts.guest')

@section('title', 'Signer le Document')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Signature Électronique
            </h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                Veuillez signer le document ci-dessous
            </p>
        </div>

        <!-- Document Info -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
                        Informations du document
                    </h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Référence:</span>
                            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $invitation->workflow->document_reference }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Type:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ ucfirst($invitation->workflow->document_type) }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Expire le:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $invitation->expires_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
                        Votre information
                    </h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $invitation->name }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Email:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $invitation->email }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Rôle:</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ $invitation->role }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Preview -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Aperçu du document
            </h3>
            
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Document: {{ $invitation->workflow->document_reference }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                    Cliquez sur "Voir le document" pour consulter le contenu complet
                </p>
                
                <button type="button" onclick="openDocumentPreview()"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Voir le document
                </button>
            </div>
        </div>

        <!-- Signature Form -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Signature électronique
            </h3>
            
            <form action="{{ route('signatures.process-signature', $invitation) }}" method="POST" id="signatureForm">
                @csrf
                
                <!-- Consent Checkbox -->
                <div class="mb-6">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="consent" name="consent" required
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="consent" class="font-medium text-gray-700 dark:text-gray-300">
                                Je certifie avoir pris connaissance du document et donne mon consentement électronique
                            </label>
                            <p class="text-gray-500 dark:text-gray-400">
                                Cette signature a la même valeur légale qu'une signature manuscrite
                            </p>
                        </div>
                    </div>
                    @error('consent')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Signature Pad -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Signature *
                    </label>
                    
                    <div class="border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        <canvas id="signaturePad" width="600" height="200" 
                               class="w-full h-48 cursor-crosshair touch-none"></canvas>
                    </div>
                    
                    <div class="mt-2 flex justify-between items-center">
                        <button type="button" onclick="clearSignature()"
                                class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                            Effacer
                        </button>
                        <input type="hidden" id="signatureData" name="signature" required>
                    </div>
                    
                    @error('signature')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex justify-center space-x-4">
                    <button type="button" onclick="declineSignature()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-md font-medium">
                        Refuser
                    </button>
                    
                    <button type="submit" id="submitButton"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        Signer le document
                    </button>
                </div>
            </form>
        </div>

        <!-- Legal Notice -->
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Conformément à la réglementation en vigueur, cette signature électronique est juridiquement contraignante.
                <br>
                Votre IP: {{ request()->ip() }} - Horodatage: {{ now()->format('d/m/Y H:i:s') }}
            </p>
        </div>
    </div>
</div>

<!-- Document Preview Modal -->
<div id="documentModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl max-h-full w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Aperçu du document
            </h3>
            <button onclick="closeDocumentPreview()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg max-h-96 overflow-y-auto">
            <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono">
{{ $documentContent }}
            </pre>
        </div>
        
        <div class="mt-4 flex justify-end">
            <button onclick="closeDocumentPreview()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Fermer
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    let signaturePad;
    
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('signaturePad');
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 1,
            maxWidth: 3,
        });
        
        // Handle form validation
        const form = document.getElementById('signatureForm');
        const consent = document.getElementById('consent');
        const submitButton = document.getElementById('submitButton');
        
        function validateForm() {
            const hasSignature = !signaturePad.isEmpty();
            const hasConsent = consent.checked;
            submitButton.disabled = !(hasSignature && hasConsent);
        }
        
        signaturePad.addEventListener('endStroke', validateForm);
        consent.addEventListener('change', validateForm);
        
        // Initial validation
        validateForm();
    });
    
    function clearSignature() {
        signaturePad.clear();
        document.getElementById('signatureData').value = '';
        document.getElementById('submitButton').disabled = true;
    }
    
    function openDocumentPreview() {
        document.getElementById('documentModal').classList.remove('hidden');
    }
    
    function closeDocumentPreview() {
        document.getElementById('documentModal').classList.add('hidden');
    }
    
    function declineSignature() {
        if (confirm('Êtes-vous sûr de vouloir refuser de signer ce document ?')) {
            window.location.href = '{{ route('signatures.decline', $invitation) }}';
        }
    }
    
    // Handle form submission
    document.getElementById('signatureForm').addEventListener('submit', function(e) {
        if (signaturePad.isEmpty()) {
            e.preventDefault();
            alert('Veuillez fournir votre signature');
            return;
        }
        
        if (!document.getElementById('consent').checked) {
            e.preventDefault();
            alert('Veuillez accepter les conditions');
            return;
        }
        
        // Convert signature to data URL
        const signatureData = signaturePad.toDataURL();
        document.getElementById('signatureData').value = signatureData;
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDocumentPreview();
        }
    });
</script>

<style>
    .signature-pad {
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
    }
    
    @media (prefers-color-scheme: dark) {
        .signature-pad {
            border-color: #4b5563;
        }
    }
</style>
@endsection