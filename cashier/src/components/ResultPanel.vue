<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppIcon from './AppIcon.vue';
import { formatDate, formatDateTime, money } from '../utils/formatters.js';

const store = inject('cashierStore');
const router = useRouter();
const resultPrintPopoverSuppressed = ref(false);
const resultInfoPopover = ref('');
const resultDeliveryRoot = ref(null);

const tickets = computed(() => store.resultTickets);
const firstTicket = computed(() => tickets.value[0] || null);
const deliveryMethodLabels = {
  print: 'Εκτύπωση',
  email: 'Email',
  sms: 'SMS',
};
const deliveryMethodKeys = Object.keys(deliveryMethodLabels);

function ticketKey(ticket, index) {
  return ticket?.ticket_uuid || ticket?.id || ticket?.html_url || index;
}

function ticketName(ticket) {
  return String(ticket?.attendee_name || '').trim() || 'Χωρίς ονοματεπώνυμο';
}

function ticketCategory(ticket) {
  const raw = String(ticket?.price_category || ticket?.ticket_type || '').trim();
  if (!raw) return 'Εισιτήριο';
  const parts = raw.split(':');
  return (parts.length > 1 ? parts.slice(1).join(':') : raw).trim() || 'Εισιτήριο';
}

function ticketPrice(ticket) {
  const raw = ticket?.unit_price ?? ticket?.price ?? '';
  if (raw === '' || raw === null || raw === undefined) return '';
  const value = Number(raw);
  return Number.isFinite(value) ? money(value) : '';
}

function shortTime(value) {
  const match = String(value || '').match(/(\d{2}:\d{2})/);
  return match ? match[1] : '';
}

function ticketDate(ticket, useSelectionFallback = true) {
  const date = String(ticket?.slot_start || '').slice(0, 10);
  return date ? formatDate(date) : (useSelectionFallback ? store.selectedDateLabel : '');
}

function ticketTime(ticket, useSelectionFallback = true) {
  const start = shortTime(ticket?.slot_start);
  const end = shortTime(ticket?.slot_end);
  if (start && end) return `${start} - ${end}`;
  return start || (useSelectionFallback ? store.selectedTimeLabel : '');
}

function ticketVisit(ticket, useSelectionFallback = true) {
  return [
    ticketDate(ticket, useSelectionFallback),
    ticketTime(ticket, useSelectionFallback),
  ].filter(Boolean).join(' · ');
}

function normalizePanelText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('el-GR')
    .trim();
}

const eventTicketItem = computed(() => {
  const result = store.result || {};
  const resultIds = [
    result.source_id,
    result.ticket_id,
    result.post_id,
    ...tickets.value.map((ticket) => ticket?.post_id),
  ].map((value) => Number(value || 0)).filter(Boolean);
  const idSet = new Set(resultIds);

  if (idSet.size) {
    const byId = store.allTicketResults.find((item) => [
      item.id,
      item.ticket_id,
      item.source_id,
    ].some((value) => idSet.has(Number(value || 0))));

    if (byId) return byId;
  }

  const title = normalizePanelText(result.title || store.resultTitle);
  if (!title) return null;

  return store.allTicketResults.find((item) => {
    const titles = [
      item.display_title,
      item.source_title,
      item.title,
    ].map(normalizePanelText).filter(Boolean);

    return titles.includes(title)
      || titles.some((candidate) => candidate.includes(title) || title.includes(candidate));
  }) || null;
});

const resultOrderTitle = computed(() => (
  store.resultOrderNumber ? `Παραγγελία ${store.resultOrderNumber}` : 'Παραγγελία'
));

const resultPaymentLabel = computed(() => {
  const payment = store.result?.payment || {};
  const provider = payment.provider || payment.id || payment.method || '';
  const fallback = payment.label || provider;
  return store.paymentMethodLabel(provider, fallback)
    || store.paymentMethodLabel(fallback, fallback)
    || '';
});

const resultCreatedAt = computed(() => formatDateTime(store.result?.created_at || store.result?.createdAt || ''));

