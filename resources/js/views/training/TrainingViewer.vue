<template>
  <div v-if="training" class="min-h-screen bg-gray-50 flex flex-col animate-fade-in">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-10 shadow-sm">
      <div class="flex items-center space-x-4">
        <button @click="router.back()" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
          <ChevronLeftIcon class="w-6 h-6 text-gray-500" />
        </button>
        <div>
          <h1 class="text-sm font-bold text-gray-900 leading-tight">{{ training.document?.title }}</h1>
          <p class="text-[10px] text-gray-500 uppercase tracking-widest">{{ training.document?.document_number }} • v{{ training.document_version?.version_number }}</p>
        </div>
      </div>

      <div class="flex items-center space-x-3">
        <div class="hidden sm:flex items-center text-xs text-gray-400 border-r border-gray-200 pr-4 mr-1">
          <ClockIcon class="w-3.5 h-3.5 mr-1.5" />
          <span>Lecture en cours : {{ timeDisplay }}</span>
        </div>
        <button 
          @click="showAcknowledgeModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all micro-interaction"
        >
          Valider la formation
          <CheckBadgeIcon class="w-4 h-4 ml-2" />
        </button>
      </div>
    </div>

    <!-- Reader Area -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Document Viewer -->
      <div class="flex-1 overflow-hidden bg-gray-200/50 p-4 sm:p-8">
        <div class="max-w-5xl mx-auto h-full bg-white shadow-2xl rounded-sm flex flex-col relative border border-gray-300 overflow-hidden">
          <DocumentReader 
            v-if="training.document"
            :url="`/api/ged/documents/${training.document.id}/view/${training.document_version_id}`"
            :extension="training.document_version?.file_extension || 'pdf'"
            :file-name="training.document?.title"
            @download="trainingStore.downloadDocument(training.document.id, training.document_version_id)"
          />
          
          <!-- Watermark (GMP style) -->
          <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-[0.02] flex items-center justify-center rotate-[-45deg] select-none z-10">
            <span class="text-9xl font-black uppercase whitespace-nowrap">DOCUMENT DE FORMATION</span>
          </div>
        </div>
      </div>

      <!-- Sidebar (Details) -->
      <div class="hidden lg:block w-80 bg-white border-l border-gray-200 p-6 overflow-y-auto">
        <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Informations</h2>
        
        <div class="space-y-8">
          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Assigné par</label>
            <div class="flex items-center">
              <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center font-bold text-xs mr-3">
                {{ training.assigned_by_user?.name.charAt(0) }}
              </div>
              <span class="text-sm font-medium text-gray-900">{{ training.assigned_by_user?.name }}</span>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Motif</label>
            <p class="text-sm text-gray-600 italic">"{{ training.assignment_reason || 'Lecture obligatoire périodique' }}"</p>
          </div>

          <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100">
            <div class="flex items-center text-blue-800 mb-2">
              <InformationCircleIcon class="w-5 h-5 mr-2" />
              <span class="text-sm font-bold">21 CFR Part 11</span>
            </div>
            <p class="text-xs text-blue-600 leading-relaxed">
              La validation de cette formation nécessite votre signature électronique (PIN). En signant, vous attestez avoir lu, compris et intégré le contenu de ce document.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Acknowledge Modal -->
    <div v-if="showAcknowledgeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showAcknowledgeModal = false"></div>
      
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden relative animate-scale-up">
        <div class="p-8">
          <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <KeyIcon class="w-8 h-8" />
            </div>
            <h2 class="text-2xl font-black text-gray-900">Signature Électronique</h2>
            <p class="text-gray-500 mt-2">Veuillez saisir votre PIN pour valider la formation.</p>
          </div>

          <form @submit.prevent="submitAcknowledge" class="space-y-6">
            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Code PIN</label>
              <input 
                v-model="acknowledgeForm.pin"
                type="password" 
                required
                maxlength="6"
                placeholder="••••••"
                class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all text-center text-2xl tracking-[1em] font-mono outline-none"
              >
            </div>

            <div>
              <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Commentaire (Optionnel)</label>
              <textarea 
                v-model="acknowledgeForm.comment"
                rows="3"
                class="w-full px-5 py-4 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-blue-600 focus:bg-white transition-all outline-none text-sm"
                placeholder="Avez-vous des remarques sur ce document ?"
              ></textarea>
            </div>

            <div class="pt-2">
              <button 
                type="submit"
                :disabled="submitting || acknowledgeForm.pin.length < 4"
                class="w-full py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 disabled:opacity-50 disabled:shadow-none transition-all flex items-center justify-center"
              >
                <span v-if="submitting" class="animate-spin rounded-full h-5 w-5 border-2 border-white/30 border-t-white mr-3"></span>
                SIGNER ET VALIDER
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTrainingStore } from '@/stores/training';
import { useAuthStore } from '@/stores/auth';
import DocumentReader from '@/components/GED/DocumentReader.vue';
import { 
  ChevronLeftIcon, 
  ClockIcon, 
  CheckBadgeIcon, 
  DocumentIcon,
  ArrowDownTrayIcon,
  InformationCircleIcon,
  KeyIcon
} from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const trainingStore = useTrainingStore();
const authStore = useAuthStore();

const training = computed(() => trainingStore.currentTraining);
const showAcknowledgeModal = ref(false);
const submitting = ref(false);
const secondsElapsed = ref(0);
let timer = null;

const acknowledgeForm = ref({
  pin: '',
  comment: ''
});

const timeDisplay = computed(() => {
  const m = Math.floor(secondsElapsed.value / 60);
  const s = secondsElapsed.value % 60;
  return `${m}:${s.toString().padStart(2, '0')}`;
});

const downloadUrl = computed(() => {
  if (!training.value?.document?.id) return '#';
  return `/api/ged/documents/${training.value.document.id}/download/${training.value.document_version_id}`;
});

onMounted(async () => {
  await trainingStore.fetchTraining(route.params.id);
  timer = setInterval(() => {
    secondsElapsed.value++;
  }, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

async function submitAcknowledge() {
  submitting.value = true;
  try {
    await trainingStore.acknowledgeTraining(training.value.id, {
      pin: acknowledgeForm.value.pin,
      comment: acknowledgeForm.value.comment,
      time_spent_minutes: Math.ceil(secondsElapsed.value / 60)
    });
    router.push({ name: 'training.my' });
  } catch (err) {
    alert(err.message || 'Le code PIN est incorrect.');
  } finally {
    submitting.value = false;
  }
}
</script>
