<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-8 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Gestion des Utilisateurs</h1>
          <p class="text-sm text-gray-500 mt-1">Gérez les accès, les rôles et la conformité des comptes personnels.</p>
        </div>
        <div class="flex items-center space-x-3">
          <button 
            @click="openCreateModal"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-200 transition-all micro-interaction"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Nouvel Utilisateur
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <!-- Search & Filters -->
      <div class="premium-card p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="md:col-span-2 relative">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
            <input 
              v-model="filters.search"
              type="text" 
              placeholder="Rechercher par nom, email ou département..."
              class="w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm"
              @keyup.enter="loadUsers"
            >
          </div>
          
          <select v-model="filters.role" @change="loadUsers" class="bg-gray-50 border-2 border-gray-100 rounded-2xl px-4 py-3 text-sm focus:border-blue-600 outline-none">
            <option value="">Tous les rôles</option>
            <option value="admin">Administrateur</option>
            <option value="qa_manager">QA Manager</option>
            <option value="qa_analyst">QA Analyst</option>
            <option value="standard_user">Utilisateur Standard</option>
          </select>

          <select v-model="filters.is_active" @change="loadUsers" class="bg-gray-50 border-2 border-gray-100 rounded-2xl px-4 py-3 text-sm focus:border-blue-600 outline-none">
            <option value="">Tous les statuts</option>
            <option value="1">Actifs</option>
            <option value="0">Désactivés</option>
          </select>
        </div>
      </div>

      <!-- User Table -->
      <div class="premium-card overflow-hidden">
        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"></div>
          <p class="text-gray-400 text-sm">Chargement des utilisateurs...</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                <th class="px-8 py-4">Utilisateur</th>
                <th class="px-8 py-4">Rôle / Dpt</th>
                <th class="px-8 py-4 text-center">Statut</th>
                <th class="px-8 py-4">Dernière Connexion</th>
                <th class="px-8 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="user in users" :key="user.id" class="group hover:bg-gray-50/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-50 to-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-xs mr-4 shadow-sm">
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <p class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ user.name }}</p>
                      <p class="text-xs text-gray-400 font-medium">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <p class="text-sm font-bold text-gray-900 capitalize">{{ user.roles?.[0]?.name?.replace('_', ' ') || 'standard user' }}</p>
                  <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">
                    <span v-if="user.department?.entity?.name">{{ user.department.entity.name }} &gt; </span>
                    <span v-if="user.department?.name">{{ user.department.name }}</span>
                    <span v-if="user.fonction?.name"> &gt; {{ user.fonction.name }}</span>
                    <span v-if="!user.department && !user.fonction">Non spécifié</span>
                  </p>
                </td>
                <td class="px-8 py-5 text-center">
                  <span :class="user.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100'" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                    {{ user.is_active ? 'Actif' : 'Bloqué' }}
                  </span>
                </td>
                <td class="px-8 py-5">
                  <p class="text-xs text-gray-900 font-medium">{{ formatDate(user.last_login_at) }}</p>
                  <p v-if="user.ip_address" class="text-[10px] text-gray-400 font-mono">{{ user.ip_address }}</p>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button 
                      @click="editUser(user)" 
                      class="p-2 bg-gray-50 text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition-all micro-interaction"
                      title="Modifier"
                    >
                      <PencilSquareIcon class="w-5 h-5" />
                    </button>
                    <button 
                      @click="toggleActive(user)"
                      :class="user.is_active ? 'hover:bg-red-50 hover:text-red-600' : 'hover:bg-emerald-50 hover:text-emerald-600'"
                      class="p-2 bg-gray-50 text-gray-400 rounded-xl transition-all micro-interaction"
                      :title="user.is_active ? 'Désactiver' : 'Activer'"
                    >
                      <NoSymbolIcon v-if="user.is_active" class="w-5 h-5" />
                      <CheckCircleIcon v-else class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="px-8 py-4 bg-gray-50/30 border-t border-gray-100 flex items-center justify-between">
          <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">{{ pagination.total }} utilisateurs au total</p>
          <div class="flex items-center space-x-2">
            <button 
              @click="loadUsers(pagination.currentPage - 1)" 
              :disabled="pagination.currentPage === 1"
              class="p-2 bg-white border border-gray-200 rounded-lg disabled:opacity-30 hover:bg-gray-50 transition-colors"
            >
              <ChevronLeftIcon class="w-4 h-4 text-gray-600" />
            </button>
            <span class="text-sm font-bold text-gray-900">{{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
            <button 
              @click="loadUsers(pagination.currentPage + 1)" 
              :disabled="pagination.currentPage === pagination.lastPage"
              class="p-2 bg-white border border-gray-200 rounded-lg disabled:opacity-30 hover:bg-gray-50 transition-colors"
            >
              <ChevronRightIcon class="w-4 h-4 text-gray-600" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal User Form -->
    <UserForm
        v-if="showModal"
        :user="selectedUser"
        @close="closeModal"
        @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/bootstrap';
import { 
  PlusIcon, 
  MagnifyingGlassIcon,
  PencilSquareIcon,
  NoSymbolIcon,
  CheckCircleIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline';
import UserForm from './UserForm.vue';

const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const selectedUser = ref(null);

const filters = reactive({
    search: '',
    role: '',
    is_active: ''
});

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0
});

onMounted(() => {
    loadUsers();
});

async function loadUsers(page = 1) {
    loading.value = true;
    try {
        const params = { page };
        if (filters.search) params.search = filters.search;
        if (filters.role) params.role = filters.role;
        if (filters.is_active !== '') params.is_active = filters.is_active;

        const response = await api.get('/admin/users', { params });
        users.value = response.data.data.data;
        pagination.currentPage = response.data.data.current_page;
        pagination.lastPage = response.data.data.last_page;
        pagination.total = response.data.data.total;
    } catch (error) {
        console.error("Failed to load users", error);
    } finally {
        loading.value = false;
    }
}

async function toggleActive(user) {
    if (!confirm(`Voulez-vous vraiment ${user.is_active ? 'désactiver' : 'activer'} cet utilisateur ?`)) return;

    try {
        await api.patch(`/admin/users/${user.id}/toggle-active`, {
            is_active: !user.is_active
        });
        loadUsers(pagination.currentPage);
    } catch (error) {
        alert("Erreur lors de la modification du statut.");
    }
}

function openCreateModal() {
    selectedUser.value = null;
    showModal.value = true;
}

function editUser(user) {
    selectedUser.value = user;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedUser.value = null;
}

function handleSaved() {
    closeModal();
    loadUsers();
}

function getInitials(name) {
    return name ? name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';
}

function formatDate(date) {
    if (!date) return 'Jamais';
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
