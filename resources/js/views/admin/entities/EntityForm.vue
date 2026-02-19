<template>
  <div class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="$emit('close')"></div>

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden relative animate-scale-up">
      <!-- Header -->
      <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div>
          <h2 class="text-2xl font-black text-gray-900 leading-tight">
            Modifier l'Entité
          </h2>
          <p class="text-sm text-gray-500 mt-1">Mettez à jour les informations de structure organisationnelle.</p>
        </div>
        <button @click="$emit('close')" class="p-2 hover:bg-white hover:shadow-sm rounded-xl transition-all">
          <XMarkIcon class="w-6 h-6 text-gray-400" />
        </button>
      </div>

      <div class="p-8">
        <form @submit.prevent="saveEntity" class="space-y-6">
          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nom de l'Entité</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
            >
          </div>

          <div>
            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Description</label>
            <textarea 
              v-model="form.description" 
              rows="4"
              class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm font-medium"
            ></textarea>
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
              METTRE À JOUR
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
import { ref, reactive, onMounted } from 'vue';
import api from '@/bootstrap';
import { 
  XMarkIcon, 
  ArrowPathIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    entity: Object
});

const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const errorMessage = ref('');

const form = reactive({
    name: '',
    description: ''
});

onMounted(() => {
    if (props.entity) {
        form.name = props.entity.name;
        form.description = props.entity.description;
    }
});

async function saveEntity() {
    saving.value = true;
    errorMessage.value = '';

    try {
        await api.put(`/admin/entities/${props.entity.id}`, form);
        emit('saved');
    } catch (error) {
        errorMessage.value = error.response?.data?.message || "Une erreur est survenue lors de la sauvegarde.";
    } finally {
        saving.value = false;
    }
}
</script>
