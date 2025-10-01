@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Edit Amenity Type (Admin)</h1>
        <a href="{{ route('admin.amenity_types.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Amenity Types</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" action="{{ route('admin.amenity_types.update', $amenityType->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $amenityType->name) }}" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Update Amenity Type</button>
        </form>
    </div>
</div>
@endsection
