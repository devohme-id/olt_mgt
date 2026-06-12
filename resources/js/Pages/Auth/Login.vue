<template>
  <div class="relative min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4">
    <!-- Theme Toggle -->
    <button @click="toggleTheme" type="button" class="absolute top-6 right-6 p-2 rounded-lg text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-800 transition" :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
      <svg v-if="isDarkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
      <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
    </button>
    
    <div class="w-full max-w-md">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 mb-4 shadow-lg shadow-blue-500/25">
          <svg class="w-8 h-8 text-slate-900 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 010-5.304m5.304 0a3.75 3.75 0 010 5.304m-7.425 2.121a6.75 6.75 0 010-9.546m9.546 0a6.75 6.75 0 010 9.546M5.106 18.894c-3.808-3.808-3.808-9.98 0-13.788m13.788 0c3.808 3.808 3.808 9.98 0 13.788M12 12h.008v.008H12V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">OLT NMS</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">Network Management System</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700/50 p-8 shadow-xl backdrop-blur-sm">
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autofocus
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="admin@oltnms.local"
            />
            <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-400">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="••••••••"
            />
          </div>

          <!-- Remember -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-blue-500 focus:ring-blue-500" />
              <span class="text-sm text-slate-500 dark:text-slate-400">Remember me</span>
            </label>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-slate-900 dark:text-white font-medium rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40"
          >
            <span v-if="form.processing" class="flex items-center justify-center gap-2">
              <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Signing in...
            </span>
            <span v-else>Sign In</span>
          </button>
        </form>
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-slate-600 mt-6">
        OLT NMS v1.0.0 • Enterprise Network Management
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

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

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
