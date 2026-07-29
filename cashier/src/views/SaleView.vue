<script setup>
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppIcon from '../components/AppIcon.vue';
import PaymentMethods from '../components/PaymentMethods.vue';
import ResultPanel from '../components/ResultPanel.vue';
import VisitorRow from '../components/VisitorRow.vue';
import { money } from '../utils/formatters.js';

const store = inject('cashierStore');
const route = useRoute();
const router = useRouter();
const deliveryPopover = ref('');
const deliveryPopoverOffset = ref(0);
const deliveryPopoverRoot = ref(null);
const deliveryHoverPopoverSuppressed = ref('');
const printHoverPopoverSuppressed = ref(false);
const deliveryPopoverStyle = computed(() => ({
  '--cashier-delivery-popover-shift': `${deliveryPopoverOffset.value}px`,
  '--cashier-delivery-popover-arrow-shift': `${-deliveryPopoverOffset.value}px`,
}));

const saleTicket = computed(() => store.selectedTicketCardItem || {});
const saleTicketInfoUrl = computed(() => saleTicket.value?.permalink || saleTicket.value?.ticket_permalink || '');
const saleTicketTitle = computed(() => (
  saleTicket.value?.display_title
  || saleTicket.value?.title
  || store.selectedTicketTitle
  || 'Έκδοση εισιτηρίων'
));
const typeLabelMap = {
  'ΜΟΝΙΜΕΣ ΕΚΘΕΣΕΙΣ': 'Μόνιμες Εκθέσεις',
  'ΠΕΡΙΟΔΙΚΕΣ ΕΚΘΕΣΕΙΣ': 'Περιοδικές Εκθέσεις',
  'ΞΕΝΑΓΗΣΕΙΣ': 'Ξεναγήσεις',
  'ΕΚΔΗΛΩΣΕΙΣ': 'Εκδηλώσεις',
  'ΕΚΠΑΙΔΕΥΤΙΚΑ ΠΡΟΓΡΑΜΜΑΤΑ': 'Εκπαιδευτικά Προγράμματα',
  EXPERIENCES: 'Experiences',
};
const saleTicketType = computed(() => {
  const label = saleTicket.value?.type_label || saleTicket.value?.post_type || '';
  return typeLabelMap[String(label).trim().toLocaleUpperCase('el-GR')] || label;
});
const hasTicketCategories = computed(() => store.ticketCategoryGroups.length > 0);

function backToTickets() {
  router.push('/');
}

async function submitSale() {
  const result = await store.issueTickets();
  const orderId = String(result?.order_id || '').trim();

  if (orderId) {
    router.replace({ name: 'history-order', params: { orderId } });
  }
}

function routeTicketId() {
  return Number(route.params.ticketId || 0);
}

function routeMatchesSelection(ticketId) {
  const selectedSourceId = Number(store.selectedTicketListItem?.source_id || store.selectedTicketData?.source_id || 0);
  const selectedTicketId = Number(store.selectedTicketListItem?.ticket_id || store.selectedTicketData?.id || store.selectedTicket || 0);
  return ticketId > 0 && (ticketId === selectedSourceId || ticketId === selectedTicketId);
}

function isDeliveryPopoverOpen(kind) {
  return deliveryPopover.value === kind;
}

function blurActiveElement() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur();
  }
}

function suppressDeliveryHoverPopover(kind) {
  deliveryHoverPopoverSuppressed.value = kind;
  blurActiveElement();
}

function clearDeliveryHoverPopoverSuppression(kind) {
  if (deliveryHoverPopoverSuppressed.value === kind) {
    deliveryHoverPopoverSuppressed.value = '';
  }
}

function closeDeliveryPopover() {
  if (
    isDeliveryPopoverOpen('email')
    && store.deliveryEmailEnabled
    && !String(store.customerEmail || '').trim()
    && !store.newsletterOptIn
    && !store.membershipInviteOptIn
  ) {
    store.setDeliveryMethodEnabled('email', false);
  }

  if (
    isDeliveryPopoverOpen('sms')
    && store.deliverySmsEnabled
    && !String(store.customerPhone || '').trim()
  ) {
    store.setDeliveryMethodEnabled('sms', false);
  }

  deliveryPopover.value = '';
  deliveryPopoverOffset.value = 0;
}

