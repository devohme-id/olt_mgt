<template>
  <div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-slate-900">
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700/50 flex flex-col flex-shrink-0">
      <!-- Logo -->
      <div class="h-16 flex items-center px-5 border-b border-slate-200 dark:border-slate-700/50">
        <a href="/" class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-blue-500/20">
            <svg class="w-4 h-4 text-slate-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 010-5.304m5.304 0a3.75 3.75 0 010 5.304m-7.425 2.121a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.788m13.788 0c3.808 3.808 3.808 9.98 0 13.788" />
            </svg>
          </div>
          <div>
            <span class="text-sm font-bold text-slate-900 dark:text-white tracking-wide">OLT NMS</span>
            <span class="block text-[10px] text-slate-500">v1.0.0</span>
          </div>
        </a>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto p-3 space-y-0.5">
        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold px-3 pt-2 pb-1">Main</p>
        <NavItem href="/" label="Dashboard" :active="isActive('/')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
          </template>
        </NavItem>

        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold px-3 pt-4 pb-1">Network</p>
        <NavItem href="/olt" label="OLT Devices" :active="isActive('/olt')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008V17.25zm0-6h.008v.008h-.008v-.008zm6 6h.008v.008h-.008V17.25zm0-6h.008v.008h-.008v-.008z" /></svg>
          </template>
        </NavItem>
        <NavItem href="/onu" label="ONU Devices" :active="isActive('/onu')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
          </template>
        </NavItem>

        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold px-3 pt-4 pb-1">Operations</p>
        <NavItem href="/alarms" label="Alarms" :active="isActive('/alarm')" :badge="alarmCount" badgeType="danger">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
          </template>
        </NavItem>
        <NavItem href="/reports" label="Reports" :active="isActive('/report')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
          </template>
        </NavItem>

        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold px-3 pt-4 pb-1">System</p>
        <NavItem href="/tools/explorer" label="OID Explorer" :active="isActive('/tools')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" /></svg>
          </template>
        </NavItem>
        <NavItem href="/audit" label="Audit Log" :active="isActive('/audit')">
          <template #icon>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
          </template>
        </NavItem>
      </nav>

      <!-- User -->
      <div class="p-3 border-t border-slate-200 dark:border-slate-700/50">
        <div class="flex items-center gap-3 px-3 py-2">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-xs font-bold text-slate-900 dark:text-white">
            {{ userInitials }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $page.props.auth.user?.name }}</p>
            <p class="text-xs text-slate-500 truncate">{{ $page.props.auth.user?.role_name }}</p>
          </div>
          
          <!-- Theme Toggle -->
          <button @click="toggleTheme" type="button" class="text-slate-500 hover:text-amber-500 dark:hover:text-blue-400 transition" :title="isDarkMode ? 'Light Mode' : 'Dark Mode'">
            <svg v-if="isDarkMode" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
          </button>

          <!-- Logout -->
          <form @submit.prevent="logout" class="flex">
            <button type="submit" class="text-slate-500 hover:text-red-400 transition" title="Logout">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
            </button>
          </form>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
      <FlashMessage />
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import NavItem from '@/Components/NavItem.vue';

const page = usePage();
const alarmCount = computed(() => null); // Will be populated from shared data later

const isDarkMode = ref(false);

onMounted(() => {
  isDarkMode.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value;
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
};

const userInitials = computed(() => {
  const name = page.props.auth.user?.name || '';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
});

const isActive = (href) => {
  if (href === '/') return window.location.pathname === '/';
  return window.location.pathname.startsWith(href);
};

const logout = () => router.post('/logout');
</script>
