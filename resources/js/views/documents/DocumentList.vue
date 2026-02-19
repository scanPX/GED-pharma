<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Documents</h1>
        <p class="mt-1 text-sm text-gray-500">
          {{ documentStore.pagination.total }} documents au total
        </p>
      </div>
      
      <router-link
        v-if="authStore.hasPermission('document.create')"
        to="/documents/create"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
      >
        <PlusIcon class="w-5 h-5 mr-2" />
        Nouveau document
      </router-link>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
      <div class="flex flex-col lg:flex-row lg:items-center gap-4">
        <!-- Search -->
        <div class="flex-1 relative">
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher par titre, numéro ou contenu..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            @keyup.enter="applyFilters"
          />
        </div>

        <!-- Category filter -->
        <select
          v-model="filters.category_id"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
          @change="applyFilters"
        >
          <option :value="null">Toutes les catégories</option>
          <option 
            v-for="cat in documentStore.categories" 
            :key="cat.id" 
            :value="cat.id"
          >
            {{ cat.name }}
          </option>
        </select>

        <!-- Status filter -->
        <select
          v-model="filters.status_id"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
          @change="applyFilters"
        >
          <option :value="null">Tous les statuts</option>
          <option 
            v-for="status in documentStore.statuses" 
            :key="status.id" 
            :value="status.id"
          >
            {{ status.name }}
          </option>
        </select>

        <!-- More filters toggle -->
        <button
          @click="showAdvancedFilters = !showAdvancedFilters"
          class="inline-flex items-center px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <AdjustmentsHorizontalIcon class="w-5 h-5 mr-1" />
          Filtres avancés
          <ChevronDownIcon 
            :class="['w-4 h-4 ml-1 transition-transform', showAdvancedFilters ? 'rotate-180' : '']" 
          />
        </button>

        <!-- Reset filters -->
        <button
          @click="resetFilters"
          v-if="hasActiveFilters"
          class="text-sm text-red-600 hover:text-red-700 font-medium"
        >
          Réinitialiser
        </button>

        <div class="h-8 w-px bg-gray-200 hidden lg:block mx-2"></div>

        <button
          @click="toggleNeedingReview"
          :class="[
            'inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors border',
            filters.needing_review 
              ? 'bg-amber-50 border-amber-200 text-amber-700 shadow-sm' 
              : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
          ]"
        >
          <InformationCircleIcon class="w-4 h-4 mr-2" />
          À réviser
          <span 
            v-if="documentStore.pendingApprovalCount > 0"
            class="ml-2 bg-amber-200 text-amber-800 text-[10px] font-bold px-1.5 py-0.5 rounded-full"
          >
            {{ documentStore.pendingApprovalCount }}
          </span>
        </button>
      </div>

      <!-- Advanced filters -->
      <div 
        v-if="showAdvancedFilters"
        class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4"
      >
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type de document</label>
          <select
            v-model="filters.type_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            @change="applyFilters"
          >
            <option :value="null">Tous les types</option>
            <option 
              v-for="type in documentStore.types" 
              :key="type.id" 
              :value="type.id"
            >
              {{ type.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date de création (depuis)</label>
          <input
            v-model="filters.date_from"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            @change="applyFilters"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date de création (jusqu'à)</label>
          <input
            v-model="filters.date_to"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
            @change="applyFilters"
          />
        </div>
      </div>
    </div>

    <!-- Documents table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- Loading -->
      <div v-if="documentStore.loading" class="p-8 text-center">
        <div class="animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
        <p class="mt-4 text-gray-500">Chargement des documents...</p>
      </div>

      <!-- Empty state -->
      <div v-else-if="documentStore.documents.length === 0" class="p-12 text-center">
        <DocumentIcon class="w-16 h-16 text-gray-300 mx-auto" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucun document trouvé</h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ hasActiveFilters ? 'Essayez de modifier vos filtres de recherche.' : 'Commencez par créer un nouveau document.' }}
        </p>
        <router-link
          v-if="!hasActiveFilters && authStore.hasPermission('document.create')"
          to="/documents/create"
          class="mt-6 inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          Créer un document
        </router-link>
      </div>

      <!-- Table -->
      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Document
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Catégorie
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Version
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Statut
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Propriétaire
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Modifié le
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr 
            v-for="doc in documentStore.documents" 
            :key="doc.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-6 py-4">
              <div class="flex items-center">
                <div 
                  :class="[
                    'w-10 h-10 rounded-lg flex items-center justify-center',
                    getCategoryColor(doc.category?.code)
                  ]"
                >
                  <DocumentTextIcon class="w-5 h-5" />
                </div>
                <div class="ml-4">
                  <router-link 
                    :to="`/documents/${doc.id}`"
                    class="text-sm font-medium text-gray-900 hover:text-blue-600"
                  >
                    {{ doc.title }}
                  </router-link>
                  <div class="text-sm text-gray-500">{{ doc.document_number }}</div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span class="text-sm text-gray-900">{{ doc.category?.name || '—' }}</span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">
              v{{ doc.current_version }}
            </td>
            <td class="px-6 py-4">
              <StatusBadge :status="doc.status" />
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ doc.owner?.name || '—' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ formatDate(doc.updated_at) }}
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end space-x-2">
                <router-link
                  :to="`/documents/${doc.id}`"
                  class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  title="Voir le document"
                >
                  <EyeIcon class="w-5 h-5" />
                </router-link>
                <button
                  v-if="authStore.hasPermission('document.download')"
                  @click.stop.prevent="downloadDocument(doc)"
                  type="button"
                  aria-label="Télécharger le document"
                  class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                  title="Télécharger"
                >
                  <ArrowDownTrayIcon class="w-5 h-5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div 
        v-if="documentStore.pagination.lastPage > 1"
        class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
      >
        <div class="text-sm text-gray-500">
          Page {{ documentStore.pagination.currentPage }} sur {{ documentStore.pagination.lastPage }}
          ({{ documentStore.pagination.total }} résultats)
        </div>
        
        <div class="flex items-center space-x-2">
          <button
            @click="changePage(documentStore.pagination.currentPage - 1)"
            :disabled="documentStore.pagination.currentPage === 1"
            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Précédent
          </button>
          
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="changePage(page)"
            :class="[
              'px-3 py-1 text-sm rounded-lg',
              page === documentStore.pagination.currentPage
                ? 'bg-blue-600 text-white'
                : 'border border-gray-300 hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
          
          <button
            @click="changePage(documentStore.pagination.currentPage + 1)"
            :disabled="documentStore.pagination.currentPage === documentStore.pagination.lastPage"
            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDocumentStore } from '@/stores/documents';
