<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden border border-white/20 premium-shadow">
      <!-- Modal Header with Glassmorphism -->
      <div class="px-8 py-6 glass-header border-b border-gray-100 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">
            {{ props.step ? 'Modifier' : 'Nouvelle' }} <span class="text-gradient">Étape</span>
          </h2>
          <p class="text-sm text-gray-500 mt-1">Définissez les règles et les responsables de cette validation.</p>
        </div>
        <button 
          @click="$emit('close')" 
          class="p-2.5 bg-gray-50 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition-all micro-interaction"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="save" class="p-8 space-y-8 max-h-[75vh] overflow-y-auto custom-scrollbar">
        <!-- Configuration Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div class="space-y-6">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Libellé de l'étape</label>
              <input 
                v-model="form.name" 
                type="text" 
                required
                placeholder="ex: Revue Assurance Qualité"
                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-gray-900 placeholder-gray-400 font-medium"
              >
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Type d'action</label>
              <select 
                v-model="form.step_type" 
                required
                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-gray-900 font-medium appearance-none"
              >
                <option value="review">Revue simple</option>
                <option value="approval">Approbation standard</option>
                <option value="signature">Signature électronique</option>
                <option value="qa_approval">Approbation QA</option>
                <option value="regulatory_approval">Validation Réglementaire</option>
                <option value="final_approval">Approbation Finale</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 ml-1">Assignation</label>
              <div class="space-y-3">
                <!-- SÉLECTION PAR RÔLE -->
                <div 
                    @click="assignmentType = 'role'"
                    :class="['p-4 rounded-xl border cursor-pointer transition-all group', assignmentType === 'role' ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-200' : 'bg-white border-gray-200 hover:border-blue-300']"
                >
                   <div class="flex items-center mb-2">
                      <div :class="['w-4 h-4 rounded-full border flex items-center justify-center mr-3', assignmentType === 'role' ? 'border-blue-600' : 'border-gray-300']">
                          <div v-if="assignmentType === 'role'" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                      </div>
                      <span :class="['text-sm font-bold', assignmentType === 'role' ? 'text-blue-700' : 'text-gray-700']">Par Rôle</span>
                   </div>
                   <select 
                      v-if="assignmentType === 'role'"
                      v-model="form.required_role_id"
                      class="w-full mt-1 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 transition-all animate-fade-in"
                      @click.stop
                   >
                      <option :value="null">-- Sélectionner un rôle --</option>
                      <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                   </select>
                </div>

                <!-- SÉLECTION PAR UTILISATEUR -->
                <div 
                    @click="assignmentType = 'user'"
                    :class="['p-4 rounded-xl border cursor-pointer transition-all group', assignmentType === 'user' ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-200' : 'bg-white border-gray-200 hover:border-blue-300']"
                >
                   <div class="flex items-center mb-2">
                       <div :class="['w-4 h-4 rounded-full border flex items-center justify-center mr-3', assignmentType === 'user' ? 'border-blue-600' : 'border-gray-300']">
                          <div v-if="assignmentType === 'user'" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                      </div>
                      <span :class="['text-sm font-bold', assignmentType === 'user' ? 'text-blue-700' : 'text-gray-700']">Par Utilisateur</span>
                   </div>
                   <select 
                      v-if="assignmentType === 'user'"
                      v-model="form.required_user_id"
                      class="w-full mt-1 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 transition-all animate-fade-in"
                      @click.stop
                   >
                      <option :value="null">-- Sélectionner --</option>
                      <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                   </select>
                </div>
                
                <!-- TOUT UTILISATEUR AUTORISÉ -->
                <div 
                    @click="assignmentType = 'any'"
                    :class="['p-4 rounded-xl border cursor-pointer transition-all group', assignmentType === 'any' ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-200' : 'bg-white border-gray-200 hover:border-blue-300']"
                >
                   <div class="flex items-center">
                       <div :class="['w-4 h-4 rounded-full border flex items-center justify-center mr-3', assignmentType === 'any' ? 'border-blue-600' : 'border-gray-300']">
                          <div v-if="assignmentType === 'any'" class="w-2 h-2 bg-blue-600 rounded-full"></div>
                      </div>
                      <span :class="['text-sm font-bold', assignmentType === 'any' ? 'text-blue-700' : 'text-gray-700']">Tout utilisateur autorisé</span>
                   </div>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-6">
            <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100 space-y-5">
              <h4 class="text-xs font-extrabold text-blue-600 uppercase tracking-widest flex items-center">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-2"></span>
                Exigences
              </h4>
              
              <label class="relative flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-emerald-300 hover:shadow-sm transition-all group">
                <span class="text-sm font-bold text-gray-700 group-hover:text-emerald-700 transition-colors">Signature Requise</span>
                <input type="checkbox" v-model="form.requires_signature" class="sr-only peer">
                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[0.95rem] after:right-[0.9rem] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
              </label>

              <label class="relative flex items-center justify-between p-3 bg-white border border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 hover:shadow-sm transition-all group">
                <span class="text-sm font-bold text-gray-700 group-hover:text-blue-700 transition-colors">Commentaire Requis</span>
                <input type="checkbox" v-model="form.requires_comment" class="sr-only peer">
                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[0.95rem] after:right-[0.9rem] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
              </label>

              <div class="pt-2">
                <label class="block text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-2 ml-1">Delai (jours)</label>
                <div class="relative">
                    <input 
                    v-model.number="form.timeout_days" 
                    type="number" 
                    min="1"
                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all font-bold text-gray-900"
                    >
                    <div class="absolute right-4 top-2.5 text-xs font-bold text-gray-400 pointer-events-none">Jours</div>
                </div>
              </div>
            </div>

            <div class="space-y-4 pt-2">
              <h4 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest ml-1">Transitions d'état</h4>
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="block text-[10px] font-bold text-emerald-600 uppercase mb-1 ml-1">En cas de succès</label>
                  <select v-model="form.target_status_id" class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-100 focus:border-emerald-500 transition-all">
                    <option :value="null">-- Statut --</option>
                    <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.name }}</option>
                  </select>
                </div>
                <div class="space-y-1">
                  <label class="block text-[10px] font-bold text-red-500 uppercase mb-1 ml-1">En cas de rejet</label>
                  <select v-model="form.rejection_status_id" class="w-full px-3 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-red-100 focus:border-red-500 transition-all">
                    <option :value="null">-- Statut --</option>
                    <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.name }}</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2 ml-1">Instructions pour l'approbateur</label>
          <textarea 
            v-model="form.description" 
            rows="3"
            placeholder="Détaillez les points de contrôle pour cette étape..."
            class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all text-gray-900 resize-none font-medium"
          ></textarea>
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
          {{ props.step ? 'Enregistrer les modifications' : 'Ajouter l\'étape' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useWorkflowStore } from '@/stores/workflows';
import api from '@/bootstrap';
import { XMarkIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  workflowId: { type: [Number, String], required: true },
  step: { type: Object, default: null }
});

