<template>
  <div class="space-y-8 animate-fade-in pb-20">
    <!-- Header with Glassmorphism -->
    <div class="sticky top-0 z-20 glass-header -mx-4 px-4 py-6 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-6">
          <router-link 
            :to="{ name: 'admin.workflows.index' }"
            class="p-3 bg-white/50 border border-gray-200 rounded-2xl text-gray-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all duration-300 micro-interaction shadow-sm"
          >
            <ArrowLeftIcon class="w-6 h-6" />
          </router-link>
          <div>
            <div class="flex items-center space-x-3">
              <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ workflow?.name || 'Chargement...' }}</h1>
              <span 
                v-if="workflow"
                :class="[
                  'px-3 py-1 text-[10px] font-bold uppercase rounded-full tracking-wider border shadow-sm',
                  workflow.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-50 text-gray-500 border-gray-100'
                ]"
              >
                {{ workflow.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>
            <p class="text-sm text-gray-400 mt-1 flex items-center">
              <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-[11px] mr-3">{{ workflow?.code }}</span>
              <span class="flex items-center">
                <component :is="getTypeIcon(workflow?.type)" class="w-3.5 h-3.5 mr-1.5 opacity-50" />
                {{ formatType(workflow?.type) }}
              </span>
            </p>
          </div>
        </div>
        <div class="flex space-x-3">
          <button 
            @click="openStepModal()"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all duration-300 micro-interaction"
          >
            <PlusIcon class="w-5 h-5 mr-2" />
            Ajouter une étape
          </button>
        </div>
      </div>
    </div>

    <!-- Visual Timeline / Stepper -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div v-if="steps.length > 0" class="premium-card p-8 mb-8 overflow-hidden">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6">Aperçu de la Séquence</h3>
        <div class="overflow-x-auto pb-4 custom-scrollbar">
            <div class="flex items-center min-w-max px-2">
            <div v-for="(step, index) in steps" :key="'timeline-' + step.id" class="relative group flex flex-col items-center">
                <!-- Line connecting steps -->
                <div 
                v-if="index < steps.length - 1" 
                class="absolute left-[50%] top-5 w-full h-0.5 bg-gray-200 group-hover:bg-blue-200 transition-colors z-0"
                :style="{ width: '100%', left: '50%' }"
                ></div>
                
                <div class="relative z-10 flex flex-col items-center w-32 px-2">
                <div 
                    :class="[
                    'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 shadow-sm border-2',
                    'bg-white text-gray-500 border-gray-200 group-hover:border-blue-500 group-hover:text-blue-600 group-hover:shadow-md'
                    ]"
                >
                    {{ index + 1 }}
                </div>
                <div class="mt-3 text-center">
                    <span class="block text-xs font-bold text-gray-800 truncate w-full group-hover:text-blue-600 transition-colors" :title="step.name">{{ step.name }}</span>
                    <span class="block text-[9px] uppercase font-bold text-gray-400 tracking-wider mt-0.5">{{ step.step_type }}</span>
                </div>
                </div>
            </div>
            <!-- End Flag -->
             <div class="flex flex-col items-center ml-2 opacity-50">
                <div class="w-10 h-10 rounded-full bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center">
                    <span class="w-2 h-2 bg-gray-300 rounded-full"></span>
                </div>
                 <span class="mt-3 text-[9px] uppercase font-bold text-gray-400 tracking-wider">Fin</span>
             </div>
            </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Steps Management -->
        <div class="lg:col-span-2 space-y-6">
          <div class="premium-card overflow-hidden border border-gray-100 shadow-sm">
            <div class="px-8 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
              <div class="flex items-center">
                <div class="p-2 bg-blue-50/50 rounded-lg mr-3 border border-blue-100">
                  <ListBulletIcon class="w-5 h-5 text-blue-600" />
                </div>
                <div>
                   <h3 class="font-bold text-gray-900 text-base">Séquencage</h3>
                   <p class="text-xs text-gray-500">Ordre d'exécution des étapes</p>
                </div>
              </div>
              <span class="text-[10px] bg-white border border-gray-200 text-gray-600 px-2.5 py-1 rounded-full uppercase tracking-widest font-bold shadow-sm">{{ steps.length }} étapes</span>
            </div>

            <div v-if="loading" class="p-16 text-center">
               <div class="animate-spin w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full mx-auto mb-4"></div>
               <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Chargement...</p>
            </div>
            
            <div v-else-if="steps.length === 0" class="p-16 text-center bg-gray-50/30">
              <div class="w-16 h-16 bg-white border border-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                 <ListBulletIcon class="w-8 h-8 text-gray-300" />
              </div>
              <h4 class="text-base font-bold text-gray-900">Workflow vide</h4>
              <p class="text-gray-500 mt-1 mb-6 max-w-xs mx-auto text-sm leading-relaxed">Commencez par ajouter la première étape de validation.</p>
              <button @click="openStepModal()" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300">
                <PlusIcon class="w-4 h-4 mr-2" />
                Ajouter une étape
              </button>
            </div>

            <div v-else class="divide-y divide-gray-50">
              <div 
                v-for="(step, index) in steps" 
                :key="step.id"
                class="group px-6 py-5 hover:bg-blue-50/20 flex items-center transition-all duration-200"
              >
                <!-- Numbering -->
                <div class="w-8 h-8 rounded-lg bg-gray-50 text-gray-400 border border-gray-100 flex items-center justify-center font-bold text-xs mr-5 shrink-0 group-hover:border-blue-200 group-hover:text-blue-600 group-hover:bg-blue-50 transition-all">
                  {{ index + 1 }}
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0 pr-4">
                  <div class="flex items-center space-x-3 mb-1.5">
                    <h4 class="text-sm font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">{{ step.name }}</h4>
                    <span 
                        :class="[
                            'text-[9px] px-2 py-0.5 rounded border shadow-sm uppercase font-extrabold tracking-wide',
                            step.step_type === 'approval' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-50 text-gray-500 border-gray-100'
                        ]"
                    >
                        {{ step.step_type }}
                    </span>
                  </div>
                  
                  <div class="flex items-center space-x-4">
                    <!-- Assignee Badge -->
                    <div class="flex items-center bg-gray-50 px-2 py-1 rounded text-[10px] text-gray-600 font-medium">
                        <span v-if="step.required_user" class="flex items-center">
                            <UserIcon class="w-3 h-3 mr-1.5 text-gray-400" /> 
                            <span class="truncate max-w-[120px]">{{ step.required_user.name }}</span>
                        </span>
                        <span v-else-if="step.required_role" class="flex items-center">
                            <UsersIcon class="w-3 h-3 mr-1.5 text-gray-400" /> 
                            <span>{{ step.required_role.name }}</span>
                        </span>
                        <span v-else-if="step.any_user_with_permission" class="flex items-center">
                            <GlobeAltIcon class="w-3 h-3 mr-1.5 text-gray-400" /> 
                            <span>Tout approbateur</span>
                        </span>
                    </div>

                    <!-- Requirements -->
                    <span v-if="step.requires_signature" class="flex items-center text-emerald-600 font-bold text-[10px]">
                      <ShieldCheckIcon class="w-3 h-3 mr-1" /> Signature
                    </span>
                  </div>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2 opacity-60 group-hover:opacity-100 transition-all duration-200">
                  <div class="flex flex-col space-y-1">
                     <button 
                      @click.stop="moveStep(step, -1)" 
                      :disabled="index === 0"
                      class="p-1 text-gray-300 hover:text-blue-600 disabled:opacity-0 hover:bg-blue-50 rounded transition-colors"
                    >
                      <ChevronUpIcon class="w-4 h-4" />
                    </button>
                    <button 
                      @click.stop="moveStep(step, 1)" 
                      :disabled="index === steps.length - 1"
                      class="p-1 text-gray-300 hover:text-blue-600 disabled:opacity-0 hover:bg-blue-50 rounded transition-colors"
                    >
                      <ChevronDownIcon class="w-4 h-4" />
                    </button>
                  </div>
                  
                  <div class="h-8 w-px bg-gray-100 mx-2"></div>

                  <button 
                    @click="openStepModal(step)" 
                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-100"
                    title="Modifier"
                  >
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <button 
                    @click="deleteStep(step)" 
                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100"
                    title="Supprimer"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Settings Side -->
        <div class="space-y-8">
          <!-- Configuration summary -->
          <div class="premium-card p-8">
             <h3 class="font-bold text-gray-900 text-lg mb-6 flex items-center">
               <div class="p-2 bg-blue-50 rounded-lg mr-3">
                 <Cog6ToothIcon class="w-5 h-5 text-blue-600" />
               </div>
               Propriétés
             </h3>
             <div class="space-y-5 text-sm">
               <div class="flex justify-between items-center py-2 border-b border-gray-50">
                 <span class="text-gray-500 font-medium">Séquentiel</span>
                 <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', workflow?.requires_sequential_approval ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-400']">
                   {{ workflow?.requires_sequential_approval ? 'Activé' : 'Désactivé' }}
                 </span>
               </div>
               <div class="flex justify-between items-center py-2 border-b border-gray-50">
                 <span class="text-gray-500 font-medium">Parallèle</span>
                 <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase', workflow?.allows_parallel_approval ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-400']">
                   {{ workflow?.allows_parallel_approval ? 'Activé' : 'Désactivé' }}
                 </span>
               </div>
               <div class="flex justify-between items-center py-2">
                 <span class="text-gray-500 font-medium">Approbateurs min.</span>
                 <span class="font-extrabold text-gray-900 border-2 border-gray-100 w-8 h-8 flex items-center justify-center rounded-lg">{{ workflow?.min_approvers }}</span>
               </div>
             </div>
          </div>

          <!-- Document Types Assignment -->
          <div class="premium-card p-8">
             <h3 class="font-bold text-gray-900 text-lg mb-6 flex items-center justify-between">
               <div class="flex items-center">
                 <div class="p-2 bg-blue-50 rounded-lg mr-3">
                   <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                 </div>
                 Assignation
               </div>
               <button 
                @click="saveAssignments" 
                v-if="hasAssignmentChanges" 
                class="px-3 py-1 bg-blue-600 text-white text-[10px] font-bold uppercase rounded-lg hover:bg-blue-700 transition-colors shadow-sm animate-pulse"
               >
                 Enregistrer
               </button>
             </h3>
             
             <div class="space-y-1 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
               <label 
                 v-for="dt in documentTypes" 
                 :key="dt.id" 
                 class="flex items-center p-3 rounded-xl hover:bg-gray-50 cursor-pointer transition-all group"
               >
                 <div class="relative flex items-center">
                    <input 
                      type="checkbox" 
                      :value="dt.id" 
                      v-model="selectedDocumentTypes"
                      class="w-5 h-5 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500 transition-all cursor-pointer"
                    >
                 </div>
                 <span class="ml-4 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">{{ dt.name }}</span>
               </label>
               <div v-if="documentTypes.length === 0" class="py-4 text-center text-xs text-gray-400 italic">
                 Aucun type disponible
               </div>
             </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Step Modal -->
    <StepForm 
       v-if="showStepModal"
       :workflow-id="workflowId"
       :step="selectedStep"
       @close="closeStepModal"
       @saved="onStepSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useWorkflowStore } from '@/stores/workflows';
