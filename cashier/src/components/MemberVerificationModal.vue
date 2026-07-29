<script setup>
import { inject, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const store = inject('cashierStore');
const searchInput = ref(null);
const scannerVideo = ref(null);

let qrScanner = null;
let qrScannerClass = null;

function cashierAssetsBase() {
  return String(store.config.assetsUrl || '/cashier/assets').replace(/\/$/, '');
}

async function loadQrScanner() {
  if (qrScannerClass) return qrScannerClass;

  const base = cashierAssetsBase();
  const module = await import(/* @vite-ignore */ `${base}/vendor/qr-scanner.min.js`);
  qrScannerClass = module.default || module;
  qrScannerClass.WORKER_PATH = `${base}/vendor/qr-scanner-worker.min.js`;
  return qrScannerClass;
}

function stopScanner() {
  if (!qrScanner) return;
  qrScanner.stop();
  qrScanner.destroy?.();
  qrScanner = null;
}

async function startScanner() {
  stopScanner();
  store.memberVerificationSheet.cameraPending = true;
  store.memberVerificationSheet.cameraError = '';

  await nextTick();

  if (!scannerVideo.value) {
    store.memberVerificationSheet.cameraPending = false;
    store.memberVerificationSheet.cameraError = 'Δεν βρέθηκε προεπισκόπηση κάμερας.';
    return;
  }

  try {
    const QrScanner = await loadQrScanner();
    qrScanner = new QrScanner(
      scannerVideo.value,
      async (result) => {
        stopScanner();
        store.memberVerificationSheet.cameraPending = false;
        await store.verifyMemberScanPayload(result?.data || result || '');
      },
      {
        highlightScanRegion: true,
        highlightCodeOutline: true,
      }
    );

    await qrScanner.start();
    store.memberVerificationSheet.cameraPending = false;
  } catch (error) {
    stopScanner();
    store.memberVerificationSheet.cameraPending = false;
    store.memberVerificationSheet.cameraError = error?.message || 'Η κάμερα δεν μπόρεσε να ανοίξει.';
  }
}

async function setMode(mode) {
  store.setMemberVerificationMode(mode);
  if (mode === 'scan') {
    await nextTick();
    await startScanner();
  } else {
    stopScanner();
    await nextTick();
    searchInput.value?.focus();
  }
}

function selectMatch(match) {
  store.verifyMemberCardForVisitor(match.card_id, match.verification || null);
}

function clearSearch() {
  store.memberVerificationSheet.query = '';
  store.memberVerificationSheet.matches = [];
  store.memberVerificationSheet.result = null;
  store.setMemberVerificationMessage('Αναζήτησε μέλος με ενεργή συνδρομή.', 'neutral');
  searchInput.value?.focus();
}

watch(
  () => store.memberVerificationSheet.open,
  async (isOpen) => {
    if (!isOpen) {
      stopScanner();
      return;
    }

    await nextTick();
    searchInput.value?.focus();
  }
);

watch(
  () => store.memberVerificationSheet.mode,
  (mode) => {
    if (mode !== 'scan') {
      stopScanner();
    }
  }
);

onBeforeUnmount(() => {
  stopScanner();
});
</script>

<template>
  <div class="cashier-member-layer is-open" v-if="store.memberVerificationSheet.open">
    <button class="cashier-select-backdrop" type="button" aria-label="Κλείσιμο" @click="store.closeMemberVerification"></button>
    <section
      class="cashier-member-sheet"
      role="dialog"
      aria-modal="true"
      aria-labelledby="cashier-member-title"
      tabindex="-1"
      @keydown.esc="store.closeMemberVerification"
    >
      <div class="cashier-select-handle" aria-hidden="true"></div>
      <header class="cashier-member-sheet-head">
        <AppIcon name="search" aria-hidden="true" />
        <h2 id="cashier-member-title">Έλεγχος συνδρομής</h2>
        <button class="cashier-icon-button" type="button" aria-label="Κλείσιμο" @click="store.closeMemberVerification">
          <AppIcon name="x" aria-hidden="true" />
        </button>
      </header>

      <div class="cashier-member-copy">
        <strong>{{ store.visitorCategoryLabel(store.memberVerificationVisitor() || {}) }}</strong>
        <span>Απαιτείται ενεργή συνδρομή για να προχωρήσει η έκδοση.</span>
      </div>

      <div class="cashier-member-tabs" role="tablist" aria-label="Τρόπος ελέγχου">
        <button type="button" :class="{ 'is-active': store.memberVerificationSheet.mode === 'search' }" @click="setMode('search')">
          <AppIcon name="search" aria-hidden="true" />
          <span>Αναζήτηση</span>
        </button>
        <button type="button" :class="{ 'is-active': store.memberVerificationSheet.mode === 'scan' }" @click="setMode('scan')">
          <AppIcon name="scan" aria-hidden="true" />
          <span>Scan</span>
        </button>
      </div>

      <form class="cashier-member-search" v-if="store.memberVerificationSheet.mode === 'search'" @submit.prevent="store.searchMemberForVerification">
        <div class="cashier-member-search-controls">
          <p
            class="cashier-member-message"
            :class="store.memberVerificationSheet.messageType ? `is-${store.memberVerificationSheet.messageType}` : ''"
            aria-live="polite"
          >
            {{ store.memberVerificationSheet.message }}
          </p>
          <label class="cashier-member-search-field">
            <input ref="searchInput" type="search" v-model="store.memberVerificationSheet.query" autocomplete="off" inputmode="search" placeholder="Αναζήτηση">
            <button type="button" aria-label="Καθαρισμός" v-if="store.memberVerificationSheet.query" @click="clearSearch">
              <AppIcon name="x" aria-hidden="true" />
            </button>
          </label>
        </div>

        <div class="cashier-member-search-scroll">
          <div class="cashier-member-results" v-if="store.memberVerificationSheet.matches.length">
            <button
              class="cashier-member-result"
              type="button"
              v-for="match in store.memberVerificationSheet.matches"
              :key="match.card_id"
              @click="selectMatch(match)"
            >
              <span class="cashier-member-avatar" aria-hidden="true">
                <img v-if="match.photo_thumb || match.photo" :src="match.photo_thumb || match.photo" alt="">
                <AppIcon v-else name="user" />
              </span>
              <span>
                <strong>{{ match.name || 'Μέλος' }}</strong>
                <small v-if="match.phone">{{ match.phone }}</small>
                <small v-if="match.email">{{ match.email }}</small>
                <small :class="match.subscription?.valid ? 'is-valid' : 'is-invalid'">
                  {{ match.subscription?.valid ? 'Ενεργή συνδρομή' : 'Χωρίς ενεργή συνδρομή' }}
                </small>
              </span>
              <AppIcon name="chevronRight" aria-hidden="true" />
            </button>
          </div>

          <article
            class="cashier-member-checked"
            v-if="store.memberVerificationSheet.result?.user && store.isMemberVerificationActive(store.memberVerificationSheet.result)"
          >
            <span class="cashier-member-avatar" aria-hidden="true">
              <img
                v-if="store.memberVerificationSheet.result.user.photo_thumb || store.memberVerificationSheet.result.user.photo"
                :src="store.memberVerificationSheet.result.user.photo_thumb || store.memberVerificationSheet.result.user.photo"
                alt=""
              >
              <AppIcon v-else name="user" />
            </span>
            <span>
              <strong>{{ store.memberVerificationSheet.result.user.name || 'Μέλος' }}</strong>
              <small>{{ store.memberVerificationSheet.result.subscription?.name || store.memberVerificationSheet.result.user.email }}</small>
            </span>
          </article>
        </div>

      </form>

      <div class="cashier-member-scan" v-else>
        <p
          class="cashier-member-message"
          :class="store.memberVerificationSheet.cameraError ? 'is-error' : 'is-neutral'"
          aria-live="polite"
        >
          {{ store.memberVerificationSheet.cameraError || store.memberVerificationSheet.message }}
        </p>
        <div class="cashier-member-camera">
          <video ref="scannerVideo" muted playsinline></video>
          <div class="cashier-member-camera-placeholder" v-if="store.memberVerificationSheet.cameraPending">
            <span class="cashier-button-spinner" aria-hidden="true"></span>
            <strong>Άνοιγμα κάμερας...</strong>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
