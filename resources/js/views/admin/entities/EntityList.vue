<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-8 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Gestion des Entités</h1>
          <p class="text-sm text-gray-500 mt-1">Gérez la structure organisationnelle de haut niveau. Création restreinte.</p>
        </div>
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
            placeholder="Rechercher une entité..."
            class="w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm"
          >
        </div>
      </div>

      <!-- Entity Table -->
      <div class="premium-card overflow-hidden">
        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mb-4"></div>
          <p class="text-gray-400 text-sm">Chargement des entités...</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                <th class="px-8 py-4">Entité</th>
                <th class="px-8 py-4">Description</th>
                <th class="px-8 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="entity in filteredEntities" :key="entity.id" class="group hover:bg-gray-50/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-50 to-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-xs mr-4 shadow-sm">
                      <BuildingOfficeIcon class="w-5 h-5" />
                    </div>
                    <div>
                      <p class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ entity.name }}</p>
                      <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest">ID: #{{ entity.id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <p class="text-xs text-gray-500 line-clamp-2 max-w-md">{{ entity.description || 'Aucune description' }}</p>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button 
                      @click="editEntity(entity)" 
                      class="p-2 bg-gray-50 text-gray-400 hover:bg-blue-50 hover:text-blue-600 rounded-xl transition-all micro-interaction"
                      title="Modifier"
                    >
                      <PencilSquareIcon class="w-5 h-5" />
                    </button>
                    <button 
                      @click="deleteEntity(entity)"
                      class="p-2 bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-600 rounded-xl transition-all micro-interaction"
                      title="Supprimer"
                    >
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredEntities.length === 0">
                <td colspan="3" class="px-8 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <ArchiveBoxIcon class="w-12 h-12 text-gray-200 mb-4" />
                    <p class="text-gray-400 text-sm font-medium">Aucune entité trouvée</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Entity Form -->
    <EntityForm
        v-if="showModal"
        :entity="selectedEntity"
        @close="closeModal"
        @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/bootstrap';
import { 
  BuildingOfficeIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  TrashIcon,
  ArchiveBoxIcon
} from '@heroicons/vue/24/outline';
import EntityForm from './EntityForm.vue';

const entities = ref([]);
const loading = ref(true);
const showModal = ref(false);
const selectedEntity = ref(null);
const search = ref('');

onMounted(() => {
    loadEntities();
});

async function loadEntities() {
    loading.value = true;
    try {
        const response = await api.get('/admin/entities');
        entities.value = response.data;
    } catch (error) {
        console.error("Failed to load entities", error);
    } finally {
        loading.value = false;
    }
}

const filteredEntities = computed(() => {
    if (!search.value) return entities.value;
    const s = search.value.toLowerCase();
    return entities.value.filter(e => 
        e.name.toLowerCase().includes(s) || 
        (e.description && e.description.toLowerCase().includes(s))
    );
});

async function deleteEntity(entity) {
    if (!confirm(`Voulez-vous vraiment supprimer l'entité "${entity.name}" ? Cette action est irréversible et ne fonctionnera que si l'entité est vide.`)) return;

    try {
        await api.delete(`/admin/entities/${entity.id}`);
        loadEntities();
    } catch (error) {
        alert(error.response?.data?.message || "Erreur lors de la suppression.");
    }
}

function editEntity(entity) {
    selectedEntity.value = entity;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedEntity.value = null;
}

function handleSaved() {
    closeModal();
    loadEntities();
}
</script>
