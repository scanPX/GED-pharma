<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
    </div>

    <template v-else-if="instance">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-start space-x-4">
          <router-link 
            to="/workflows" 
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </router-link>
          
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ instance.workflow?.name }}</h1>
            <p class="mt-1 text-sm text-gray-500">
              {{ instance.document?.title }} • {{ instance.document?.document_number }}
            </p>
          </div>
        </div>

        <span :class="['px-3 py-1 rounded-full text-sm font-medium', statusClasses]">
          {{ statusLabel }}
        </span>
      </div>

      <!-- Main content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Workflow steps -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Étapes du workflow</h2>
          </div>
          
          <div class="p-6">
            <div class="relative">
              <!-- Timeline line -->
              <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200"></div>
              
              <!-- Steps -->
              <div class="space-y-8">
                <div 
                  v-for="(step, index) in mergedSteps" 
                  :key="step.id"
                  class="relative flex items-start"
                >
                  <!-- Step indicator -->
                  <div 
                    :class="[
                      'relative z-10 w-10 h-10 rounded-full flex items-center justify-center border-2',
                      getStepIndicatorClasses(step)
                    ]"
                  >
                    <CheckIcon v-if="step.is_done" class="w-5 h-5" />
                    <XMarkIcon v-else-if="step.is_rejected" class="w-5 h-5" />
                    <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
                  </div>
                  
                  <!-- Step content -->
                  <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between">
                      <h4 class="font-medium text-gray-900">{{ step.name }}</h4>
                      <span 
                        v-if="step.action_at"
                        class="text-xs text-gray-500"
                      >
                        {{ formatDate(step.action_at) }}
                      </span>
                    </div>
                    
                    <p class="mt-1 text-sm text-gray-500">{{ step.description }}</p>
                    
                    <!-- Action info -->
                    <div v-if="step.actor" class="mt-2 text-sm">
                      <span class="text-gray-500">
                        {{ formatActionLabel(step.action) }} par
                      </span>
                      <span class="font-medium text-gray-700">{{ step.actor.name }}</span>
                    </div>
                    
                    <!-- Comment -->
                    <div v-if="step.comment" class="mt-2 p-3 bg-gray-50 rounded-lg">
                      <p class="text-sm text-gray-700 italic">"{{ step.comment }}"</p>
                    </div>

                    <!-- Signature badge -->
                    <div v-if="step.has_signature" class="mt-2 flex items-center text-emerald-600 text-xs font-medium">
                      <ShieldCheckIcon class="w-3.5 h-3.5 mr-1" />
                      Signé électroniquement ({{ step.signature_meaning || 'Approbation' }})
                    </div>
                    
                    <!-- Current step actions -->
                    <div 
                      v-if="step.is_current && canAct"
                      class="mt-4 flex items-center space-x-3"
                    >
                      <button
                        @click="openApproval('approve')"
                        class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm"
                      >
                        Approuver
                      </button>
                      <button
                        @click="openApproval('reject')"
                        class="px-4 py-2 border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors shadow-sm"
                      >
                        Rejeter
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Document info -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">Document</h3>
            </div>
            <div class="p-6">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                  <DocumentTextIcon class="w-6 h-6 text-blue-600" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 truncate">{{ instance.document?.title }}</p>
                  <p class="text-sm text-gray-500">{{ instance.document?.document_number }}</p>
                </div>
              </div>
              <router-link
                :to="{ name: 'documents.show', params: { id: instance.document?.id }}"
                class="mt-4 block text-center py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
              >
                Voir le document
              </router-link>
            </div>
          </div>

          <!-- Workflow info -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">Informations</h3>
            </div>
            <dl class="divide-y divide-gray-100">
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Initié par</dt>
                <dd class="text-sm font-medium text-gray-900">{{ instance.initiator?.name }}</dd>
              </div>
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Date d'initiation</dt>
                <dd class="text-sm font-medium text-gray-900">{{ formatDate(instance.created_at) }}</dd>
              </div>
              <div v-if="instance.completed_at" class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Date de clôture</dt>
                <dd class="text-sm font-medium text-gray-900">{{ formatDate(instance.completed_at) }}</dd>
              </div>
            </dl>
          </div>

          <!-- Cancel button -->
          <button
            v-if="canCancel"
            @click="showCancelModal = true"
            class="w-full py-2 border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors"
          >
            Annuler le workflow
          </button>
        </div>
      </div>
    </template>

    <!-- Cancel Modal -->
    <div 
      v-if="showCancelModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showCancelModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900">Annuler le workflow</h3>
        <p class="mt-2 text-sm text-gray-500">
          Cette action est irréversible. Veuillez indiquer la raison de l'annulation.
        </p>
        <textarea
          v-model="cancelReason"
          rows="3"
          class="mt-4 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
          placeholder="Raison de l'annulation..."
        ></textarea>
        <div class="mt-4 flex justify-end space-x-3">
          <button
            @click="showCancelModal = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium"
          >
            Fermer
          </button>
          <button
            @click="cancelWorkflow"
            :disabled="!cancelReason.trim() || cancelling"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 font-medium flex items-center"
          >
            <span v-if="cancelling" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full mr-2"></span>
            Confirmer l'annulation
          </button>
        </div>
      </div>
    </div>

    <!-- Approval Modal -->
    <ApprovalModal
      v-if="showApprovalModal"
      :workflow="instance"
      :action="approvalAction"
      @close="showApprovalModal = false"
      @submit="handleApprovalSubmit"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useWorkflowStore } from '@/stores/workflows';
