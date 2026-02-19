<template>
  <div 
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          Nouvelle version
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- File upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Fichier</label>
          <div
            :class="[
              'border-2 border-dashed rounded-lg p-6 text-center',
              dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300',
              error ? 'border-red-500' : ''
            ]"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="handleDrop"
          >
            <input
              ref="fileInput"
              type="file"
              class="hidden"
              accept=".pdf,.doc,.docx"
              @change="handleFileSelect"
            />
            
            <template v-if="!form.file">
              <ArrowUpTrayIcon class="w-10 h-10 text-gray-400 mx-auto" />
              <p class="mt-2 text-sm text-gray-600">
                <button 
                  type="button"
                  @click="$refs.fileInput.click()"
                  class="text-blue-600 hover:text-blue-700 font-medium"
                >
                  Sélectionner un fichier
                </button>
                ou glisser-déposer
              </p>
            </template>
            
            <template v-else>
              <div class="flex items-center justify-center space-x-3">
                <DocumentTextIcon class="w-8 h-8 text-blue-600" />
                <div class="text-left">
                  <p class="font-medium text-gray-900">{{ form.file.name }}</p>
                  <p class="text-sm text-gray-500">{{ formatFileSize(form.file.size) }}</p>
                </div>
                <button 
                  type="button"
                  @click="form.file = null"
                  class="p-1 text-red-500 hover:bg-red-50 rounded"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>
            </template>
          </div>
          <p v-if="error" class="mt-1 text-sm text-red-500">{{ error }}</p>
        </div>

        <!-- Version number -->
        <div>
          <label for="version" class="block text-sm font-medium text-gray-700 mb-1">
            Numéro de version
          </label>
          <input
            id="version"
            v-model="form.version_number"
            type="text"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            :placeholder="suggestedVersion"
          />
        </div>

        <!-- Change summary -->
        <div>
          <label for="summary" class="block text-sm font-medium text-gray-700 mb-1">
            Résumé des modifications <span class="text-red-500">*</span>
          </label>
          <textarea
            id="summary"
            v-model="form.change_summary"
            rows="3"
            required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none"
            placeholder="Décrivez les modifications apportées..."
          ></textarea>
        </div>

        <!-- Change type -->
        <div>
          <label for="change_type" class="block text-sm font-medium text-gray-700 mb-1">
            Type de modification
          </label>
          <select
            id="change_type"
            v-model="form.change_type"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="major">Majeure</option>
            <option value="minor">Mineure</option>
            <option value="editorial">Éditoriale</option>
          </select>
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
            :disabled="!form.file || !form.change_summary || loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center"
          >
            <span v-if="loading" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
            {{ loading ? 'Téléversement...' : 'Téléverser' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useDocumentStore } from '@/stores/documents';
import {
  XMarkIcon,
  ArrowUpTrayIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  document: { type: Object, required: true }
});

const emit = defineEmits(['close', 'uploaded']);

const documentStore = useDocumentStore();
const fileInput = ref(null);
const loading = ref(false);
const dragOver = ref(false);
const error = ref(null);

const form = reactive({
  file: null,
  version_number: '',
  change_summary: '',
  change_type: 'minor',
});

const suggestedVersion = computed(() => {
  const current = props.document?.current_version || '1.0';
  const parts = current.split('.');
  parts[parts.length - 1] = parseInt(parts[parts.length - 1]) + 1;
  return parts.join('.');
});

function handleFileSelect(event) {
  const file = event.target.files[0];
  if (file) validateAndSetFile(file);
}

function handleDrop(event) {
  dragOver.value = false;
  const file = event.dataTransfer.files[0];
  if (file) validateAndSetFile(file);
}

function validateAndSetFile(file) {
  error.value = null;
  
  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ];
  
  if (!allowedTypes.includes(file.type)) {
    error.value = 'Format non supporté (PDF, DOC, DOCX uniquement)';
    return;
  }
  
  if (file.size > 50 * 1024 * 1024) {
    error.value = 'Le fichier ne doit pas dépasser 50 Mo';
    return;
  }
  
  form.file = file;
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' o';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

async function handleSubmit() {
  if (!form.file || !form.change_summary) return;
  
  loading.value = true;
  
  const formData = new FormData();
  formData.append('file', form.file);
  formData.append('version_number', form.version_number || suggestedVersion.value);
  formData.append('change_summary', form.change_summary);
  formData.append('change_type', form.change_type);
  
  const result = await documentStore.uploadVersion(props.document.id, formData);
  
  loading.value = false;
  
  if (result.success) {
    emit('uploaded', result.version);
  } else {
    error.value = result.error;
  }
}
</script>
