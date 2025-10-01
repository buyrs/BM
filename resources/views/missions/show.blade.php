@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Mission Details: {{ $mission->title }}</h1>
        @if (auth()->guard('admin')->check())
            <a href="{{ route('admin.missions.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Missions</a>
        @elseif (auth()->guard('ops')->check())
            <a href="{{ route('ops.missions.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Missions</a>
        @endif
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Title:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->title }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Property Address:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->property_address }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Check-in Date:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->checkin_date }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Check-out Date:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->checkout_date }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ ucfirst($mission->status) }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Ops:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->ops->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Checker:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->checker->name ?? 'N/A' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Description:</p>
                <p class="mt-1 text-lg text-gray-900 dark:text-white">{{ $mission->description ?? 'N/A' }}</p>
            </div>
        </div>

        @if (auth()->guard('admin')->check() && $mission->status === 'pending')
            <div class="mt-6">
                <form action="{{ route('admin.missions.approve', $mission->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" onclick="return confirm('Approve this mission?')">Approve Mission</button>
                </form>
            </div>
        @endif

        <div class="mt-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Checklists</h2>
            @foreach ($mission->checklists as $checklist)
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-2">
                    <p class="text-lg font-medium text-gray-900 dark:text-white">Type: {{ ucfirst($checklist->type) }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Status: {{ ucfirst($checklist->status) }}</p>
                    @if ($checklist->status === 'completed')
                        <p class="text-sm text-gray-700 dark:text-gray-300">Submitted At: {{ $checklist->submitted_at }}</p>
                    @endif
                    @if ($checklist->signature_path)
                        <p class="text-sm text-gray-700 dark:text-gray-300">Signature: <a href="{{ asset('storage/' . $checklist->signature_path) }}" target="_blank" class="text-blue-500 hover:underline">View Signature</a></p>
                    @endif
                    @if (auth()->guard('admin')->check())
                        <form action="{{ route('admin.checklists.sendToGuest', $checklist->id) }}" method="POST" class="mt-2">
                            @csrf
                            <input type="email" name="guest_email" placeholder="Guest Email" class="px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                            <button type="submit" class="ml-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Send to Guest</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