import { useAuthStore } from '@/stores/auth';
import {
  ArrowLeftIcon,
  DocumentTextIcon,
  CheckIcon,
  XMarkIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline';
import ApprovalModal from '@/components/workflows/ApprovalModal.vue';

const props = defineProps({
  id: { type: [String, Number], required: true }
});

const router = useRouter();
const workflowStore = useWorkflowStore();
const authStore = useAuthStore();

const loading = ref(true);
const cancelling = ref(false);
const showCancelModal = ref(false);
const cancelReason = ref('');
const showApprovalModal = ref(false);
const approvalAction = ref('approve');

const instance = computed(() => workflowStore.currentInstance);

const statusClasses = computed(() => {
  const classes = {
    pending: 'bg-amber-100 text-amber-700 border border-amber-200',
    in_progress: 'bg-blue-100 text-blue-700 border border-blue-200',
    approved: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    rejected: 'bg-red-100 text-red-700 border border-red-200',
    cancelled: 'bg-gray-100 text-gray-700 border border-gray-200',
  };
  return classes[instance.value?.status] || classes.pending;
});

const statusLabel = computed(() => {
  const labels = {
    pending: 'En attente',
    in_progress: 'En cours',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    cancelled: 'Annulé',
  };
  return labels[instance.value?.status] || instance.value?.status;
});

const mergedSteps = computed(() => {
  if (!instance.value || !instance.value.workflow?.steps) return [];
  
  return instance.value.workflow.steps.map(step => {
    // Trouver les actions pour cette étape
    const action = instance.value.actions?.find(a => a.workflow_step_id === step.id);
    
    return {
      ...step,
      is_done: action?.action === 'approved',
      is_rejected: action?.action === 'rejected',
      is_current: instance.value.current_step_id === step.id && !instance.value.isComplete,
      action: action?.action,
      action_at: action?.action_at,
      actor: action?.user,
      comment: action?.comment,
      has_signature: action?.signature_provided,
      signature_meaning: action?.signature?.meaning
    };
  });
});

const canAct = computed(() => {
  // Check if current user can approve current step
  if (!instance.value || instance.value.status !== 'in_progress' && instance.value.status !== 'pending') return false;
  
  const currentStep = instance.value.current_step || instance.value.workflow?.steps?.find(s => s.id === instance.value.current_step_id);
  if (!currentStep) return false;
  
  // Logic to check if user belongs to step (simplified, using backend logic eventually)
  return true; // The backend will enforce this anyway
});

const canCancel = computed(() => {
  return ['pending', 'in_progress'].includes(instance.value?.status) &&
         (instance.value?.initiated_by === authStore.user?.id || authStore.hasRole('admin'));
});

onMounted(async () => {
  await loadInstance();
});

async function loadInstance() {
  loading.value = true;
  await workflowStore.fetchInstance(props.id);
  loading.value = false;
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

function getStepIndicatorClasses(step) {
  if (step.is_done) return 'bg-emerald-600 border-emerald-600 text-white';
  if (step.is_rejected) return 'bg-red-600 border-red-600 text-white';
  if (step.is_current) return 'bg-blue-600 border-blue-600 text-white animate-pulse';
  return 'bg-white border-gray-200 text-gray-400';
}

function formatActionLabel(action) {
  const labels = {
    submitted: 'Soumis',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    revision_requested: 'Révision demandée'
  };
  return labels[action] || action || 'Traité';
}

function openApproval(action) {
  approvalAction.value = action;
  showApprovalModal.value = true;
}

async function handleApprovalSubmit(data) {
  const action = approvalAction.value === 'approve' 
    ? workflowStore.approveStep(instance.value.id, data)
    : workflowStore.rejectStep(instance.value.id, { reason: data.comment, ...data });

  const result = await action;
  if (result.success) {
    showApprovalModal.value = false;
    await loadInstance();
  } else {
    alert(result.error || 'Une erreur est survenue');
  }
}

async function cancelWorkflow() {
  cancelling.value = true;
  const result = await workflowStore.cancelWorkflow(instance.value.id, cancelReason.value);
  if (result.success) {
    showCancelModal.value = false;
    router.push('/workflows');
  } else {
    alert(result.error);
  }
  cancelling.value = false;
}
</script>
