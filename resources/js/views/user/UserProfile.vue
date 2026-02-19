<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">Mon profil</h1>

    <!-- Profile info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="font-semibold text-gray-900">Informations personnelles</h2>
      </div>
      
      <div class="p-6">
        <div class="flex items-center space-x-6">
          <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-bold">
            {{ authStore.userInitials }}
          </div>
          <div>
            <h3 class="text-xl font-semibold text-gray-900">{{ authStore.user?.name }}</h3>
            <p class="text-gray-500">{{ authStore.user?.email }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <span 
                v-for="role in authStore.roles" 
                :key="role"
                class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full"
              >
                {{ getRoleLabel(role) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="font-semibold text-gray-900">Permissions</h2>
      </div>
      
      <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div 
            v-for="perm in permissionGroups"
            :key="perm.module"
          >
            <h4 class="text-sm font-medium text-gray-700 mb-2">{{ perm.label }}</h4>
            <ul class="space-y-1">
              <li 
                v-for="p in perm.permissions"
                :key="p"
                class="flex items-center text-sm"
              >
                <CheckCircleIcon 
                  v-if="authStore.hasPermission(p)"
                  class="w-4 h-4 text-emerald-500 mr-2"
                />
                <XCircleIcon 
                  v-else
                  class="w-4 h-4 text-gray-300 mr-2"
                />
                <span :class="authStore.hasPermission(p) ? 'text-gray-700' : 'text-gray-400'">
                  {{ getPermissionLabel(p) }}
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Electronic signature -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">Signature électronique</h2>
        <span 
          :class="[
            'px-2 py-1 text-xs rounded-full',
            authStore.canSign ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'
          ]"
        >
          {{ authStore.canSign ? 'Activée' : 'Non activée' }}
        </span>
      </div>
      
      <div class="p-6">
        <div v-if="authStore.canSign">
          <p class="text-sm text-gray-600 mb-4">
            Votre signature électronique est active et conforme aux exigences 21 CFR Part 11.
          </p>
          <button
            @click="showChangePinModal = true"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
          >
            Changer mon code PIN
          </button>
        </div>
        <div v-else class="text-sm text-gray-500">
          <p>
            La signature électronique n'est pas activée pour votre compte.
            Contactez votre administrateur pour l'activation.
          </p>
        </div>
      </div>
    </div>

    <!-- Security -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="font-semibold text-gray-900">Sécurité</h2>
      </div>
      
      <div class="p-6 space-y-4">
        <button
          @click="showChangePasswordModal = true"
          class="w-full text-left px-4 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-between"
        >
          <div class="flex items-center">
            <KeyIcon class="w-5 h-5 text-gray-400 mr-3" />
            <div>
              <p class="font-medium text-gray-900">Changer le mot de passe</p>
              <p class="text-sm text-gray-500">Dernière modification: il y a 30 jours</p>
            </div>
          </div>
          <ChevronRightIcon class="w-5 h-5 text-gray-400" />
        </button>

        <div class="px-4 py-3 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <ClockIcon class="w-5 h-5 text-gray-400 mr-3" />
              <div>
                <p class="font-medium text-gray-900">Dernière connexion</p>
                <p class="text-sm text-gray-500">{{ formatDate(authStore.user?.last_login_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Logout -->
    <button
      @click="handleLogout"
      class="w-full py-3 border border-red-300 text-red-600 font-medium rounded-lg hover:bg-red-50 transition-colors"
    >
      Se déconnecter
    </button>

    <!-- Change Password Modal -->
    <div 
      v-if="showChangePasswordModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showChangePasswordModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900">Changer le mot de passe</h3>
        <form @submit.prevent="changePassword" class="mt-4 space-y-4">
          <div v-if="passwordError" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg">
            {{ passwordError }}
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
            <input
              v-model="passwordForm.current"
              type="password"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
            <input
              v-model="passwordForm.new"
              type="password"
              required
              minlength="8"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
            <input
              v-model="passwordForm.confirm"
              type="password"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showChangePasswordModal = false"
              class="px-4 py-2 text-gray-600 hover:text-gray-900"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="authStore.loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ authStore.loading ? 'Enregistrement...' : 'Mettre à jour' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change PIN Modal -->
    <div 
      v-if="showChangePinModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="showChangePinModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ authStore.canSign ? 'Changer le code PIN' : 'Activer le code PIN' }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          Le code PIN est requis pour les signatures électroniques (21 CFR Part 11).
        </p>
        <form @submit.prevent="changePin" class="mt-4 space-y-4">
          <div v-if="pinError" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg">
            {{ pinError }}
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
            <input
              v-model="pinForm.password"
              type="password"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau code PIN (6 chiffres)</label>
            <input
              v-model="pinForm.newPin"
              type="password"
              maxlength="6"
              pattern="\d{6}"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="••••••"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau PIN</label>
            <input
              v-model="pinForm.confirmPin"
              type="password"
              maxlength="6"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="••••••"
            />
          </div>
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="showChangePinModal = false"
              class="px-4 py-2 text-gray-600 hover:text-gray-900"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="authStore.loading"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ authStore.loading ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import {
  CheckCircleIcon,
  XCircleIcon,
  KeyIcon,
  ClockIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const authStore = useAuthStore();

const showChangePinModal = ref(false);
const showChangePasswordModal = ref(false);

const pinForm = reactive({
  password: '',
  newPin: '',
  confirmPin: '',
});

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: '',
});

const pinError = ref('');
const passwordError = ref('');

const roleLabels = {
  admin: 'Administrateur',
  qa_manager: 'QA Manager',
  qa_analyst: 'QA Analyst',
  qc_analyst: 'QC Analyst',
  regulatory_affairs: 'Affaires Réglementaires',
  document_control: 'Contrôle Documentaire',
  standard_user: 'Utilisateur Standard',
};

const permissionGroups = computed(() => [
  {
    module: 'documents',
    label: 'Documents',
    permissions: ['document.create', 'document.read', 'document.update', 'document.delete', 'document.download'],
  },
  {
    module: 'workflows',
    label: 'Workflows',
    permissions: ['workflow.initiate', 'workflow.approve', 'workflow.reject', 'workflow.manage'],
  },
  {
    module: 'audit',
    label: 'Audit',
    permissions: ['audit.view', 'audit.export', 'audit.verify'],
  },
]);

const permissionLabels = {
  'document.create': 'Créer',
  'document.read': 'Consulter',
  'document.update': 'Modifier',
  'document.delete': 'Supprimer',
  'document.download': 'Télécharger',
  'workflow.initiate': 'Initier',
  'workflow.approve': 'Approuver',
  'workflow.reject': 'Rejeter',
  'workflow.manage': 'Gérer',
  'audit.view': 'Consulter',
  'audit.export': 'Exporter',
  'audit.verify': 'Vérifier',
};

function getRoleLabel(role) {
  return roleLabels[role] || role;
}

function getPermissionLabel(permission) {
  return permissionLabels[permission] || permission;
}

function formatDate(dateString) {
  if (!dateString) return 'Jamais';
  return new Date(dateString).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

async function changePin() {
  pinError.value = '';
  
  if (pinForm.newPin !== pinForm.confirmPin) {
    pinError.value = 'Les codes PIN ne correspondent pas.';
    return;
  }
  
  if (!/^\d{6}$/.test(pinForm.newPin)) {
    pinError.value = 'Le code PIN doit contenir exactement 6 chiffres.';
    return;
  }
  
  const result = await authStore.setSignaturePin(pinForm.password, pinForm.newPin);
  
  if (result.success) {
    showChangePinModal.value = false;
    pinForm.password = '';
    pinForm.newPin = '';
    pinForm.confirmPin = '';
    alert('Code PIN mis à jour avec succès.');
  } else {
    pinError.value = result.error || 'Une erreur est survenue.';
  }
}

async function changePassword() {
  passwordError.value = '';
  
  if (passwordForm.new !== passwordForm.confirm) {
    passwordError.value = 'Les nouveaux mots de passe ne correspondent pas.';
    return;
  }
  
  const result = await authStore.changePassword(
    passwordForm.current,
    passwordForm.new,
    passwordForm.confirm
  );
  
  if (result.success) {
    showChangePasswordModal.value = false;
    passwordForm.current = '';
    passwordForm.new = '';
    passwordForm.confirm = '';
    alert('Mot de passe mis à jour avec succès.');
  } else {
    passwordError.value = result.error || 'Une erreur est survenue.';
  }
}

async function handleLogout() {
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>
