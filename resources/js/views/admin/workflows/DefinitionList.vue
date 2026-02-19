<template>
  <div class="space-y-8 animate-fade-in">
    <!-- Header with Glassmorphism -->
    <div class="glass-header -mx-4 px-4 py-4 sm:-mx-8 sm:px-8 mb-6 border-b border-gray-200/50">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 max-w-7xl mx-auto">
        <div>
          <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
            Gestion des <span class="text-gradient">Workflows</span>
          </h1>
          <p class="mt-2 text-base text-gray-500 max-w-2xl">
            Configurez et supervisez vos processus d'approbation conformes aux normes pharmaceutiques.
          </p>
        </div>
        <div class="flex items-center">
          <button
            @click="openCreateModal"
            class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/30 transition-all duration-300 micro-interaction"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Nouveau Workflow
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <!-- Listings with Premium Cards -->
      <div v-if="workflowStore.loading" class="flex flex-col items-center justify-center py-20 bg-white/50 rounded-3xl border border-gray-100 backdrop-blur-sm min-h-[400px]">
        <div class="relative w-16 h-16">
          <div class="absolute inset-0 border-4 border-blue-600/20 rounded-full"></div>
          <div class="absolute inset-0 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        </div>
        <p class="mt-6 text-gray-500 font-medium text-lg">Initialisation des protocoles...</p>
      </div>

      <div v-else-if="workflowStore.workflows.length === 0" class="premium-card p-16 text-center border-dashed border-2 border-gray-200 bg-gray-50/50">
        <div class="w-24 h-24 bg-white text-blue-500 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm ring-1 ring-gray-100">
          <RectangleGroupIcon class="w-12 h-12" />
        </div>
        <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Aucun workflow configuré</h3>
        <p class="mt-3 text-gray-500 max-w-md mx-auto text-lg leading-relaxed">
          Définissez vos processus d'approbation pour automatiser le cycle de vie de vos documents.
        </p>
        <button 
          @click="openCreateModal"
          class="mt-10 inline-flex items-center px-8 py-4 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 transform hover:-translate-y-0.5"
        >
          <PlusIcon class="w-5 h-5 mr-2" />
          Créer mon premier workflow
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
        <div 
          v-for="workflow in workflowStore.workflows" 
          :key="workflow.id" 
          class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300 group relative overflow-hidden flex flex-col"
        >
          <!-- Status Line -->
          <div :class="['h-1.5 w-full absolute top-0 left-0', workflow.is_active ? 'bg-gradient-to-r from-blue-500 to-cyan-400' : 'bg-gray-200']"></div>
          
          <div class="p-8 flex-1">
            <div class="flex justify-between items-start mb-6">
              <div class="p-3.5 rounded-2xl bg-gray-50 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors duration-300">
                <component 
                  :is="getTypeIcon(workflow.type)" 
                  class="w-7 h-7" 
                />
              </div>
              <span 
                :class="[
                  'px-3 py-1 text-[11px] font-bold uppercase rounded-full tracking-wider border transition-all duration-300',
                  workflow.is_active 
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100' 
                    : 'bg-gray-50 text-gray-500 border-gray-100'
                ]"
              >
                {{ workflow.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>

            <div class="mb-8">
              <h3 class="text-xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors duration-200 leading-tight mb-2">
                {{ workflow.name }}
              </h3>
              <p class="text-xs font-mono text-gray-400 uppercase tracking-wide">{{ workflow.code }}</p>
              <p v-if="workflow.description" class="mt-3 text-sm text-gray-500 line-clamp-2">
                {{ workflow.description }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-8">
              <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-50 hover:border-blue-100 transition-colors">
                <span class="block text-[10px] text-gray-400 uppercase font-bold mb-1 tracking-wider">Type</span>
                <span class="text-sm font-bold text-gray-700">{{ formatType(workflow.type) }}</span>
              </div>
              <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-50 hover:border-blue-100 transition-colors">
                <span class="block text-[10px] text-gray-400 uppercase font-bold mb-1 tracking-wider">Complexité</span>
                <span class="text-sm font-bold text-gray-700 flex items-center">
                    {{ workflow.steps_count }} étape{{ workflow.steps_count > 1 ? 's' : '' }}
                </span>
              </div>
            </div>

            <!-- Doc Types Chips -->
            <div>
              <span class="block text-[10px] text-gray-400 uppercase font-bold mb-3 tracking-wider">Assigné à</span>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="dt in workflow.document_types.slice(0, 3)" 
                  :key="dt.id"
                  class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-[11px] font-bold border border-blue-100"
                >
                  {{ dt.name }}
                </span>
                <span v-if="workflow.document_types.length > 3" class="px-2 py-1 bg-gray-50 text-gray-500 rounded-lg text-[10px] font-bold">
                  +{{ workflow.document_types.length - 3 }}
                </span>
                <span v-if="!workflow.document_types?.length" class="text-sm text-gray-400 italic pl-1">Non assigné</span>
              </div>
            </div>
          </div>

          <!-- Actions Footer -->
          <div class="px-8 py-5 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between group-hover:bg-blue-50/10 transition-colors">
            <div class="flex items-center space-x-1">
              <button 
                @click="editWorkflow(workflow)"
                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-white hover:shadow-sm rounded-lg transition-all"
                title="Modifier les propriétés"
              >
                <PencilSquareIcon class="w-5 h-5" />
              </button>
              <button 
                @click="deleteWorkflow(workflow)"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-white hover:shadow-sm rounded-lg transition-all"
                title="Supprimer définitivement"
              >
                <TrashIcon class="w-5 h-5" />
              </button>
            </div>
            
            <router-link 
              :to="{ name: 'admin.workflows.detail', params: { id: workflow.id }}"
              class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-xl hover:bg-blue-600 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300"
            >
              Configurer
              <ArrowRightIcon class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <WorkflowForm 
       v-if="showModal"
       :workflow="selectedWorkflow"
       @close="closeModal"
       @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useWorkflowStore } from '@/stores/workflows';
import { 
  PlusIcon, 
  RectangleGroupIcon, 
  ShieldCheckIcon, 
  DocumentMagnifyingGlassIcon,
  CircleStackIcon,
  ArrowRightIcon,
  DocumentCheckIcon,
  PencilSquareIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';
import WorkflowForm from './WorkflowForm.vue';

const workflowStore = useWorkflowStore();
const showModal = ref(false);
const selectedWorkflow = ref(null);

onMounted(() => {
  loadWorkflows();
});

async function loadWorkflows() {
  await workflowStore.fetchAdminWorkflows();
}

function openCreateModal() {
  selectedWorkflow.value = null;
  showModal.value = true;
}

function editWorkflow(workflow) {
  selectedWorkflow.value = workflow;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  selectedWorkflow.value = null;
}

function onSaved() {
  closeModal();
  loadWorkflows();
}

async function deleteWorkflow(workflow) {
    if (!confirm(`Etes-vous sûr de vouloir supprimer le workflow "${workflow.name}" ? Cette action est irréversible.`)) {
        return;
    }

    // Call store action
    const result = await workflowStore.deleteWorkflow(workflow.id);
    
    if (result.success) {
        loadWorkflows();
    } else {
        alert(result.error || 'Impossible de supprimer ce workflow. Vérifiez qu\'il n\'a pas d\'instances actives.');
    }
}

function formatType(type) {
  const types = {
    approval: 'Approbation',
    review: 'Revue',
    validation: 'Validation',
    change_control: 'Changement'
  };
  return types[type] || type;
}

function getTypeIcon(type) {
  const icons = {
    approval: DocumentCheckIcon,
    review: DocumentMagnifyingGlassIcon,
    validation: ShieldCheckIcon,
    change_control: CircleStackIcon
  };
  return icons[type] || RectangleGroupIcon;
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
