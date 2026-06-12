<template>
  <div>
    <div class="flex items-center justify-between mb-1.5">
      <span class="text-sm text-slate-500 dark:text-slate-400">{{ label }}</span>
      <span class="text-sm font-medium" :class="valueColor">{{ displayValue }}{{ unit }}</span>
    </div>
    <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
      <div class="h-full rounded-full transition-all duration-500" :class="barColor" :style="{ width: barWidth + '%' }"></div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
  label: String,
  value: [Number, null],
  unit: { type: String, default: '%' },
  max: { type: Number, default: 100 },
  warn: { type: Number, default: 80 },
  crit: { type: Number, default: 95 },
});

const displayValue = computed(() => props.value !== null && props.value !== undefined ? props.value : '-');
const barWidth = computed(() => Math.min((props.value || 0) / props.max * 100, 100));

const barColor = computed(() => {
  if (props.value === null || props.value === undefined) return 'bg-slate-600';
  if (props.value >= props.crit) return 'bg-red-500';
  if (props.value >= props.warn) return 'bg-amber-500';
  return 'bg-green-500';
});

const valueColor = computed(() => {
  if (props.value === null || props.value === undefined) return 'text-slate-500';
  if (props.value >= props.crit) return 'text-red-400';
  if (props.value >= props.warn) return 'text-amber-400';
  return 'text-green-400';
});
</script>
