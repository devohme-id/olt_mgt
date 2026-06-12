<template>
  <AppLayout>
    <div class="p-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">SNMP OID Explorer</h1>
          <p class="text-slate-400 text-sm mt-0.5">Advanced diagnostics and raw SNMP querying</p>
        </div>
      </div>

      <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6">
        <form @submit.prevent="runQuery" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Target OLT</label>
            <select v-model="form.olt_id" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" required>
              <option :value="null" disabled>Select an OLT</option>
              <option v-for="olt in olts" :key="olt.id" :value="olt.id">{{ olt.name }} ({{ olt.ip_address }})</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-300 mb-1">Target OID</label>
            <input v-model="form.oid" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white font-mono focus:ring-2 focus:ring-blue-500 outline-none" placeholder=".1.3.6.1.2.1.1.1.0" required>
          </div>
          <div class="flex gap-2">
            <select v-model="form.method" class="w-24 bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
              <option value="get">GET</option>
              <option value="walk">WALK</option>
            </select>
            <button type="submit" :disabled="loading" class="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition disabled:opacity-50">
              {{ loading ? 'Querying...' : 'Execute' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Results -->
      <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
        <span class="font-bold">Error:</span> {{ error }}
      </div>

      <div v-if="results" class="bg-[#1E293B] rounded-xl border border-slate-700/50 overflow-hidden">
        <div class="p-4 border-b border-slate-700/50 flex justify-between items-center bg-slate-800/50">
          <h2 class="text-white font-medium">Query Results</h2>
          <span class="text-xs text-slate-400">Time: {{ timeMs }}ms | Count: {{ results.length }}</span>
        </div>
        <div class="overflow-x-auto max-h-[600px]">
          <table class="w-full text-sm text-left">
            <thead class="text-xs text-slate-400 uppercase bg-slate-800/80 sticky top-0">
              <tr>
                <th class="px-4 py-3">OID</th>
                <th class="px-4 py-3 w-32">Type</th>
                <th class="px-4 py-3">Value</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/30">
              <tr v-for="res in results" :key="res.oid" class="hover:bg-slate-800/30 font-mono text-xs">
                <td class="px-4 py-2 text-blue-400 break-all">{{ res.oid }}</td>
                <td class="px-4 py-2 text-slate-500">{{ res.type }}</td>
                <td class="px-4 py-2 text-slate-300 break-all">{{ res.value }}</td>
              </tr>
              <tr v-if="results.length === 0">
                <td colspan="3" class="px-4 py-8 text-center text-slate-500">No results found for this OID.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ olts: Array });

const form = ref({ olt_id: null, oid: '.1.3.6.1.2.1.1.1.0', method: 'get' });
const loading = ref(false);
const results = ref(null);
const error = ref(null);
const timeMs = ref(0);

const runQuery = async () => {
  loading.value = true;
  error.value = null;
  results.value = null;
  
  try {
    const res = await fetch('/tools/explorer', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
      body: JSON.stringify(form.value)
    });
    
    const data = await res.json();
    if (data.success) {
      results.value = data.data;
      timeMs.value = data.time_ms;
    } else {
      error.value = data.error;
    }
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};
</script>
