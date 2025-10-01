@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white">Checker Dashboard</h1>
        <form method="POST" action="{{ route('checker.logout') }}">
            @csrf
            <button type="submit" class="px-4 py-2 sm:px-6 sm:py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300 w-full sm:w-auto text-center">Logout</button>
        </form>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <x-responsive-table title="Assigned Checklists" responsive="true">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mission Title</th>
                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($checklists as $checklist)
                <tr>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white max-w-xs truncate" title="{{ $checklist->mission->title }}">{{ $checklist->mission->title }}</td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ ucfirst($checklist->type) }}</td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ ucfirst($checklist->status) }}</td>
                    <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('checklists.show', $checklist->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600 inline-block mb-2 sm:mb-0 sm:mr-3">View/Fill</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-responsive-table>

    @if ($checklists->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-600 dark:text-gray-400">No checklists assigned to you yet.</p>
        </div>
    @endif
</div>
@endsection