import api from '@/bootstrap';
import { 
  ArrowLeftIcon, PlusIcon, ChevronUpIcon, ChevronDownIcon, 
  PencilSquareIcon, TrashIcon, ListBulletIcon, Cog6ToothIcon,
  DocumentTextIcon, ShieldCheckIcon, UserIcon, UsersIcon,
  GlobeAltIcon, DocumentCheckIcon, DocumentMagnifyingGlassIcon,
  RectangleGroupIcon, CircleStackIcon
} from '@heroicons/vue/24/outline';
import StepForm from './StepForm.vue';

const route = useRoute();
const workflowStore = useWorkflowStore();
const workflowId = route.params.id;

const workflow = ref(null);
const loading = ref(true);
const documentTypes = ref([]);
const selectedDocumentTypes = ref([]);
const initialDocumentTypes = ref([]);
const showStepModal = ref(false);
const selectedStep = ref(null);

const steps = computed(() => workflow.value?.steps || []);
const hasAssignmentChanges = computed(() => {
  return JSON.stringify(selectedDocumentTypes.value.sort()) !== JSON.stringify(initialDocumentTypes.value.sort());
});

onMounted(async () => {
  await Promise.all([
    loadWorkflow(),
    loadDocumentTypes()
  ]);
});

async function loadWorkflow() {
  loading.value = true;
  try {
    const response = await api.get(`/admin/workflows/${workflowId}`);
    workflow.value = response.data.data;
    selectedDocumentTypes.value = workflow.value.document_types.map(dt => dt.id);
    initialDocumentTypes.value = [...selectedDocumentTypes.value];
  } catch (err) {
    console.error('Erreur chargement workflow:', err);
  } finally {
    loading.value = false;
  }
}

