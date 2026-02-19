<template>
  <span 
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      statusClasses
    ]"
  >
    <span 
      v-if="showDot"
      :class="['w-1.5 h-1.5 rounded-full mr-1.5', dotClass]"
    ></span>
    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { 
    type: [Object, String], 
    required: true 
  },
  showDot: {
    type: Boolean,
    default: true
  }
});

const statusConfig = {
  DRAFT: {
    label: 'Brouillon',
    classes: 'bg-gray-100 text-gray-700',
    dot: 'bg-gray-400',
  },
  IN_REVIEW: {
    label: 'En revue',
    classes: 'bg-amber-100 text-amber-700',
    dot: 'bg-amber-400',
  },
  PENDING_APPROVAL: {
    label: 'En attente',
    classes: 'bg-blue-100 text-blue-700',
    dot: 'bg-blue-400',
  },
  APPROVED: {
    label: 'Approuvé',
    classes: 'bg-emerald-100 text-emerald-700',
    dot: 'bg-emerald-400',
  },
  EFFECTIVE: {
    label: 'En vigueur',
    classes: 'bg-green-100 text-green-800',
    dot: 'bg-green-500',
  },
  SUPERSEDED: {
    label: 'Remplacé',
    classes: 'bg-orange-100 text-orange-700',
    dot: 'bg-orange-400',
  },
  OBSOLETE: {
    label: 'Obsolète',
    classes: 'bg-red-100 text-red-700',
    dot: 'bg-red-400',
  },
  ARCHIVED: {
    label: 'Archivé',
    classes: 'bg-slate-100 text-slate-700',
    dot: 'bg-slate-400',
  },
};

const statusCode = computed(() => {
  if (typeof props.status === 'string') return props.status;
  return props.status?.code || 'DRAFT';
});

const config = computed(() => {
  return statusConfig[statusCode.value] || statusConfig.DRAFT;
});

const statusLabel = computed(() => {
  if (typeof props.status === 'object' && props.status?.name) {
    return props.status.name;
  }
  return config.value.label;
});

const statusClasses = computed(() => config.value.classes);
const dotClass = computed(() => config.value.dot);
</script>
