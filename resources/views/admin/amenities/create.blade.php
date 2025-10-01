@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Create New Amenity (Admin)</h1>
        <a href="{{ route('admin.amenities.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Amenities</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg p-6">
        <form method="POST" action="{{ route('admin.amenities.store') }}">
            @csrf
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name" id="name" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="amenity_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amenity Type</label>
                <select name="amenity_type_id" id="amenity_type_id" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                    <option value="">Select Amenity Type</option>
                    @foreach ($amenityTypes as $amenityType)
                        <option value="{{ $amenityType->id }}">{{ $amenityType->name }}</option>
                    @endforeach
                </select>
                @error('amenity_type_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="property_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Property ID</label>
                <input type="number" name="property_id" id="property_id" class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                @error('property_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Create Amenity</button>
        </form>
    </div>
</div>
@endsection
