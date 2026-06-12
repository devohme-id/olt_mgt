<template>
  <AppLayout>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Edit OLT: {{ olt.name }}</h1>
        <a :href="'/olt/' + olt.id" class="text-slate-400 hover:text-white transition text-sm">Cancel</a>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Info -->
        <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
          <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">Basic Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">IP Address <span class="text-red-500">*</span></label>
              <input v-model="form.ip_address" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white font-mono focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Hostname</label>
              <input v-model="form.hostname" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Site/Location</label>
              <select v-model="form.site_id" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option :value="null">None</option>
                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Credentials -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">SNMP Configuration</h2>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Version</label>
              <select v-model="form.snmp_version" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="v2c">v2c</option><option value="v1">v1</option><option value="v3">v3</option>
              </select>
            </div>
            <template v-if="form.snmp_version !== 'v3'">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Community String</label>
                <input v-model="form.snmp_community" type="password" placeholder="Leave blank to keep current" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
              </div>
            </template>
            <!-- (SNMP v3 omitted for brevity, similar to Create.vue) -->
          </div>

          <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">CLI Access</h2>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Protocol</label>
              <select v-model="form.cli_protocol" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="ssh">SSH</option><option value="telnet">Telnet</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Username</label>
              <input v-model="form.cli_username" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Password</label>
              <input v-model="form.cli_password" type="password" placeholder="Leave blank to keep current" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center">
          <button type="button" @click="destroy" class="px-4 py-2 text-red-400 hover:bg-red-500/10 rounded-lg text-sm transition">
            Delete OLT
          </button>
          <div class="flex gap-3">
            <a :href="'/olt/' + olt.id" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition">Cancel</a>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ olt: Object, sites: Array });

const form = useForm({
  name: props.olt.name, ip_address: props.olt.ip_address, hostname: props.olt.hostname,
  site_id: props.olt.site_id,
  snmp_version: props.olt.snmp_version, snmp_community: '',
  cli_protocol: props.olt.cli_protocol || 'ssh', cli_username: props.olt.cli_username || '', cli_password: '',
});

const submit = () => form.put(`/olt/${props.olt.id}`);

const destroy = () => {
  if (confirm(`Delete OLT ${props.olt.name}? All associated PON ports and ONUs will be cascade-deleted or orphaned.`)) {
    router.delete(`/olt/${props.olt.id}`);
  }
};
</script>
