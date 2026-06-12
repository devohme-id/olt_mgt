<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Network Overview</h1>
        <div class="text-sm text-slate-500 dark:text-slate-400">
          Last updated: <span class="text-slate-700 dark:text-slate-300 font-medium">{{ currentTime }}</span>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- OLT Stats -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5 hover:border-blue-500/50 transition">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">OLT Devices</h3>
            <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3" /></svg>
            </div>
          </div>
          <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.total_olts }}</span>
            <span class="text-sm text-green-400 font-medium mb-1">{{ stats.online_olts }} Online</span>
          </div>
        </div>

        <!-- ONU Stats -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5 hover:border-cyan-500/50 transition">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">ONU Terminals</h3>
            <div class="w-10 h-10 rounded-lg bg-cyan-500/10 flex items-center justify-center">
              <svg class="w-5 h-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0" /></svg>
            </div>
          </div>
          <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.total_onus }}</span>
            <span class="text-sm text-red-400 font-medium mb-1">{{ stats.offline_onus }} Offline</span>
          </div>
        </div>

        <!-- Alarms -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5 hover:border-amber-500/50 transition">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">Active Alarms</h3>
            <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
              <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022" /></svg>
            </div>
          </div>
          <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.active_alarms }}</span>
            <span class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-1">Requires Attention</span>
          </div>
        </div>

        <!-- Health -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5 hover:border-green-500/50 transition">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-500 dark:text-slate-400 text-sm font-medium">Network Health</h3>
            <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center">
              <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>
          <div class="flex items-end gap-3">
            <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ stats.network_health }}%</span>
            <span class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-1">Overall Stability</span>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- OLT List -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50">
          <div class="p-4 border-b border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
            <h2 class="text-slate-900 dark:text-white font-semibold">Managed OLTs</h2>
            <a href="/olt" class="text-sm text-blue-400 hover:text-blue-300">View All →</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-100 dark:bg-slate-800/50">
                <tr>
                  <th class="px-4 py-3">Device Name</th>
                  <th class="px-4 py-3">IP Address</th>
                  <th class="px-4 py-3">Vendor</th>
                  <th class="px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-700/30">
                <tr v-for="olt in olts" :key="olt.id" class="hover:bg-slate-50 dark:hover:bg-slate-100 dark:bg-slate-800/50 transition">
                  <td class="px-4 py-3">
                    <a :href="'/olt/' + olt.id" class="font-medium text-slate-900 dark:text-white hover:text-blue-400">{{ olt.name }}</a>
                  </td>
                  <td class="px-4 py-3 font-mono text-slate-700 dark:text-slate-300 text-xs">{{ olt.ip_address }}</td>
                  <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ olt.vendor?.name }}</td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1.5">
                      <span class="w-2 h-2 rounded-full" :class="olt.status === 'online' ? 'bg-green-400' : 'bg-red-400'"></span>
                      <span class="capitalize text-slate-700 dark:text-slate-300">{{ olt.status }}</span>
                    </span>
                  </td>
                </tr>
                <tr v-if="olts.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-slate-500">No OLT devices added yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recent Alarms -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50">
          <div class="p-4 border-b border-slate-200 dark:border-slate-700/50 flex items-center justify-between">
            <h2 class="text-slate-900 dark:text-white font-semibold">Recent Alarms</h2>
            <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-500/20 text-red-400 animate-pulse">Live</span>
          </div>
          <div class="p-4 flex flex-col items-center justify-center h-[300px] text-center">
            <svg class="w-12 h-12 text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-slate-500 dark:text-slate-400 font-medium">No Active Alarms</p>
            <p class="text-slate-500 text-sm mt-1">Your network is running smoothly</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ stats: Object, olts: Array });

const currentTime = ref(new Date().toLocaleTimeString());
let timer;

onMounted(() => {
  timer = setInterval(() => {
    currentTime.value = new Date().toLocaleTimeString();
  }, 1000);
});

onUnmounted(() => clearInterval(timer));
</script>
