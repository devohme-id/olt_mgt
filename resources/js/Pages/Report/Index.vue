<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Reports & Analytics</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Generate and download network reports</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Optical Power Report -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-6 flex flex-col justify-between">
          <div>
            <div class="w-12 h-12 rounded-lg bg-cyan-500/10 flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">ONU Optical Report</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Export a complete list of ONUs ordered by Rx optical power. Useful for finding degraded connections before customers complain.</p>
          </div>
          <div class="mt-6">
            <a href="/reports/export-optical" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-slate-900 dark:text-white text-sm font-medium rounded-lg transition w-full justify-center">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
              Download CSV
            </a>
          </div>
        </div>

        <!-- Alarms Report -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-6 flex flex-col justify-between">
          <div>
            <div class="w-12 h-12 rounded-lg bg-amber-500/10 flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2.25m0 0v2.25m0-2.25h2.25m-2.25 0H9.75m1.5-6a9 9 0 110 18 9 9 0 010-18z" /></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Alarm History Report</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Export historical alarm data for incident review and SLA calculations.</p>
            
            <form @submit.prevent="exportAlarms" class="mt-4 grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Start Date</label>
                <input v-model="alarmStart" type="date" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">End Date</label>
                <input v-model="alarmEnd" type="date" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-1.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
              </div>
            </form>
          </div>
          <div class="mt-6">
            <button @click="exportAlarms" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-slate-900 dark:text-white text-sm font-medium rounded-lg transition w-full justify-center">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
              Download CSV
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ overview: Object });

const alarmStart = ref(new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0]);
const alarmEnd = ref(new Date().toISOString().split('T')[0]);

const exportAlarms = () => {
  window.location.href = `/reports/export-alarms?start_date=${alarmStart.value}&end_date=${alarmEnd.value}`;
};
</script>
