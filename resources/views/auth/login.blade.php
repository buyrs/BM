<x-guest-layout>
    <div class="text-center">
        <h2 class="text-3xl font-bold tracking-tight text-[var(--text-primary)]">Welcome Back</h2>
        <p class="mt-2 text-sm text-[var(--text-secondary)]">
            @php
                $roleText = 'Sign in to manage your mobility leases.';
                if (request()->is('admin/*')) {
                    $roleText = 'Sign in to your admin dashboard.';
                } elseif (request()->is('checker/*')) {
                    $roleText = 'Sign in to access your checker missions.';
                } elseif (request()->is('ops/*')) {
                    $roleText = 'Sign in to your operations dashboard.';
                }
            @endphp
            {{ $roleText }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $prefix = request()->is('admin/*') ? 'admin' : (request()->is('checker/*') ? 'checker' : (request()->is('ops/*') ? 'ops' : null));
    @endphp
    
    <form method="POST" action="{{ $prefix ? route($prefix.'.login') : route('login') }}" class="mt-8 space-y-6">
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

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-[var(--text-secondary)] mb-1" for="password">Password</label>
                <input 
                    id="password" 
                    name="password" 
                    type="password" 
                    autocomplete="current-password" 
                    required
                    placeholder="Password"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all duration-200"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <div class="text-sm">
                @if (Route::has('password.request'))
                    <a class="font-medium text-[var(--primary-color)] hover:underline transition-colors duration-200" href="{{ route('password.request') }}">
                        Forgot your password?
                    </a>
                @endif
            </div>
        </div>

        <div>
            <button 
                type="submit"
                class="group relative flex w-full justify-center rounded-lg border border-transparent bg-[var(--primary-color)] py-3 px-4 text-sm font-semibold text-white hover:bg-[var(--accent-color)] hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:ring-offset-2 transition-colors duration-200"
            >
                Login
            </button>
        </div>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-white px-2 text-[var(--text-secondary)]">Or continue with</span>
            </div>
        </div>

        <div class="mt-6">
            <a 
                href="{{ route('auth.google') }}"
                class="w-full inline-flex justify-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-[var(--text-secondary)] hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--primary-color)] transition-colors duration-200"
            >
                <span class="sr-only">Login with Google</span>
                <svg class="h-6 w-6" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"></path>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"></path>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"></path>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"></path>
                </svg>
                <span class="ml-3">Login with Google</span>
            </a>
        </div>
    </div>

    @if (Route::has('register'))
        @php
            $canRegister = request()->is('admin/*');
        @endphp
        @if ($canRegister)
            <p class="mt-10 text-center text-sm text-[var(--text-secondary)]">
                Don't have an account?
                <a class="font-medium text-[var(--primary-color)] hover:underline transition-colors duration-200" href="{{ route('admin.register') }}">
                    Register as Admin
                </a>
            </p>
        @else
            <p class="mt-10 text-center text-sm text-[var(--text-secondary)]">
                Need an account? Contact your administrator.
            </p>
        @endif
    @endif
</x-guest-layout>
