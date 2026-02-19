<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center shadow-xl">
          <DocumentTextIcon class="w-10 h-10 text-white" />
        </div>
        <h1 class="mt-6 text-3xl font-bold text-white">GED Pharmaceutique</h1>
        <p class="mt-2 text-sm text-blue-200">
          Système de Gestion Documentaire<br>
          Conforme GMP, 21 CFR Part 11, ISO 13485
        </p>
      </div>

      <!-- Card container -->
      <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <router-view />
      </div>

      <!-- Footer -->
      <div class="mt-8 text-center">
        <p class="text-xs text-blue-200">
          &copy; {{ new Date().getFullYear() }} GED Pharma. Tous droits réservés.
        </p>
        <p class="mt-1 text-xs text-blue-300/70">
          Version 1.0.0 | Environnement validé
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { DocumentTextIcon } from '@heroicons/vue/24/outline';
</script>
