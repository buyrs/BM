<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist for Mission: {{ $checklist->mission->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Checklist for Mission: {{ $checklist->mission->title }} ({{ ucfirst($checklist->type) }})</h1>

        <div class="space-y-6">
            @foreach ($checklist->checklistItems as $item)
                <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">{{ $item->amenity->amenityType->name }} - {{ $item->amenity->name }}</h2>

                    <div class="mb-4">
                        <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">State: <span class="font-bold">{{ ucfirst(str_replace('_', ' ', $item->state)) }}</span></p>
                    </div>

                    @if ($item->comment)
                        <div class="mb-4">
                            <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comment: <span class="font-bold">{{ $item->comment }}</span></p>
                        </div>
                    @endif

                    @if ($item->photo_path)
                        <div class="mb-4">
                            <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">Photo:</p>
                            <img src="{{ asset('storage/' . $item->photo_path) }}" alt="Item Photo" class="mt-2 w-64 h-64 object-cover rounded-md">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($checklist->signature_path)
            <div class="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Tenant Signature</h2>
                <img src="{{ asset('storage/' . $checklist->signature_path) }}" alt="Tenant Signature" class="w-full max-w-md border border-gray-300 dark:border-gray-600 rounded-md">
            </div>
        @endif

        <div class="mt-8 text-center text-gray-600 dark:text-gray-400">
            <p>Checklist completed on: {{ $checklist->submitted_at ? $checklist->submitted_at->format('M d, Y H:i') : 'N/A' }}</p>
        </div>
    </div>
</body>
</html>