const emit = defineEmits(['close', 'saved']);
const workflowStore = useWorkflowStore();

const loading = ref(false);
const error = ref(null);
const roles = ref([]);
const users = ref([]);
const statuses = ref([]);

const assignmentType = ref('role'); // 'role', 'user', 'any'

const form = reactive({
  name: '',
  description: '',
  step_type: 'approval',
  required_role_id: null,
  required_user_id: null,
  any_user_with_permission: false,
  requires_comment: true,
  requires_signature: false,
  timeout_days: 7,
  target_status_id: null,
  rejection_status_id: null
});

onMounted(async () => {
  if (props.step) {
    Object.assign(form, {
      name: props.step.name,
      description: props.step.description,
      step_type: props.step.step_type,
      required_role_id: props.step.required_role_id,
      required_user_id: props.step.required_user_id,
      any_user_with_permission: props.step.any_user_with_permission,
      requires_comment: props.step.requires_comment,
      requires_signature: props.step.requires_signature,
      timeout_days: props.step.timeout_days,
      target_status_id: props.step.target_status_id,
      rejection_status_id: props.step.rejection_status_id
    });
    
    // Determine initial assignment type
    if (props.step.required_user_id) {
        assignmentType.value = 'user';
    } else if (props.step.any_user_with_permission) {
        assignmentType.value = 'any';
    } else {
        assignmentType.value = 'role';
    }
  }

  // Fetch reference data
  try {
    const [rolesRes, usersRes, statusesRes] = await Promise.all([
      api.get('/admin/roles'),
      api.get('/admin/users?per_page=100'),
      api.get('/ged/documents/statuses') 
    ]);
    roles.value = rolesRes.data.data;
    users.value = usersRes.data.data.data; 
    statuses.value = statusesRes.data.data;
  } catch (err) {
    console.error('Erreur chargement données de référence:', err);
  }
});

// Watch assignment type to clear irrelevant fields
watch(assignmentType, (newType) => {
    if (newType === 'role') {
        form.required_user_id = null;
        form.any_user_with_permission = false;
    } else if (newType === 'user') {
        form.required_role_id = null;
        form.any_user_with_permission = false;
    } else if (newType === 'any') {
        form.required_role_id = null;
        form.required_user_id = null;
        form.any_user_with_permission = true;
    }
});

async function save() {
  loading.value = true;
  error.value = null;

  // Final cleanup before send
  if (assignmentType.value === 'role') {
      form.required_user_id = null;
      form.any_user_with_permission = false;
  } else if (assignmentType.value === 'user') {
      form.required_role_id = null;
      form.any_user_with_permission = false;
  } else if (assignmentType.value === 'any') {
      form.required_role_id = null;
      form.required_user_id = null;
      form.any_user_with_permission = true;
  }

  const action = props.step 
    ? workflowStore.updateWorkflowStep(props.workflowId, props.step.id, { ...form })
    : workflowStore.addWorkflowStep(props.workflowId, { ...form });

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