function toggleEmailPopover() {
  suppressDeliveryHoverPopover('email');

  if (deliveryPopover.value && !isDeliveryPopoverOpen('email')) {
    closeDeliveryPopover();
  }

  if (
    isDeliveryPopoverOpen('email')
    && store.deliveryEmailEnabled
    && !String(store.customerEmail || '').trim()
    && !store.newsletterOptIn
    && !store.membershipInviteOptIn
  ) {
    store.setDeliveryMethodEnabled('email', false);
    closeDeliveryPopover();
    return;
  }

  if (!store.deliveryEmailEnabled) {
    store.setDeliveryMethodEnabled('email', true);
  }
  deliveryPopover.value = isDeliveryPopoverOpen('email') ? '' : 'email';
}

function toggleSmsPopover() {
  suppressDeliveryHoverPopover('sms');

  if (deliveryPopover.value && !isDeliveryPopoverOpen('sms')) {
    closeDeliveryPopover();
  }

  if (
    isDeliveryPopoverOpen('sms')
    && store.deliverySmsEnabled
    && !String(store.customerPhone || '').trim()
  ) {
    store.setDeliveryMethodEnabled('sms', false);
    closeDeliveryPopover();
    return;
  }

  if (!store.deliverySmsEnabled) {
    store.setDeliveryMethodEnabled('sms', true);
  }
  deliveryPopover.value = isDeliveryPopoverOpen('sms') ? '' : 'sms';
}

function togglePrintDelivery() {
  store.toggleDeliveryMethod('print');
  closeDeliveryPopover();
  printHoverPopoverSuppressed.value = true;
  blurActiveElement();
}

function clearPrintHoverPopoverSuppression() {
  printHoverPopoverSuppressed.value = false;
}

function clearEmailDelivery() {
  store.customerEmail = '';
  store.newsletterOptIn = false;
  store.membershipInviteOptIn = false;
  store.setDeliveryMethodEnabled('email', false);
  closeDeliveryPopover();
}

function clearSmsDelivery() {
  store.customerPhone = '';
  store.setDeliveryMethodEnabled('sms', false);
  closeDeliveryPopover();
}

function saveDeliveryPopover() {
  closeDeliveryPopover();
}

function updateDeliveryPopoverPosition() {
  if (!deliveryPopover.value) {
    deliveryPopoverOffset.value = 0;
    return;
  }

  deliveryPopoverOffset.value = 0;

  nextTick(() => {
    window.requestAnimationFrame(() => {
      if (!deliveryPopover.value) return;

      const root = deliveryPopoverRoot.value;
      const popover = root?.querySelector('.cashier-delivery-popover');
      const boundary = root?.closest('.cashier-app-main') || root?.closest('.cashier-app-view') || root?.closest('.cashier-phone');
      if (!popover || !boundary) return;

      const gutter = 16;
      const popoverRect = popover.getBoundingClientRect();
      const boundaryRect = boundary.getBoundingClientRect();
      let offset = 0;

      if (popoverRect.left < boundaryRect.left + gutter) {
        offset = boundaryRect.left + gutter - popoverRect.left;
      }

      if (popoverRect.right + offset > boundaryRect.right - gutter) {
        offset -= popoverRect.right + offset - (boundaryRect.right - gutter);
      }

      deliveryPopoverOffset.value = Math.round(offset);
    });
  });
}

function handleDeliveryOutside(event) {
  const root = deliveryPopoverRoot.value;
  if (!root || root.contains(event.target)) return;
  closeDeliveryPopover();
}

function ensureSelectedTicket() {
  const ticketId = routeTicketId();

  if (ticketId > 0) {
    if (routeMatchesSelection(ticketId) && (store.selectedTicketData || store.ticketDetailLoading)) {
      return;
    }

    const item = store.findTicketListItem(ticketId);
    store.prepareTicketSelection(Number(item?.ticket_id || ticketId), item);
    store.loadTicket(ticketId, item);
    return;
  }

  if (!store.selectedTicket && !store.selectedTicketListItem && !store.selectedTicketData) {
    router.replace('/');
  } else if (store.selectedTicket && !store.selectedTicketData && !store.ticketDetailLoading) {
    store.loadTicket(store.selectedTicket, store.selectedTicketListItem);
  }
}

