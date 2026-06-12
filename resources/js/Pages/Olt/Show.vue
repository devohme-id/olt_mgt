<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a href="/olt" class="text-slate-500 hover:text-slate-900 dark:text-white transition"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg></a>
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ olt.name }}</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-mono">{{ olt.ip_address }}</p>
          </div>
          <span class="ml-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium" :class="statusClass">
            <span class="w-1.5 h-1.5 rounded-full" :class="statusDot"></span>
            {{ olt.status }}
          </span>
        </div>
        <div class="flex items-center gap-2">
          <button @click="syncOlt" class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-500/10 text-blue-400 text-sm rounded-lg hover:bg-blue-500/20 transition border border-blue-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
            Sync
          </button>
          <button @click="syncOnus" class="inline-flex items-center gap-1.5 px-3 py-2 bg-cyan-500/10 text-cyan-400 text-sm rounded-lg hover:bg-cyan-500/20 transition border border-cyan-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0" /></svg>
            Sync ONUs
          </button>
          <button @click="backupConfig" class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-500/10 text-green-400 text-sm rounded-lg hover:bg-green-500/20 transition border border-green-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
            Backup
          </button>
          <a :href="'/olt/' + olt.id + '/edit'" class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-500/10 text-amber-400 text-sm rounded-lg hover:bg-amber-500/20 transition border border-amber-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
            Edit
          </a>
        </div>
      </div>

      <!-- Info Cards Row -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <InfoCard label="Total ONUs" :value="olt.onus_count || 0" icon="users" />
        <InfoCard label="Online ONUs" :value="olt.online_onus_count || 0" icon="check" color="green" />
        <InfoCard label="Offline ONUs" :value="olt.offline_onus_count || 0" icon="x" color="red" />
        <InfoCard label="PON Ports" :value="olt.pon_ports?.length || 0" icon="server" color="cyan" />
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Device Info -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5">
          <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-4">Device Information</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Vendor</dt><dd class="text-slate-900 dark:text-white">{{ olt.vendor?.name || '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Model</dt><dd class="text-slate-900 dark:text-white">{{ olt.device_model?.model_number || '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Firmware</dt><dd class="text-slate-900 dark:text-white text-xs">{{ olt.firmware_profile?.version || '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Site</dt><dd class="text-slate-900 dark:text-white">{{ olt.site?.name || '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">SNMP</dt><dd class="text-slate-900 dark:text-white">{{ olt.snmp_version }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">CLI</dt><dd class="text-slate-900 dark:text-white">{{ olt.cli_protocol || 'N/A' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Last Polled</dt><dd class="text-slate-700 dark:text-slate-300 text-xs">{{ formatDate(olt.last_polled_at) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Last Seen</dt><dd class="text-slate-700 dark:text-slate-300 text-xs">{{ formatDate(olt.last_seen_at) }}</dd></div>
          </dl>
        </div>

        <!-- System Health -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50 p-5">
          <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-4">System Health</h2>
          <div class="space-y-4">
            <MetricBar label="CPU Usage" :value="systemInfo.cpu_usage" unit="%" :warn="80" :crit="95" />
            <MetricBar label="Memory Usage" :value="systemInfo.memory_usage" unit="%" :warn="80" :crit="95" />
            <MetricBar label="Temperature" :value="systemInfo.temperature" unit="°C" :warn="60" :crit="75" :max="100" />
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">Uptime</span>
              <span class="text-slate-900 dark:text-white">{{ formatUptime(systemInfo.uptime) }}</span>
            </div>
          </div>
        </div>

        <!-- PON Ports -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700/50">
          <div class="p-4 border-b border-slate-200 dark:border-slate-700/50">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">PON Ports</h2>
          </div>
          <div class="divide-y divide-slate-700/30">
            <div v-for="port in olt.pon_ports" :key="port.id" class="px-4 py-3 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-50 dark:bg-slate-800/30 transition">
              <div>
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ port.port_name }}</p>
                <p class="text-xs text-slate-500">Index: {{ port.port_index }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm">
                  <span class="text-green-400">{{ port.online_onus_count || 0 }}</span>
                  <span class="text-slate-600"> / </span>
                  <span class="text-slate-700 dark:text-slate-300">{{ port.onus_count || 0 }}</span>
                </p>
                <p class="text-[10px] text-slate-500">ONUs</p>
              </div>
            </div>
            <div v-if="!olt.pon_ports?.length" class="px-4 py-8 text-center text-slate-500 text-sm">
              No PON ports configured
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InfoCard from '@/Components/InfoCard.vue';
import MetricBar from '@/Components/MetricBar.vue';

const props = defineProps({ olt: Object, metrics: Array });

const systemInfo = computed(() => props.olt.system_info || {});

const statusClass = computed(() => ({
  online: 'bg-green-500/10 text-green-400',
  offline: 'bg-red-500/10 text-red-400',
  maintenance: 'bg-amber-500/10 text-amber-400',
}[props.olt.status] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400'));

const statusDot = computed(() => ({
  online: 'bg-green-400', offline: 'bg-red-400', maintenance: 'bg-amber-400',
}[props.olt.status] || 'bg-slate-400'));

const syncOlt = () => router.post(`/olt/${props.olt.id}/sync`);
const syncOnus = () => router.post(`/olt/${props.olt.id}/sync-onus`);
const backupConfig = () => router.post(`/olt/${props.olt.id}/backup`);

const formatDate = (d) => d ? new Date(d).toLocaleString() : 'Never';
const formatUptime = (seconds) => {
  if (!seconds) return '-';
  const d = Math.floor(seconds / 86400);
  const h = Math.floor((seconds % 86400) / 3600);
  return d > 0 ? `${d}d ${h}h` : `${h}h`;
};
</script>
