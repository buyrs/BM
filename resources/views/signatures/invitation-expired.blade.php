@extends('layouts.guest')

@section('title', 'Invitation Expirée')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <svg class="mx-auto h-16 w-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                Invitation Expirée
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Cette invitation de signature a expiré ou a déjà été utilisée.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Détails de l'invitation
                </h3>
                
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>Document:</strong> {{ $invitation->workflow->document_reference }}</p>
                    <p><strong>Expirée le:</strong> {{ $invitation->expires_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Statut:</strong> 
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Expirée
                        </span>
                    </p>
                </div>

                <div class="mt-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'expéditeur.
                    </p>
                    
                    <div class="mt-4">
                        <a href="mailto:{{ config('mail.from.address') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Contacter le support
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ url('/') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Retour à l'accueil
            </a>
        </div>
    </div>
</div>
@endsection