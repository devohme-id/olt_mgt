<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <a href="/onu" class="text-slate-500 hover:text-slate-900 dark:text-white transition"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg></a>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ onu.name || onu.mac_address }}</h1>
              <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider" :class="statusClass(onu.status)">{{ onu.status }}</span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">ONU {{ onu.onu_index }} • OLT: <a :href="'/olt/' + onu.olt_id" class="text-blue-400 hover:underline">{{ onu.olt?.name }}</a></p>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="rebootOnu" class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-500/10 text-amber-400 text-sm font-medium rounded-lg hover:bg-amber-500/20 transition border border-amber-500/20">
            Reboot
          </button>
          <button @click="deleteOnu" class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-500/10 text-red-400 text-sm font-medium rounded-lg hover:bg-red-500/20 transition border border-red-500/20">
            Delete
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Optical Power & Profile -->
        <div class="space-y-6">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-6 flex flex-col items-center text-center justify-center min-h-[200px]">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Optical Rx Power</h2>
            <div class="text-5xl font-black font-mono tracking-tighter" :class="rxPowerClass(onu.rx_power_dbm)">
              {{ onu.rx_power_dbm ? `${onu.rx_power_dbm}` : '--' }} <span class="text-lg font-medium text-slate-500">dBm</span>
            </div>
            <p class="text-xs text-slate-500 mt-4">Last updated: {{ formatDate(onu.last_seen_at) }}</p>
            <p v-if="onu.distance_meters !== null" class="text-xs text-slate-500 dark:text-slate-400 mt-2 font-medium">Distance: {{ onu.distance_meters }} m</p>
            <button @click="refreshOptical" :disabled="isRefreshing" class="mt-4 px-4 py-1.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full text-xs hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:text-white transition disabled:opacity-50">
              {{ isRefreshing ? 'Polling...' : 'Poll Realtime' }}
            </button>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-6">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-4">Service Profile</h2>
            <div class="flex justify-between items-center bg-slate-100 dark:bg-slate-800/50 rounded-lg p-3 border border-slate-200 dark:border-slate-700/50">
              <div>
                <p class="text-slate-900 dark:text-white font-medium">{{ onu.service_profile?.name || 'Unassigned' }}</p>
                <p class="text-xs text-slate-500 mt-1" v-if="onu.service_profile">
                  Up: {{ onu.service_profile.upstream_bw_kbps }}Kbps / Down: {{ onu.service_profile.downstream_bw_kbps }}Kbps
                </p>
              </div>
              <button @click="showProfileModal = true" class="text-blue-400 hover:text-blue-300 text-sm font-medium">Change</button>
            </div>
          </div>
        </div>

        <!-- Customer & Device Data -->
        <div class="xl:col-span-2 space-y-6">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Customer Information</h2>
              <button @click="showEditModal = true" class="text-blue-400 hover:text-blue-300 text-sm font-medium">Edit</button>
            </div>
            <div class="grid grid-cols-2 gap-6">
              <div><p class="text-xs text-slate-500">Customer Name</p><p class="text-slate-900 dark:text-white font-medium mt-1">{{ onu.customer_name || onu.name || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Customer ID</p><p class="text-slate-900 dark:text-white font-medium mt-1">{{ onu.customer_id || '-' }}</p></div>
              <div class="col-span-2"><p class="text-xs text-slate-500">Description</p><p class="text-slate-900 dark:text-white mt-1 text-sm">{{ onu.description || '-' }}</p></div>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-6">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-6">Hardware Details</h2>
            <div class="grid grid-cols-2 gap-6">
              <div><p class="text-xs text-slate-500">Serial Number</p><p class="text-slate-900 dark:text-white font-mono mt-1">{{ onu.serial_number || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">MAC Address</p><p class="text-slate-900 dark:text-white font-mono mt-1">{{ onu.mac_address || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">ONU Model / Type</p><p class="text-slate-900 dark:text-white mt-1">{{ onu.onu_type || onu.onu_model || 'Auto-detect' }}</p></div>
              <div><p class="text-xs text-slate-500">Device Type</p><p class="text-slate-900 dark:text-white mt-1">{{ onu.device_type || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Firmware</p><p class="text-slate-900 dark:text-white mt-1">{{ onu.firmware_version || '-' }}</p></div>
              <div><p class="text-xs text-slate-500">Registration Status</p><p class="text-slate-900 dark:text-white mt-1 capitalize">{{ onu.auth_status }}</p></div>
              <div><p class="text-xs text-slate-500">Registered At</p><p class="text-slate-900 dark:text-white mt-1">{{ formatDate(onu.registered_at) }}</p></div>
              <div><p class="text-xs text-slate-500">Deregistered At</p><p class="text-slate-900 dark:text-white mt-1">{{ formatDate(onu.deregistered_at) }}</p></div>
            </div>
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

const props = defineProps({ onu: Object, opticalHistory: Array, profiles: Array });

const isRefreshing = ref(false);
const showEditModal = ref(false); // Can be implemented fully later
const showProfileModal = ref(false); // Can be implemented fully later

const refreshOptical = async () => {
  isRefreshing.value = true;
  try {
    const res = await fetch(`/onu/${props.onu.id}/optical`);
    if (res.ok) router.reload({ only: ['onu'] });
  } finally {
    isRefreshing.value = false;
  }
};

const rebootOnu = () => {
  if (confirm('Are you sure you want to reboot this ONU? This will interrupt customer service.')) {
    router.post(`/onu/${props.onu.id}/reboot`);
  }
};

const deleteOnu = () => {
  if (confirm('Delete this ONU from NMS?')) {
    router.delete(`/onu/${props.onu.id}`);
  }
};

const statusClass = (s) => ({
  online: 'bg-green-500/20 text-green-400',
  offline: 'bg-slate-500/20 text-slate-500 dark:text-slate-400',
  los: 'bg-red-500/20 text-red-400',
}[s] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400');

const rxPowerClass = (p) => {
  if (!p) return 'text-slate-600';
  if (p < -27 || p > -8) return 'text-red-400';
  if (p < -24) return 'text-amber-400';
  return 'text-green-400';
};

const formatDate = (d) => d ? new Date(d).toLocaleString() : 'Never';
</script>
