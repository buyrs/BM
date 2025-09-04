@extends('layouts.role-based')

@section('title', 'Role Management')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-3xl font-bold text-gray-900">Role Management</h2>
        <p class="text-gray-600 mt-1">
            Manage user roles and permissions across the system
        </p>
    </div>
    <div class="flex space-x-3">
        <button 
            onclick="openCreateRoleModal()"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
        >
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Role
        </button>
        <button 
            onclick="openCreateUserModal()"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
        >
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Create User
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total_users'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        @foreach($stats['users_by_role'] ?? [] as $role => $count)
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate capitalize">{{ str_replace('-', ' ', $role) }}</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $count }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button 
                onclick="switchTab('roles')"
                id="roles-tab"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600"
            >
                Roles & Permissions
            </button>
            <button 
                onclick="switchTab('users')"
                id="users-tab"
                class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
            >
                Users
            </button>
        </nav>
    </div>

    <!-- Roles Tab -->
    <div id="roles-content" class="space-y-6">
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($roles ?? [] as $role)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 capitalize">
                                    {{ str_replace('-', ' ', $role->name) }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $role->permissions->count() }} permissions
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button 
                                onclick="editRole({{ $role->id }}, '{{ $role->name }}', [{{ $role->permissions->pluck('id')->implode(',') }}])"
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Edit
                            </button>
                            @if(!in_array($role->name, ['super-admin', 'admin', 'ops', 'checker']))
                            <button 
                                onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')"
                                class="text-red-600 hover:text-red-900 text-sm font-medium"
                            >
                                Delete
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex flex-wrap gap-1">
                            @foreach($role->permissions as $permission)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $permission->name }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-6 py-4 text-center text-gray-500">No roles found</li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Users Tab -->
    <div id="users-content" class="space-y-6 hidden">
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($users ?? [] as $user)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $user['name'] ?? 'Unknown' }}</div>
                                <div class="text-sm text-gray-500">{{ $user['email'] ?? 'N/A' }}</div>
                                <div class="flex space-x-1 mt-1">
                                    @foreach($user['roles'] ?? [] as $role)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ str_replace('-', ' ', $role) }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button 
                                onclick="editUser({{ $user['id'] }}, '{{ $user['name'] }}', '{{ $user['email'] }}', '{{ $user['roles'][0] ?? '' }}')"
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Edit
                            </button>
                            <button 
                                onclick="resetUserPassword({{ $user['id'] }}, '{{ $user['name'] }}')"
                                class="text-yellow-600 hover:text-yellow-900 text-sm font-medium"
                            >
                                Reset Password
                            </button>
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-6 py-4 text-center text-gray-500">No users found</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<!-- Create Role Modal -->
<div id="createRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="roleModalTitle">Create Role</h3>
            <form id="roleForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="roleName"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g., manager"
                    />
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                    <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3">
                        @foreach($permissions ?? [] as $group => $groupPermissions)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-900 capitalize mb-2">{{ str_replace('_', ' ', $group) }}</h4>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($groupPermissions as $permission)
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        name="permissions[]" 
                                        value="{{ $permission->id }}"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">{{ $permission->name }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        type="button" 
                        onclick="closeCreateRoleModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <span id="roleSubmitText">Create Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div id="createUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="userModalTitle">Create User</h3>
            <form id="userForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="userName"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="userEmail"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                
                <div class="mb-4" id="passwordField">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="userPassword"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select 
                        name="role" 
                        id="userRole"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Select a role</option>
                        @foreach($roles ?? [] as $role)
                        <option value="{{ $role->name }}">{{ str_replace('-', ' ', $role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        type="button" 
                        onclick="closeCreateUserModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <span id="userSubmitText">Create User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reset Password</h3>
            <form id="passwordForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="newPassword"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button 
                        type="button" 
                        onclick="closeResetPasswordModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700"
                    >
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentUserId = null;
let currentRoleId = null;

// Tab switching
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('[id$="-tab"]').forEach(btn => {
        btn.className = 'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
    });
    document.getElementById(tab + '-tab').className = 'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600';
    
    // Update content
    document.querySelectorAll('[id$="-content"]').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(tab + '-content').classList.remove('hidden');
}

// Role management
function openCreateRoleModal() {
    document.getElementById('roleModalTitle').textContent = 'Create Role';
    document.getElementById('roleSubmitText').textContent = 'Create Role';
    document.getElementById('roleForm').action = '{{ route("admin.roles.store") }}';
    document.getElementById('roleForm').method = 'POST';
    document.getElementById('roleName').value = '';
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = false);
    document.getElementById('createRoleModal').classList.remove('hidden');
}

function editRole(roleId, roleName, permissionIds) {
    currentRoleId = roleId;
    document.getElementById('roleModalTitle').textContent = 'Edit Role';
    document.getElementById('roleSubmitText').textContent = 'Update Role';
    document.getElementById('roleForm').action = '{{ route("admin.roles.update", ":id") }}'.replace(':id', roleId);
    document.getElementById('roleForm').method = 'POST';
    document.getElementById('roleForm').innerHTML += '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('roleName').value = roleName;
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
        cb.checked = permissionIds.includes(parseInt(cb.value));
    });
    document.getElementById('createRoleModal').classList.remove('hidden');
}

function deleteRole(roleId, roleName) {
    if (confirm(`Are you sure you want to delete the role "${roleName}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.roles.destroy", ":id") }}'.replace(':id', roleId);
        form.innerHTML = '@csrf <input type="hidden" name="_method" value="DELETE">';
        document.body.appendChild(form);
        form.submit();
    }
}

function closeCreateRoleModal() {
    document.getElementById('createRoleModal').classList.add('hidden');
    currentRoleId = null;
}

// User management
function openCreateUserModal() {
    document.getElementById('userModalTitle').textContent = 'Create User';
    document.getElementById('userSubmitText').textContent = 'Create User';
    document.getElementById('userForm').action = '{{ route("admin.users.store") }}';
    document.getElementById('userForm').method = 'POST';
    document.getElementById('userName').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = '';
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('createUserModal').classList.remove('hidden');
}

function editUser(userId, userName, userEmail, userRole) {
    currentUserId = userId;
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userSubmitText').textContent = 'Update User';
    document.getElementById('userForm').action = '{{ route("admin.users.update", ":id") }}'.replace(':id', userId);
    document.getElementById('userForm').method = 'POST';
    document.getElementById('userForm').innerHTML += '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('userName').value = userName;
    document.getElementById('userEmail').value = userEmail;
    document.getElementById('userRole').value = userRole;
    document.getElementById('passwordField').style.display = 'none';
    document.getElementById('createUserModal').classList.remove('hidden');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.add('hidden');
    currentUserId = null;
}

function resetUserPassword(userId, userName) {
    currentUserId = userId;
    document.getElementById('passwordForm').action = '{{ route("admin.users.reset-password", ":id") }}'.replace(':id', userId);
    document.getElementById('newPassword').value = '';
    document.getElementById('resetPasswordModal').classList.remove('hidden');
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
    currentUserId = null;
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('bg-gray-600')) {
        closeCreateRoleModal();
        closeCreateUserModal();
        closeResetPasswordModal();
    }
});
</script>
@endpush
