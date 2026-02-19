<template>
  <div 
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
      <div 
        :class="[
          'px-6 py-4 border-b flex items-center justify-between',
          action === 'approve' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'
        ]"
      >
        <div class="flex items-center space-x-3">
          <div 
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center',
              action === 'approve' ? 'bg-emerald-600' : 'bg-red-600'
            ]"
          >
            <CheckIcon v-if="action === 'approve'" class="w-5 h-5 text-white" />
            <XMarkIcon v-else class="w-5 h-5 text-white" />
          </div>
          <h3 class="text-lg font-semibold text-gray-900">
            {{ action === 'approve' ? 'Approuver' : 'Rejeter' }} le workflow
          </h3>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Document info -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="font-medium text-gray-900">{{ workflow.document?.title }}</p>
          <p class="text-sm text-gray-500">
            {{ workflow.document?.document_number }} • 
            Étape: {{ workflow.current_step?.name }}
          </p>
        </div>

        <!-- Comment -->
        <div>
          <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
            Commentaire {{ action === 'reject' ? '(obligatoire)' : '(optionnel)' }}
            <span v-if="action === 'reject'" class="text-red-500">*</span>
          </label>
          <textarea
            id="comment"
            v-model="form.comment"
            rows="3"
            :required="action === 'reject'"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none"
            :placeholder="action === 'reject' ? 'Veuillez indiquer la raison du rejet...' : 'Ajouter un commentaire...'"
          ></textarea>
        </div>

        <!-- Electronic signature -->
        <div v-if="requiresSignature" class="space-y-4">
          <div class="flex items-center space-x-2 text-sm text-amber-700 bg-amber-50 p-3 rounded-lg">
            <ShieldCheckIcon class="w-5 h-5" />
            <span>Cette action requiert une signature électronique (21 CFR Part 11)</span>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              Mot de passe <span class="text-red-500">*</span>
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Confirmez avec votre mot de passe"
            />
          </div>

          <div>
            <label for="pin" class="block text-sm font-medium text-gray-700 mb-1">
              Code PIN <span class="text-red-500">*</span>
            </label>
            <input
              id="pin"
              v-model="form.pin"
              type="password"
              maxlength="6"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="••••••"
            />
          </div>

          <div>
            <label for="meaning" class="block text-sm font-medium text-gray-700 mb-1">
              Signification de la signature <span class="text-red-500">*</span>
            </label>
            <select
              id="meaning"
              v-model="form.signature_meaning"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Sélectionner...</option>
              <option value="approval">Approbation</option>
              <option value="review">Revue effectuée</option>
              <option value="authorship">Auteur du document</option>
              <option value="verification">Vérification effectuée</option>
            </select>
          </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
          {{ error }}
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
            :disabled="loading || (action === 'reject' && !form.comment)"
            :class="[
              'px-4 py-2 text-white rounded-lg disabled:opacity-50 flex items-center',
              action === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'
            ]"
          >
            <span v-if="loading" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
            {{ loading ? 'Traitement...' : (action === 'approve' ? 'Approuver' : 'Rejeter') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import {
  XMarkIcon,
  CheckIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  workflow: { type: Object, required: true },
  action: { type: String, required: true, validator: v => ['approve', 'reject'].includes(v) },
});

const emit = defineEmits(['close', 'submit']);

const authStore = useAuthStore();
const loading = ref(false);
const error = ref(null);

const form = reactive({
  comment: '',
  password: '',
  pin: '',
  signature_meaning: '',
});

const requiresSignature = computed(() => {
  return props.action === 'approve' && 
         props.workflow.current_step?.requires_signature &&
         authStore.canSign;
});

async function handleSubmit() {
  error.value = null;
  
  if (props.action === 'reject' && !form.comment.trim()) {
    error.value = 'Le commentaire est obligatoire pour un rejet';
    return;
  }
  
  if (requiresSignature.value) {
    if (!form.password || !form.pin || !form.signature_meaning) {
      error.value = 'Tous les champs de signature sont obligatoires';
      return;
    }
  }
  
  loading.value = true;
  
  const data = {
    comment: form.comment,
  };
  
  if (requiresSignature.value) {
    data.password = form.password;
    data.pin = form.pin;
    data.signature_meaning = form.signature_meaning;
  }
  
  emit('submit', data);
}
</script>
