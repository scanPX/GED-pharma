<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
    <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden border border-white/20 premium-shadow">
      <!-- Modal Header with Glassmorphism -->
      <div class="px-8 py-6 glass-header border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">
            {{ props.workflow ? 'Modifier' : 'Nouveau' }} <span class="text-gradient">Workflow</span>
          </h2>
          <p class="text-sm text-gray-500 mt-1">Définissez les paramètres de base du processus.</p>
        </div>
        <button 
          @click="$emit('close')" 
          class="p-2.5 bg-gray-50 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all micro-interaction"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="save" class="p-8 space-y-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
        <!-- Basic Info Section -->
        <div class="grid grid-cols-1 gap-6">
          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Désignation du Workflow</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              placeholder="ex: Approbation SOP Qualité"
              class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-gray-900 placeholder-gray-400 font-medium"
            >
          </div>

          <div class="grid grid-cols-2 gap-6">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Code Unique</label>
              <div class="relative">
                <input 
                  v-model="form.code" 
                  type="text" 
                  required
                  :disabled="!!props.workflow"
                  placeholder="ex: SOP_STD_APPROV"
                  class="w-full pl-10 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all font-mono text-sm uppercase disabled:bg-gray-100 disabled:text-gray-400"
                >
                <div class="absolute left-3 top-3.5 text-gray-400">
                  <span class="text-xs font-bold">#</span>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Type de Processus</label>
              <select 
                v-model="form.type" 
                required
                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-gray-900 font-medium appearance-none"
              >
                <option value="approval">Approbation (Classique)</option>
                <option value="review">Revue Documentaire</option>
                <option value="validation">Validation Technique</option>
                <option value="change_control">Contrôle de Changement</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Description</label>
            <textarea 
              v-model="form.description" 
              rows="3"
              placeholder="Décrivez l'objectif et la portée de ce workflow..."
              class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-gray-900 resize-none font-medium"
            ></textarea>
          </div>
        </div>

        <!-- Configuration Switches -->
        <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100">
          <h3 class="text-xs font-extrabold text-blue-600 uppercase tracking-widest mb-4 flex items-center">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-2"></span>
            Paramètres Avancés
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Switch Item -->
            <label class="relative flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 hover:shadow-sm transition-all group">
              <span class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 group-hover:text-blue-700 transition-colors">Approbation Séquentielle</span>
                <span class="text-[10px] text-gray-500 font-medium mt-0.5">Les étapes se suivent dans l'ordre</span>
              </span>
              <input type="checkbox" v-model="form.requires_sequential_approval" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1.15rem] after:right-[1.15rem] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>

            <label class="relative flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 hover:shadow-sm transition-all group">
              <span class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 group-hover:text-blue-700 transition-colors">Approbation Parallèle</span>
                <span class="text-[10px] text-gray-500 font-medium mt-0.5">Plusieurs approbateurs en même temps</span>
              </span>
              <input type="checkbox" v-model="form.allows_parallel_approval" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1.15rem] after:right-[1.15rem] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>

            <label class="relative flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 hover:shadow-sm transition-all group">
              <span class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 group-hover:text-blue-700 transition-colors">Unanimité Requise</span>
                <span class="text-[10px] text-gray-500 font-medium mt-0.5">Tous les approbateurs doivent valider</span>
              </span>
              <input type="checkbox" v-model="form.requires_all_approvers" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1.15rem] after:right-[1.15rem] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>

            <label class="relative flex items-center justify-between p-4 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-emerald-300 hover:shadow-sm transition-all group">
              <span class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 group-hover:text-emerald-700 transition-colors">Workflow Actif</span>
                <span class="text-[10px] text-gray-500 font-medium mt-0.5">Disponible pour les nouveaux documents</span>
              </span>
              <input type="checkbox" v-model="form.is_active" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1.15rem] after:right-[1.15rem] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
          </div>
        </div>

        <div v-if="error" class="p-4 bg-red-50 text-red-700 border border-red-100 rounded-xl text-sm font-medium animate-shake flex items-center">
          <ExclamationCircleIcon class="w-5 h-5 mr-2" />
          {{ error }}
        </div>
      </form>

      <div class="px-8 py-5 bg-white border-t border-gray-100 flex items-center justify-end space-x-4">
        <button 
          type="button" 
          @click="$emit('close')"
          class="px-6 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-all"
        >
          Annuler
        </button>
        <button 
          @click="save"
          :disabled="loading"
          class="px-8 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 disabled:opacity-50 transition-all micro-interaction flex items-center"
        >
          <span v-if="loading" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
          {{ props.workflow ? 'Enregistrer les modifications' : 'Créer le Workflow' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useWorkflowStore } from '@/stores/workflows';
import { XMarkIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  workflow: { type: Object, default: null }
});

const emit = defineEmits(['close', 'saved']);
const workflowStore = useWorkflowStore();

const loading = ref(false);
const error = ref(null);

const form = reactive({
  name: '',
  code: '',
  description: '',
  type: 'approval',
  requires_sequential_approval: true,
  allows_parallel_approval: false,
  requires_all_approvers: false,
  min_approvers: 1,
  is_active: true
});

onMounted(() => {
  if (props.workflow) {
    Object.assign(form, {
      name: props.workflow.name,
      code: props.workflow.code,
      description: props.workflow.description,
      type: props.workflow.type,
      requires_sequential_approval: props.workflow.requires_sequential_approval,
      allows_parallel_approval: props.workflow.allows_parallel_approval,
      requires_all_approvers: props.workflow.requires_all_approvers,
      min_approvers: props.workflow.min_approvers,
      is_active: props.workflow.is_active
    });
  }
});

async function save() {
  loading.value = true;
  error.value = null;

  const action = props.workflow 
    ? workflowStore.updateWorkflow(props.workflow.id, { ...form })
    : workflowStore.createWorkflow({ ...form });

  const result = await action;

  if (result.success) {
    emit('saved');
  } else {
    error.value = result.error || 'Une erreur est survenue';
  }
  loading.value = false;
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.animate-shake {
  animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
}
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #e2e8f0;
  border-radius: 10px;
}
</style>
