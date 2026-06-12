<template>
  <a
    :href="href"
    class="flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-all duration-150"
    :class="active ? 'bg-blue-500/10 text-blue-400 font-medium' : 'text-slate-400 hover:text-white hover:bg-slate-700/50'"
  >
    <span class="flex-shrink-0"><slot name="icon" /></span>
    <span>{{ label }}</span>
    <span v-if="badge" class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full font-medium"
      :class="badgeType === 'danger' ? 'bg-red-500/20 text-red-400' : 'bg-blue-500/20 text-blue-400'"
    >{{ badge }}</span>
  </a>
</template>

<script setup>
defineProps({
  href: String,
  label: String,
  active: Boolean,
  badge: [String, Number],
  badgeType: { type: String, default: 'info' },
});
</script>
