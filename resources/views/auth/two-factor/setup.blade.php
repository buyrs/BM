<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Two-Factor Authentication Setup') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="max-w-xl">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ __('Enable Two-Factor Authentication') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Two-factor authentication adds an additional layer of security to your account by requiring more than just a password to log in.') }}
                        </p>

                        <div class="mt-6">
                            <p class="text-sm text-gray-600 mb-4">
                                {{ __('To enable two-factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key manually.') }}
                            </p>

                            <div class="flex justify-center mb-4">
                                <img src="{{ $qrCodeUrl }}" alt="QR Code" class="border rounded">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ __('Setup Key') }}
                                </label>
                                <div class="mt-1 p-2 bg-gray-100 rounded text-sm font-mono break-all">
                                    {{ $secret }}
                                </div>
                            </div>

                            <form method="POST" action="{{ route('two-factor.store') }}">
                                @csrf
                                <input type="hidden" name="secret" value="{{ $secret }}">

                                <div class="mb-4">
                                    <label for="code" class="block text-sm font-medium text-gray-700">
                                        {{ __('Authentication Code') }}
                                    </label>
                                    <input type="text" 
                                           id="code" 
                                           name="code" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="000000"
                                           maxlength="6"
                                           required>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('Enter the 6-digit code from your authenticator app.') }}
                                    </p>
                                    @error('code')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button>
                                        {{ __('Enable Two-Factor Authentication') }}
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>