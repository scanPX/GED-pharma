<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="animate-spin w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full"></div>
    </div>

    <!-- Document not found -->
    <div v-else-if="!document" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
      <ExclamationTriangleIcon class="w-16 h-16 text-amber-400 mx-auto" />
      <h2 class="mt-4 text-xl font-semibold text-gray-900">Document non trouvé</h2>
      <p class="mt-2 text-gray-500">Le document demandé n'existe pas ou vous n'avez pas les droits d'accès.</p>
      <router-link to="/documents" class="mt-6 inline-flex items-center text-blue-600 hover:text-blue-700">
        <ArrowLeftIcon class="w-5 h-5 mr-2" />
        Retour à la liste
      </router-link>
    </div>

    <!-- Document content -->
    <template v-else>
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start space-x-4">
          <router-link 
            to="/documents" 
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </router-link>
          
          <div>
            <div class="flex items-center space-x-3">
              <h1 class="text-2xl font-bold text-gray-900">{{ document.title }}</h1>
              <StatusBadge :status="document.status" />
            </div>
            <p class="mt-1 text-sm text-gray-500">
              {{ document.document_number }} • Version {{ document.current_version }}
            </p>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <button
            v-if="authStore.hasPermission('document.download')"
            @click="downloadDocument"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
          >
            <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
            Télécharger
          </button>

          <button
            v-if="canPrint"
            @click="printDocument"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
          >
            <PrinterIcon class="w-5 h-5 mr-2" />
            Imprimer
          </button>

          <button
            v-if="canDelete"
            @click="deleteDocument"
            class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 font-medium rounded-lg hover:bg-red-50 transition-colors"
          >
            <TrashIcon class="w-5 h-5 mr-2" />
            Supprimer
          </button>
          
          <button
            v-if="canArchive"
            @click="archiveDocument"
            :disabled="archiving"
            class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 font-medium rounded-lg hover:bg-red-50 transition-colors disabled:opacity-50"
          >
            <ArchiveBoxIcon class="w-5 h-5 mr-2" />
            Archiver
          </button>
          
          <button
            v-if="canEdit"
            @click="showUploadModal = true"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors"
          >
            <ArrowUpTrayIcon class="w-5 h-5 mr-2" />
            Nouvelle version
          </button>
          
          <button
            v-if="canInitiateWorkflow"
            @click="showWorkflowModal = true"
            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors"
          >
            <PlayIcon class="w-5 h-5 mr-2" />
            Soumettre
          </button>
        </div>
      </div>

      <!-- Document Viewer -->
      <div v-if="document.current_version_id" class="h-[600px] lg:h-[800px]">
        <DocumentReader 
          :url="`/api/ged/documents/${document.id}/view`"
          :extension="document.current_version_relation?.file_extension || 'pdf'"
          :file-name="document.title"
          @download="downloadDocument"
        />
      </div>

      <!-- Main content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Document info (left column) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Description -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Description</h2>
            </div>
            <div class="p-6">
              <p class="text-gray-700 whitespace-pre-wrap">
                {{ document.description || 'Aucune description disponible.' }}
              </p>
            </div>
          </div>

          <!-- Version history -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-900">Historique des versions</h2>
              <span class="text-sm text-gray-500">{{ versions.length }} version(s)</span>
            </div>
            
            <div class="divide-y divide-gray-100">
              <div 
                v-for="version in versions" 
                :key="version.id"
                :class="[
                  'px-6 py-4 flex items-start justify-between',
                  version.is_current ? 'bg-blue-50' : ''
                ]"
              >
                <div class="flex items-start space-x-4">
                  <div 
                    :class="[
                      'w-10 h-10 rounded-lg flex items-center justify-center',
                      version.is_current ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'
                    ]"
                  >
                    v{{ version.version_number }}
                  </div>
                  <div>
                    <div class="flex items-center space-x-2">
                      <span class="font-medium text-gray-900">Version {{ version.version_number }}</span>
                      <span v-if="version.is_current" class="px-2 py-0.5 bg-blue-600 text-white text-xs rounded-full">
                        Actuelle
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ version.change_summary || 'Pas de description' }}</p>
                    <p class="mt-1 text-xs text-gray-400">
                      Par {{ version.created_by?.name }} • {{ formatDate(version.created_at) }}
                    </p>
                  </div>
                </div>
                
                <button
                  @click="downloadVersion(version)"
                  class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                >
                  <ArrowDownTrayIcon class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Workflow history -->
          <div 
            v-if="document.workflow_instances?.length > 0"
            class="premium-card overflow-hidden"
          >
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
              <div class="flex items-center">
                <div class="p-2 bg-blue-50 rounded-lg mr-4">
                  <PlayIcon class="w-5 h-5 text-blue-600" />
                </div>
                <h2 class="text-lg font-bold text-gray-900">Historique des Workflows</h2>
              </div>
              <span class="text-[10px] bg-blue-600 text-white px-3 py-1 rounded-full uppercase tracking-widest font-extrabold">{{ document.workflow_instances.length }} cycles</span>
            </div>
            
            <div class="divide-y divide-gray-100">
              <div 
                v-for="instance in document.workflow_instances" 
                :key="instance.id"
                class="group px-8 py-6 hover:bg-blue-50/30 transition-all duration-300"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center group-hover:bg-blue-600 transition-all duration-300">
                      <component :is="instance.status === 'completed' ? ShieldCheckIcon : PlayIcon" class="w-5 h-5 text-gray-400 group-hover:text-white" />
                    </div>
                    <div>
                      <h4 class="text-base font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ instance.workflow?.name }}</h4>
                      <div class="flex items-center space-x-3 mt-1">
                        <span 
                          :class="[
                            'px-2 py-0.5 text-[10px] uppercase font-bold rounded-md border',
                            getWorkflowStatusClasses(instance.status)
                          ]"
                        >
                          {{ getWorkflowStatusLabel(instance.status) }}
                        </span>
                        <span class="text-xs text-gray-400 font-medium">Initié par {{ instance.initiator?.name }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="block text-sm font-bold text-gray-900">{{ formatDate(instance.created_at) }}</span>
                    <router-link 
                      :to="{ name: 'workflows.show', params: { id: instance.id } }"
                      class="text-[10px] text-blue-600 font-extrabold uppercase tracking-tighter hover:underline mt-1 block"
                    >
                      Détails du cycle →
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar (right column) -->
        <div class="space-y-6">
          <!-- Document metadata -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">Informations</h3>
            </div>
            <dl class="divide-y divide-gray-100">
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Catégorie</dt>
                <dd class="text-sm font-medium text-gray-900">{{ document.category?.name || '—' }}</dd>
              </div>
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Type</dt>
                <dd class="text-sm font-medium text-gray-900">{{ document.type?.name || '—' }}</dd>
              </div>
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Propriétaire</dt>
                <dd class="text-sm font-medium text-gray-900">{{ document.owner?.name || '—' }}</dd>
              </div>
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Créé le</dt>
                <dd class="text-sm font-medium text-gray-900">{{ formatDate(document.created_at) }}</dd>
              </div>
              <div class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Modifié le</dt>
                <dd class="text-sm font-medium text-gray-900">{{ formatDate(document.updated_at) }}</dd>
              </div>
              <div v-if="document.effective_date" class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Date d'effet</dt>
                <dd class="text-sm font-medium text-gray-900">{{ formatDate(document.effective_date) }}</dd>
              </div>
              <div v-if="document.review_date" class="px-6 py-3 flex justify-between">
                <dt class="text-sm text-gray-500">Prochaine revue</dt>
                <dd 
                  :class="[
                    'text-sm font-medium',
                    isReviewDue ? 'text-red-600' : 'text-gray-900'
                  ]"
                >
                  {{ formatDate(document.review_date) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Related documents -->
          <div 
            v-if="document.related_documents?.length > 0"
            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
          >
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="font-semibold text-gray-900">Documents liés</h3>
            </div>
            <div class="divide-y divide-gray-100">
              <router-link
                v-for="related in document.related_documents"
                :key="related.id"
                :to="`/documents/${related.id}`"
                class="px-6 py-3 flex items-center hover:bg-gray-50 transition-colors"
              >
                <DocumentTextIcon class="w-5 h-5 text-gray-400 mr-3" />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ related.title }}</p>
                  <p class="text-xs text-gray-500">{{ related.document_number }}</p>
                </div>
              </router-link>
            </div>
          </div>

          <!-- Audit info -->
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start space-x-3">
              <ShieldCheckIcon class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
              <div class="text-xs text-amber-800">
                <p class="font-medium">Document contrôlé</p>
                <p class="mt-1">
                  Ce document est soumis aux exigences GMP et 21 CFR Part 11.
                  Toutes les modifications sont tracées.
                </p>
                <router-link 
                  :to="`/audit?document_id=${document.id}`"
                  class="mt-2 inline-block text-amber-700 hover:text-amber-900 font-medium"
                >
                  Voir l'audit trail →
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Upload modal -->
    <UploadVersionModal
      v-if="showUploadModal"
      :document="document"
      @close="showUploadModal = false"
      @uploaded="onVersionUploaded"
    />

    <!-- Workflow modal -->
    <InitiateWorkflowModal
      v-if="showWorkflowModal"
      :document="document"
      @close="showWorkflowModal = false"
      @initiated="onWorkflowInitiated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useDocumentStore } from '@/stores/documents';
