<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Property Details') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('admin.properties.edit', $property) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Edit Property
                </a>
                <a href="{{ route('admin.properties.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Properties
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Property Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Property Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Property Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $property->property_address }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $property->description ?: 'No description available' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Created Date</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $property->created_at->format('F j, Y \a\t g:i A') }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $property->updated_at->format('F j, Y \a\t g:i A') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Owner Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Owner Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Owner Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $property->owner_name ?: 'Not specified' }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Owner Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $property->owner_address ?: 'Not specified' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Missions -->
                    @if($property->missions->count() > 0)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Missions</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($property->missions as $mission)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $mission->title }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        {{ $mission->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                                           ($mission->status === 'approved' ? 'bg-blue-100 text-blue-800' : 
                                                           ($mission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                        {{ ucfirst($mission->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $mission->checkin_date->format('M d, Y') }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $mission->checkout_date->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>