const resultMeta = computed(() => [
  resultCreatedAt.value,
].filter(Boolean).join(' · '));

const eventTitle = computed(() => {
  const resultTitle = String(store.result?.title || '').trim();
  return resultTitle
    || String(store.resultContext?.title || '').trim()
    || eventTicketItem.value?.display_title
    || eventTicketItem.value?.source_title
    || eventTicketItem.value?.title
    || store.resultTitle
    || 'Εισιτήριο';
});

const eventThumb = computed(() => (
  store.ticketImageUrl(store.result || {})
    || store.ticketImageUrl(eventTicketItem.value || {})
    || ''
));

const eventLocation = computed(() => {
  const result = store.result || {};
  return String(
    result.building_title
      || result.location
      || eventTicketItem.value?.building_title
      || result.building_address
      || ''
  ).trim();
});

const eventVisit = computed(() => (
  firstTicket.value ? ticketVisit(firstTicket.value, !store.resultIsHistory) : ''
));

const eventMeta = computed(() => [
  eventVisit.value,
  eventLocation.value,
].filter(Boolean).join(' · '));

const hasEventInfo = computed(() => Boolean(eventTitle.value || eventMeta.value || eventThumb.value));

function normalizeDeliveryMethods(value) {
  let methods = value;
  if (typeof methods === 'string') {
    try {
      const decoded = JSON.parse(methods);
      methods = Array.isArray(decoded) ? decoded : methods.split(',');
    } catch {
      methods = methods.split(',');
    }
  }

  const selected = Array.isArray(methods) ? methods : [];
  return deliveryMethodKeys.filter((method) => selected.includes(method));
}