async function loadDocumentTypes() {
  try {
    const response = await api.get('/ged/documents/types');
    documentTypes.value = response.data.data;
  } catch (err) {
    console.error('Erreur chargement types doc:', err);
  }
}

async function saveAssignments() {
  const result = await workflowStore.assignWorkflowTypes(workflowId, {
    document_type_ids: selectedDocumentTypes.value
  });
  if (result.success) {
    initialDocumentTypes.value = [...selectedDocumentTypes.value];
  } else {
    alert(result.error);
  }
}

async function moveStep(step, direction) {
  const currentIndex = steps.value.findIndex(s => s.id === step.id);
  const targetIndex = currentIndex + direction;
  
  if (targetIndex < 0 || targetIndex >= steps.value.length) return;
  
  const newSteps = [...steps.value];
  const temp = newSteps[currentIndex];
  newSteps[currentIndex] = newSteps[targetIndex];
  newSteps[targetIndex] = temp;
  
  const orderData = newSteps.map((s, idx) => ({ id: s.id, order: idx + 1 }));
  
  const result = await workflowStore.reorderWorkflowSteps(workflowId, orderData);
  if (result.success) {
    await loadWorkflow();
  }
}

async function deleteStep(step) {
  if (!confirm('Voulez-vous vraiment supprimer cette étape ?')) return;
  
  const result = await workflowStore.removeWorkflowStep(workflowId, step.id);
  if (result.success) {
    await loadWorkflow();
  } else {
    alert(result.error);
  }
}

function openStepModal(step = null) {
  selectedStep.value = step;
  showStepModal.value = true;
}

function closeStepModal() {
  showStepModal.value = false;
  selectedStep.value = null;
}

function onStepSaved() {
  closeStepModal();
  loadWorkflow();
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

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #e2e8f0;
  border-radius: 10px;
}
</style>
