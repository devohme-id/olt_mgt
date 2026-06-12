<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">ONU Devices</h1>
          <p class="text-slate-400 text-sm mt-0.5">Manage Customer Premises Equipment</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px] max-w-sm">
          <input
            v-model="search" @input="debouncedFilter"
            type="text" placeholder="Search MAC, SN, Customer..."
            class="w-full px-3 py-2 bg-[#1E293B] border border-slate-700 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <select v-model="statusFilter" @change="applyFilter" class="px-3 py-2 bg-[#1E293B] border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Status</option>
          <option value="online">Online</option>
          <option value="offline">Offline</option>
          <option value="los">LOS (Loss of Signal)</option>
        </select>
        <select v-model="oltFilter" @change="applyFilter" class="px-3 py-2 bg-[#1E293B] border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 max-w-[200px] truncate">
          <option value="">All OLTs</option>
          <option v-for="o in olts" :key="o.id" :value="o.id">{{ o.name }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-xs text-slate-400 uppercase bg-slate-800/50">
              <tr>
                <th class="px-4 py-3 text-left">ONU ID</th>
                <th class="px-4 py-3 text-left">Hardware (SN / MAC)</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-left">OLT / Port</th>
                <th class="px-4 py-3 text-right">Optical (Rx)</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
              <tr v-for="onu in onus.data" :key="onu.id" class="hover:bg-slate-800/30 transition-colors group">
                <td class="px-4 py-3">
                  <a :href="'/onu/' + onu.id" class="font-medium text-white hover:text-blue-400">{{ onu.onu_index }}</a>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-300">
                  <div class="text-white">{{ onu.serial_number || '-' }}</div>
                  <div class="text-slate-500">{{ onu.mac_address || '-' }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="text-white">{{ onu.customer_name || 'Unassigned' }}</div>
                  <div class="text-xs text-slate-500">{{ onu.customer_id }}</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider" :class="statusClass(onu.status)">
                    {{ onu.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-400">
                  <div>{{ onu.olt?.name }}</div>
                  <div class="text-xs">{{ onu.pon_port?.port_name || `Port ${onu.pon_port_id}` }}</div>
                </td>
                <td class="px-4 py-3 text-right font-mono" :class="rxPowerClass(onu.optical_rx_power)">
                  {{ formatPower(onu.optical_rx_power) }}
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                    <button @click="rebootOnu(onu)" title="Reboot" class="text-slate-400 hover:text-amber-400">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                    </button>
                    <a :href="'/onu/' + onu.id" class="text-slate-400 hover:text-blue-400">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="onus.data?.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                  No ONUs found matching your filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div v-if="onus.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-700/50">
          <span class="text-xs text-slate-500">Showing {{ onus.from }}–{{ onus.to }} of {{ onus.total }}</span>
          <div class="flex gap-1">
            <a v-for="link in onus.links" :key="link.label" :href="link.url || '#'"
              class="px-3 py-1 text-xs rounded-md transition"
              :class="link.active ? 'bg-blue-500 text-white' : link.url ? 'text-slate-400 hover:bg-slate-700' : 'text-slate-600 cursor-not-allowed'"
              v-html="link.label" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ onus: Object, filters: Object, olts: Array });

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const oltFilter = ref(props.filters?.olt_id || '');

let timeout;
const debouncedFilter = () => {
  clearTimeout(timeout);
  timeout = setTimeout(applyFilter, 300);
};

const applyFilter = () => {
  router.get('/onu', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    olt_id: oltFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

const rebootOnu = (onu) => {
  if (confirm(`Reboot ONU ${onu.serial_number}?`)) router.post(`/onu/${onu.id}/reboot`);
};

const statusClass = (s) => ({
  online: 'bg-green-500/20 text-green-400',
  offline: 'bg-slate-500/20 text-slate-400',
  los: 'bg-red-500/20 text-red-400 animate-pulse',
}[s] || 'bg-slate-500/20 text-slate-400');

const rxPowerClass = (p) => {
  if (!p) return 'text-slate-600';
  if (p < -27 || p > -8) return 'text-red-400 font-bold';
  if (p < -24) return 'text-amber-400';
  return 'text-green-400';
};

const formatPower = (p) => p ? `${p} dBm` : '-';
</script>