import { useAuthStore } from '@/stores/auth';
import StatusBadge from '@/components/common/StatusBadge.vue';
import UploadVersionModal from '@/components/documents/UploadVersionModal.vue';
import InitiateWorkflowModal from '@/components/documents/InitiateWorkflowModal.vue';
import DocumentReader from '@/components/GED/DocumentReader.vue';
import {
  ArrowLeftIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  PlayIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
  ArchiveBoxIcon,
  PrinterIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  id: { type: [String, Number], required: true }
});

const route = useRoute();
const documentStore = useDocumentStore();
const authStore = useAuthStore();

const loading = ref(true);
const showUploadModal = ref(false);
const showWorkflowModal = ref(false);
const archiving = ref(false);

const document = computed(() => documentStore.currentDocument);
const versions = computed(() => document.value?.versions || []);

const canEdit = computed(() => {
  if (!document.value || !authStore.hasPermission('document.update')) return false;
  return document.value.status?.is_editable === true;
});

const canInitiateWorkflow = computed(() => {
  if (!document.value || !authStore.hasPermission('workflow.initiate')) return false;
  return document.value.status?.code === 'DRAFT';
});

const canArchive = computed(() => {
  if (!document.value || !authStore.hasPermission('document.archive')) return false;
  return ['EFFECTIVE', 'STALE'].includes(document.value.status?.code) && !document.value.is_archived;
});

