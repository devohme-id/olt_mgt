<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">System Audit Log</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Track all operations and user activity</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex items-center gap-3">
        <select v-model="filters.module" @change="applyFilter" class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Modules</option>
          <option value="auth">Authentication</option>
          <option value="olt">OLT</option>
          <option value="onu">ONU</option>
          <option value="alarm">Alarm</option>
          <option value="system">System</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left">
            <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-100 dark:bg-slate-800/50">
              <tr>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Module</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Entity ID</th>
                <th class="px-4 py-3">IP Address</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-50 dark:bg-slate-800/30 transition text-slate-700 dark:text-slate-300">
                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ formatDate(log.created_at) }}</td>
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ log.user?.name || 'System' }}</td>
                <td class="px-4 py-3 uppercase text-xs">{{ log.module }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium" :class="actionClass(log.action)">
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs font-mono">{{ log.entity_id || '-' }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ log.ip_address }}</td>
              </tr>
              <tr v-if="logs.data?.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-slate-500">No audit logs found.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="logs.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-200 dark:border-slate-700/50">
          <span class="text-xs text-slate-500">Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}</span>
          <div class="flex gap-1">
            <a v-for="link in logs.links" :key="link.label" :href="link.url || '#'"
              class="px-3 py-1 text-xs rounded-md transition"
              :class="link.active ? 'bg-blue-500 text-slate-900 dark:text-white' : link.url ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' : 'text-slate-600 cursor-not-allowed'"
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

const props = defineProps({ logs: Object, filters: Object });

const filters = reactive({
  module: props.filters?.module || '',
});

const applyFilter = () => {
  router.get('/audit', {
    module: filters.module || undefined,
  }, { preserveState: true, replace: true });
};

const actionClass = (a) => {
  const map = {
    create: 'bg-green-500/10 text-green-400',
    update: 'bg-blue-500/10 text-blue-400',
    delete: 'bg-red-500/10 text-red-400',
    login: 'bg-indigo-500/10 text-indigo-400',
    logout: 'bg-slate-500/10 text-slate-500 dark:text-slate-400',
    sync: 'bg-cyan-500/10 text-cyan-400',
    acknowledge: 'bg-amber-500/10 text-amber-400',
    resolve: 'bg-green-500/10 text-green-400',
  };
  return map[a] || 'bg-slate-500/10 text-slate-700 dark:text-slate-300';
};

const formatDate = (d) => d ? new Date(d).toLocaleString() : '-';
</script>
