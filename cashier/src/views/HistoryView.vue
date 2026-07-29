<script setup>
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppIcon from '../components/AppIcon.vue';
import ResultPanel from '../components/ResultPanel.vue';
import { formatDateTime, money } from '../utils/formatters.js';

const store = inject('cashierStore');
const route = useRoute();
const router = useRouter();
const historySearch = ref('');
const historySearchInput = ref(null);
const historySearchOpen = ref(false);
const historyPaymentFilter = ref('all');
const historyPaymentFilters = [
  { id: 'all', label: 'Όλα' },
  { id: 'cash', label: 'Μετρητά' },
  { id: 'pos', label: 'POS' },
];

function historyMeta(item) {
  return [
    store.historyItemTicketCountLabel(item),
    money(item.total || item.result?.total_price || 0),
    store.historyItemPaymentLabel(item),
  ].filter(Boolean).join(' · ');
}

function normalizeHistoryText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

function historyTicketValues(item) {
  const tickets = Array.isArray(item.result?.tickets)
    ? item.result.tickets
    : (Array.isArray(item.tickets) ? item.tickets : []);

  if (!tickets.length) {
    return [];
  }

  return tickets.flatMap((ticket) => [
    ticket.name,
    ticket.full_name,
    ticket.first_name,
    ticket.last_name,
    ticket.firstname,
    ticket.lastname,
    ticket.first,
    ticket.last,
    ticket.email,
    ticket.visitor_name,
    ticket.visitor_full_name,
    ticket.visitor_first_name,
    ticket.visitor_last_name,
    ticket.holder_name,
    ticket.customer_name,
    ticket.member_name,
    ticket.user?.name,
    ticket.user?.email,
    ticket.member?.name,
    ticket.member?.email,
    ticket.category,
    ticket.ticket_type,
    ticket.uuid,
  ]);
}

function normalizePaymentKind(value) {
  const normalized = normalizeHistoryText(value).replace(/[\s-]+/g, '_');
  if (!normalized) return '';

  if (normalized.includes('cash') || normalized.includes('μετρητ') || normalized.includes('ταμει')) {
    return 'cash';
  }

  if (normalized.includes('pos')) {
    return 'pos';
  }

  return '';
}

function historyPaymentKind(item) {
  const candidates = [
    item.payment,
    item.paymentMethod,
    item.paymentLabel,
    item.result?.payment?.provider,
    item.result?.payment?.label,
    store.historyItemPaymentLabel(item),
  ];

  for (const candidate of candidates) {
    const kind = normalizePaymentKind(candidate);
    if (kind) return kind;
  }

  return '';
}

function historySearchText(item) {
  const orderId = store.historyOrderId(item);
  return normalizeHistoryText([
    item.title,
    item.ticketTitle,
    item.eventTitle,
    item.result?.title,
    store.historyItemTicketCountLabel(item),
    formatDateTime(item.createdAt),
    orderId,
    orderId ? `#${orderId}` : '',
    money(item.total || item.result?.total_price || 0),
    ...historyTicketValues(item),
  ].filter(Boolean).join(' '));
}

const filteredHistory = computed(() => {
  const needle = normalizeHistoryText(historySearch.value);
  const paymentFilter = historyPaymentFilter.value;

  return store.saleHistory.filter((item) => {
    if (paymentFilter !== 'all' && historyPaymentKind(item) !== paymentFilter) {
      return false;
    }

    return !needle || historySearchText(item).includes(needle);
  });
});

function routeOrderId() {
  return String(route.params.orderId || '').trim();
}

function ensureHistoryRoute() {
  const orderId = routeOrderId();

  if (!orderId) {
    if (store.resultIsHistory || store.historyDetailLoading) {
      store.closeHistoryResult();
    }
    return;
  }

  if (store.resultIsHistory && String(store.result?.order_id || '') === orderId) {
    return;
  }

  store.openHistoryOrder(orderId);
}

function openHistoryItem(item) {
  const orderId = store.historyOrderId(item);
  if (!orderId) {
    store.openHistoryResult(item);
    return;
  }

  if (routeOrderId() === orderId) {
    ensureHistoryRoute();
    return;
  }

  router.push({ name: 'history-order', params: { orderId } });
}

function openHistorySearch() {
  historySearchOpen.value = true;
  nextTick(() => {
    historySearchInput.value?.focus();
  });
}

