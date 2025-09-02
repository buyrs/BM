<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Bail Mobilité Management System - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style type="text/tailwindcss">
            :root {
                --primary-color: #137fec;
                --secondary-color: #e0effc;
                --background-color: #f8faff;
                --text-primary: #1a202c;
                --text-secondary: #5a677d;
                --accent-color: #137fec;
            }
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--background-color);
                color: var(--text-primary);
            }
        </style>
    </head>
    <body class="bg-[var(--background-color)] text-[var(--text-primary)]">
        <div class="flex min-h-screen flex-col">
            <header class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <div class="flex items-center gap-4">
                            <svg class="h-8 w-8 text-[var(--primary-color)]" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path>
                            </svg>
                            <h1 class="text-xl font-bold text-[var(--text-primary)]">Bail Mobilité Management System</h1>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex flex-1 items-center justify-center py-12 sm:px-6 lg:px-8">
                <div class="w-full max-w-md space-y-8">
                    <div class="bg-white shadow-lg rounded-xl p-8 sm:p-10">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
