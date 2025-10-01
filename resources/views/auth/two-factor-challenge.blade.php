<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
    </div>

    <form method="POST" action="{{ route('two-factor.login') }}">
        @csrf

        <!-- Authentication Code -->
        <div>
            <x-input-label for="code" :value="__('Code')" />
            <x-text-input id="code" 
                          class="block mt-1 w-full" 
                          type="text" 
                          name="code" 
                          placeholder="000000"
                          maxlength="6"
                          autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
            {{ __('Or use a recovery code') }}
        </p>
        
        <form method="POST" action="{{ route('two-factor.login') }}" class="mt-2">
            @csrf

            <div>
                <x-text-input id="recovery_code" 
                              class="block mt-1 w-full" 
                              type="text" 
                              name="recovery_code" 
                              placeholder="{{ __('Recovery code') }}" />
                <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-secondary-button>
                    {{ __('Use Recovery Code') }}
                </x-secondary-button>
            </div>
        </form>
    </div>
</x-guest-layout>