function closeHistorySearch() {
  historySearchOpen.value = Boolean(historySearch.value);
}

onMounted(() => {
  ensureHistoryRoute();
});

watch(
  () => route.params.orderId,
  () => {
    ensureHistoryRoute();
  }
);
</script>

<template>
  <section class="cashier-app-view is-active" data-cashier-view="history" aria-labelledby="cashier-history-title" aria-hidden="false">
    <div class="cashier-view-loader cashier-list-loader" v-if="store.historyDetailLoading" role="status" aria-live="polite">
      <div class="cashier-list-loader-mark" aria-hidden="true">
        <span class="cashier-list-loader-spinner"></span>
      </div>
      <span class="cashier-list-loader-text">Φόρτωση παραγγελίας...</span>
    </div>
    <ResultPanel v-else-if="store.resultIsHistory" />
    <template v-else>
      <div class="cashier-section-heading">
        <h1 id="cashier-history-title" class="cashier-app-title">Ιστορικό πωλήσεων</h1>
        <p class="cashier-app-status">Πρόσφατες εκδόσεις σε αυτή τη συσκευή.</p>
      </div>
      <div class="cashier-history-tools" v-if="store.saleHistory.length">
        <div class="cashier-ticket-filters cashier-history-filters" role="group" aria-label="Φίλτρο τρόπου πληρωμής">
          <button
            class="cashier-filter"
            :class="{ 'is-selected': filter.id === historyPaymentFilter }"
            type="button"
            v-for="filter in historyPaymentFilters"
            :key="filter.id"
            @click="historyPaymentFilter = filter.id"
          >
            {{ filter.label }}
          </button>
        </div>
        <label
          class="cashier-searchbox cashier-expand-search cashier-history-search"
          :class="{ 'is-expanded': historySearchOpen || historySearch }"
          for="cashier-history-search"
          @click="openHistorySearch"
          @focusin="historySearchOpen = true"
          @focusout="closeHistorySearch"
        >
          <AppIcon name="search" aria-hidden="true" />
          <input
            ref="historySearchInput"
            id="cashier-history-search"
            type="search"
            autocomplete="off"
            placeholder="Τίτλος, #παραγγελίας, όνομα"
            aria-label="Αναζήτηση ιστορικού πωλήσεων"
            v-model="historySearch"
          >
        </label>
      </div>
      <div class="cashier-scroll-fade">
        <div class="cashier-history-list">
          <article class="cashier-history-row cashier-history-row--empty" v-if="!store.saleHistory.length">
            <AppIcon class="cashier-row-icon" name="history" aria-hidden="true" />
            <span>
              <strong>Δεν υπάρχουν πωλήσεις ακόμα</strong>
              <small>Οι πρόσφατες εκδόσεις θα εμφανίζονται εδώ.</small>
            </span>
          </article>
          <article class="cashier-history-row cashier-history-row--empty" v-else-if="!filteredHistory.length">
            <AppIcon class="cashier-row-icon" name="search" aria-hidden="true" />
            <span>
              <strong>Δεν βρέθηκαν πωλήσεις</strong>
              <small>Δοκίμασε τίτλο, όνομα, ημερομηνία ή άλλο φίλτρο πληρωμής.</small>
            </span>
          </article>
          <button
            class="cashier-history-row"
            type="button"
            v-for="item in filteredHistory"
            :key="item.createdAt + item.orderId"
            @click="openHistoryItem(item)"
          >
            <span
              class="cashier-history-avatar"
              :class="{ 'cashier-history-avatar--placeholder': !store.historyItemThumb(item) }"
              aria-hidden="true"
            >
              <img v-if="store.historyItemThumb(item)" :src="store.historyItemThumb(item)" alt="">
              <AppIcon v-else name="ticket" />
            </span>
            <span class="cashier-history-copy">
              <strong>{{ item.title || 'Εισιτήριο' }}</strong>
              <small>{{ historyMeta(item) }}</small>
              <time>{{ formatDateTime(item.createdAt) }}<template v-if="item.orderId"> · #{{ item.orderId }}</template></time>
            </span>
            <AppIcon class="cashier-history-chevron" name="chevronRight" aria-hidden="true" />
          </button>
        </div>
      </div>
    </template>
  </section>
</template>
