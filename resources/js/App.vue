<template>
  <div id="ged-app" class="min-h-screen bg-gray-50">
    <!-- Loading state during auth check -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-4 text-gray-600">Chargement...</p>
      </div>
    </div>
    
    <!-- Main app -->
    <router-view v-else />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const loading = ref(true);

onMounted(async () => {
  // Initialiser depuis le localStorage pour un chargement rapide
  authStore.initializeFromStorage();
  
  // Vérifier l'authentification avec le serveur
  if (authStore.token) {
    await authStore.checkAuth();
  }
  
  loading.value = false;
});
</script>
