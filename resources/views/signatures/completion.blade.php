@extends('layouts.guest')

@section('title', 'Signature Complétée')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900">
                <svg class="h-10 w-10 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                Signature Complétée
            </h2>
            
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Votre signature a été enregistrée avec succès.
            </p>
        </div>

        <!-- Success Details -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Détails de la signature
                </h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Document:</span>
                        <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $signature->workflow->document_reference }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Signataire:</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $signature->name }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Signé le:</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $signature->signed_at->format('d/m/Y à H:i') }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Référence:</span>
                        <span class="text-gray-900 dark:text-gray-100 font-mono text-xs">{{ $signature->signature_hash }}</span>
                    </div>
                </div>

                <!-- Signature Preview -->
                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Votre signature
                    </h4>
                    <img src="{{ $signature->signature_data }}" 
                         alt="Signature" 
                         class="mx-auto h-20 object-contain border border-gray-300 dark:border-gray-600 rounded">
                </div>

                <!-- Next Steps -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Prochaines étapes
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Le document signé sera disponible dans votre espace personnel.
                        Vous recevrez une confirmation par email.
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="text-center space-y-4">
            <!-- Download Signed Document -->
            <a href="{{ route('signatures.download-signed', $signature) }}" 
               class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Télécharger le document signé
            </a>

            <!-- Email Confirmation -->
            <button onclick="sendEmailConfirmation()"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Renvoyer la confirmation
            </button>

            <!-- Return Home -->
            <a href="{{ url('/') }}" 
               class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Retour à l'accueil
            </a>
        </div>

        <!-- Legal Notice -->
        <div class="text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Cette signature électronique est juridiquement contraignante.
                <br>
                Horodatage: {{ $signature->signed_at->format('d/m/Y H:i:s') }}
                <br>
                Référence: {{ $signature->id }}
            </p>
        </div>
    </div>
</div>

<script>
    function sendEmailConfirmation() {
        fetch('{{ route('signatures.send-confirmation', $signature) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Email de confirmation envoyé avec succès');
            } else {
                alert('Erreur lors de l\'envoi de la confirmation');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de l\'envoi de la confirmation');
        });
    }
    
    // Auto-download after 2 seconds
    setTimeout(() => {
        const downloadLink = document.querySelector('a[href*="download-signed"]');
        if (downloadLink) {
            downloadLink.click();
        }
    }, 2000);
</script>
@endsection