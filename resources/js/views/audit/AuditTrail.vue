<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Audit Trail</h1>
        <p class="mt-1 text-sm text-gray-500">
          Journal complet des actions - Conforme 21 CFR Part 11
        </p>
      </div>
      
      <div class="flex items-center space-x-3">
        <button
          @click="showStatsModal = true"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          <ChartBarIcon class="w-5 h-5 mr-2" />
          Statistiques
        </button>
        <button
          @click="verifyIntegrity"
          :disabled="verifying"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
        >
          <ShieldCheckIcon class="w-5 h-5 mr-2" />
          {{ verifying ? 'Vérification...' : 'Vérifier l\'intégrité' }}
        </button>
        <button
          @click="showReportModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
          Générer Rapport
        </button>
      </div>
    </div>

    <!-- Integrity status -->
    <div 
      v-if="integrityStatus"
      :class="[
        'p-4 rounded-xl flex items-center space-x-3',
        integrityStatus.valid ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200'
      ]"
    >
      <CheckCircleIcon v-if="integrityStatus.valid" class="w-6 h-6 text-emerald-600" />
      <ExclamationTriangleIcon v-else class="w-6 h-6 text-red-600" />
      <div>
        <p :class="integrityStatus.valid ? 'text-emerald-800' : 'text-red-800'" class="font-medium">
          {{ integrityStatus.valid ? 'Intégrité vérifiée' : 'Problème d\'intégrité détecté' }}
        </p>
        <p :class="integrityStatus.valid ? 'text-emerald-600' : 'text-red-600'" class="text-sm">
          {{ integrityStatus.message }}
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
      <div class="flex flex-col lg:flex-row lg:items-center gap-4">
        <div class="flex-1 relative">
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" />
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher dans l'audit trail..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            @keyup.enter="loadAuditLogs"
          />
        </div>

        <select
          v-model="filters.action"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
          @change="loadAuditLogs"
        >
          <option value="">Toutes les actions</option>
          <option value="document_created">Document créé</option>
          <option value="document_updated">Document modifié</option>
          <option value="version_uploaded">Version ajoutée</option>
          <option value="workflow_approved">Workflow approuvé</option>
          <option value="workflow_rejected">Workflow rejeté</option>
          <option value="signature_applied">Signature appliquée</option>
          <option value="user_login">Connexion</option>
        </select>

        <input
          v-model="filters.from_date"
          type="date"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
          @change="loadAuditLogs"
        />
        <span class="text-gray-400">—</span>
        <input
          v-model="filters.to_date"
          type="date"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
          @change="loadAuditLogs"
        />
      </div>
    </div>

    <!-- Audit logs table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
        <p class="mt-4 text-gray-500">Chargement de l'audit trail...</p>
      </div>

      <table v-else class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Heure</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Objet</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Détails</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">IP / Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr 
            v-for="log in auditLogs" 
            :key="log.id"
            class="hover:bg-gray-50 group cursor-pointer"
            @click="openDetails(log)"
          >
            <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
              {{ formatDateTime(log.created_at) }}
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                  {{ getInitials(log.user_name || log.user?.name) }}
                </div>
                <div class="ml-3">
                  <span class="block text-sm text-gray-900">{{ log.user_name || log.user?.name || 'Système' }}</span>
                  <span v-if="log.user_email" class="text-[10px] text-gray-400">{{ log.user_email }}</span>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span :class="['px-2 py-1 text-xs rounded-full', getActionClasses(log.action)]">
                {{ getActionLabel(log.action) }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <div class="flex flex-col">
                <span class="font-medium text-gray-700">{{ log.auditable_name || log.entity_name || log.entity_type || '—' }}</span>
                <span v-if="log.document_number" class="text-[10px] text-gray-400">Doc: {{ log.document_number }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
              {{ log.description || log.action_description || '—' }}
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end space-x-2">
                <span class="text-xs text-gray-400 font-mono hidden sm:inline">{{ log.ip_address || '—' }}</span>
                <button 
                  class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg group-hover:opacity-100 transition-all opacity-0"
                  title="Voir détails"
                >
                  <EyeIcon class="w-4 h-4" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div 
        v-if="auditStore.pagination.lastPage > 1"
        class="px-6 py-4 border-t border-gray-200 flex items-center justify-between"
      >
        <div class="text-sm text-gray-500">
          {{ auditStore.pagination.total }} entrées au total
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="changePage(auditStore.pagination.currentPage - 1)"
            :disabled="auditStore.pagination.currentPage === 1"
            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
          >
            Précédent
          </button>
          <span class="text-sm text-gray-600">
            Page {{ auditStore.pagination.currentPage }} / {{ auditStore.pagination.lastPage }}
          </span>
          <button
            @click="changePage(auditStore.pagination.currentPage + 1)"
            :disabled="auditStore.pagination.currentPage === auditStore.pagination.lastPage"
            class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Modal -->
    <div v-if="showStatsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl p-6 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900">Statistiques d'Audit</h3>
          <button @click="showStatsModal = false" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
        
        <div class="overflow-y-auto pr-2 space-y-6">
          <div v-if="statsLoading" class="p-12 text-center text-gray-500">Chargement...</div>
          <div v-else-if="auditStore.statistics" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
             <div v-for="(count, type) in auditStore.statistics.actions_count || auditStore.statistics.by_action" :key="type" class="p-4 bg-gray-50 rounded-xl">
               <span class="block text-xs text-gray-500 uppercase font-bold">{{ getActionLabel(type) }}</span>
               <span class="text-2xl font-bold text-gray-900">{{ count }}</span>
             </div>
          </div>
          <div v-else class="text-center text-gray-500">Aucune statistique disponible.</div>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <AuditDetailModal 
      :is-open="showDetailModal"
      :log="selectedLog"
      @close="showDetailModal = false"
    />

    <!-- Report Modal -->
    <div v-if="showReportModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900">Générer un Rapport d'Audit</h3>
        <p class="mt-1 text-sm text-gray-500">Rapport PDF certifié pour inspection.</p>
        
        <form @submit.prevent="generateReport" class="mt-4 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="reportForm.from_date" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm">
              <input v-model="reportForm.to_date" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm">
            </div>
          </div>
          <div>
             <label class="block text-sm font-medium text-gray-700 mb-1">Type d'entité</label>
             <select v-model="reportForm.entity_type" class="w-full px-3 py-2 border rounded-lg text-sm">
               <option value="">Tous</option>
               <option value="document">Documents</option>
               <option value="workflow">Workflows</option>
               <option value="user">Utilisateurs</option>
             </select>
          </div>
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="showReportModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-900">Annuler</button>
            <button type="submit" :disabled="reporting" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ reporting ? 'Génération...' : 'Télécharger PDF' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- GMP Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
      <div class="flex items-start space-x-3">
        <InformationCircleIcon class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
        <div class="text-sm text-blue-800">
          <p class="font-medium">Audit Trail conforme 21 CFR Part 11</p>
          <p class="mt-1">
            Ce journal est immuable et horodaté. Chaque entrée est liée cryptographiquement 
            à la précédente pour garantir l'intégrité. Toutes les données sont conservées 
            pendant la durée réglementaire requise.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuditStore } from '@/stores/audit';
import AuditDetailModal from './AuditDetailModal.vue';
import {
  MagnifyingGlassIcon,
  ShieldCheckIcon,
  ArrowDownTrayIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  ChartBarIcon,
  XMarkIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline';

const route = useRoute();
const auditStore = useAuditStore();

const verifying = ref(false);
const showStatsModal = ref(false);
const statsLoading = ref(false);
const showReportModal = ref(false);
const reporting = ref(false);
const showDetailModal = ref(false);
const selectedLog = ref(null);

const integrityStatus = ref(null);

const reportForm = reactive({
  from_date: '',
  to_date: '',
  entity_type: '',
});

const auditLogs = computed(() => auditStore.logs);
const loading = computed(() => auditStore.loading);

const filters = reactive({
  search: '',
  action: '',
  from_date: '',
  to_date: '',
  document_id: route.query.document_id || null,
});

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
  workflow_assigned_to_types: { label: 'Assignation workflow', classes: 'bg-purple-100 text-purple-700' },
};

onMounted(() => {
  loadAuditLogs();
});

watch(showStatsModal, (val) => {
  if (val) loadStats();
});

// Watch filters for live search with debounce or simple reactive load
watch(() => filters.search, (val) => {
    if (val === '') loadAuditLogs();
});

async function loadStats() {
  statsLoading.value = true;
  await auditStore.fetchStatistics();
  statsLoading.value = false;
}

async function loadAuditLogs(page = 1) {
  await auditStore.fetchLogs({ ...filters }, page);
}

function openDetails(log) {
    selectedLog.value = log;
    showDetailModal.value = true;
}

async function verifyIntegrity() {
  verifying.value = true;
  integrityStatus.value = await auditStore.verifyIntegrity();
  verifying.value = false;
}

async function exportAudit() {
  try {
    const data = await auditStore.exportAudit({ ...filters });
    const blob = new Blob([data], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = `audit_trail_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
  } catch (error) {
    console.error('Erreur export:', error);
  }
}

async function generateReport() {
  reporting.value = true;
  try {
    const data = await auditStore.generateReport({ ...reportForm });
    const blob = new Blob([data], { type: 'application/pdf' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = `rapport_audit_${reportForm.from_date}_au_${reportForm.to_date}.pdf`;
    link.click();
    showReportModal.value = false;
  } catch (error) {
    alert('Erreur lors de la génération du rapport.');
  } finally {
    reporting.value = false;
  }
}

function changePage(page) {
  loadAuditLogs(page);
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

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function getActionLabel(action) {
  return actionConfig[action]?.label || action;
}

function getActionClasses(action) {
  return actionConfig[action]?.classes || 'bg-gray-100 text-gray-700';
}
</script>
