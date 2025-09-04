<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Role Management
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Manage user roles and permissions across the system
        </p>
      </div>
      <div class="mt-4 flex md:mt-0 md:ml-4">
        <button
          @click="showCreateRoleModal = true"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          Create Role
        </button>
        <button
          @click="showCreateUserModal = true"
          class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          <UserPlusIcon class="h-4 w-4 mr-2" />
          Create User
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <UsersIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.total_users }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div
        v-for="(count, role) in stats.users_by_role"
        :key="role"
        class="bg-white overflow-hidden shadow rounded-lg"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ShieldCheckIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate capitalize">{{ role.replace('-', ' ') }}</dt>
                <dd class="text-lg font-medium text-gray-900">{{ count }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'roles'"
          :class="[
            activeTab === 'roles'
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Roles & Permissions
        </button>
        <button
          @click="activeTab = 'users'"
          :class="[
            activeTab === 'users'
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Users
        </button>
      </nav>
    </div>

    <!-- Roles Tab -->
    <div v-if="activeTab === 'roles'" class="space-y-6">
      <!-- Roles List -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
          <li v-for="role in roles" :key="role.id" class="px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <ShieldCheckIcon class="h-8 w-8 text-gray-400" />
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900 capitalize">
                    {{ role.name.replace('-', ' ') }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ role.permissions.length }} permissions
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="editRole(role)"
                  class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                >
                  Edit
                </button>
                <button
                  v-if="!['super-admin', 'admin', 'ops', 'checker'].includes(role.name)"
                  @click="deleteRole(role)"
                  class="text-red-600 hover:text-red-900 text-sm font-medium"
                >
                  Delete
                </button>
              </div>
            </div>
            <div class="mt-2">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="permission in role.permissions"
                  :key="permission.id"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  {{ permission.name }}
                </span>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Users Tab -->
    <div v-if="activeTab === 'users'" class="space-y-6">
      <!-- Users List -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
          <li v-for="user in users" :key="user.id" class="px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                  <div class="text-sm text-gray-500">{{ user.email }}</div>
                  <div class="flex space-x-1 mt-1">
                    <span
                      v-for="role in user.roles"
                      :key="role"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                      {{ role.replace('-', ' ') }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="editUser(user)"
                  class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                >
                  Edit
                </button>
                <button
                  @click="resetUserPassword(user)"
                  class="text-yellow-600 hover:text-yellow-900 text-sm font-medium"
                >
                  Reset Password
                </button>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Create Role Modal -->
    <Modal v-model:open="showCreateRoleModal" :title="editingRole ? 'Edit Role' : 'Create Role'">
      <form @submit.prevent="saveRole" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Role Name</label>
          <input
            v-model="roleForm.name"
            type="text"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="e.g., manager"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Permissions</label>
          <div class="mt-2 space-y-2 max-h-60 overflow-y-auto">
            <div v-for="(groupPermissions, group) in permissions" :key="group" class="space-y-1">
              <h4 class="text-sm font-medium text-gray-900 capitalize">{{ group }}</h4>
              <div class="grid grid-cols-2 gap-2">
                <label
                  v-for="permission in groupPermissions"
                  :key="permission.id"
                  class="flex items-center"
                >
                  <input
                    v-model="roleForm.permissions"
                    :value="permission.id"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">{{ permission.name }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="cancelRoleForm"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
          >
            {{ editingRole ? 'Update' : 'Create' }} Role
          </button>
        </div>
      </form>
    </Modal>

    <!-- Create User Modal -->
    <Modal v-model:open="showCreateUserModal" :title="editingUser ? 'Edit User' : 'Create User'">
      <form @submit.prevent="saveUser" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Full Name</label>
          <input
            v-model="userForm.name"
            type="text"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input
            v-model="userForm.email"
            type="email"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        
        <div v-if="!editingUser">
          <label class="block text-sm font-medium text-gray-700">Password</label>
          <input
            v-model="userForm.password"
            type="password"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Role</label>
          <select
            v-model="userForm.role"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          >
            <option value="">Select a role</option>
            <option v-for="role in roles" :key="role.id" :value="role.name">
              {{ role.name.replace('-', ' ') }}
            </option>
          </select>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="cancelUserForm"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
          >
            {{ editingUser ? 'Update' : 'Create' }} User
          </button>
        </div>
      </form>
    </Modal>

    <!-- Reset Password Modal -->
    <Modal v-model:open="showResetPasswordModal" title="Reset Password">
      <form @submit.prevent="resetPassword" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">New Password</label>
          <input
            v-model="passwordForm.password"
            type="password"
            required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="showResetPasswordModal = false"
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
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import {
  PlusIcon,
  UserPlusIcon,
  UsersIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  roles: Array,
  permissions: Object,
  users: Array,
  stats: Object
})

// Reactive data
const activeTab = ref('roles')
const showCreateRoleModal = ref(false)
const showCreateUserModal = ref(false)
const showResetPasswordModal = ref(false)
const editingRole = ref(null)
const editingUser = ref(null)
const selectedUser = ref(null)

// Forms
const roleForm = ref({
  name: '',
  permissions: []
})

const userForm = ref({
  name: '',
  email: '',
  password: '',
  role: ''
})

const passwordForm = ref({
  password: ''
})

// Methods
const editRole = (role) => {
  editingRole.value = role
  roleForm.value = {
    name: role.name,
    permissions: role.permissions.map(p => p.id)
  }
  showCreateRoleModal.value = true
}

const editUser = (user) => {
  editingUser.value = user
  userForm.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.roles[0] || ''
  }
  showCreateUserModal.value = true
}

const deleteRole = async (role) => {
  if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
    router.delete(route('admin.roles.destroy', role.id), {
      onSuccess: () => {
        // Refresh the page or update the roles list
        router.reload()
      }
    })
  }
}

const resetUserPassword = (user) => {
  selectedUser.value = user
  passwordForm.value.password = ''
  showResetPasswordModal.value = true
}

const saveRole = () => {
  const url = editingRole.value 
    ? route('admin.roles.update', editingRole.value.id)
    : route('admin.roles.store')
  
  const method = editingRole.value ? 'put' : 'post'
  
  router[method](url, roleForm.value, {
    onSuccess: () => {
      cancelRoleForm()
      router.reload()
    }
  })
}

const saveUser = () => {
  const url = editingUser.value 
    ? route('admin.users.update', editingUser.value.id)
    : route('admin.users.store')
  
  const method = editingUser.value ? 'put' : 'post'
  
  router[method](url, userForm.value, {
    onSuccess: () => {
      cancelUserForm()
      router.reload()
    }
  })
}

const resetPassword = () => {
  router.post(route('admin.users.reset-password', selectedUser.value.id), passwordForm.value, {
    onSuccess: () => {
      showResetPasswordModal.value = false
      selectedUser.value = null
    }
  })
}

const cancelRoleForm = () => {
  editingRole.value = null
  roleForm.value = {
    name: '',
    permissions: []
  }
  showCreateRoleModal.value = false
}

const cancelUserForm = () => {
  editingUser.value = null
  userForm.value = {
    name: '',
    email: '',
    password: '',
    role: ''
  }
  showCreateUserModal.value = false
}
</script>
