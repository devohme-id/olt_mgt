<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5 hover:border-slate-300 dark:border-slate-600 transition">
    <div class="flex items-center justify-between mb-3">
      <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ label }}</span>
      <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="bgClass">
        <svg v-if="icon === 'users'" class="w-4 h-4" :class="iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
        <svg v-else-if="icon === 'check'" class="w-4 h-4" :class="iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <svg v-else-if="icon === 'x'" class="w-4 h-4" :class="iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <svg v-else class="w-4 h-4" :class="iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3" /></svg>
      </div>
    </div>
    <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ value }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({ label: String, value: [String, Number], icon: String, color: { type: String, default: 'blue' } });

const bgClass = computed(() => ({
  blue: 'bg-blue-500/10', green: 'bg-green-500/10', red: 'bg-red-500/10', cyan: 'bg-cyan-500/10', amber: 'bg-amber-500/10',
}[props.color]));
const iconClass = computed(() => ({
  blue: 'text-blue-400', green: 'text-green-400', red: 'text-red-400', cyan: 'text-cyan-400', amber: 'text-amber-400',
}[props.color]));
</script>
