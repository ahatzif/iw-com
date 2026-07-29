<script setup>
import { computed, inject, nextTick, ref, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const store = inject('cashierStore');
const sheet = ref(null);
const currentMessage = computed(() => {
  const messages = store.printSheet.messages || [];
  return messages[messages.length - 1] || null;
});

const printStatusLabels = {
  queued: 'Σε ουρά',
  processing: 'Σε εκτύπωση',
  done: 'Εκτυπώθηκε',
  failed: 'Απέτυχε',
};

function formatPrintTime(value) {
  const seconds = Number(value || 0);
  if (!seconds) return '';
  try {
    return new Intl.DateTimeFormat('el-GR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    }).format(new Date(seconds * 1000));
  } catch (error) {
    return '';
  }
}

const detailRows = computed(() => {
  const payload = store.printSheet.payload || {};
  const job = payload.job || {};
  const rows = [];
  const printerId = payload.printer_id || job.printer_id || '';
  const jobId = job.id || payload.job_id || '';
  const status = String(job.status || '').toLowerCase();
  const completedAt = formatPrintTime(job.completed_at);

  if (payload.queued && jobId) rows.push({ label: 'Εργασία', value: `#${jobId}` });
  if (payload.queued && printerId) rows.push({ label: 'Εκτυπωτής', value: printerId });
  if (payload.queued && status) rows.push({ label: 'Κατάσταση', value: printStatusLabels[status] || status });
  if (payload.queued && job.agent_id) rows.push({ label: 'Σταθμός', value: job.agent_id });
  if (payload.queued && completedAt) rows.push({ label: status === 'failed' ? 'Ολοκληρώθηκε' : 'Εκτυπώθηκε', value: completedAt });
  if (payload.queued && status === 'failed' && job.message) rows.push({ label: 'Μήνυμα', value: job.message });

  return rows;
});

watch(
  () => store.printSheet.open,
  async (isOpen) => {
    if (!isOpen) return;
    await nextTick();
    sheet.value?.focus();
  }
);
</script>

<template>
  <div class="cashier-print-layer is-open" v-if="store.printSheet.open">
    <button
      class="cashier-select-backdrop"
      type="button"
      aria-label="Κλείσιμο"
      :disabled="store.printSheet.pending"
      @click="store.closePrintSheet"
    ></button>
    <section
      ref="sheet"
      class="cashier-print-sheet"
      :class="[
        store.printSheet.status ? `is-${store.printSheet.status}` : '',
        { 'is-pending': store.printSheet.pending },
      ]"
      role="dialog"
      aria-modal="true"
      aria-labelledby="cashier-print-title"
      tabindex="-1"
      @keydown.esc="store.closePrintSheet"
    >
      <button
        class="cashier-select-close cashier-print-close"
        type="button"
        aria-label="Κλείσιμο"
        :disabled="store.printSheet.pending"
        @click="store.closePrintSheet"
      >
        <AppIcon name="x" aria-hidden="true" />
      </button>

      <header class="cashier-print-sheet-head">
        <span class="cashier-print-status-icon" aria-hidden="true">
          <AppIcon name="printer" />
        </span>
        <h2 id="cashier-print-title">{{ store.printSheet.title || 'Εκτύπωση' }}</h2>
        <small v-if="store.printSheet.subtitle">{{ store.printSheet.subtitle }}</small>
      </header>

      <dl class="cashier-print-details" v-if="detailRows.length">
        <div v-for="row in detailRows" :key="row.label">
          <dt>{{ row.label }}</dt>
          <dd>{{ row.value }}</dd>
        </div>
      </dl>

      <div class="cashier-print-current" aria-live="polite" aria-label="Τρέχουσα κατάσταση εκτύπωσης">
        <p
          class="cashier-print-message"
          :class="currentMessage?.type ? `is-${currentMessage.type}` : ''"
          v-if="currentMessage || store.printSheet.summary"
        >
          <span aria-hidden="true"></span>
          <span>
            <strong>{{ currentMessage?.text || store.printSheet.summary }}</strong>
            <small v-if="currentMessage?.detail">{{ currentMessage.detail }}</small>
          </span>
        </p>
      </div>

      <footer class="cashier-print-actions">
        <button
          class="cashier-primary"
          type="button"
          :disabled="store.printSheet.pending"
          @click="store.closePrintSheet"
        >
          <AppIcon name="check" aria-hidden="true" />
          <span>OK</span>
        </button>
      </footer>
    </section>
  </div>
</template>