import { useAuthStore } from '@/stores/auth';
import StatusBadge from '@/components/common/StatusBadge.vue';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  AdjustmentsHorizontalIcon,
  ChevronDownIcon,
  DocumentTextIcon,
  DocumentIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const documentStore = useDocumentStore();
const authStore = useAuthStore();

const showAdvancedFilters = ref(false);
const filters = ref({
  search: '',
  category_id: null,
  type_id: null,
  status_id: null,
  date_from: null,
  date_to: null,
  needing_review: false,
});

const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(v => v !== null && v !== '');
});

const visiblePages = computed(() => {
  const current = documentStore.pagination.currentPage;
  const last = documentStore.pagination.lastPage;
  const pages = [];
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i);
  }
  
  return pages;
});

onMounted(async () => {
  // Charger les données de référence
  await documentStore.fetchReferenceData();
  
  // Appliquer les filtres de l'URL
  if (route.query.search) {
    filters.value.search = route.query.search;
  }
  
  // Charger les documents
  await documentStore.fetchDocuments();
});

// Synchroniser les filtres avec le store
watch(filters, (newFilters) => {
  documentStore.setFilters(newFilters);
}, { deep: true });

function applyFilters() {
  documentStore.fetchDocuments(1);
  
  // Mettre à jour l'URL
  const query = {};
  if (filters.value.search) query.search = filters.value.search;
  router.replace({ query });
}

function resetFilters() {
  filters.value = {
    search: '',
    category_id: null,
    type_id: null,
    status_id: null,
    date_from: null,
    date_to: null,
    needing_review: false,
  };
  documentStore.resetFilters();
  documentStore.fetchDocuments(1);
  router.replace({ query: {} });
}

function toggleNeedingReview() {
  filters.value.needing_review = !filters.value.needing_review;
  applyFilters();
}

function changePage(page) {
  documentStore.fetchDocuments(page);
}

async function downloadDocument(doc) {
  await documentStore.downloadDocument(doc.id);
}

function formatDate(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

function getCategoryColor(code) {
  const colors = {
    SOP: 'bg-blue-100 text-blue-600',
    WI: 'bg-purple-100 text-purple-600',
    FORM: 'bg-gray-100 text-gray-600',
    SPEC: 'bg-emerald-100 text-emerald-600',
    VR: 'bg-amber-100 text-amber-600',
    VP: 'bg-orange-100 text-orange-600',
    POL: 'bg-red-100 text-red-600',
    DEV: 'bg-pink-100 text-pink-600',
    CAPA: 'bg-cyan-100 text-cyan-600',
    CC: 'bg-indigo-100 text-indigo-600',
  };
  return colors[code] || 'bg-gray-100 text-gray-600';
}
</script>
