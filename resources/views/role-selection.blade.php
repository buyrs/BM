<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select Role - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex items-center justify-center min-h-screen p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-2xl font-bold mb-2">Select Your Role</h1>
            <p class="text-[#706f6c] dark:text-[#A1A09A]">Choose the role you want to log in with</p>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Admin Role Card -->
            <a href="{{ route('admin.login') }}" 
               class="role-card bg-white dark:bg-[#161615] rounded-lg p-6 border border-[#e3e3e0] dark:border-[#3E3E3A] transition-all duration-200 block">
                <div class="flex items-center">
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Admin</h2>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Manage users, settings, and system configuration</p>
                        <p class="text-xs mt-1 text-green-600 dark:text-green-400">Self-registration available</p>
                    </div>
                </div>
            </a>

            <!-- Ops Role Card -->
            <a href="{{ route('ops.login') }}" 
               class="role-card bg-white dark:bg-[#161615] rounded-lg p-6 border border-[#e3e3e0] dark:border-[#3E3E3A] transition-all duration-200 block">
                <div class="flex items-center">
                    <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Ops</h2>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Handle operations and mission management</p>
                        <p class="text-xs mt-1 text-red-600 dark:text-red-400">Admin-created only</p>
                    </div>
                </div>
            </a>

            <!-- Checker Role Card -->
            <a href="{{ route('checker.login') }}" 
               class="role-card bg-white dark:bg-[#161615] rounded-lg p-6 border border-[#e3e3e0] dark:border-[#3E3E3A] transition-all duration-200 block">
                <div class="flex items-center">
                    <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Checker</h2>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Perform inspections and submit reports</p>
                        <p class="text-xs mt-1 text-red-600 dark:text-red-400">Admin/Ops-created only</p>
                    </div>
                </div>
            </a>

            <!-- Super Admin Role Card -->
            <a href="{{ route('super-admin.login') }}" 
               class="role-card bg-white dark:bg-[#161615] rounded-lg p-6 border border-[#e3e3e0] dark:border-[#3E3E3A] transition-all duration-200 block">
                <div class="flex items-center">
                    <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-lg mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">Super Admin</h2>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Full system access and administrative control</p>
                        <p class="text-xs mt-1 text-red-600 dark:text-red-400">Pre-seeded account only</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('welcome') }}" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors">
                ← Back to Welcome Page
            </a>
        </div>
    </div>
</body>
</html>