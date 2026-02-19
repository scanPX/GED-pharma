<template>
  <div class="p-8">
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Connexion</h2>

    <!-- Alerte erreur -->
    <div 
      v-if="error" 
      class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3"
    >
      <ExclamationCircleIcon class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
      <div class="flex-1">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>
      <button @click="error = null" class="text-red-400 hover:text-red-600">
        <XMarkIcon class="w-5 h-5" />
      </button>
    </div>

    <form @submit.prevent="handleLogin" class="space-y-6">
      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
          Adresse email
        </label>
        <div class="relative">
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            :disabled="loading"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors disabled:bg-gray-100"
            placeholder="votre.email@entreprise.com"
          />
          <EnvelopeIcon class="absolute right-3 top-3.5 w-5 h-5 text-gray-400" />
        </div>
      </div>

      <!-- Mot de passe -->
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
          Mot de passe
        </label>
        <div class="relative">
          <input
            id="password"
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            required
            autocomplete="current-password"
            :disabled="loading"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors disabled:bg-gray-100"
            placeholder="••••••••"
          />
          <button 
            type="button" 
            @click="showPassword = !showPassword"
            class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600"
          >
            <EyeSlashIcon v-if="showPassword" class="w-5 h-5" />
            <EyeIcon v-else class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Remember me -->
      <div class="flex items-center justify-between">
        <label class="flex items-center">
          <input
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
        </label>
      </div>

      <!-- Submit -->
      <button
        type="submit"
        :disabled="loading"
        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
      >
        <svg 
          v-if="loading" 
          class="animate-spin h-5 w-5 text-white" 
          xmlns="http://www.w3.org/2000/svg" 
          fill="none" 
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <span>{{ loading ? 'Connexion...' : 'Se connecter' }}</span>
      </button>
    </form>

    <!-- Info GMP -->
    <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-lg">
      <div class="flex items-start space-x-3">
        <ShieldCheckIcon class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
        <div class="text-xs text-amber-800">
          <p class="font-medium">Connexion sécurisée - 21 CFR Part 11</p>
          <p class="mt-1">
            Toutes les connexions sont enregistrées dans l'audit trail. 
            L'accès non autorisé est interdit et peut faire l'objet de poursuites.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import {
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  ExclamationCircleIcon,
  XMarkIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = reactive({
  email: '',
  password: '',
  remember: false,
});

const loading = ref(false);
const error = ref(null);
const showPassword = ref(false);

async function handleLogin() {
  loading.value = true;
  error.value = null;

  const result = await authStore.login({
    email: form.email,
    password: form.password,
    remember: form.remember,
  });

  loading.value = false;

  if (result.success) {
    const redirect = route.query.redirect || '/';
    router.push(redirect);
  } else {
    error.value = result.error;
  }
}
</script>
