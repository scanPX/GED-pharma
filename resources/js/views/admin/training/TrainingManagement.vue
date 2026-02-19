<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-8 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Gestion des Formations</h1>
          <p class="text-sm text-gray-500 mt-1">Supervisez et assignez les formations documentaires obligatoires.</p>
        </div>
        <div class="flex items-center space-x-3">
          <button 
            @click="showAssignModal = true"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-200 transition-all micro-interaction"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Nouvelle Assignation
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Stats Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="premium-card p-6 flex items-center">
          <div class="p-3 bg-blue-50 rounded-2xl mr-4">
            <UserGroupIcon class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">En cours</p>
            <p class="text-2xl font-black text-gray-900">{{ stats.pending }}</p>
          </div>
        </div>
        <div class="premium-card p-6 flex items-center">
          <div class="p-3 bg-emerald-50 rounded-2xl mr-4">
            <CheckBadgeIcon class="w-6 h-6 text-emerald-600" />
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Validées</p>
            <p class="text-2xl font-black text-gray-900">{{ stats.completed }}</p>
          </div>
        </div>
        <div class="premium-card p-6 flex items-center border-red-100 shadow-red-50">
          <div class="p-3 bg-red-50 rounded-2xl mr-4">
            <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">En retard</p>
            <p class="text-2xl font-black text-gray-900">{{ stats.overdue }}</p>
          </div>
        </div>
        <div class="premium-card p-6 flex items-center">
          <div class="p-3 bg-gray-50 rounded-2xl mr-4">
            <DocumentTextIcon class="w-6 h-6 text-gray-400" />
          </div>
          <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</p>
            <p class="text-2xl font-black text-gray-900">{{ trainingStore.allTrainings.length }}</p>
          </div>
        </div>
      </div>

      <!-- Filters & Table -->
      <div class="premium-card p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <div class="relative flex-1 max-w-md">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
            <input 
              v-model="search"
              type="text" 
              placeholder="Rechercher un utilisateur ou un document..."
              class="w-full pl-12 pr-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm"
            >
          </div>
          
          <div class="flex items-center space-x-3">
            <select v-model="filterStatus" class="bg-gray-50 border-2 border-gray-100 rounded-2xl px-4 py-3 text-sm focus:border-blue-600 outline-none">
              <option value="">Tous les statuts</option>
              <option value="assigned">Assigné</option>
              <option value="in_progress">En cours</option>
              <option value="acknowledged">Validé</option>
              <option value="overdue">En retard</option>
            </select>
            <button @click="trainingStore.fetchAllTrainings()" class="p-3 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-colors">
              <ArrowPathIcon class="w-5 h-5 text-gray-500" />
            </button>
          </div>
        </div>

        <div class="overflow-x-auto -mx-8">
          <table class="w-full text-left">
            <thead>
              <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                <th class="px-8 py-4">Utilisateur</th>
                <th class="px-8 py-4">Document</th>
                <th class="px-8 py-4">Statut</th>
                <th class="px-8 py-4">Détails</th>
                <th class="px-8 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="training in filteredTrainings" :key="training.id" class="group hover:bg-gray-50/50 transition-colors">
                <td class="px-8 py-5">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center font-bold text-gray-500 mr-3 overflow-hidden shadow-inner">
                      {{ training.user?.name?.charAt(0) }}
                    </div>
                    <div>
                      <p class="text-sm font-bold text-gray-900">{{ training.user?.name }}</p>
                      <p class="text-xs text-gray-400">{{ training.user?.department }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <p class="text-sm font-bold text-gray-900">{{ training.document?.title }}</p>
                  <p class="text-[10px] text-gray-400 uppercase tracking-widest font-mono">{{ training.document?.document_number }} • v{{ training.document_version?.version_number }}</p>
                </td>
                <td class="px-8 py-5">
                  <span :class="getStatusClass(training.status)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                    {{ getStatusLabel(training.status) }}
                  </span>
                </td>
                <td class="px-8 py-5 text-xs text-gray-500 space-y-1">
                  <p v-if="training.completed_at">Validé le {{ formatDate(training.completed_at) }}</p>
                  <p v-else-if="training.due_date">Échéance : {{ formatDate(training.due_date) }}</p>
                  <p v-else>Assigné le {{ formatDate(training.assigned_at) }}</p>
                </td>
                <td class="px-8 py-5 text-right">
                  <button class="p-2 hover:bg-white hover:text-blue-600 rounded-lg transition-all micro-interaction group-hover:shadow-sm">
                    <EyeIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-600" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Assign Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showAssignModal = false"></div>
      
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden relative animate-scale-up">
        <div class="p-8">
          <h2 class="text-2xl font-black text-gray-900 mb-6">Assigner une formation</h2>
          
          <form @submit.prevent="submitAssign" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Choisir le Document</label>
                <select v-model="assignForm.document_id" required class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 transition-all outline-none">
                  <option value="">Sélectionnez un document</option>
                  <option v-for="doc in documents" :key="doc.id" :value="doc.id">
                    [{{ doc.document_number }}] {{ doc.title }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nombre d'utilisateurs</label>
                <div class="flex items-center space-x-2 bg-gray-50 border-2 border-gray-100 rounded-2xl px-5 py-4">
                  <UserGroupIcon class="w-5 h-5 text-gray-400" />
                  <span class="text-sm font-bold text-gray-900">{{ assignForm.user_ids.length }} sélectionnés</span>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Destinataires</label>
              <div class="h-40 overflow-y-auto bg-gray-50 border-2 border-gray-100 rounded-2xl p-4 space-y-2">
                <div v-for="user in users" :key="user.id" class="flex items-center">
                  <input type="checkbox" :id="'user-'+user.id" :value="user.id" v-model="assignForm.user_ids" class="rounded-md border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                  <label :for="'user-'+user.id" class="text-sm text-gray-700 cursor-pointer">{{ user.name }} ({{ user.department }})</label>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Date d'échéance</label>
                <input type="date" v-model="assignForm.due_date" class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 transition-all outline-none">
              </div>
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Motif d'assignation</label>
                <input type="text" v-model="assignForm.reason" placeholder="Ex: Lecture suite à révision v2.0" class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 transition-all outline-none">
              </div>
            </div>

            <div class="pt-4 flex justify-end space-x-4">
              <button type="button" @click="showAssignModal = false" class="px-8 py-4 bg-gray-100 text-gray-600 font-bold rounded-2xl hover:bg-gray-200 transition-all">Annuler</button>
              <button 
                type="submit" 
                :disabled="submittingAssignment || !assignForm.document_id || !assignForm.user_ids.length"
                class="px-8 py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 disabled:opacity-50 transition-all flex items-center"
              >
                <span v-if="submittingAssignment" class="animate-spin rounded-full h-5 w-5 border-2 border-white/30 border-t-white mr-3"></span>
                ASSIGNER LA FORMATION
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTrainingStore } from '@/stores/training';
import api from '@/bootstrap';
import { 
  PlusIcon,
  UserGroupIcon,
  CheckBadgeIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  EyeIcon
} from '@heroicons/vue/24/outline';

const trainingStore = useTrainingStore();
const search = ref('');
const filterStatus = ref('');
const showAssignModal = ref(false);
const submittingAssignment = ref(false);

const users = ref([]);
const documents = ref([]);

const stats = computed(() => {
  const all = trainingStore.allTrainings;
  return {
    pending: all.filter(t => ['assigned', 'in_progress'].includes(t.status)).length,
    completed: all.filter(t => t.status === 'acknowledged').length,
    overdue: all.filter(t => t.status === 'overdue').length
  };
});

const filteredTrainings = computed(() => {
  let list = trainingStore.allTrainings;
  
  if (filterStatus.value) {
    list = list.filter(t => t.status === filterStatus.value);
  }
  
  if (search.value) {
    const s = search.value.toLowerCase();
    list = list.filter(t => 
      t.user?.name?.toLowerCase().includes(s) || 
      t.document?.title?.toLowerCase().includes(s) ||
      t.document?.document_number?.toLowerCase().includes(s)
    );
  }
  
  return list;
});

const assignForm = ref({
  document_id: '',
  user_ids: [],
  due_date: '',
  reason: ''
});

onMounted(() => {
  trainingStore.fetchAllTrainings();
  fetchUsersAndDocs();
});

async function fetchUsersAndDocs() {
  try {
    const [uRes, dRes] = await Promise.all([
      api.get('/ged/admin/users'),
      api.get('/ged/documents')
    ]);
    users.value = uRes.data.data.data || uRes.data.data;
    documents.value = (dRes.data.data.data || dRes.data.data).filter(d => !d.is_archived);
  } catch (err) {
    console.error('Failed to fetch data for assignment modal', err);
  }
}

async function submitAssign() {
  submittingAssignment.value = true;
  try {
    await trainingStore.assignTraining(assignForm.value);
    showAssignModal.value = false;
    assignForm.value = { document_id: '', user_ids: [], due_date: '', reason: '' };
    trainingStore.fetchAllTrainings();
  } catch (err) {
    alert(err.message || "Erreur lors de l'assignation");
  } finally {
    submittingAssignment.value = false;
  }
}

function getStatusLabel(status) {
  const labels = {
    assigned: 'Assigné',
    in_progress: 'En cours',
    completed: 'Terminé',
    acknowledged: 'Validé',
    overdue: 'En retard',
    exempted: 'Exempté'
  };
  return labels[status] || status;
}

function getStatusClass(status) {
  const classes = {
    assigned: 'bg-blue-50 text-blue-600 border-blue-100',
    in_progress: 'bg-amber-50 text-amber-600 border-amber-100',
    acknowledged: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    overdue: 'bg-red-50 text-red-600 border-red-100',
    exempted: 'bg-gray-50 text-gray-500 border-gray-100'
  };
  return classes[status] || 'bg-gray-50 text-gray-500 border-gray-100';
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR');
}
</script>
