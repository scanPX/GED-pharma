<template>
  <div 
    :class="[
      'bg-white rounded-xl shadow-sm border border-gray-200 p-6',
      urgent ? 'ring-2 ring-red-200' : ''
    ]"
  >
    <div class="flex items-start justify-between">
      <div 
        :class="[
          'w-12 h-12 rounded-xl flex items-center justify-center',
          colorClasses.bg
        ]"
      >
        <component :is="iconComponent" :class="['w-6 h-6', colorClasses.icon]" />
      </div>
      
      <div v-if="change !== undefined" class="flex items-center space-x-1">
        <ArrowUpIcon v-if="change > 0" class="w-4 h-4 text-emerald-500" />
        <ArrowDownIcon v-else-if="change < 0" class="w-4 h-4 text-red-500" />
        <span 
          :class="[
            'text-sm font-medium',
            change > 0 ? 'text-emerald-600' : change < 0 ? 'text-red-600' : 'text-gray-500'
          ]"
        >
          {{ change > 0 ? '+' : '' }}{{ change }}%
        </span>
      </div>
    </div>

    <div class="mt-4">
      <p class="text-sm font-medium text-gray-500">{{ title }}</p>
      <p class="mt-1 text-3xl font-bold text-gray-900">
        {{ formattedValue }}
      </p>
      <p v-if="changeLabel" class="mt-1 text-xs text-gray-400">{{ changeLabel }}</p>
    </div>

    <button
      v-if="actionLabel"
      @click="$emit('action')"
      :class="[
        'mt-4 text-sm font-medium transition-colors',
        colorClasses.action
      ]"
    >
      {{ actionLabel }} →
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  DocumentCheckIcon,
  ClockIcon,
  ClipboardDocumentListIcon,
  ExclamationTriangleIcon,
  ArrowUpIcon,
  ArrowDownIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [Number, String], required: true },
  icon: { type: String, default: 'DocumentCheckIcon' },
  color: { type: String, default: 'blue' },
  change: { type: Number, default: undefined },
  changeLabel: { type: String, default: '' },
  actionLabel: { type: String, default: '' },
  urgent: { type: Boolean, default: false },
});

defineEmits(['action']);

const icons = {
  DocumentCheckIcon,
  ClockIcon,
  ClipboardDocumentListIcon,
  ExclamationTriangleIcon,
};

const iconComponent = computed(() => icons[props.icon] || DocumentCheckIcon);

const colorClasses = computed(() => {
  const colors = {
    blue: {
      bg: 'bg-blue-100',
      icon: 'text-blue-600',
      action: 'text-blue-600 hover:text-blue-700',
    },
    emerald: {
      bg: 'bg-emerald-100',
      icon: 'text-emerald-600',
      action: 'text-emerald-600 hover:text-emerald-700',
    },
    amber: {
      bg: 'bg-amber-100',
      icon: 'text-amber-600',
      action: 'text-amber-600 hover:text-amber-700',
    },
    red: {
      bg: 'bg-red-100',
      icon: 'text-red-600',
      action: 'text-red-600 hover:text-red-700',
    },
  };
  return colors[props.color] || colors.blue;
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('fr-FR');
  }
  return props.value;
});
</script>
