<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Alarm Management</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Monitor and respond to network incidents</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-3">
        <select v-model="filters.status" @change="applyFilter" class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
          <option value="">All Statuses</option>
          <option value="active">Active</option>
          <option value="acknowledged">Acknowledged</option>
          <option value="cleared">Cleared</option>
        </select>
        <select v-model="filters.severity" @change="applyFilter" class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
          <option value="">All Severities</option>
          <option value="critical">Critical</option>
          <option value="major">Major</option>
          <option value="minor">Minor</option>
          <option value="warning">Warning</option>
          <option value="info">Info</option>
        </select>
        <select v-model="filters.entity_type" @change="applyFilter" class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500">
          <option value="">All Entities</option>
          <option value="olt">OLT</option>
          <option value="onu">ONU</option>
          <option value="system">System</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-100 dark:bg-slate-800/50">
              <tr>
                <th class="px-4 py-3 text-left">Time</th>
                <th class="px-4 py-3 text-left">Severity</th>
                <th class="px-4 py-3 text-left">Message</th>
                <th class="px-4 py-3 text-left">Source</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
              <tr v-for="alarm in alarms.data" :key="alarm.id" class="hover:bg-slate-50 dark:hover:bg-slate-50 dark:bg-slate-800/30 transition group">
                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ formatDate(alarm.created_at) }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase" :class="severityClass(alarm.severity)">
                    {{ alarm.severity }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-900 dark:text-white max-w-md truncate" :title="alarm.message">{{ alarm.message }}</td>
                <td class="px-4 py-3">
                  <span class="uppercase text-xs font-bold text-slate-500 mr-1">{{ alarm.entity_type }}</span>
                  <a v-if="alarm.entity_type === 'olt'" :href="'/olt/' + alarm.entity_id" class="text-blue-400 hover:underline">{{ alarm.olt?.name || alarm.entity_id }}</a>
                  <a v-else-if="alarm.entity_type === 'onu'" :href="'/onu/' + alarm.entity_id" class="text-blue-400 hover:underline">{{ alarm.onu?.serial_number || alarm.entity_id }}</a>
                  <span v-else class="text-slate-700 dark:text-slate-300">System</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(alarm.status)">
                    {{ alarm.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                    <button v-if="alarm.status === 'active'" @click="ackAlarm(alarm)" class="text-amber-400 hover:text-amber-300 text-xs font-medium bg-amber-500/10 px-2 py-1 rounded">Ack</button>
                    <button v-if="alarm.status !== 'cleared'" @click="resolveAlarm(alarm)" class="text-green-400 hover:text-green-300 text-xs font-medium bg-green-500/10 px-2 py-1 rounded">Resolve</button>
                  </div>
                </td>
              </tr>
              <tr v-if="alarms.data?.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-slate-500">No alarms found.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="alarms.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-200 dark:border-slate-700/50">
          <span class="text-xs text-slate-500">Showing {{ alarms.from }}–{{ alarms.to }} of {{ alarms.total }}</span>
          <div class="flex gap-1">
            <a v-for="link in alarms.links" :key="link.label" :href="link.url || '#'"
              class="px-3 py-1 text-xs rounded-md transition"
              :class="link.active ? 'bg-amber-500 text-slate-900 dark:text-white' : link.url ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' : 'text-slate-600 cursor-not-allowed'"
              v-html="link.label" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ alarms: Object, filters: Object });

const filters = reactive({
  status: props.filters?.status || '',
  severity: props.filters?.severity || '',
  entity_type: props.filters?.entity_type || '',
});

const applyFilter = () => {
  router.get('/alarms', {
    status: filters.status || undefined,
    severity: filters.severity || undefined,
    entity_type: filters.entity_type || undefined,
  }, { preserveState: true, replace: true });
};

const ackAlarm = (alarm) => {
  router.post(`/alarms/${alarm.id}/ack`);
};

const resolveAlarm = (alarm) => {
  const notes = prompt('Enter resolution notes (optional):');
  if (notes !== null) {
    router.post(`/alarms/${alarm.id}/resolve`, { notes });
  }
};

const severityClass = (s) => ({
  critical: 'bg-red-500/20 text-red-400 border border-red-500/30',
  major: 'bg-orange-500/20 text-orange-400 border border-orange-500/30',
  minor: 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
  warning: 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
  info: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border border-slate-500/30',
}[s] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400');

const statusClass = (s) => ({
  active: 'bg-red-500/10 text-red-400',
  acknowledged: 'bg-amber-500/10 text-amber-400',
  cleared: 'bg-green-500/10 text-green-400',
}[s] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400');

const formatDate = (d) => d ? new Date(d).toLocaleString() : '-';
</script>
