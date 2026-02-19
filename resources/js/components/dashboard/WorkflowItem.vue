<template>
  <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
    <div class="flex items-start justify-between">
      <div class="flex items-start space-x-4">
        <div 
          :class="[
            'w-10 h-10 rounded-lg flex items-center justify-center',
            stepTypeColors.bg
          ]"
        >
          <component :is="stepTypeIcon" :class="['w-5 h-5', stepTypeColors.icon]" />
        </div>
        
        <div class="min-w-0">
          <div class="flex items-center space-x-2">
            <h4 class="text-sm font-medium text-gray-900 truncate">
              {{ workflow.document?.title || 'Document' }}
            </h4>
            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
              {{ workflow.current_step?.name || 'Étape en cours' }}
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            {{ workflow.document?.document_number }} • v{{ workflow.document?.current_version }}
          </p>
          <p class="mt-1 text-xs text-gray-400">
            Initié par {{ workflow.initiated_by?.name }} • {{ timeAgo(workflow.created_at) }}
          </p>
        </div>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="$emit('view', workflow)"
          class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
        >
          Détails
        </button>
        <button
          v-if="canApprove"
          @click="$emit('approve', workflow)"
          class="px-3 py-1.5 text-sm text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors"
        >
          Approuver
        </button>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="mt-4 flex items-center space-x-3">
      <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
        <div 
          :class="['h-full rounded-full transition-all', stepTypeColors.progress]"
          :style="{ width: progressPercent + '%' }"
        ></div>
      </div>
      <span class="text-xs text-gray-500 whitespace-nowrap">
        Étape {{ workflow.current_step_order || 1 }}/{{ workflow.total_steps || 2 }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import {
  EyeIcon,
  CheckCircleIcon,
  DocumentCheckIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  workflow: { type: Object, required: true },
});

defineEmits(['approve', 'view']);

const authStore = useAuthStore();

const canApprove = computed(() => authStore.canApprove);

const stepTypeColors = computed(() => {
  const type = props.workflow.current_step?.step_type || 'review';
  const colors = {
    review: {
      bg: 'bg-blue-100',
      icon: 'text-blue-600',
      progress: 'bg-blue-600',
    },
    qa_approval: {
      bg: 'bg-emerald-100',
      icon: 'text-emerald-600',
      progress: 'bg-emerald-600',
    },
    final_approval: {
      bg: 'bg-purple-100',
      icon: 'text-purple-600',
      progress: 'bg-purple-600',
    },
  };
  return colors[type] || colors.review;
});

const stepTypeIcon = computed(() => {
  const type = props.workflow.current_step?.step_type || 'review';
  const icons = {
    review: EyeIcon,
    qa_approval: CheckCircleIcon,
    final_approval: ShieldCheckIcon,
  };
  return icons[type] || EyeIcon;
});

const progressPercent = computed(() => {
  const current = props.workflow.current_step_order || 1;
  const total = props.workflow.total_steps || 2;
  return Math.round((current / total) * 100);
});

function timeAgo(dateString) {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return "À l'instant";
  if (diffMins < 60) return `Il y a ${diffMins} min`;
  if (diffHours < 24) return `Il y a ${diffHours}h`;
  if (diffDays < 7) return `Il y a ${diffDays}j`;
  
  return date.toLocaleDateString('fr-FR');
}
</script>
