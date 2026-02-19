<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center space-x-4">
      <router-link 
        to="/documents" 
        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"
      >
        <ArrowLeftIcon class="w-5 h-5" />
      </router-link>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Nouveau document</h1>
        <p class="text-sm text-gray-500">Créer un nouveau document contrôlé</p>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Document info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="font-semibold text-gray-900">Informations du document</h2>
        </div>
        
        <div class="p-6 space-y-6">
          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
              Titre <span class="text-red-500">*</span>
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.title }"
              placeholder="Titre du document"
            />
            <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
          </div>

          <!-- Category & Type -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                Catégorie <span class="text-red-500">*</span>
              </label>
              <select
                id="category"
                v-model="form.category_id"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                @change="onCategoryChange"
              >
                <option value="">Sélectionner une catégorie</option>
                <option 
                  v-for="cat in documentStore.categories" 
                  :key="cat.id" 
                  :value="cat.id"
                >
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div>
              <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                Type de document <span class="text-red-500">*</span>
              </label>
              <select
                id="type"
                v-model="form.document_type_id"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Sélectionner un type</option>
                <option 
                  v-for="type in filteredTypes" 
                  :key="type.id" 
                  :value="type.id"
                >
                  {{ type.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Description du document..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- File upload -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="font-semibold text-gray-900">Fichier</h2>
        </div>
        
        <div class="p-6">
          <div
            :class="[
              'border-2 border-dashed rounded-xl p-8 text-center transition-colors',
              dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400',
              errors.file ? 'border-red-500' : ''
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
              <ArrowUpTrayIcon class="w-12 h-12 text-gray-400 mx-auto" />
              <p class="mt-4 text-sm text-gray-600">
                Glissez-déposez votre fichier ici, ou
                <button 
                  type="button"
                  @click="$refs.fileInput.click()"
                  class="text-blue-600 hover:text-blue-700 font-medium"
                >
                  parcourez
                </button>
              </p>
              <p class="mt-2 text-xs text-gray-500">
                PDF, DOC, DOCX jusqu'à 50 Mo
              </p>
            </template>
            
            <template v-else>
              <div class="flex items-center justify-center space-x-4">
                <DocumentTextIcon class="w-10 h-10 text-blue-600" />
                <div class="text-left">
                  <p class="font-medium text-gray-900">{{ form.file.name }}</p>
                  <p class="text-sm text-gray-500">{{ formatFileSize(form.file.size) }}</p>
                </div>
                <button 
                  type="button"
                  @click="form.file = null"
                  class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
              </div>
            </template>
          </div>
          <p v-if="errors.file" class="mt-2 text-sm text-red-500">{{ errors.file }}</p>
        </div>
      </div>

      <!-- Version info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="font-semibold text-gray-900">Version initiale</h2>
        </div>
        
        <div class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="version" class="block text-sm font-medium text-gray-700 mb-1">
                Numéro de version
              </label>
              <input
                id="version"
                v-model="form.version_number"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="1.0"
              />
            </div>

            <div>
              <label for="effective_date" class="block text-sm font-medium text-gray-700 mb-1">
                Date d'effet prévue
              </label>
              <input
                id="effective_date"
                v-model="form.effective_date"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label for="change_summary" class="block text-sm font-medium text-gray-700 mb-1">
              Résumé des modifications
            </label>
            <textarea
              id="change_summary"
              v-model="form.change_summary"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Création initiale du document..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end space-x-4">
        <router-link 
          to="/documents"
          class="px-6 py-2 text-gray-700 font-medium hover:text-gray-900"
        >
          Annuler
        </router-link>
        <button
          type="submit"
          :disabled="loading"
          class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center"
        >
          <span v-if="loading" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full mr-2"></span>
          {{ loading ? 'Création...' : 'Créer le document' }}
        </button>
      </div>
    </form>

    <!-- GMP Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
      <div class="flex items-start space-x-3">
        <InformationCircleIcon class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
        <div class="text-sm text-blue-800">
          <p class="font-medium">Document contrôlé GMP</p>
          <p class="mt-1">
            Ce document sera créé avec le statut "Brouillon". Vous devrez initier un workflow 
            d'approbation pour le mettre en vigueur. Toutes les modifications seront tracées 
            dans l'audit trail conformément aux exigences 21 CFR Part 11.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useDocumentStore } from '@/stores/documents';
import {
  ArrowLeftIcon,
  ArrowUpTrayIcon,
  DocumentTextIcon,
  XMarkIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const documentStore = useDocumentStore();

const fileInput = ref(null);
const loading = ref(false);
const dragOver = ref(false);
const errors = reactive({});

const form = reactive({
  title: '',
  description: '',
  category_id: '',
  document_type_id: '',
  file: null,
  version_number: '1.0',
  effective_date: '',
  change_summary: 'Création initiale du document',
});

const filteredTypes = computed(() => {
  if (!form.category_id) return documentStore.types;
  return documentStore.types.filter(t => t.category_id === parseInt(form.category_id));
});

onMounted(async () => {
  await documentStore.fetchReferenceData();
});

function onCategoryChange() {
  // Reset type when category changes
  form.document_type_id = '';
}

function handleFileSelect(event) {
  const file = event.target.files[0];
  if (file) {
    validateAndSetFile(file);
  }
}

function handleDrop(event) {
  dragOver.value = false;
  const file = event.dataTransfer.files[0];
  if (file) {
    validateAndSetFile(file);
  }
}

function validateAndSetFile(file) {
  errors.file = null;
  
  const allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
  ];
  
  if (!allowedTypes.includes(file.type)) {
    errors.file = 'Le fichier doit être au format PDF, DOC ou DOCX';
    return;
  }
  
  if (file.size > 50 * 1024 * 1024) {
    errors.file = 'Le fichier ne doit pas dépasser 50 Mo';
    return;
  }
  
  form.file = file;
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' octets';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
  return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

async function handleSubmit() {
  // Validate
  Object.keys(errors).forEach(key => errors[key] = null);
  
  if (!form.title.trim()) {
    errors.title = 'Le titre est requis';
  }
  if (!form.file) {
    errors.file = 'Un fichier est requis';
  }
  
  if (Object.values(errors).some(e => e)) return;
  
  loading.value = true;
  
  const formData = new FormData();
  formData.append('title', form.title);
  formData.append('description', form.description);
  formData.append('category_id', form.category_id);
  formData.append('type_id', form.document_type_id);
  formData.append('file', form.file);
  formData.append('version_number', form.version_number);
  formData.append('change_summary', form.change_summary);
  if (form.effective_date) {
    formData.append('effective_date', form.effective_date);
  }
  
  const result = await documentStore.createDocument(formData);
  
  loading.value = false;
  
  if (result.success) {
    router.push(`/documents/${result.document.id}`);
  } else {
    if (result.errors) {
      Object.assign(errors, result.errors);
    }
  }
}
</script>
