<x-guest-layout>
    <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-[var(--text-primary)]">Forgot Password</h2>
        <p class="mt-2 text-sm text-[var(--text-secondary)]">
            No problem. Just let us know your email address and we will email you a password reset link.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-6">
        @csrf

        <div class="space-y-4 rounded-md shadow-sm">
            <!-- Email Address -->
            <div>
                <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1" for="email">Email address</label>
                <input 
                    id="email" 
                    name="email" 
                    type="email" 
                    autocomplete="email" 
                    required 
                    autofocus
                    value="{{ old('email') }}"
                    placeholder="Email address"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
        </div>

        <div>
            <button 
                type="submit"
                class="group relative flex w-full justify-center rounded-lg border border-transparent bg-[var(--primary-color)] py-3 px-4 text-sm font-semibold text-white hover:bg-[var(--accent-color)] hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:ring-offset-2 transition-colors duration-200"
            >
                Email Password Reset Link
            </button>
        </div>
    </form>

    <p class="mt-10 text-center text-sm text-[var(--text-secondary)]">
        Remember your password?
        <a class="font-medium text-[var(--primary-color)] hover:underline transition-colors duration-200" href="{{ route('login') }}">
            Back to login
        </a>
    </p>
</x-guest-layout>
