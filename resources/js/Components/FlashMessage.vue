<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 translate-y-[-8px]"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-[-8px]"
  >
    <div v-if="show" class="fixed top-4 right-4 z-50 max-w-md">
      <div
        class="flex items-center gap-3 px-4 py-3 rounded-lg border shadow-xl backdrop-blur-sm"
        :class="styles[type]"
      >
        <component :is="icons[type]" class="w-5 h-5 flex-shrink-0" />
        <p class="text-sm font-medium flex-1">{{ message }}</p>
        <button @click="show = false" class="text-current opacity-60 hover:opacity-100 transition">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, computed, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success');

const styles = {
  success: 'bg-green-500/10 border-green-500/30 text-green-400',
  error: 'bg-red-500/10 border-red-500/30 text-red-400',
  warning: 'bg-amber-500/10 border-amber-500/30 text-amber-400',
};

const CheckIcon = { render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })]) };
const ErrorIcon = { render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z' })]) };
const WarningIcon = { render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z' })]) };

const icons = { success: CheckIcon, error: ErrorIcon, warning: WarningIcon };

watch(
  () => page.props.flash,
  (flash) => {
    for (const t of ['success', 'error', 'warning']) {
      if (flash?.[t]) {
        message.value = flash[t];
        type.value = t;
        show.value = true;
        setTimeout(() => { show.value = false; }, 5000);
        return;
      }
    }
  },
  { immediate: true, deep: true }
);
</script>
