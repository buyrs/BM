@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-6">Mission Calendar</h2>
                
                <!-- Calendar Navigation -->
                <div class="mb-6 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            Today
                        </button>
                        <div class="flex space-x-1">
                            <button class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-md">
                                &lt;
                            </button>
                            <button class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 px-3 py-2 rounded-md">
                                &gt;
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-xl font-semibold">
                        {{ now()->format('F Y') }}
                    </div>
                    
                    <div class="flex space-x-2">
                        <select class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                            <option>Month</option>
                            <option>Week</option>
                            <option>Day</option>
                        </select>
                        
                        <select class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                            <option value="">All Checkers</option>
                            @foreach($checkers as $checker)
                                <option value="{{ $checker->id }}">{{ $checker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Calendar Grid -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <!-- Week Days Header -->
                    <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-700">
                        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                            <div class="p-3 text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $day }}
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7">
                        @for($i = 0; $i < 35; $i++)
                            @php
                                $date = now()->startOfMonth()->addDays($i);
                                $isCurrentMonth = $date->month == now()->month;
                                $isToday = $date->isToday();
                                $dayMissions = $missions->filter(fn($mission) => $mission['scheduled_date'] == $date->format('Y-m-d'));
                            @endphp
                            
                            <div class="min-h-24 border border-gray-200 dark:border-gray-700 p-2 {{ $isCurrentMonth ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900' }}">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium {{ $isToday ? 'bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $date->day }}
                                    </span>
                                    @if($dayMissions->count() > 0)
                                        <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full">
                                            {{ $dayMissions->count() }}
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Mission Events -->
                                <div class="space-y-1">
                                    @foreach($dayMissions as $mission)
                                        <div class="text-xs p-1 rounded border-l-4 
                                            {{ $mission['status'] === 'completed' ? 'bg-green-100 border-green-500 text-green-800 dark:bg-green-900 dark:border-green-400 dark:text-green-200' : '' }}
                                            {{ $mission['status'] === 'in_progress' ? 'bg-blue-100 border-blue-500 text-blue-800 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-200' : '' }}
                                            {{ $mission['status'] === 'assigned' ? 'bg-yellow-100 border-yellow-500 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-400 dark:text-yellow-200' : '' }}
                                            {{ $mission['status'] === 'pending_validation' ? 'bg-purple-100 border-purple-500 text-purple-800 dark:bg-purple-900 dark:border-purple-400 dark:text-purple-200' : '' }}
                                        ">
                                            <div class="font-medium">{{ $mission['scheduled_time'] }}</div>
                                            <div class="truncate">{{ $mission['address'] }}</div>
                                            <div class="text-xs opacity-75">{{ $mission['agent_name'] ?? 'Unassigned' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="mt-6 flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-100 border-l-4 border-green-500 mr-2"></div>
                        <span class="text-sm">Completed</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-blue-100 border-l-4 border-blue-500 mr-2"></div>
                        <span class="text-sm">In Progress</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-yellow-100 border-l-4 border-yellow-500 mr-2"></div>
                        <span class="text-sm">Assigned</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-purple-100 border-l-4 border-purple-500 mr-2"></div>
                        <span class="text-sm">Pending Validation</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection