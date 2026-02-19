<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div>
          <h3 class="text-xl font-bold text-gray-900">Détails de l'entrée d'audit</h3>
          <p class="text-xs text-gray-500 font-mono mt-0.5">{{ log?.uuid }}</p>
        </div>
        <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto p-6 space-y-8">
        <!-- Summary Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="space-y-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Événement</span>
            <div class="flex items-center space-x-2">
              <span :class="['px-2 py-1 text-xs rounded-full font-medium', getActionClasses(log?.action)]">
                {{ getActionLabel(log?.action) }}
              </span>
              <span v-if="log?.is_gmp_critical" class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 font-bold">
                CRITIQUE GMP
              </span>
            </div>
          </div>

          <div class="space-y-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Utilisateur</span>
            <p class="text-sm text-gray-900 font-medium">{{ log?.user_name || 'Système' }}</p>
            <p class="text-xs text-gray-500">{{ log?.user_email || '-' }}</p>
          </div>

          <div class="space-y-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Date & Heure</span>
            <p class="text-sm text-gray-900">{{ formatDateTime(log?.occurred_at || log?.created_at) }}</p>
          </div>

          <div class="space-y-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Objet</span>
            <p class="text-sm text-gray-900">{{ log?.auditable_name || log?.auditable_type || '-' }}</p>
            <p class="text-xs text-gray-500" v-if="log?.document_number">Doc: {{ log.document_number }}</p>
          </div>

          <div class="space-y-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Adresse IP</span>
            <p class="text-sm text-gray-900 font-mono">{{ log?.ip_address || '-' }}</p>
          </div>

          <div class="space-y-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Requête ID</span>
            <p class="text-sm text-gray-900 font-mono text-xs">{{ log?.request_id || '-' }}</p>
          </div>
        </div>

        <!-- Description & Comment -->
        <div class="space-y-4">
          <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl">
            <span class="block text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Description de l'action</span>
            <p class="text-sm text-blue-900 leading-relaxed">{{ log?.action_description }}</p>
          </div>

          <div v-if="log?.comment" class="p-4 bg-amber-50 border border-amber-100 rounded-xl">
            <span class="block text-xs font-bold text-amber-600 uppercase tracking-wider mb-1">Commentaire / Justification</span>
            <p class="text-sm text-amber-900 leading-relaxed">{{ log.comment }}</p>
          </div>
        </div>

        <!-- Diff Viewer -->
        <div v-if="hasChanges" class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-bold text-gray-900 flex items-center">
              <ArrowsRightLeftIcon class="w-4 h-4 mr-2 text-gray-400" />
              Modification des données
            </h4>
            <span class="text-xs text-gray-500">{{ log.changed_fields?.length || 0 }} champ(s) modifié(s)</span>
          </div>

          <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200">
                <tr>
                  <th class="px-4 py-2 text-left w-1/4">Champ</th>
                  <th class="px-4 py-2 text-left">Ancienne Valeur</th>
                  <th class="px-4 py-2 text-left">Nouvelle Valeur</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="field in log.changed_fields" :key="field" class="hover:bg-gray-50/50">
                  <td class="px-4 py-3 font-medium text-gray-700 bg-gray-50/30">{{ field }}</td>
                  <td class="px-4 py-3 text-red-600 bg-red-50/20 break-all">
                    {{ formatValue(log.old_values?.[field]) }}
                  </td>
                  <td class="px-4 py-3 text-emerald-600 bg-emerald-50/20 break-all">
                    {{ formatValue(log.new_values?.[field]) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Technical Metadata -->
        <div class="pt-6 border-t border-gray-100">
          <details class="group">
            <summary class="flex items-center justify-between cursor-pointer list-none text-xs font-bold text-gray-400 uppercase tracking-wider hover:text-gray-600">
              Informations Techniques
              <ChevronDownIcon class="w-4 h-4 transition-transform group-open:rotate-180" />
            </summary>
            <div class="mt-4 grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-xl text-xs font-mono text-gray-600">
              <div v-if="log?.user_agent"><span class="font-bold text-gray-800">User Agent:</span> {{ log.user_agent }}</div>
              <div v-if="log?.request_method"><span class="font-bold text-gray-800">Method:</span> {{ log.request_method }} {{ log.request_url }}</div>
              <div v-if="log?.entry_hash"><span class="font-bold text-gray-800">Entry Hash:</span> {{ log.entry_hash }}</div>
              <div v-if="log?.previous_hash"><span class="font-bold text-gray-800">Prev Hash:</span> {{ log.previous_hash }}</div>
            </div>
          </details>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
        <button 
          @click="close"
          class="px-6 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-sm"
        >
          Fermer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { 
  XMarkIcon, 
  ArrowsRightLeftIcon, 
  ChevronDownIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
  log: Object
});

const emit = defineEmits(['close']);

const hasChanges = computed(() => {
  return props.log?.changed_fields && props.log.changed_fields.length > 0;
});

function close() {
  emit('close');
}

function formatDateTime(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
}

function formatValue(val) {
  if (val === null || val === undefined) return '—';
  if (typeof val === 'boolean') return val ? 'Oui' : 'Non';
  if (typeof val === 'object') return JSON.stringify(val, null, 2);
  return val;
}

const actionConfig = {
  document_created: { label: 'Document créé', classes: 'bg-emerald-100 text-emerald-700' },
  document_updated: { label: 'Document modifié', classes: 'bg-blue-100 text-blue-700' },
  document_viewed: { label: 'Document consulté', classes: 'bg-gray-100 text-gray-700' },
  document_downloaded: { label: 'Document téléchargé', classes: 'bg-purple-100 text-purple-700' },
  version_uploaded: { label: 'Version ajoutée', classes: 'bg-indigo-100 text-indigo-700' },
  workflow_initiated: { label: 'Workflow initié', classes: 'bg-amber-100 text-amber-700' },
  workflow_approved: { label: 'Workflow approuvé', classes: 'bg-emerald-100 text-emerald-700' },
  workflow_rejected: { label: 'Workflow rejeté', classes: 'bg-red-100 text-red-700' },
  signature_applied: { label: 'Signature appliquée', classes: 'bg-green-100 text-green-700' },
  user_login: { label: 'Connexion', classes: 'bg-blue-100 text-blue-700' },
  user_logout: { label: 'Déconnexion', classes: 'bg-gray-100 text-gray-700' },
  user_created: { label: 'Utilisateur créé', classes: 'bg-emerald-100 text-emerald-700' },
  user_updated: { label: 'Utilisateur modifié', classes: 'bg-blue-100 text-blue-700' },
  user_disabled: { label: 'Accès désactivé', classes: 'bg-red-100 text-red-700' },
  user_enabled: { label: 'Accès activé', classes: 'bg-emerald-100 text-emerald-700' },
  config_updated: { label: 'Config modifiée', classes: 'bg-amber-100 text-amber-700' },
};

function getActionLabel(action) {
  return actionConfig[action]?.label || action;
}

function getActionClasses(action) {
  return actionConfig[action]?.classes || 'bg-gray-100 text-gray-700';
}
</script>
