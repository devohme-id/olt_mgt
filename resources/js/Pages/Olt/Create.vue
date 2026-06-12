<template>
  <AppLayout>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">Add New OLT</h1>
        <a href="/olt" class="text-slate-400 hover:text-white transition text-sm">Cancel</a>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Info -->
        <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
          <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">Basic Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Name <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" required>
              <div v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">IP Address <span class="text-red-500">*</span></label>
              <input v-model="form.ip_address" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white font-mono focus:ring-2 focus:ring-blue-500 outline-none" required placeholder="192.168.1.10">
              <div v-if="form.errors.ip_address" class="text-red-400 text-xs mt-1">{{ form.errors.ip_address }}</div>
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

        <!-- Hardware Info -->
        <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
          <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">Hardware</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Vendor <span class="text-red-500">*</span></label>
              <select v-model="form.vendor_id" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" required>
                <option :value="null" disabled>Select Vendor</option>
                <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{ vendor.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Device Model <span class="text-red-500">*</span></label>
              <select v-model="form.device_model_id" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" :disabled="!form.vendor_id" required>
                <option :value="null" disabled>Select Model</option>
                <option v-for="model in filteredModels" :key="model.id" :value="model.id">{{ model.model_number }} ({{ model.max_pon_ports }} ports)</option>
              </select>
            </div>
            <div v-if="form.device_model_id">
              <label class="block text-sm font-medium text-slate-300 mb-1">Firmware Profile</label>
              <select v-model="form.firmware_profile_id" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option :value="null">None</option>
                <option v-for="profile in filteredFirmwares" :key="profile.id" :value="profile.id">{{ profile.version }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Credentials -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- SNMP -->
          <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">SNMP Configuration</h2>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Version</label>
              <select v-model="form.snmp_version" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="v2c">v2c</option>
                <option value="v1">v1</option>
                <option value="v3">v3</option>
              </select>
            </div>
            
            <template v-if="form.snmp_version !== 'v3'">
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Community String <span class="text-red-500">*</span></label>
                <input v-model="form.snmp_community" type="password" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" required>
              </div>
            </template>
            
            <template v-else>
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-1">Username <span class="text-red-500">*</span></label>
                <input v-model="form.snmp_v3_username" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" required>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-1">Auth Protocol</label>
                  <select v-model="form.snmp_v3_auth_protocol" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <option :value="null">None</option>
                    <option value="MD5">MD5</option><option value="SHA">SHA</option><option value="SHA256">SHA256</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-1">Auth Password</label>
                  <input v-model="form.snmp_v3_auth_password" type="password" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" :disabled="!form.snmp_v3_auth_protocol">
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-1">Priv Protocol</label>
                  <select v-model="form.snmp_v3_priv_protocol" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" :disabled="!form.snmp_v3_auth_protocol">
                    <option :value="null">None</option>
                    <option value="DES">DES</option><option value="AES">AES</option><option value="AES256">AES256</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-300 mb-1">Priv Password</label>
                  <input v-model="form.snmp_v3_priv_password" type="password" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" :disabled="!form.snmp_v3_priv_protocol">
                </div>
              </div>
            </template>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">SNMP Port</label>
              <input v-model="form.snmp_port" type="number" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" placeholder="161">
            </div>
          </div>

          <!-- CLI -->
          <div class="bg-[#1E293B] rounded-xl border border-slate-700/50 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white border-b border-slate-700/50 pb-2">CLI Access (Optional)</h2>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Protocol</label>
              <select v-model="form.cli_protocol" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="ssh">SSH</option>
                <option value="telnet">Telnet</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Username</label>
              <input v-model="form.cli_username" type="text" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Password</label>
              <input v-model="form.cli_password" type="password" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Enable/Privilege Password</label>
              <input v-model="form.cli_enable_password" type="password" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">CLI Port</label>
              <input v-model="form.cli_port" type="number" class="w-full bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white focus:ring-2 focus:ring-blue-500 outline-none" :placeholder="form.cli_protocol === 'ssh' ? '22' : '23'">
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <a href="/olt" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition">Cancel</a>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
            {{ form.processing ? 'Saving...' : 'Add OLT' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ vendors: Array, deviceModels: Array, firmwareProfiles: Array, sites: Array });

const form = useForm({
  name: '', ip_address: '', hostname: '',
  vendor_id: null, device_model_id: null, site_id: null, firmware_profile_id: null,
  snmp_version: 'v2c', snmp_community: 'public', snmp_port: 161,
  snmp_v3_username: '', snmp_v3_auth_protocol: null, snmp_v3_auth_password: '', snmp_v3_priv_protocol: null, snmp_v3_priv_password: '',
  cli_protocol: 'ssh', cli_username: '', cli_password: '', cli_enable_password: '', cli_port: 22,
  description: '',
});

const filteredModels = computed(() => form.vendor_id ? props.deviceModels.filter(m => m.vendor_id === form.vendor_id) : []);
const filteredFirmwares = computed(() => form.device_model_id ? props.firmwareProfiles.filter(p => p.device_model_id === form.device_model_id) : []);

const submit = () => form.post('/olt');
</script>
