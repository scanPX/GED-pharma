<template>
  <div class="space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="glass-header -mx-4 px-4 py-6 sm:-mx-8 sm:px-8 mb-8 border-b border-gray-200/50">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Mes Formations</h1>
        <p class="text-sm text-gray-500 mt-1">Gérez vos lectures obligatoires et formations documentaires.</p>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="trainingStore.loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!trainingStore.myTrainings.length" class="premium-card p-20 text-center">
        <div class="w-20 h-20 bg-emerald-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
          <CheckCircleIcon class="w-10 h-10 text-emerald-500" />
        </div>
        <h3 class="text-xl font-bold text-gray-900">Tout est à jour !</h3>
        <p class="text-gray-500 mt-2">Vous n'avez aucune formation en attente pour le moment.</p>
      </div>

      <!-- Training Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="training in trainingStore.myTrainings" 
          :key="training.id"
          class="premium-card group hover:shadow-2xl transition-all duration-500 overflow-hidden flex flex-col"
        >
          <div class="p-6 flex-1">
            <div class="flex items-start justify-between mb-4">
              <div :class="[
                'p-3 rounded-2xl transition-colors duration-300',
                training.status === 'in_progress' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-400'
              ]">
                <BookOpenIcon class="w-6 h-6" />
              </div>
              <span v-if="training.due_date" :class="[
                'px-3 py-1 text-[10px] font-bold uppercase rounded-full tracking-wider border',
                isOverdue(training.due_date) ? 'bg-red-50 text-red-600 border-red-100' : 'bg-amber-50 text-amber-600 border-amber-100'
              ]">
                {{ isOverdue(training.due_date) ? 'En retard' : 'À faire' }}
              </span>
            </div>

            <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-2">
              {{ training.document?.title }}
            </h3>
            <p class="text-xs font-mono text-gray-400 mt-1 uppercase tracking-wider">
              {{ training.document?.document_number }} • v{{ training.document_version?.version_number }}
            </p>

            <div class="mt-6 space-y-3">
              <div class="flex items-center text-sm text-gray-500">
                <CalendarIcon class="w-4 h-4 mr-2 opacity-50" />
                <span>Assigné le {{ formatDate(training.assigned_at) }}</span>
              </div>
              <div v-if="training.due_date" class="flex items-center text-sm text-gray-500">
                <ClockIcon class="w-4 h-4 mr-2 opacity-50" />
                <span>Échéance : {{ formatDate(training.due_date) }}</span>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
            <button 
              @click="startTraining(training)"
              class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-200 text-blue-600 font-bold rounded-xl hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all duration-300 micro-interaction shadow-sm"
            >
              {{ training.status === 'in_progress' ? 'Continuer' : 'Commencer' }}
              <ArrowRightIcon class="w-4 h-4 ml-2" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useTrainingStore } from '@/stores/training';
import { 
  BookOpenIcon, 
  CheckCircleIcon, 
  CalendarIcon, 
  ClockIcon,
  ArrowRightIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();
const trainingStore = useTrainingStore();

onMounted(() => {
  trainingStore.fetchMyTrainings();
});

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
}

function isOverdue(date) {
  return new Date(date) < new Date();
}

async function startTraining(training) {
  if (training.status === 'assigned') {
    await trainingStore.startTraining(training.id);
  }
  router.push({ name: 'training.view', params: { id: training.id } });
}
</script>
