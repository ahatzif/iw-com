<script setup>
import { inject, nextTick, ref } from 'vue';
import AppIcon from '../components/AppIcon.vue';

const store = inject('cashierStore');
const passwordInput = ref(null);
const accountInput = ref(null);

async function openAccountSettings() {
  store.openAccountSheet();
  await nextTick();
  accountInput.value?.focus();
}

async function openPasswordSettings() {
  store.openPasswordSheet();
  await nextTick();
  passwordInput.value?.focus();
}
</script>

<template>
  <section class="cashier-app-view is-active" data-cashier-view="settings" aria-labelledby="cashier-settings-title" aria-hidden="false">
    <div class="cashier-section-heading">
      <h1 id="cashier-settings-title" class="cashier-app-title">Ρυθμίσεις</h1>
      <p class="cashier-app-status">Πλαίσιο ταμείου και λογαριασμός.</p>
    </div>
    <div class="cashier-settings-list">
      <button class="cashier-settings-row" type="button" @click="store.openSelect('building')">
        <AppIcon class="cashier-row-icon" name="pin" aria-hidden="true" />
        <span>
          <strong>Τοποθεσία μουσείου</strong>
          <small>{{ store.selectedBuildingTitle }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="openAccountSettings">
        <AppIcon class="cashier-row-icon" name="user" aria-hidden="true" />
        <span>
          <strong>Operator</strong>
          <small>{{ store.operatorSummary }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="openPasswordSettings">
        <AppIcon class="cashier-row-icon" name="password" aria-hidden="true" />
        <span>
          <strong>Αλλαγή password</strong>
          <small>Ενημέρωση κωδικού λογαριασμού.</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" :disabled="!store.enabledPaymentMethods.length" @click="store.openSelect('payment')">
        <AppIcon class="cashier-row-icon" name="card" aria-hidden="true" />
        <span>
          <strong>POS mode</strong>
          <small>{{ store.selectedPaymentSummary }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="store.openSelect('printer')">
        <AppIcon class="cashier-row-icon" name="printer" aria-hidden="true" />
        <span>
          <strong>Εκτυπωτής</strong>
          <small>{{ store.selectedPrinterSummary }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="store.handleFullscreenAction()">
        <AppIcon class="cashier-row-icon" name="fullscreen" aria-hidden="true" />
        <span>
          <strong>Fullscreen mode</strong>
          <small>{{ store.fullscreenStatus }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="store.openSelect('theme')">
        <AppIcon class="cashier-row-icon" name="theme" aria-hidden="true" />
        <span>
          <strong>Theme mode</strong>
          <small>{{ store.themeSummary }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="store.openSelect('language')">
        <AppIcon class="cashier-row-icon" name="language" aria-hidden="true" />
        <span>
          <strong>Γλώσσα</strong>
          <small>{{ store.languageSummary }}</small>
        </span>
      </button>
      <button class="cashier-settings-row" type="button" @click="store.handleAppUpdateAction()">
        <AppIcon class="cashier-row-icon" name="refresh" aria-hidden="true" />
        <span>
          <strong>Έκδοση app</strong>
          <small>{{ store.appVersionSummary }}</small>
        </span>
      </button>
      <a class="cashier-settings-row cashier-settings-row--danger" :href="store.config.logoutUrl || '#'">
        <AppIcon class="cashier-row-icon" name="logout" aria-hidden="true" />
        <span>
          <strong>Αποσύνδεση</strong>
          <small>Έξοδος από το cashier.</small>
        </span>
      </a>
    </div>

    <div class="cashier-select-layer is-open" v-if="store.accountSheet.open">
      <button class="cashier-select-backdrop" type="button" aria-label="Κλείσιμο" @click="store.closeAccountSheet"></button>
      <section class="cashier-select-sheet cashier-password-sheet" role="dialog" aria-modal="true" aria-labelledby="cashier-account-title" tabindex="-1" @keydown.esc="store.closeAccountSheet">
        <div class="cashier-select-handle" aria-hidden="true"></div>
        <header class="cashier-select-sheet-head">
          <h2 id="cashier-account-title">Operator details</h2>
          <button class="cashier-sheet-close" type="button" aria-label="Κλείσιμο" :disabled="store.accountSheet.pending" @click="store.closeAccountSheet">
            <AppIcon name="x" aria-hidden="true" />
          </button>
        </header>
        <form class="cashier-sheet-form" @submit.prevent="store.submitAccountChange">
          <label class="cashier-field-label">
            <span>Όνομα <em class="cashier-required-mark" aria-hidden="true">*</em></span>
            <div class="cashier-field-control">
              <AppIcon class="cashier-field-icon" name="user" aria-hidden="true" />
              <input ref="accountInput" type="text" v-model="store.accountSheet.firstName" autocomplete="given-name" :disabled="store.accountSheet.pending" required>
            </div>
          </label>
          <label class="cashier-field-label">
            <span>Επώνυμο <em class="cashier-required-mark" aria-hidden="true">*</em></span>
            <div class="cashier-field-control">
              <AppIcon class="cashier-field-icon" name="user" aria-hidden="true" />
              <input type="text" v-model="store.accountSheet.lastName" autocomplete="family-name" :disabled="store.accountSheet.pending" required>
            </div>
          </label>
          <label class="cashier-field-label">
            <span>Email <em class="cashier-required-mark" aria-hidden="true">*</em></span>
            <div class="cashier-field-control">
              <AppIcon class="cashier-field-icon" name="mail" aria-hidden="true" />
              <input type="email" v-model="store.accountSheet.email" autocomplete="email" :disabled="store.accountSheet.pending" required>
            </div>
          </label>
          <p class="cashier-sheet-message" :data-status="store.accountSheet.messageType" aria-live="polite">{{ store.accountSheet.message }}</p>
          <div class="cashier-sheet-actions">
            <button class="cashier-secondary" type="button" :disabled="store.accountSheet.pending" @click="store.closeAccountSheet">Άκυρο</button>
            <button class="cashier-primary" type="submit" :disabled="store.accountSheet.pending">
              <span>{{ store.accountSheet.pending ? 'Αποθήκευση...' : 'Αποθήκευση' }}</span>
            </button>
          </div>
        </form>
      </section>
    </div>

    <div class="cashier-select-layer is-open" v-if="store.passwordSheet.open">
      <button class="cashier-select-backdrop" type="button" aria-label="Κλείσιμο" @click="store.closePasswordSheet"></button>
      <section class="cashier-select-sheet cashier-password-sheet" role="dialog" aria-modal="true" aria-labelledby="cashier-password-title" tabindex="-1" @keydown.esc="store.closePasswordSheet">
        <div class="cashier-select-handle" aria-hidden="true"></div>
        <header class="cashier-select-sheet-head">
          <h2 id="cashier-password-title">Αλλαγή password</h2>
          <button class="cashier-sheet-close" type="button" aria-label="Κλείσιμο" :disabled="store.passwordSheet.pending" @click="store.closePasswordSheet">
            <AppIcon name="x" aria-hidden="true" />
          </button>
        </header>
        <form class="cashier-sheet-form" @submit.prevent="store.submitPasswordChange">
          <label class="cashier-field-label">
            <span>Νέο password <em class="cashier-required-mark" aria-hidden="true">*</em></span>
            <div class="cashier-field-control">
              <AppIcon class="cashier-field-icon" name="password" aria-hidden="true" />
              <input ref="passwordInput" type="password" v-model="store.passwordSheet.newPassword" autocomplete="new-password" :disabled="store.passwordSheet.pending" required>
            </div>
          </label>
          <label class="cashier-field-label">
            <span>Επιβεβαίωση password <em class="cashier-required-mark" aria-hidden="true">*</em></span>
            <div class="cashier-field-control">
              <AppIcon class="cashier-field-icon" name="password" aria-hidden="true" />
              <input type="password" v-model="store.passwordSheet.confirmPassword" autocomplete="new-password" :disabled="store.passwordSheet.pending" required>
            </div>
          </label>
          <p class="cashier-sheet-helper">Τουλάχιστον 8 χαρακτήρες, ένα πεζό γράμμα και ένα ειδικό χαρακτήρα.</p>
          <p class="cashier-sheet-message" :data-status="store.passwordSheet.messageType" aria-live="polite">{{ store.passwordSheet.message }}</p>
          <div class="cashier-sheet-actions">
            <button class="cashier-secondary" type="button" :disabled="store.passwordSheet.pending" @click="store.closePasswordSheet">Άκυρο</button>
            <button class="cashier-primary" type="submit" :disabled="store.passwordSheet.pending">
              <span>{{ store.passwordSheet.pending ? 'Αποθήκευση...' : 'Αποθήκευση' }}</span>
            </button>
          </div>
        </form>
      </section>
    </div>
  </section>
</template>
