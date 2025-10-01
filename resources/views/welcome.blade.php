@extends('layouts.app')

@section('content')
<div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="text-center">
        <h1 class="text-5xl font-bold text-gray-800 dark:text-white mb-8">Bail Mobilité Management System</h1>
        <div class="space-x-4">
            <a href="{{ url('/admin/login') }}" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">Login as Admin</a>
            <a href="{{ url('/ops/login') }}" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300">Login as Ops</a>
            <a href="{{ url('/checker/login') }}" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition duration-300">Login as Checker</a>
        </div>
    </div>
</div>
@endsection