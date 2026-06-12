<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">OLT Devices</h1>
          <p class="text-slate-400 text-sm mt-0.5">Manage and monitor your Optical Line Terminals</p>
        </div>
        <a href="/olt/create" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white text-sm font-medium rounded-lg transition shadow-lg shadow-blue-500/25">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
          Add OLT
        </a>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px] max-w-sm">
          <input
            v-model="search" @input="debouncedFilter"
            type="text" placeholder="Search by name, IP, hostname..."
            class="w-full px-3 py-2 bg-[#1E293B] border border-slate-700 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <select v-model="statusFilter" @change="applyFilter" class="px-3 py-2 bg-[#1E293B] border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Status</option>
          <option value="online">Online</option>
          <option value="offline">Offline</option>
          <option value="maintenance">Maintenance</option>
        </select>
        <select v-model="vendorFilter" @change="applyFilter" class="px-3 py-2 bg-[#1E293B] border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Vendors</option>
          <option v-for="v in vendors" :key="v.id" :value="v.id">{{ v.name }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-xs text-slate-400 uppercase bg-slate-800/50">
              <tr>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">IP Address</th>
                <th class="px-4 py-3 text-center">Status</th>
                <th class="px-4 py-3 text-left">Vendor / Model</th>
                <th class="px-4 py-3 text-center">ONUs</th>
                <th class="px-4 py-3 text-left">Site</th>
                <th class="px-4 py-3 text-left">Last Seen</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
              <tr v-for="olt in olts.data" :key="olt.id" class="hover:bg-slate-800/30 transition-colors group">
                <td class="px-4 py-3">
                  <a :href="'/olt/' + olt.id" class="font-medium text-white hover:text-blue-400 transition">{{ olt.name }}</a>
                  <p v-if="olt.hostname" class="text-xs text-slate-500 mt-0.5">{{ olt.hostname }}</p>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ olt.ip_address }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium" :class="statusClass(olt.status)">
                    <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(olt.status)"></span>
                    {{ olt.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-slate-300">{{ olt.vendor?.name || '-' }}</span>
                  <span v-if="olt.device_model" class="text-slate-500 text-xs block">{{ olt.device_model.model_number }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="text-green-400">{{ olt.online_onus_count || 0 }}</span>
                  <span class="text-slate-600">/</span>
                  <span class="text-slate-300">{{ olt.onus_count || 0 }}</span>
                </td>
                <td class="px-4 py-3 text-slate-400">{{ olt.site?.name || '-' }}</td>
                <td class="px-4 py-3 text-xs text-slate-500">{{ formatDate(olt.last_seen_at) }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition">
                    <button @click.prevent="syncOlt(olt)" title="Sync" class="p-1.5 rounded-md text-slate-400 hover:text-blue-400 hover:bg-blue-500/10 transition">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
                    </button>
                    <a :href="'/olt/' + olt.id + '/edit'" title="Edit" class="p-1.5 rounded-md text-slate-400 hover:text-amber-400 hover:bg-amber-500/10 transition">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                    </a>
                    <a :href="'/olt/' + olt.id" title="View" class="p-1.5 rounded-md text-slate-400 hover:text-green-400 hover:bg-green-500/10 transition">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="olts.data?.length === 0">
                <td colspan="8" class="px-4 py-12 text-center">
                  <svg class="w-12 h-12 mx-auto text-slate-700 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3" /></svg>
                  <p class="text-slate-500 mb-3">No OLT devices found</p>
                  <a href="/olt/create" class="text-blue-400 hover:text-blue-300 text-sm">+ Add your first OLT</a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="olts.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-700/50">
          <span class="text-xs text-slate-500">
            Showing {{ olts.from }}–{{ olts.to }} of {{ olts.total }}
          </span>
          <div class="flex gap-1">
            <a v-for="link in olts.links" :key="link.label" :href="link.url || '#'"
              class="px-3 py-1 text-xs rounded-md transition"
              :class="link.active ? 'bg-blue-500 text-white' : link.url ? 'text-slate-400 hover:bg-slate-700' : 'text-slate-600 cursor-not-allowed'"
              v-html="link.label"
            />
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

const props = defineProps({
  olts: Object,
  filters: Object,
  vendors: Array,
  sites: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const vendorFilter = ref(props.filters?.vendor_id || '');

let timeout;
const debouncedFilter = () => {
  clearTimeout(timeout);
  timeout = setTimeout(applyFilter, 300);
};

const applyFilter = () => {
  router.get('/olt', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    vendor_id: vendorFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

const syncOlt = (olt) => {
  if (confirm(`Sync OLT "${olt.name}"?`)) {
    router.post(`/olt/${olt.id}/sync`);
  }
};

const statusClass = (s) => ({
  'bg-green-500/10 text-green-400': s === 'online',
  'bg-red-500/10 text-red-400': s === 'offline',
  'bg-amber-500/10 text-amber-400': s === 'maintenance',
  'bg-slate-500/10 text-slate-400': s === 'unknown',
}[true] || 'bg-slate-500/10 text-slate-400');

const statusDot = (s) => ({
  online: 'bg-green-400', offline: 'bg-red-400', maintenance: 'bg-amber-400',
}[s] || 'bg-slate-400');

const formatDate = (d) => d ? new Date(d).toLocaleString() : 'Never';
</script>
