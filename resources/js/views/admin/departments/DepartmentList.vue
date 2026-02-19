<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-8 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Gestion des Départements</h1>
          <p class="text-sm text-gray-500 mt-1">Gérez les départements rattachés aux entités.</p>
        </div>
        <button 
          @click="openModal()" 
          class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-black rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all micro-interaction"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          AJOUTER UN DÉPARTEMENT
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <!-- Search -->
      <div class="premium-card p-8 mb-8">
        <div class="relative max-w-xl">
          <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
          <input 
            v-model="search"
            type="text" 
            placeholder="Rechercher un département..."
            class="w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm"
          >
        </div>
      </div>

      <!-- Department Table -->
      <div class="premium-card overflow-hidden">
        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"></div>
          <p class="text-gray-400 text-sm">Chargement des départements...</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                <th class="px-8 py-4">Département</th>
                <th class="px-8 py-4">Entité parente</th>
                <th class="px-8 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="dept in filteredDepartments" :key="dept.id" class="group hover:bg-gray-50/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-teal-50 to-teal-100 text-teal-600 rounded-full flex items-center justify-center font-bold text-xs mr-4 shadow-sm">
                      <RectangleGroupIcon class="w-5 h-5" />
                    </div>
                    <div>
                      <p class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ dept.name }}</p>
                      <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest">ID: #{{ dept.id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <span v-if="dept.entity" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 italic">
                    {{ dept.entity.name }}
                  </span>
                  <span v-else class="text-gray-400 text-xs italic">N/A</span>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button 
                      @click="openModal(dept)" 
                      class="p-2 bg-gray-50 text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition-all micro-interaction"
                      title="Modifier"
                    >
                      <PencilSquareIcon class="w-5 h-5" />
                    </button>
                    <button 
                      @click="deleteDepartment(dept)"
                      class="p-2 bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-600 rounded-xl transition-all micro-interaction"
                      title="Supprimer"
                    >
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredDepartments.length === 0">
                <td colspan="3" class="px-8 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <ArchiveBoxIcon class="w-12 h-12 text-gray-200 mb-4" />
                    <p class="text-gray-400 text-sm font-medium">Aucun département trouvé</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <DepartmentForm
      v-if="showModal"
      :department="selectedDepartment"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/bootstrap';
import { 
  RectangleGroupIcon,
  MagnifyingGlassIcon,
  ArchiveBoxIcon,
  PlusIcon,
  PencilSquareIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';
import DepartmentForm from './DepartmentForm.vue';

const departments = ref([]);
const loading = ref(true);
const search = ref('');
const showModal = ref(false);
const selectedDepartment = ref(null);

onMounted(() => {
    loadDepartments();
});

async function loadDepartments() {
    loading.value = true;
    try {
        const response = await api.get('/admin/departments');
        departments.value = response.data;
    } catch (error) {
        console.error("Failed to load departments", error);
    } finally {
        loading.value = false;
    }
}

const filteredDepartments = computed(() => {
    if (!search.value) return departments.value;
    const s = search.value.toLowerCase();
    return departments.value.filter(d => 
        d.name.toLowerCase().includes(s) || 
        (d.entity && d.entity.name.toLowerCase().includes(s))
    );
});

function openModal(dept = null) {
  selectedDepartment.value = dept;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedDepartment.value = null;
}

function handleSaved() {
  closeModal();
  loadDepartments();
}

async function deleteDepartment(dept) {
  if (!confirm(`Voulez-vous vraiment supprimer le département "${dept.name}" ? Cette action est irréversible et ne fonctionnera que si le département est vide.`)) return;

  try {
    await api.delete(`/admin/departments/${dept.id}`);
    loadDepartments();
  } catch (error) {
    alert(error.response?.data?.message || "Erreur lors de la suppression.");
  }
}
</script>
