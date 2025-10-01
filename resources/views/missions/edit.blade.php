@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Edit Mission: {{ $mission->title }}</h1>
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

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" action="{{ route((auth()->guard('admin')->check() ? 'admin' : 'ops') . '.missions.update', $mission->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title', $mission->title) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" id="description" rows="3" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('description', $mission->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="property_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Property Address</label>
                <input type="text" name="property_address" id="property_address" value="{{ old('property_address', $mission->property_address) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('property_address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="checkin_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Check-in Date</label>
                <input type="date" name="checkin_date" id="checkin_date" value="{{ old('checkin_date', $mission->checkin_date) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('checkin_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="checkout_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Check-out Date</label>
                <input type="date" name="checkout_date" id="checkout_date" value="{{ old('checkout_date', $mission->checkout_date) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('checkout_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="checker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assign Checker</label>
                <select name="checker_id" id="checker_id" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="">Select Checker</option>
                    @foreach ($checkers as $checker)
                        <option value="{{ $checker->id }}" {{ $mission->checker_id == $checker->id ? 'selected' : '' }}>{{ $checker->name }} ({{ $checker->email }})</option>
                    @endforeach
                </select>
                @error('checker_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            @if (auth()->guard('admin')->check())
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="status" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                        <option value="pending" {{ $mission->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $mission->status === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="in_progress" {{ $mission->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $mission->status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $mission->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Update Mission</button>
        </form>
    </div>
</div>
@endsection
