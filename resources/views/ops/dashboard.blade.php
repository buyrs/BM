@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="text-center">
        <h1 class="text-5xl font-bold text-gray-800 dark:text-white mb-8">Ops Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">Welcome, Ops! What would you like to do?</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <a href="{{ route('ops.users.index') }}" class="px-6 py-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300 flex items-center justify-center">
                Manage Checkers
            </a>
            <a href="{{ route('ops.missions.index') }}" class="px-6 py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center">
                Manage Missions
            </a>
            <a href="{{ route('ops.properties.index') }}" class="px-6 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-300 flex items-center justify-center">
                Manage Properties
            </a>
        </div>
        <form method="POST" action="{{ route('ops.logout') }}">
            @csrf
            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-300">Logout</button>
        </form>
    </div>
</div>
@endsection