function normalizeEmailList(value) {
  const input = Array.isArray(value) ? value.join(' ') : String(value || '');
  const seen = new Set();
  return input
    .split(/[\s,;\u037e]+/u)
    .map((email) => email.trim())
    .filter(Boolean)
    .filter((email) => {
      const key = email.toLocaleLowerCase('el-GR');
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
}

const resultDelivery = computed(() => {
  const result = store.result || {};
  const delivery = result.delivery && typeof result.delivery === 'object' ? result.delivery : {};
  const methods = normalizeDeliveryMethods(delivery.methods || result.delivery_methods);
  const emails = normalizeEmailList(delivery.emails || result.customer_emails || delivery.email || result.customer_email);
  const phone = String(delivery.phone || result.customer_phone || '').trim();

  return {
    methods: methods.length ? methods : ['print'],
    emails,
    phone,
    newsletter: Boolean(delivery.newsletter_opt_in || result.newsletter_opt_in),
    membership: Boolean(delivery.membership_invite_opt_in || result.membership_invite_opt_in),
  };
});

const resultDeliveryEmail = computed(() => resultDelivery.value.emails.join(' '));

const resultEmailPopoverText = computed(() => {
  if (!resultDeliveryMethodSelected('email')) {
    return 'Δεν επιλέχθηκε αποστολή email για αυτή την παραγγελία.';
  }

  return resultDeliveryEmail.value
    ? 'Η παραγγελία στάλθηκε με email σε:'
    : 'Η παραγγελία στάλθηκε με email στους παραλήπτες.';
});

const resultSmsPopoverText = computed(() => {
  if (!resultDeliveryMethodSelected('sms')) {
    return 'Δεν επιλέχθηκε αποστολή SMS για αυτή την παραγγελία.';
  }

  return resultDelivery.value.phone
    ? 'Στάλθηκε SMS με σύνδεσμο στο:'
    : 'Στάλθηκε SMS με σύνδεσμο για τα εισιτήρια της παραγγελίας.';
});

const resultPaymentProvider = computed(() => {
  const payment = store.result?.payment || {};
  return payment.provider || payment.id || payment.method || '';
});

const resultPaymentMethod = computed(() => store.paymentMethodByValue(resultPaymentProvider.value));

const resultPaymentOptions = computed(() => {
  if (store.enabledPaymentMethods.length) return store.enabledPaymentMethods;

  const method = resultPaymentMethod.value;
  if (method) return [method];

  const id = resultPaymentProvider.value || resultPaymentLabel.value;
  if (!id) return [];

  return [{
    id,
    label: resultPaymentLabel.value || id,
    type: String(resultPaymentLabel.value || id).toLocaleLowerCase('el-GR').includes('μετρητ') ? 'cash' : 'pos',
  }];
});

function paymentIcon(method = {}) {
  return method.type === 'cash' ? 'cash' : 'card';
}

function resultPaymentMethodSelected(method = {}) {
  const selected = resultPaymentMethod.value;
  if (selected) {
    const selectedValues = [selected.id, selected.gateway_id, selected.provider].filter(Boolean).map(String);
    const methodValues = [method.id, method.gateway_id, method.provider].filter(Boolean).map(String);
    return methodValues.some((value) => selectedValues.includes(value));
  }

  return resultPaymentOptions.value.length === 1 && String(method.id || '') === String(resultPaymentOptions.value[0]?.id || '');
}

function resultPaymentPopoverTitle(method = {}) {
  return method.type === 'cash' ? 'Πληρωμή με μετρητά' : 'Πληρωμή με POS';
}

function resultPaymentPopoverText(method = {}) {
  if (!resultPaymentMethodSelected(method)) {
    return 'Δεν επιλέχθηκε αυτός ο τρόπος πληρωμής για την παραγγελία.';
  }

  return `Η παραγγελία πληρώθηκε με ${resultPaymentLabel.value || method.label || 'τον επιλεγμένο τρόπο πληρωμής'}.`;
}

function resultDeliveryMethodSelected(method) {
  return resultDelivery.value.methods.includes(method);
}

function blurActiveElement() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
}

function printResultOrder() {
  resultPrintPopoverSuppressed.value = true;
  closeResultInfoPopover();
  blurActiveElement();
  store.printOrderWithZebra();
}

function clearResultPrintPopoverSuppression() {
  resultPrintPopoverSuppressed.value = false;
}

function isResultInfoPopoverOpen(kind) {
  return resultInfoPopover.value === kind;
}

function toggleResultInfoPopover(kind) {
  resultInfoPopover.value = isResultInfoPopoverOpen(kind) ? '' : kind;
  blurActiveElement();
}

function toggleResultInfoPopoverWhen(enabled, kind) {
  if (!enabled) {
    closeResultInfoPopover();
    return;
  }

  toggleResultInfoPopover(kind);
}

function closeResultInfoPopover() {
  resultInfoPopover.value = '';
}

function handleResultInfoOutside(event) {
  const root = resultDeliveryRoot.value;
  if (!root || root.contains(event.target)) return;
  closeResultInfoPopover();
}

function backToTickets() {
  store.startNewSale();
  router.push('/');
}

onMounted(() => {
  document.addEventListener('pointerdown', handleResultInfoOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', handleResultInfoOutside);
});
</script>

<template>
  <section
    class="cashier-result cashier-result--screen"
    :class="{ 'cashier-result--history': store.resultIsHistory }"
    v-if="store.result"
    aria-labelledby="cashier-result-title"
  >
    <header class="cashier-result-summary" :class="{ 'cashier-result-summary--history': store.resultIsHistory }">
      <div>
        <p v-if="!store.resultIsHistory">Η έκδοση ολοκληρώθηκε</p>
        <h2 id="cashier-result-title">{{ resultOrderTitle }}</h2>
        <span v-if="resultMeta">{{ resultMeta }}</span>
      </div>
      <button class="cashier-secondary cashier-result-back" type="button" @click="backToTickets">
        <AppIcon name="chevronLeft" />
        <span>Εισιτήρια</span>
      </button>
    </header>

    <section ref="resultDeliveryRoot" class="cashier-result-control-bar" aria-label="Σύνοψη παραγγελίας">
      <div class="cashier-delivery-panel cashier-result-delivery" aria-label="Παράδοση εισιτηρίων">
        <span
          class="cashier-hover-target cashier-hover-target--start"
          :class="{ 'is-hover-popover-suppressed': resultPrintPopoverSuppressed }"
          @mouseleave="clearResultPrintPopoverSuppression"
        >
          <button
            type="button"
            class="cashier-delivery-option"
            :class="{ 'is-selected': resultDeliveryMethodSelected('print') }"
            :aria-pressed="resultDeliveryMethodSelected('print')"
            :disabled="!resultDeliveryMethodSelected('print') || !tickets.length || store.printingAllTickets || Boolean(store.printingTicketKey)"
            aria-label="Εκτύπωση"
            @click="printResultOrder"
          >
            <span v-if="store.printingAllTickets" class="cashier-button-spinner" aria-hidden="true"></span>
            <AppIcon v-else name="printer" />
          </button>
          <span class="cashier-hover-popover" role="tooltip" v-if="resultDeliveryMethodSelected('print')">
            <span class="cashier-hover-popover-title">Εκτύπωση παραγγελίας</span>
            <span class="cashier-hover-popover-text">Στέλνει όλα τα εισιτήρια της παραγγελίας στον εκτυπωτή.</span>
          </span>
        </span>
        <span
          class="cashier-hover-target cashier-hover-target--start"
          :class="{ 'is-hover-popover-active': isResultInfoPopoverOpen('email') }"
        >
          <button
            type="button"
            class="cashier-delivery-option"
            :class="{ 'is-selected': resultDeliveryMethodSelected('email') }"
            :aria-pressed="resultDeliveryMethodSelected('email')"
            :aria-expanded="isResultInfoPopoverOpen('email')"
            :disabled="!resultDeliveryMethodSelected('email')"
            aria-label="Email"
            @click.stop.prevent="toggleResultInfoPopoverWhen(resultDeliveryMethodSelected('email'), 'email')"
          >
            <AppIcon name="mail" />
          </button>
          <span class="cashier-hover-popover" role="tooltip" v-if="resultDeliveryMethodSelected('email')">
            <span class="cashier-hover-popover-title">Αποστολή με Email</span>
            <span class="cashier-hover-popover-text">
              {{ resultEmailPopoverText }}
              <span
                class="cashier-hover-popover-value"
                v-if="resultDeliveryMethodSelected('email') && resultDeliveryEmail"
              >
                {{ resultDeliveryEmail }}
              </span>
            </span>
            <span class="cashier-hover-popover-list" v-if="resultDelivery.newsletter || resultDelivery.membership">
              <span class="cashier-hover-popover-flag" v-if="resultDelivery.newsletter">Εγγραφή στο Newsletter</span>
              <span class="cashier-hover-popover-flag" v-if="resultDelivery.membership">Εγγραφή Μέλους</span>
            </span>
          </span>
        </span>
        <span
          class="cashier-hover-target cashier-hover-target--start"
          :class="{ 'is-hover-popover-active': isResultInfoPopoverOpen('sms') }"
        >
          <button
            type="button"
            class="cashier-delivery-option"
            :class="{ 'is-selected': resultDeliveryMethodSelected('sms') }"
            :aria-pressed="resultDeliveryMethodSelected('sms')"
            :aria-expanded="isResultInfoPopoverOpen('sms')"
            :disabled="!resultDeliveryMethodSelected('sms')"
            aria-label="SMS"
            @click.stop.prevent="toggleResultInfoPopoverWhen(resultDeliveryMethodSelected('sms'), 'sms')"
          >
            <AppIcon name="phone" />
          </button>
          <span class="cashier-hover-popover" role="tooltip" v-if="resultDeliveryMethodSelected('sms')">
            <span class="cashier-hover-popover-title">Αποστολή με SMS</span>
            <span class="cashier-hover-popover-text">
              {{ resultSmsPopoverText }}
              <span
                class="cashier-hover-popover-value"
                v-if="resultDeliveryMethodSelected('sms') && resultDelivery.phone"
              >
                {{ resultDelivery.phone }}
              </span>
            </span>
          </span>
        </span>
      </div>

      <div class="cashier-payment-block cashier-result-payment-block">
        <div class="cashier-payment-methods cashier-result-payment-methods">
          <button
            v-for="(method, index) in resultPaymentOptions"
            :key="method.id || index"
            type="button"
            class="cashier-payment cashier-hover-target cashier-hover-target--end"
            :class="{
              'is-selected': resultPaymentMethodSelected(method),
              'is-hover-popover-active': isResultInfoPopoverOpen(`payment-${index}`),
            }"
            :aria-pressed="resultPaymentMethodSelected(method)"
            :aria-expanded="isResultInfoPopoverOpen(`payment-${index}`)"
            :aria-label="method.label || method.id || 'Πληρωμή'"
            :disabled="!resultPaymentMethodSelected(method)"
            @click.stop.prevent="toggleResultInfoPopoverWhen(resultPaymentMethodSelected(method), `payment-${index}`)"
          >
            <span class="cashier-payment-mark" aria-hidden="true">
              <AppIcon :name="paymentIcon(method)" />
            </span>
            <span class="cashier-hover-popover" role="tooltip" v-if="resultPaymentMethodSelected(method)">
              <span class="cashier-hover-popover-title">{{ resultPaymentPopoverTitle(method) }}</span>
              <span class="cashier-hover-popover-text">{{ resultPaymentPopoverText(method) }}</span>
            </span>
          </button>
        </div>

        <span class="cashier-sale-summary cashier-result-sale-summary">
          <strong>{{ money(store.resultTotal) }}</strong>
          <span>{{ store.ticketCountLabel(store.resultTicketCount) }}</span>
        </span>
      </div>
    </section>

    <article class="cashier-result-event" v-if="hasEventInfo">
      <span
        class="cashier-history-avatar"
        :class="{ 'cashier-history-avatar--placeholder': !eventThumb }"
        aria-hidden="true"
      >
        <img v-if="eventThumb" :src="eventThumb" alt="">
        <AppIcon v-else name="ticket" />
      </span>
      <span class="cashier-result-event-copy">
        <strong>{{ eventTitle }}</strong>
        <small v-if="eventMeta">{{ eventMeta }}</small>
      </span>
    </article>

    <div class="cashier-scroll-fade cashier-result-ticket-scroll" v-if="tickets.length">
      <div class="cashier-issued-ticket-list">
        <article class="cashier-issued-ticket cashier-issued-ticket--simple" v-for="(ticket, index) in tickets" :key="ticketKey(ticket, index)">
          <span class="cashier-issued-ticket-number" aria-hidden="true">{{ index + 1 }}</span>

          <div class="cashier-issued-ticket-copy cashier-issued-ticket-copy--simple">
            <p class="cashier-issued-ticket-category">
              <span v-if="ticketPrice(ticket)">{{ ticketPrice(ticket) }}</span>
              <span v-if="ticketPrice(ticket)" aria-hidden="true">·</span>
              <span>{{ ticketCategory(ticket) }}</span>
            </p>
            <strong class="cashier-issued-ticket-name">{{ ticketName(ticket) }}</strong>
          </div>

          <div class="cashier-issued-ticket-actions">
            <button
              type="button"
              class="cashier-icon-button cashier-issued-ticket-print"
              :disabled="!ticket.ticket_uuid || store.printingAllTickets || Boolean(store.printingTicketKey)"
              aria-label="Εκτύπωση εισιτηρίου"
              @click="store.printTicketWithZebra(ticket)"
            >
              <span v-if="store.isTicketPrinting(ticket)" class="cashier-button-spinner" aria-hidden="true"></span>
              <AppIcon v-else name="printer" />
            </button>
          </div>
        </article>
      </div>
    </div>

    <div class="cashier-empty" v-else>
      <p>Η παραγγελία δημιουργήθηκε, αλλά δεν βρέθηκαν διαθέσιμα αρχεία εισιτηρίων.</p>
    </div>
  </section>
</template>
