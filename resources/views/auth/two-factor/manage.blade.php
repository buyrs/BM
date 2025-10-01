<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="max-w-xl">
                        @if (session('status'))
                            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                {{ session('status') }}
                            </div>
                        @endif

                        <h3 class="text-lg font-medium text-gray-900">
                            {{ __('Two-Factor Authentication Enabled') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Two-factor authentication is currently enabled for your account.') }}
                        </p>

                        <!-- Recovery Codes -->
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900">
                                {{ __('Recovery Codes') }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor authentication device is lost.') }}
                            </p>

                            @if (session('recoveryCodes') || $recoveryCodes)
                                <div class="mt-4 p-4 bg-gray-100 rounded">
                                    <div class="grid grid-cols-2 gap-2 text-sm font-mono">
                                        @foreach (session('recoveryCodes', $recoveryCodes) as $code)
                                            <div>{{ $code }}</div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-4">
                                @csrf
                                <div class="mb-4">
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        {{ __('Password') }}
                                    </label>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           required>
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <x-secondary-button>
                                    {{ __('Generate New Recovery Codes') }}
                                </x-secondary-button>
                            </form>
                        </div>

                        <!-- Disable 2FA -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-md font-medium text-gray-900">
                                {{ __('Disable Two-Factor Authentication') }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('If you wish to disable two-factor authentication, confirm your password below.') }}
                            </p>

                            <form method="POST" action="{{ route('two-factor.destroy') }}" class="mt-4">
                                @csrf
                                @method('DELETE')

                                <div class="mb-4">
                                    <label for="disable_password" class="block text-sm font-medium text-gray-700">
                                        {{ __('Password') }}
                                    </label>
                                    <input type="password" 
                                           id="disable_password" 
                                           name="password" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           required>
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <x-danger-button>
                                    {{ __('Disable Two-Factor Authentication') }}
                                </x-danger-button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>