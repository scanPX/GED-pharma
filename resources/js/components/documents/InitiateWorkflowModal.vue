<template>
  <div 
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          Soumettre pour approbation
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Document info -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
              <DocumentTextIcon class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ document.title }}</p>
              <p class="text-sm text-gray-500">{{ document.document_number }} • v{{ document.current_version }}</p>
            </div>
          </div>
        </div>

        <!-- Workflow selection -->
        <div>
          <label for="workflow" class="block text-sm font-medium text-gray-700 mb-1">
            Workflow d'approbation <span class="text-red-500">*</span>
          </label>
          <select
            id="workflow"
            v-model="form.workflow_id"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Sélectionner un workflow</option>
            <option 
              v-for="wf in availableWorkflows" 
              :key="wf.id" 
              :value="wf.id"
            >
              {{ wf.name }}
            </option>
          </select>
        </div>

        <!-- Workflow info -->
        <div v-if="selectedWorkflow" class="p-4 border border-gray-200 rounded-lg">
          <h4 class="font-medium text-gray-900 mb-3">Étapes du workflow</h4>
          <div class="space-y-2">
            <div 
              v-for="(step, index) in selectedWorkflow.steps" 
              :key="step.id"
              class="flex items-center text-sm"
            >
              <span class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs mr-3">
                {{ index + 1 }}
              </span>
              <span class="text-gray-700">{{ step.name }}</span>
              <span v-if="step.requires_signature" class="ml-2 text-xs text-amber-600">
                (Signature requise)
              </span>
            </div>
          </div>
        </div>

        <!-- Comment -->
        <div>
          <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
            Commentaire (optionnel)
          </label>
          <textarea
            id="comment"
            v-model="form.comment"
            rows="3"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none"
            placeholder="Ajouter un commentaire pour les approbateurs..."
          ></textarea>
        </div>

        <!-- Warning -->
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
          <div class="flex items-start space-x-3">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
            <div class="text-sm text-amber-800">
              <p class="font-medium">Attention</p>
              <p class="mt-1">
                Une fois soumis, le document ne pourra plus être modifié tant que 
                le workflow n'est pas terminé ou annulé.
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-gray-600 hover:text-gray-900"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="!form.workflow_id || loading"
            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 disabled:opacity-50 flex items-center"
          >
            <span v-if="loading" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
            {{ loading ? 'Soumission...' : 'Soumettre' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useWorkflowStore } from '@/stores/workflows';
import {
  XMarkIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  document: { type: Object, required: true }
});

const emit = defineEmits(['close', 'initiated']);

const workflowStore = useWorkflowStore();
const loading = ref(false);

const form = reactive({
  workflow_id: '',
  comment: '',
});

const availableWorkflows = computed(() => workflowStore.workflows);

const selectedWorkflow = computed(() => {
  if (!form.workflow_id) return null;
  return availableWorkflows.value.find(w => w.id === parseInt(form.workflow_id));
});

onMounted(async () => {
  await workflowStore.fetchWorkflows();
  
  // Auto-select if only one workflow
  if (availableWorkflows.value.length === 1) {
    form.workflow_id = availableWorkflows.value[0].id;
  }
});

async function handleSubmit() {
  if (!form.workflow_id) return;
  
  loading.value = true;
  
  const result = await workflowStore.initiateWorkflow(
    props.document.id,
    form.workflow_id,
    { comment: form.comment }
  );
  
  loading.value = false;
  
  if (result.success) {
    emit('initiated', result.instance);
  }
}
</script>
