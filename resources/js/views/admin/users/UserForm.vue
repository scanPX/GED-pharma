<template>
  <div class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="$emit('close')"></div>

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden relative animate-scale-up">
      <!-- Header -->
      <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div>
          <h2 class="text-2xl font-black text-gray-900 leading-tight">
            {{ user ? 'Modifier l\'utilisateur' : 'Nouvel Utilisateur' }}
          </h2>
          <p class="text-sm text-gray-500 mt-1">Configurez l'identité et les habilitations de l'agent.</p>
        </div>
        <button @click="$emit('close')" class="p-2 hover:bg-white hover:shadow-sm rounded-xl transition-all">
          <XMarkIcon class="w-6 h-6 text-gray-400" />
        </button>
      </div>

      <div class="p-8">
        <form @submit.prevent="saveUser" class="space-y-8">
          <!-- Identity Section -->
          <div>
            <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-4">Identité Professionnelle</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nom Complet</label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  required 
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
                >
              </div>

              <div class="md:col-span-2">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Adresse Email</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  required 
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
                >
              </div>

              <!-- New Structure Fields -->
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Entité</label>
                <select 
                  v-model="form.entitie_id" 
                  @change="onEntityChange"
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
                >
                  <option value="">Sélectionner une entité</option>
                  <option v-for="entity in entities" :key="entity.id" :value="entity.id">{{ entity.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Département</label>
                <select 
                  v-model="form.department_id" 
                  @change="onDepartmentChange"
                  :disabled="!form.entitie_id"
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium disabled:opacity-50"
                >
                  <option value="">Sélectionner un département</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Fonction</label>
                <select 
                  v-model="form.fonction_id" 
                  :disabled="!form.department_id"
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium disabled:opacity-50"
                >
                  <option value="">Sélectionner une fonction</option>
                  <option v-for="func in functions" :key="func.id" :value="func.id">{{ func.name }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Titre (Facultatif)</label>
                <input 
                  v-model="form.title" 
                  type="text" 
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
                >
              </div>
            </div>
          </div>

          <!-- Access Section -->
          <div>
            <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-4">Habilitations & Accès</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Rôle Principal</label>
                <select 
                  v-model="form.role" 
                  required 
                  class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
                >
                  <option value="standard_user">Utilisateur Standard</option>
                  <option value="qa_analyst">QA Analyst</option>
                  <option value="qa_manager">QA Manager</option>
                  <option value="admin">Administrateur</option>
                </select>
              </div>

              <div class="flex items-center pt-8">
                <button 
                  type="button"
                  @click="form.is_active = !form.is_active"
                  :class="form.is_active ? 'bg-blue-600' : 'bg-gray-200'"
                  class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                >
                  <span :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                </button>
                <span class="ml-3 text-sm font-bold text-gray-700">Compte {{ form.is_active ? 'Actif' : 'Désactivé' }}</span>
              </div>
            </div>
          </div>

          <!-- Security (Create or Password Reset) -->
          <div v-if="!user || showPasswordFields" class="p-8 bg-blue-50/50 rounded-3xl border border-blue-100 space-y-6">
            <h3 class="text-xs font-black text-blue-800 uppercase tracking-widest">Sécurité du Compte</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-black text-blue-400 uppercase tracking-widest mb-2">Mot de passe</label>
                <input 
                  v-model="form.password" 
                  type="password" 
                  :required="!user" 
                  minlength="8" 
                  class="w-full px-5 py-4 bg-white border-2 border-blue-100 rounded-2xl focus:border-blue-600 transition-all outline-none text-sm font-medium"
                >
              </div>
              <div>
                <label class="block text-xs font-black text-blue-400 uppercase tracking-widest mb-2">Confirmation</label>
                <input 
                  v-model="form.password_confirmation" 
                  type="password" 
                  :required="!user" 
                  class="w-full px-5 py-4 bg-white border-2 border-blue-100 rounded-2xl focus:border-blue-600 transition-all outline-none text-sm font-medium"
                >
              </div>
            </div>
            <div class="flex items-start">
              <input v-model="form.must_change_password" type="checkbox" class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded-md focus:ring-blue-500">
              <label class="ml-3 text-xs font-bold text-blue-800 leading-normal">
                Forcer le changement de mot de passe à la prochaine connexion.<br>
                <span class="text-[10px] font-medium opacity-70">Exigence GMP pour les nouveaux comptes ou réinitialisations.</span>
              </label>
            </div>
          </div>

          <div v-if="user && !showPasswordFields" class="flex justify-center">
            <button 
              type="button" 
              @click="showPasswordFields = true"
              class="text-xs font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest py-2 px-4 rounded-xl hover:bg-blue-50 transition-all"
            >
              Réinitialiser le mot de passe
            </button>
          </div>

          <!-- Actions -->
          <div class="pt-4 flex space-x-4">
            <button 
              type="button" 
              @click="$emit('close')" 
              class="flex-1 py-4 bg-gray-100 text-gray-600 font-bold rounded-2xl hover:bg-gray-200 transition-all"
            >
              Annuler
            </button>
            <button 
              type="submit" 
              :disabled="saving" 
              class="flex-2 py-4 px-12 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 disabled:opacity-50 transition-all flex items-center justify-center"
            >
              <ArrowPathIcon v-if="saving" class="w-5 h-5 mr-3 animate-spin" />
              {{ user ? 'METTRE À JOUR' : 'CRÉER LE COMPTE' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Compliance Note -->
      <div v-if="errorMessage" class="p-4 bg-red-50 border-t border-red-100 text-center">
        <p class="text-sm font-bold text-red-600">{{ errorMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/bootstrap';
import { 
  XMarkIcon, 
  ArrowPathIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const showPasswordFields = ref(false);
const errorMessage = ref('');

// Structure Data
const entities = ref([]);
const departments = ref([]);
const functions = ref([]);

const form = reactive({
    name: '',
    email: '',
    entitie_id: '',
    department_id: '',
    fonction_id: '',
    title: '',
    role: 'standard_user',
    password: '',
    password_confirmation: '',
    must_change_password: true,
    is_active: true
});

onMounted(async () => {
    // Load Entities
    try {
        const { data } = await api.get('/admin/entities');
        entities.value = data;
    } catch (e) {
        console.error("Failed to load entities", e);
    }

    if (props.user) {
        form.name = props.user.name;
        form.email = props.user.email;
        form.title = props.user.title;
        form.role = props.user.roles?.[0]?.name || 'standard_user';
        form.is_active = !!props.user.is_active;
        form.must_change_password = !!props.user.must_change_password;
        
        // Load relationships if existing user
        if (props.user.department?.entitie_id) {
            form.entitie_id = props.user.department.entitie_id;
            await loadDepartments(form.entitie_id);
        }
        
        if (props.user.department_id) {
            // If user has department but maybe we didn't get entity from it (if not loaded), try to infer?
            // Assuming user.department IS loaded with entity.
            form.department_id = props.user.department_id;
            await loadFunctions(form.department_id);
        }

        if (props.user.fonction_id) {
            form.fonction_id = props.user.fonction_id;
        }
    }
});

async function loadDepartments(entityId) {
    if (!entityId) {
        departments.value = [];
        return;
    }
    try {
        const { data } = await api.get(`/admin/entities/${entityId}/departments`);
        departments.value = data;
    } catch (e) {
        console.error("Failed to load departments", e);
    }
}

async function loadFunctions(deptId) {
    if (!deptId) {
        functions.value = [];
        return;
    }
    try {
        const { data } = await api.get(`/admin/departments/${deptId}/functions`);
        functions.value = data;
    } catch (e) {
        console.error("Failed to load functions", e);
    }
}

async function onEntityChange() {
    form.department_id = '';
    form.fonction_id = '';
    functions.value = [];
    await loadDepartments(form.entitie_id);
}

async function onDepartmentChange() {
    form.fonction_id = '';
    await loadFunctions(form.department_id);
}

async function saveUser() {
    saving.value = true;
    errorMessage.value = '';

    try {
        const payload = { ...form };
        if (props.user && !showPasswordFields) {
            delete payload.password;
            delete payload.password_confirmation;
        }

        if (props.user) {
            await api.put(`/admin/users/${props.user.id}`, payload);
        } else {
            await api.post('/admin/users', payload);
        }
        emit('saved');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Une erreur est survenue lors de la sauvegarde.";
    } finally {
        saving.value = false;
    }
}
</script>