onMounted(() => {
  ensureSelectedTicket();
  document.addEventListener('pointerdown', handleDeliveryOutside);
  window.addEventListener('resize', updateDeliveryPopoverPosition);
});

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', handleDeliveryOutside);
  window.removeEventListener('resize', updateDeliveryPopoverPosition);
});

watch(deliveryPopover, updateDeliveryPopoverPosition);

watch(
  () => route.params.ticketId,
  () => {
    ensureSelectedTicket();
  }
);
</script>

<template>
  <section class="cashier-app-view is-active" data-cashier-view="sale" aria-labelledby="cashier-sale-title" aria-hidden="false">
    <div class="cashier-view-loader cashier-list-loader" v-if="store.ticketDetailLoading" role="status" aria-live="polite">
      <div class="cashier-list-loader-mark" aria-hidden="true">
        <span class="cashier-list-loader-spinner"></span>
      </div>
      <span class="cashier-list-loader-text">Φόρτωση πληροφοριών εισιτηρίου...</span>
    </div>

    <ResultPanel v-else-if="store.result" />

    <form class="cashier-sale" v-else @submit.prevent="submitSale">
      <div class="cashier-sale-fixed-head">
        <div class="cashier-sale-heading cashier-section-heading cashier-section-heading--with-action">
          <div class="cashier-section-heading">
            <h1 id="cashier-sale-title" class="cashier-app-title">{{ saleTicketTitle }}</h1>
            <div class="cashier-app-status" v-if="saleTicket.date_label || saleTicket.building_title || saleTicket.price_label || saleTicketInfoUrl">
              <span v-if="saleTicket.date_label">
                {{ saleTicket.date_label }} ·
              </span>
              <span v-if="saleTicket.building_title">
                {{ saleTicket.building_title }} ·
              </span>
              <span v-if="saleTicket.price_label">
                {{ saleTicket.price_label }} ·
              </span>
              <a v-if="saleTicketInfoUrl" :href="saleTicketInfoUrl" target="_blank" rel="noopener noreferrer" >
                Πληροφορίες
              </a>
            </div>
          </div>
          <button class="cashier-secondary" type="button" @click="backToTickets">
            <AppIcon name="chevronLeft" />
            <span>Εισιτήρια</span>
          </button>
        </div>

        <div class="cashier-grid cashier-grid--datetime" v-if="store.selectedTicketData">
          <label>
            <span>Ημερομηνία</span>
            <button class="cashier-settings-row cashier-datetime-trigger" type="button" :disabled="!store.availableDates.length" @click="store.openSelect('date')">
              <AppIcon class="cashier-row-icon" name="calendar" aria-hidden="true" />
              <span>
                <strong>{{ store.selectedDateLabel }}</strong>
                <small>Ημερομηνία επίσκεψης</small>
              </span>
              <AppIcon class="cashier-row-chevron" name="chevronDown" />
            </button>
          </label>
          <label>
            <span>Ώρα</span>
            <button class="cashier-settings-row cashier-datetime-trigger" type="button" :disabled="!store.selectedDateSlots.length" @click="store.openSelect('time')">
              <AppIcon class="cashier-row-icon" name="clock" aria-hidden="true" />
              <span>
                <strong>{{ store.selectedTimeLabel }}</strong>
                <small>{{ store.selectedTimeAvailabilityLabel }}</small>
              </span>
              <AppIcon class="cashier-row-chevron" name="chevronDown" />
            </button>
          </label>
        </div>
      </div>

      <template v-if="store.selectedTicketData">
        <div class="cashier-ticket-empty cashier-empty" v-if="!hasTicketCategories" role="status">
          <span>
            <strong>Δεν υπάρχουν ακόμα εισιτήρια</strong>
            <small>Δεν έχουν οριστεί κατηγορίες ή τιμές για αυτή την εκδήλωση.</small>
          </span>
        </div>

        <template v-else>
          <div class="cashier-scroll-fade cashier-sale-category-scroll">
            <div class="cashier-ticket-category-list">
              <section class="cashier-ticket-category" v-for="group in store.ticketCategoryGroups" :key="group.key">
                <div class="cashier-ticket-category-head">
                  <div class="cashier-ticket-category-copy">
                    <div class="cashier-ticket-category-title">
                      <strong>{{ group.label }}</strong>
                      <small v-if="group.priceLabel">{{ group.priceLabel }}</small>
                    </div>
                    <p class="cashier-ticket-category-description" v-if="group.description" v-html="group.description"></p>
                  </div>
                  <div class="cashier-ticket-category-quantity" :aria-label="`${group.label}: ${store.ticketCountLabel(group.count, 'lower')}`">
                    <button class="cashier-icon-button" type="button" :disabled="!group.canRemove" :aria-label="`Αφαίρεση από ${group.label}`" @click="store.decreaseTicketCategory(group.key)">
                      <AppIcon name="minus" />
                    </button>
                    <span>{{ group.count }}</span>
                    <button class="cashier-icon-button" type="button" :disabled="!group.canAdd" :aria-label="`Προσθήκη σε ${group.label}`" @click="store.increaseTicketCategory(group.key)">
                      <AppIcon name="plus" />
                    </button>
                  </div>
                  <strong class="cashier-ticket-category-total">{{ money(group.total) }}</strong>
                </div>
                <div class="cashier-visitors cashier-visitors--category" v-if="group.visitors.length">
                  <VisitorRow
                    v-for="entry in group.visitors"
                    :key="entry.visitor.uid"
                    :visitor="entry.visitor"
                    :index="entry.index"
                    :category-key="group.key"
                    :show-type="group.showType"
                  />
                </div>
              </section>
            </div>
          </div>

          <div class="cashier-sale-footer">
            <div class="cashier-sale-footer-main">
              <div class="cashier-delivery-panel cashier-delivery-panel--popover" ref="deliveryPopoverRoot" role="group" aria-label="Παράδοση εισιτηρίων">
                <div
                  class="cashier-hover-target cashier-hover-target--start"
                  :class="{ 'is-hover-popover-suppressed': printHoverPopoverSuppressed }"
                  @mouseleave="clearPrintHoverPopoverSuppression"
                >
                  <button
                    type="button"
                    class="cashier-delivery-option"
                    :class="{ 'is-selected': store.deliveryPrintReady }"
                    :aria-pressed="store.isDeliveryMethodSelected('print')"
                    aria-label="Εκτύπωση"
                    @click="togglePrintDelivery"
                  >
                    <AppIcon name="printer" />
                  </button>
                  <span class="cashier-hover-popover" role="tooltip">
                    <span class="cashier-hover-popover-title">Εκτύπωση εισιτηρίων</span>
                    <span class="cashier-hover-popover-text">Τα εισιτήρια θα εκτυπωθούν με την έκδοση της παραγγελίας.</span>
                  </span>
                </div>

                <div
                  class="cashier-delivery-popover-wrap cashier-hover-target cashier-hover-target--start"
                  :class="{
                    'is-open': isDeliveryPopoverOpen('email'),
                    'is-hover-popover-suppressed': isDeliveryPopoverOpen('email') || deliveryHoverPopoverSuppressed === 'email',
                  }"
                  @mouseleave="clearDeliveryHoverPopoverSuppression('email')"
                >
                  <button
                    type="button"
                    class="cashier-delivery-option"
                    :class="{
                      'is-selected': store.deliveryEmailReady,
                      'is-open': isDeliveryPopoverOpen('email'),
                      'has-error': store.deliveryValidationViolation && store.deliveryEmailViolation,
                    }"
                    :aria-pressed="store.deliveryEmailReady"
                    :aria-expanded="isDeliveryPopoverOpen('email')"
                    aria-haspopup="dialog"
                    aria-label="Email"
                    @click="toggleEmailPopover"
                  >
                    <AppIcon name="mail" />
                  </button>
                  <span class="cashier-hover-popover" role="tooltip">
                    <span class="cashier-hover-popover-title">Αποστολή με Email</span>
                    <span class="cashier-hover-popover-text">Άνοιγμα επιλογών για email, newsletter και εγγραφή μέλους.</span>
                  </span>

                  <div
                    v-if="isDeliveryPopoverOpen('email')"
                    class="cashier-delivery-popover cashier-delivery-popover--email"
                    :style="deliveryPopoverStyle"
                    role="dialog"
                    aria-label="Email παραλήπτη"
                    @keydown.esc.prevent.stop="closeDeliveryPopover"
                  >
                    <div class="cashier-delivery-popover-head">
                      <div>
                        <strong class="cashier-delivery-popover-title">Αποστολή εισιτηρίων με Email</strong>
                        <p class="cashier-delivery-popover-description">
                          Θα σταλεί email παραγγελίας με τα εισιτήρια στους παραλήπτες.
                        </p>
                      </div>
                      <button
                        type="button"
                        class="cashier-delivery-popover-close"
                        aria-label="Κλείσιμο"
                        @click="closeDeliveryPopover"
                      >
                        <AppIcon name="x" />
                      </button>
                    </div>

                    <label
                      class="cashier-delivery-field cashier-delivery-field--email"
                      :class="{ 'is-invalid': store.deliveryValidationViolation && store.deliveryEmailViolation }"
                    >
                      <input
                        type="text"
                        v-model="store.customerEmail"
                        inputmode="email"
                        autocomplete="email"
                        placeholder="Email παραλήπτη/ων *"
                        :required="store.deliveryRequiresEmail"
                        :aria-required="store.deliveryRequiresEmail"
                        :aria-invalid="Boolean(store.deliveryValidationViolation && store.deliveryEmailViolation)"
                      >
                    </label>

                    <div class="cashier-delivery-chips">
                      <button
                        type="button"
                        class="cashier-delivery-newsletter"
                        :class="{ 'is-selected': store.newsletterOptIn }"
                        :aria-pressed="store.newsletterOptIn"
                        @click="store.newsletterOptIn = !store.newsletterOptIn"
                      >
                        <span>Εγγραφή στο Newsletter</span>
                      </button>
                      <button
                        type="button"
                        class="cashier-delivery-newsletter"
                        :class="{ 'is-selected': store.membershipInviteOptIn }"
                        :aria-pressed="store.membershipInviteOptIn"
                        @click="store.membershipInviteOptIn = !store.membershipInviteOptIn"
                      >
                        <span>Εγγραφή Μέλους</span>
                      </button>
                    </div>

                    <div class="cashier-delivery-popover-actions">
                      <button
                        type="button"
                        class="cashier-delivery-popover-action cashier-delivery-popover-action--clear"
                        @click="clearEmailDelivery"
                      >
                        Καθαρισμός
                      </button>
                      <button
                        type="button"
                        class="cashier-delivery-popover-action cashier-delivery-popover-action--save"
                        @click="saveDeliveryPopover"
                      >
                        Αποθήκευση
                      </button>
                    </div>
                  </div>
                </div>

                <div
                  class="cashier-delivery-popover-wrap cashier-hover-target"
                  :class="{
                    'is-open': isDeliveryPopoverOpen('sms'),
                    'is-hover-popover-suppressed': isDeliveryPopoverOpen('sms') || deliveryHoverPopoverSuppressed === 'sms',
                  }"
                  @mouseleave="clearDeliveryHoverPopoverSuppression('sms')"
                >
                  <button
                    type="button"
                    class="cashier-delivery-option"
                    :class="{
                      'is-selected': store.deliverySmsReady,
                      'is-open': isDeliveryPopoverOpen('sms'),
                      'has-error': store.deliveryValidationViolation && store.deliveryPhoneViolation,
                    }"
                    :aria-pressed="store.deliverySmsReady"
                    :aria-expanded="isDeliveryPopoverOpen('sms')"
                    aria-haspopup="dialog"
                    aria-label="SMS"
                    @click="toggleSmsPopover"
                  >
                    <AppIcon name="phone" />
                  </button>
                  <span class="cashier-hover-popover" role="tooltip">
                    <span class="cashier-hover-popover-title">Αποστολή με SMS</span>
                    <span class="cashier-hover-popover-text">Άνοιγμα πεδίου για κινητό και αποστολή συνδέσμου εισιτηρίων.</span>
                  </span>

                  <div
                    v-if="isDeliveryPopoverOpen('sms')"
                    class="cashier-delivery-popover cashier-delivery-popover--sms"
                    :style="deliveryPopoverStyle"
                    role="dialog"
                    aria-label="Κινητό για SMS"
                    @keydown.esc.prevent.stop="closeDeliveryPopover"
                  >
                    <div class="cashier-delivery-popover-head">
                      <div>
                        <strong class="cashier-delivery-popover-title">Αποστολή εισιτηρίων με SMS</strong>
                        <p class="cashier-delivery-popover-description">
                          Θα σταλεί SMS με σύνδεσμο για τα εισιτήρια της παραγγελίας.
                        </p>
                      </div>
                      <button
                        type="button"
                        class="cashier-delivery-popover-close"
                        aria-label="Κλείσιμο"
                        @click="closeDeliveryPopover"
                      >
                        <AppIcon name="x" />
                      </button>
                    </div>

                    <label
                      class="cashier-delivery-field cashier-delivery-field--phone"
                      :class="{ 'is-invalid': store.deliveryValidationViolation && store.deliveryPhoneViolation }"
                    >
                      <input
                        type="tel"
                        v-model="store.customerPhone"
                        autocomplete="tel"
                        placeholder="Κινητό για SMS *"
                        :required="store.deliveryRequiresPhone"
                        :aria-required="store.deliveryRequiresPhone"
                        :aria-invalid="Boolean(store.deliveryValidationViolation && store.deliveryPhoneViolation)"
                      >
                    </label>

                    <div class="cashier-delivery-popover-actions">
                      <button
                        type="button"
                        class="cashier-delivery-popover-action cashier-delivery-popover-action--clear"
                        @click="clearSmsDelivery"
                      >
                        Καθαρισμός
                      </button>
                      <button
                        type="button"
                        class="cashier-delivery-popover-action cashier-delivery-popover-action--save"
                        @click="saveDeliveryPopover"
                      >
                        Αποθήκευση
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="cashier-payment-block">
                <PaymentMethods />
                <div class="cashier-sale-summary">
                  <strong>{{ money(store.totalPrice) }}</strong>
                  <span>{{ store.visitors.length }} {{ store.visitors.length === 1 ? 'Εισιτήριο' : 'Εισιτήρια' }}</span>
                </div>
                <div class="cashier-actions">
                  <button class="cashier-primary" type="submit" :disabled="store.loading || store.ticketDetailLoading">
                    <span v-if="store.loading" class="cashier-button-spinner" aria-hidden="true"></span>
                    <AppIcon v-else class="cashier-button-icon" name="check" />
                    <span>Έκδοση</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="cashier-payment-status-row" v-if="store.statusMessage || store.deliveryValidationViolation">
              <p
                class="cashier-status"
                :data-status="store.deliveryValidationViolation ? 'error' : store.statusType"
              >
                {{ store.deliveryValidationViolation || store.statusMessage }}
              </p>
            </div>

            <div class="cashier-pos-fields" v-if="store.selectedPaymentIsPos">
              <label>
                <input type="text" v-model="store.posReference" autocomplete="off" placeholder="Απόδειξη POS">
              </label>
              <label>
                <input
                  type="text"
                  v-model="store.terminalId"
                  autocomplete="off"
                  :placeholder="store.selectedPaymentRequiresTerminal ? 'Terminal ID *' : 'Terminal ID'"
                  :required="store.selectedPaymentRequiresTerminal"
                  :aria-required="store.selectedPaymentRequiresTerminal"
                >
              </label>
            </div>
          </div>
        </template>
      </template>

      <div class="cashier-sale-detail-loader cashier-list-loader" v-else role="status" aria-live="polite">
        <span class="cashier-list-loader-text">{{ store.statusMessage || 'Δεν φορτώθηκαν οι πληροφορίες εισιτηρίου.' }}</span>
        <button class="cashier-secondary" type="button" @click="backToTickets">
          <AppIcon name="chevronLeft" />
          <span>Επιστροφή στα εισιτήρια</span>
        </button>
      </div>
    </form>
  </section>
</template>
