<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Workflows</h1>
        <p class="mt-1 text-sm text-gray-500">
          Gérez les processus d'approbation documentaire
        </p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ tab.name }}
          <span 
            v-if="tab.count > 0"
            :class="[
              'ml-2 px-2.5 py-0.5 rounded-full text-xs',
              activeTab === tab.id ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600'
            ]"
          >
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Pending actions (my tasks) -->
    <div v-if="activeTab === 'pending'" class="space-y-4">
      <div v-if="workflowStore.loading" class="flex items-center justify-center h-32">
        <div class="animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
      </div>

      <div v-else-if="pendingActions.length === 0" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <CheckCircleIcon class="w-16 h-16 text-emerald-400 mx-auto" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucune action en attente</h3>
        <p class="mt-2 text-sm text-gray-500">Vous n'avez pas de workflow à traiter pour le moment.</p>
      </div>

      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="divide-y divide-gray-100">
          <div 
            v-for="action in pendingActions" 
            :key="action.id"
            class="p-6"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                  <ClipboardDocumentCheckIcon class="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <h4 class="text-lg font-medium text-gray-900">{{ action.document?.title }}</h4>
                  <p class="text-sm text-gray-500">
                    {{ action.document?.document_number }} • v{{ action.document?.current_version }}
                  </p>
                  <div class="mt-2 flex items-center space-x-4 text-sm">
                    <span class="text-gray-500">
                      Workflow: <span class="font-medium text-gray-700">{{ action.workflow?.name }}</span>
                    </span>
                    <span class="text-gray-500">
                      Étape: <span class="font-medium text-gray-700">{{ action.current_step?.name }}</span>
                    </span>
                  </div>
                  <p class="mt-2 text-xs text-gray-400">
                    Initié par {{ action.initiated_by?.name }} • {{ formatDate(action.created_at) }}
                  </p>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <button
                  @click="viewWorkflow(action)"
                  class="px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                >
                  Détails
                </button>
                <button
                  @click="openRejectModal(action)"
                  class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                >
                  Rejeter
                </button>
                <button
                  @click="openApproveModal(action)"
                  class="px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg transition-colors"
                >
                  Approuver
                </button>
              </div>
            </div>

            <!-- Progress -->
            <div class="mt-4 flex items-center space-x-4">
              <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-blue-600 rounded-full transition-all"
                  :style="{ width: getProgress(action) + '%' }"
                ></div>
              </div>
              <span class="text-sm text-gray-500">
                Étape {{ action.current_step_order }}/{{ action.total_steps }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- All workflows -->
    <div v-if="activeTab === 'all'" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Workflow</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Initié par</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="wf in allWorkflows" :key="wf.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ wf.document?.title }}</div>
              <div class="text-sm text-gray-500">{{ wf.document?.document_number }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ wf.workflow?.name }}</td>
            <td class="px-6 py-4">
              <span :class="['px-2 py-1 text-xs rounded-full', getStatusClasses(wf.status)]">
                {{ getStatusLabel(wf.status) }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ wf.initiated_by?.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(wf.created_at) }}</td>
            <td class="px-6 py-4 text-right">
              <button
                @click="viewWorkflow(wf)"
                class="text-blue-600 hover:text-blue-700 text-sm font-medium"
              >
                Voir
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Approve Modal -->
    <ApprovalModal
      v-if="showApproveModal"
      :workflow="selectedWorkflow"
      action="approve"
      @close="showApproveModal = false"
      @submit="handleApprove"
    />

    <!-- Reject Modal -->
    <ApprovalModal
      v-if="showRejectModal"
      :workflow="selectedWorkflow"
      action="reject"
      @close="showRejectModal = false"
      @submit="handleReject"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useWorkflowStore } from '@/stores/workflows';
import { useAuthStore } from '@/stores/auth';
import ApprovalModal from '@/components/workflows/ApprovalModal.vue';
import { CheckCircleIcon, ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const workflowStore = useWorkflowStore();
const authStore = useAuthStore();

const activeTab = ref('pending');
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedWorkflow = ref(null);
const allWorkflows = computed(() => workflowStore.workflowInstances);

const pendingActions = computed(() => workflowStore.pendingActions);

const tabs = computed(() => [
  { id: 'pending', name: 'Mes tâches', count: pendingActions.value.length },
  { id: 'all', name: 'Tous les workflows', count: allWorkflows.value.length },
]);

onMounted(async () => {
  await workflowStore.fetchPendingActions();
  await workflowStore.fetchInstances();
});

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

function getProgress(action) {
  return Math.round((action.current_step_order / action.total_steps) * 100);
}

function getStatusClasses(status) {
  const classes = {
    pending: 'bg-amber-100 text-amber-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-700',
  };
  return classes[status] || classes.pending;
}

function getStatusLabel(status) {
  const labels = {
    pending: 'En attente',
    in_progress: 'En cours',
    completed: 'Terminé',
    rejected: 'Rejeté',
    cancelled: 'Annulé',
  };
  return labels[status] || status;
}

function viewWorkflow(workflow) {
  router.push(`/workflows/${workflow.id}`);
}

function openApproveModal(workflow) {
  selectedWorkflow.value = workflow;
  showApproveModal.value = true;
}

function openRejectModal(workflow) {
  selectedWorkflow.value = workflow;
  showRejectModal.value = true;
}

async function handleApprove(data) {
  const result = await workflowStore.approveStep(selectedWorkflow.value.id, data);
  if (result.success) {
    showApproveModal.value = false;
    selectedWorkflow.value = null;
  }
}

async function handleReject(data) {
  const result = await workflowStore.rejectStep(selectedWorkflow.value.id, data);
  if (result.success) {
    showRejectModal.value = false;
    selectedWorkflow.value = null;
  }
}
</script>
