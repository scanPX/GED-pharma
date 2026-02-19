<template>
  <div class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="$emit('close')"></div>

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden relative animate-scale-up">
      <!-- Header -->
      <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div>
          <h2 class="text-2xl font-black text-gray-900 leading-tight">
            {{ fonction ? 'Modifier la Fonction' : 'Nouvelle Fonction' }}
          </h2>
          <p class="text-sm text-gray-500 mt-1">Configurez les informations de la fonction.</p>
        </div>
        <button @click="$emit('close')" class="p-2 hover:bg-white hover:shadow-sm rounded-xl transition-all">
          <XMarkIcon class="w-6 h-6 text-gray-400" />
        </button>
      </div>

      <div class="p-8">
        <form @submit.prevent="saveFunction" class="space-y-6">
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Département</label>
            <select 
              v-model="form.departement_id" 
              required 
              class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
            >
              <option value="" disabled>Sélectionner un département</option>
              <optgroup v-for="entity in groupedDepartments" :key="entity.id" :label="entity.name">
                <option v-for="dept in entity.departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </optgroup>
            </select>
          </div>

          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nom de la Fonction</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              placeholder="ex: Analyste QA"
              class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
            >
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
              {{ fonction ? 'METTRE À JOUR' : 'ENREGISTRER' }}
            </button>
          </div>
        </form>
      </div>

      <div v-if="errorMessage" class="p-4 bg-red-50 border-t border-red-100 text-center">
        <p class="text-sm font-bold text-red-600">{{ errorMessage }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import api from '@/bootstrap';
import { 
  XMarkIcon, 
  ArrowPathIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    fonction: Object
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const entities = ref([]);
const departments = ref([]);
const errorMessage = ref('');

const form = reactive({
    name: '',
    departement_id: ''
});

const groupedDepartments = computed(() => {
    return entities.value.map(entity => ({
        ...entity,
        departments: departments.value.filter(d => d.entitie_id === entity.id)
    })).filter(e => e.departments.length > 0);
});

onMounted(async () => {
    loadData();
    if (props.fonction) {
        form.name = props.fonction.name;
        form.departement_id = props.fonction.departement_id;
    }
});

async function loadData() {
    try {
        const [entitiesRes, departmentsRes] = await Promise.all([
            api.get('/admin/entities'),
            api.get('/admin/departments')
        ]);
        entities.value = entitiesRes.data;
        departments.value = departmentsRes.data;
    } catch (error) {
        console.error("Failed to load reference data", error);
    }
}

async function saveFunction() {
    saving.value = true;
    errorMessage.value = '';

    try {
        if (props.fonction) {
            await api.put(`/admin/functions/${props.fonction.id}`, form);
        } else {
            await api.post('/admin/functions', form);
        }
        emit('saved');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Une erreur est survenue lors de la sauvegarde.";
    } finally {
        saving.value = false;
    }
}
</script>
