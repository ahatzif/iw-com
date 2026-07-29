<script setup>
import { inject, onBeforeUnmount, onMounted } from 'vue';
import { RouterView, useRoute, useRouter } from 'vue-router';

import AppIcon from './components/AppIcon.vue';
import MemberVerificationModal from './components/MemberVerificationModal.vue';
import PrintStatusModal from './components/PrintStatusModal.vue';
import SelectModal from './components/SelectModal.vue';

const store = inject('cashierStore');
const route = useRoute();
const router = useRouter();
const themePreferenceQuery = window.matchMedia?.('(prefers-color-scheme: dark)') || null;

const navItems = [
  { path: '/', label: 'Εισιτήρια', icon: 'ticket' },
  { path: '/history', label: 'Ιστορικό', icon: 'history' },
  { path: '/settings', label: 'Ρυθμίσεις', icon: 'settings' },
];

function navigate(path) {
  if (store.resultIsHistory && path === '/history' && route.path === '/history') {
    store.closeHistoryResult();
    return;
  }

  if (store.resultIsHistory && path !== '/history') {
    store.closeHistoryResult();
  }

  if (route.path !== path) {
    router.push(path);
  }
}

function isActive(path) {
  return path === '/' ? route.path === '/' || route.path.startsWith('/sale') : route.path.startsWith(path);
}

function handleKeydown(event) {
  if (event.key === 'Escape' && store.selectSheet.open) {
    store.closeSelect();
  } else if (event.key === 'Escape' && store.passwordSheet.open) {
    store.closePasswordSheet();
  }
}

function handleBeforeInstallPrompt(event) {
  event.preventDefault();
  store.setInstallPrompt(event);
}

function handleAppInstalled() {
  store.handleAppInstalled();
}

function handleFullscreenChange() {
  store.updateFullscreenStatus();
}

function handleThemePreferenceChange() {
  store.handleThemePreferenceChange();
}

onMounted(() => {
  store.bootSettings();
  window.addEventListener('keydown', handleKeydown);
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
  window.addEventListener('appinstalled', handleAppInstalled);
  document.addEventListener('fullscreenchange', handleFullscreenChange);
  if (typeof themePreferenceQuery?.addEventListener === 'function') {
    themePreferenceQuery.addEventListener('change', handleThemePreferenceChange);
  }
  store.loadConfig();
  store.searchTickets();
  store.registerServiceWorker();
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown);
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
  window.removeEventListener('appinstalled', handleAppInstalled);
  document.removeEventListener('fullscreenchange', handleFullscreenChange);
  if (typeof themePreferenceQuery?.removeEventListener === 'function') {
    themePreferenceQuery.removeEventListener('change', handleThemePreferenceChange);
  }
});
</script>

<template>
  <section class="cashier-phone cashier-phone--app" :class="{ 'is-cashier-busy': store.loading }" data-cashier-app>
    <div class="cashier-app-shell">
      <div class="cashier-app-main">
        <RouterView />
      </div>

      <nav class="cashier-app-nav" aria-label="Cashier navigation">
        <button
          v-for="item in navItems"
          :key="item.path"
          class="cashier-app-nav-item"
          type="button"
          :class="{ 'is-active': isActive(item.path) }"
          :aria-current="isActive(item.path) ? 'page' : undefined"
          @click="navigate(item.path)"
        >
          <AppIcon :name="item.icon" aria-hidden="true" />
          <span>{{ item.label }}</span>
        </button>
      </nav>

      <div v-if="store.loading" class="cashier-interaction-lock" aria-hidden="true"></div>
    </div>
  </section>

  <SelectModal />
  <MemberVerificationModal />
  <PrintStatusModal />
</template>