const canDelete = computed(() => {
  return document.value && authStore.hasPermission('document.delete');
});

const canPrint = computed(() => {
  return document.value && authStore.hasPermission('document.download');
});

const isReviewDue = computed(() => {
  if (!document.value?.review_date) return false;
  return new Date(document.value.review_date) <= new Date();
});

onMounted(async () => {
  await documentStore.fetchDocument(props.id);
  loading.value = false;
});

function formatDate(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
}

async function downloadDocument() {
  await documentStore.downloadDocument(document.value.id);
}

async function downloadVersion(version) {
  await documentStore.downloadDocument(document.value.id, version.id);
}

async function archiveDocument() {
  const reason = prompt('Veuillez saisir le motif d\'archivage :');
  if (!reason) return;
  
  archiving.value = true;
  const result = await documentStore.archiveDocument(document.value.id, reason);
  archiving.value = false;
  
  if (result.success) {
    alert('Document archivé avec succès.');
  } else {
    alert(result.error || 'Une erreur est survenue lors de l\'archivage.');
  }
}

async function deleteDocument() {
  if (!confirm('ATTENTION : Voulez-vous vraiment supprimer définitivement ce document et tout son historique ? Cette action est irréversible.')) return;
  
  const result = await documentStore.deleteDocument(document.value.id);
  if (result.success) {
    alert('Document supprimé avec succès.');
    router.push({ name: 'documents.index' });
  } else {
    alert(result.error || 'Erreur lors de la suppression.');
  }
}

async function printDocument() {
  const result = await documentStore.printDocument(document.value.id);
  if (result.success) {
    // Dans un cas réel, on ouvrirait un modal de print ou une nouvelle page stylisée
    console.log('Données pour impression:', result.data);
    window.print();
  } else {
    alert(result.error || 'Erreur lors de la préparation de l\'impression.');
  }
}

function getWorkflowStatusClasses(status) {
  const classes = {
    pending: 'bg-amber-100 text-amber-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-700',
  };
  return classes[status] || classes.pending;
}

function getWorkflowStatusLabel(status) {
  const labels = {
    pending: 'En attente',
    in_progress: 'En cours',
    completed: 'Terminé',
    rejected: 'Rejeté',
    cancelled: 'Annulé',
  };
  return labels[status] || status;
}

async function onVersionUploaded() {
  showUploadModal.value = false;
  await documentStore.fetchDocument(props.id);
}

async function onWorkflowInitiated() {
  showWorkflowModal.value = false;
  await documentStore.fetchDocument(props.id);
}
</script>
