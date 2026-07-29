<script setup>
import { inject, ref } from 'vue';
import AppIcon from './AppIcon.vue';

const store = inject('cashierStore');
const suppressedPaymentPopover = ref('');

function paymentIcon(method = {}) {
  return method.type === 'cash' ? 'cash' : 'card';
}

function paymentPopoverTitle(method = {}) {
  return method.type === 'cash' ? 'Πληρωμή με μετρητά' : 'Πληρωμή με POS';
}

function paymentPopoverDescription(method = {}) {
  if (method.type === 'cash') {
    return 'Η παραγγελία θα καταχωρηθεί ως πληρωμένη στο ταμείο.';
  }

  if (method.api_integration) {
    return 'Η χρέωση θα σταλεί στο συνδεδεμένο POS.';
  }

  return 'Η πληρωμή γίνεται στο POS και καταχωρείται χειροκίνητα.';
}

function blurActiveElement() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
}

function suppressPaymentPopover(method = {}) {
  suppressedPaymentPopover.value = String(method.id || '');
  blurActiveElement();
}

function clearPaymentPopoverSuppression() {
  suppressedPaymentPopover.value = '';
}
</script>

<template>
  <div class="cashier-payment-methods">
    <p class="cashier-muted" v-if="!store.enabledPaymentMethods.length">Δεν υπάρχουν ενεργοί τρόποι πληρωμής.</p>
    <label
      class="cashier-payment cashier-hover-target"
      :class="{
        'is-selected': method.id === store.selectedPaymentMethod,
        'is-disabled': store.loading,
        'is-hover-popover-suppressed': suppressedPaymentPopover === String(method.id || ''),
      }"
      v-for="method in store.enabledPaymentMethods"
      :key="method.id"
      :aria-label="method.label || method.id"
      @click="suppressPaymentPopover(method)"
      @mouseleave="clearPaymentPopoverSuppression"
    >
      <input type="radio" name="payment_method" :value="method.id" v-model="store.selectedPaymentMethod" :disabled="store.loading">
      <span class="cashier-payment-mark" aria-hidden="true">
        <AppIcon :name="paymentIcon(method)" />
      </span>
      <span class="cashier-hover-popover" role="tooltip">
        <span class="cashier-hover-popover-title">{{ paymentPopoverTitle(method) }}</span>
        <span class="cashier-hover-popover-text">{{ paymentPopoverDescription(method) }}</span>
      </span>
    </label>
  </div>
</template>
