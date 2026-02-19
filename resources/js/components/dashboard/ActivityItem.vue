<template>
  <div class="px-6 py-3 flex items-start space-x-3">
    <div 
      :class="[
        'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
        actionColors.bg
      ]"
    >
      <component :is="actionIcon" :class="['w-4 h-4', actionColors.icon]" />
    </div>
    
    <div class="flex-1 min-w-0">
      <p class="text-sm text-gray-900">
        <span class="font-medium">{{ activity.user?.name || 'Système' }}</span>
        {{ actionText }}
      </p>
      <p class="mt-0.5 text-xs text-gray-500">
        {{ timeAgo(activity.created_at) }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  PlusCircleIcon,
  PencilIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowUpCircleIcon,
  EyeIcon,
  DocumentIcon,
  ArchiveBoxIcon,
  UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  activity: { type: Object, required: true },
});

const actionConfig = {
  document_created: {
    text: 'a créé un nouveau document',
    icon: PlusCircleIcon,
    color: 'emerald',
  },
  document_updated: {
    text: 'a modifié un document',
    icon: PencilIcon,
    color: 'blue',
  },
  version_uploaded: {
    text: 'a téléversé une nouvelle version',
    icon: ArrowUpCircleIcon,
    color: 'purple',
  },
  workflow_approved: {
    text: 'a approuvé un workflow',
    icon: CheckCircleIcon,
    color: 'emerald',
  },
  workflow_rejected: {
    text: 'a rejeté un workflow',
    icon: XCircleIcon,
    color: 'red',
  },
  document_viewed: {
    text: 'a consulté un document',
    icon: EyeIcon,
    color: 'gray',
  },
  document_archived: {
    text: 'a archivé un document',
    icon: ArchiveBoxIcon,
    color: 'amber',
  },
  user_login: {
    text: 's\'est connecté',
    icon: UserIcon,
    color: 'blue',
  },
};

const config = computed(() => {
  return actionConfig[props.activity.action] || {
    text: props.activity.action || 'action inconnue',
    icon: DocumentIcon,
    color: 'gray',
  };
});

const actionText = computed(() => config.value.text);
const actionIcon = computed(() => config.value.icon);

const actionColors = computed(() => {
  const colors = {
    emerald: { bg: 'bg-emerald-100', icon: 'text-emerald-600' },
    blue: { bg: 'bg-blue-100', icon: 'text-blue-600' },
    purple: { bg: 'bg-purple-100', icon: 'text-purple-600' },
    red: { bg: 'bg-red-100', icon: 'text-red-600' },
    amber: { bg: 'bg-amber-100', icon: 'text-amber-600' },
    gray: { bg: 'bg-gray-100', icon: 'text-gray-600' },
  };
  return colors[config.value.color] || colors.gray;
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
  
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>
