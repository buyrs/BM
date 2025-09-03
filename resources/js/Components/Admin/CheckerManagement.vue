<template>
  <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg sm:text-xl font-bold text-text-primary">
        Checker Management
      </h3>
      <div class="flex gap-2">
        <button 
          @click="refreshCheckers"
          :disabled="loading"
          class="text-primary hover:text-primary-dark text-sm font-medium disabled:opacity-50"
        >
          <svg 
            :class="['w-4 h-4 inline mr-1', loading ? 'animate-spin' : '']" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Refresh
        </button>
        <button 
          @click="showCreateModal = true"
          class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition-colors duration-200 text-sm font-medium"
        >
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Add Checker
        </button>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="text-center p-4 bg-gray-50 rounded-lg">
        <p class="text-2xl font-bold text-primary">{{ checkers.length }}</p>
        <p class="text-sm text-text-secondary">Total Checkers</p>
      </div>
      <div class="text-center p-4 bg-gray-50 rounded-lg">
        <p class="text-2xl font-bold text-success-text">{{ activeCheckersCount }}</p>
        <p class="text-sm text-text-secondary">Active Checkers</p>
      </div>
      <div class="text-center p-4 bg-gray-50 rounded-lg">
        <p class="text-2xl font-bold text-info-text">{{ onlineCheckersCount }}</p>
        <p class="text-sm text-text-secondary">Online Now</p>
      </div>
    </div>

    <!-- Checkers List -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="flex items-center space-x-4 p-4 border rounded-lg">
          <div class="w-12 h-12 bg-gray-200 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
          </div>
          <div class="w-20 h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <div v-else-if="checkers.length === 0" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
      </svg>
      <p class="text-text-secondary mb-4">No checkers found</p>
      <button 
        @click="showCreateModal = true"
        class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition-colors duration-200"
      >
        Add First Checker
      </button>
    </div>

    <div v-else class="space-y-3">
      <div 
        v-for="checker in displayedCheckers" 
        :key="checker.id"
        class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-150"
      >
        <div class="flex items-center space-x-4">
          <div class="relative">
            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
              <span class="text-lg font-medium text-text-secondary">
                {{ checker.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div 
              :class="[
                'absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white',
                getOnlineStatusClass(checker)
              ]"
            ></div>
          </div>
          
          <div>
            <h4 class="font-medium text-text-primary">{{ checker.name }}</h4>
            <p class="text-sm text-text-secondary">{{ checker.email }}</p>
            <div class="flex items-center space-x-4 mt-1">
              <span class="text-xs text-text-secondary">
                {{ checker.completed_missions_count || 0 }} completed
              </span>
              <span class="text-xs text-text-secondary">
                {{ checker.assigned_missions_count || 0 }} assigned
              </span>
              <span v-if="checker.performance_score" class="text-xs text-success-text">
                {{ checker.performance_score }}% performance
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <span 
            :class="getStatusBadgeClass(checker.status)"
            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
          >
            {{ formatStatus(checker.status) }}
          </span>
          
          <div class="flex space-x-1">
            <button 
              @click="editChecker(checker)"
              class="p-2 text-text-secondary hover:text-primary hover:bg-gray-100 rounded-md transition-colors duration-200"
              title="Edit checker"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            
            <button 
              @click="toggleCheckerStatus(checker)"
              :class="[
                'p-2 rounded-md transition-colors duration-200',
                checker.status === 'active' 
                  ? 'text-error-text hover:bg-error-bg' 
                  : 'text-success-text hover:bg-success-bg'
              ]"
              :title="checker.status === 'active' ? 'Deactivate checker' : 'Activate checker'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  :d="checker.status === 'active' 
                    ? 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
                    : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-if="checkers.length > maxDisplayed" class="text-center pt-4 border-t border-gray-200">
        <Link 
          :href="route('admin.checkers')"
          class="text-primary hover:text-primary-dark text-sm font-medium"
        >
          View All {{ checkers.length }} Checkers →
        </Link>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showCreateModal || showEditModal" @close="closeModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-text-primary mb-4">
          {{ editingChecker ? 'Edit Checker' : 'Add New Checker' }}
        </h3>
        
        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-secondary mb-1">Name</label>
            <input 
              v-model="form.name"
              type="text" 
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="Enter checker name"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-text-secondary mb-1">Email</label>
            <input 
              v-model="form.email"
              type="email" 
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="Enter email address"
            >
          </div>
          
          <div v-if="!editingChecker">
            <label class="block text-sm font-medium text-text-secondary mb-1">Password</label>
            <input 
              v-model="form.password"
              type="password" 
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="Enter password"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-text-secondary mb-1">Phone</label>
            <input 
              v-model="form.phone"
              type="tel" 
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="Enter phone number"
            >
          </div>
          
          <div class="flex justify-end space-x-3 pt-4">
            <button 
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-text-secondary hover:text-text-primary transition-colors duration-200"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="submitting"
              class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition-colors duration-200 disabled:opacity-50"
            >
              {{ submitting ? 'Saving...' : (editingChecker ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  checkers: {
    type: Array,
    default: () => []
  },
  maxDisplayed: {
    type: Number,
    default: 5
  }
});

const emit = defineEmits(['refresh', 'create', 'update', 'toggle-status']);

const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingChecker = ref(null);
const submitting = ref(false);

const form = reactive({
  name: '',
  email: '',
  password: '',
  phone: ''
});

const displayedCheckers = computed(() => {
  return props.checkers.slice(0, props.maxDisplayed);
});

const activeCheckersCount = computed(() => {
  return props.checkers.filter(checker => checker.status === 'active').length;
});

const onlineCheckersCount = computed(() => {
  return props.checkers.filter(checker => checker.is_online).length;
});

const refreshCheckers = async () => {
  loading.value = true;
  try {
    await emit('refresh');
  } finally {
    loading.value = false;
  }
};

const editChecker = (checker) => {
  editingChecker.value = checker;
  form.name = checker.name;
  form.email = checker.email;
  form.phone = checker.phone || '';
  form.password = '';
  showEditModal.value = true;
};

const toggleCheckerStatus = async (checker) => {
  try {
    await emit('toggle-status', checker);
  } catch (error) {
    console.error('Failed to toggle checker status:', error);
  }
};

const submitForm = async () => {
  submitting.value = true;
  try {
    if (editingChecker.value) {
      await emit('update', editingChecker.value.id, form);
    } else {
      await emit('create', form);
    }
    closeModal();
  } catch (error) {
    console.error('Failed to submit form:', error);
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingChecker.value = null;
  Object.keys(form).forEach(key => form[key] = '');
};

const getOnlineStatusClass = (checker) => {
  return checker.is_online ? 'bg-success-text' : 'bg-gray-400';
};

const getStatusBadgeClass = (status) => {
  const classes = {
    active: 'bg-success-bg text-success-text',
    inactive: 'bg-gray-100 text-gray-600',
    suspended: 'bg-error-bg text-error-text'
  };
  return classes[status] || classes.inactive;
};

const formatStatus = (status) => {
  const labels = {
    active: 'Active',
    inactive: 'Inactive',
    suspended: 'Suspended'
  };
  return labels[status] || status;
};
</script>