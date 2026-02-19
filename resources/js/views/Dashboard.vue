<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Tableau de Bord</h1>
        <p class="mt-1 text-sm text-gray-500">
          Bienvenue, {{ authStore.userName }}. Voici votre aperçu quotidien.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 text-sm text-gray-500">
        {{ currentDate }}
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <DashboardCard
        title="Documents en vigueur"
        :value="stats.effective_documents"
        icon="DocumentCheckIcon"
        color="emerald"
        :change="stats.effective_change"
        changeLabel="depuis le mois dernier"
      />
      <DashboardCard
        title="En attente d'approbation"
        :value="stats.pending_approval"
        icon="ClockIcon"
        color="amber"
        :urgent="stats.pending_approval > 10"
      />
      <DashboardCard
        title="Mes tâches"
        :value="stats.my_pending_tasks"
        icon="ClipboardDocumentListIcon"
        color="blue"
        actionLabel="Voir les tâches"
        @action="$router.push('/workflows')"
      />
      <DashboardCard
        title="Documents à réviser"
        :value="stats.review_due"
        icon="ExclamationTriangleIcon"
        color="red"
        :urgent="stats.review_due > 0"
        actionLabel="Voir la liste"
        @action="showReviewDue = true"
      />
    </div>

    <!-- Main content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Workflows en attente -->
      <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900">Workflows en attente</h2>
          <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-medium rounded-full">
            {{ pendingWorkflows.length }} en cours
          </span>
        </div>
        
        <div class="divide-y divide-gray-100">
          <WorkflowItem
            v-for="workflow in pendingWorkflows.slice(0, 5)"
            :key="workflow.id"
            :workflow="workflow"
            @approve="handleApprove"
            @view="viewWorkflow"
          />
          
          <div v-if="pendingWorkflows.length === 0" class="p-8 text-center">
            <CheckCircleIcon class="w-12 h-12 text-emerald-400 mx-auto" />
            <p class="mt-3 text-gray-500">Aucun workflow en attente</p>
          </div>
        </div>

        <div v-if="pendingWorkflows.length > 5" class="px-6 py-3 bg-gray-50 border-t border-gray-200">
          <router-link 
            to="/workflows" 
            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
          >
            Voir tous les workflows →
          </router-link>
        </div>
      </div>

      <!-- Activité récente -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Activité récente</h2>
        </div>
        
        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
          <ActivityItem
            v-for="activity in recentActivity"
            :key="activity.id"
            :activity="activity"
          />
        </div>

        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
          <router-link 
            to="/audit" 
            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
          >
            Voir l'audit trail complet →
          </router-link>
        </div>
      </div>
    </div>

    <!-- Documents récents -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Documents récemment modifiés</h2>
        <router-link 
          to="/documents" 
          class="text-sm text-blue-600 hover:text-blue-700 font-medium"
        >
          Tous les documents →
        </router-link>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Document
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Version
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Statut
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Modifié par
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr 
              v-for="doc in recentDocuments" 
              :key="doc.id"
              class="hover:bg-gray-50 cursor-pointer transition-colors"
              @click="$router.push(`/documents/${doc.id}`)"
            >
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ doc.title }}</div>
                    <div class="text-sm text-gray-500">{{ doc.document_number }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                v{{ doc.current_version }}
              </td>
              <td class="px-6 py-4">
                <StatusBadge :status="doc.status" />
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ doc.updated_by?.name || '—' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">
                {{ formatDate(doc.updated_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Alertes Compliance -->
    <div 
      v-if="complianceAlerts.length > 0"
      class="bg-amber-50 border border-amber-200 rounded-xl p-6"
    >
      <div class="flex items-start space-x-4">
        <ExclamationTriangleIcon class="w-6 h-6 text-amber-500 flex-shrink-0" />
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-amber-800">Alertes de conformité</h3>
          <ul class="mt-3 space-y-2">
            <li 
              v-for="alert in complianceAlerts" 
              :key="alert.id"
              class="flex items-center justify-between text-sm"
            >
              <span class="text-amber-700">{{ alert.message }}</span>
              <button 
                @click="resolveAlert(alert)"
                class="text-amber-600 hover:text-amber-800 font-medium"
              >
                Résoudre
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useWorkflowStore } from '@/stores/workflows';
import api from '@/bootstrap';
import {
  DocumentTextIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

import DashboardCard from '@/components/dashboard/DashboardCard.vue';
import WorkflowItem from '@/components/dashboard/WorkflowItem.vue';
import ActivityItem from '@/components/dashboard/ActivityItem.vue';
import StatusBadge from '@/components/common/StatusBadge.vue';

const authStore = useAuthStore();
const workflowStore = useWorkflowStore();

const loading = ref(true);
const stats = ref({
  effective_documents: 0,
  effective_change: 0,
  pending_approval: 0,
  my_pending_tasks: 0,
  review_due: 0,
});
const recentDocuments = ref([]);
const recentActivity = ref([]);
const complianceAlerts = ref([]);

const currentDate = computed(() => {
  return new Date().toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

const pendingWorkflows = computed(() => workflowStore.pendingActions);

onMounted(async () => {
  // Only fetch data if authenticated
  if (!authStore.isAuthenticated) {
    loading.value = false;
    return;
  }
  
  await Promise.all([
    fetchDashboardData(),
    workflowStore.fetchPendingActions(),
  ]);
  loading.value = false;
});

async function fetchDashboardData() {
  try {
    const response = await api.get('/dashboard');
    const data = response.data.data;
    
    stats.value = data.stats || stats.value;
    recentDocuments.value = data.recent_documents || [];
    recentActivity.value = data.recent_activity || [];
    complianceAlerts.value = data.compliance_alerts || [];
  } catch (error) {
    console.error('Erreur chargement dashboard:', error);
  }
}

function formatDate(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

async function handleApprove(workflow) {
  // TODO: Implémenter l'approbation rapide
  console.log('Approve workflow:', workflow);
}

function viewWorkflow(workflow) {
  // Navigation vers le détail du workflow
}

function resolveAlert(alert) {
  // TODO: Implémenter la résolution d'alerte
  console.log('Resolve alert:', alert);
}
</script>
