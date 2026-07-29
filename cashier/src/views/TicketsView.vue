<script setup>
import { inject, nextTick, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppIcon from '../components/AppIcon.vue';
import TicketCard from '../components/TicketCard.vue';

const store = inject('cashierStore');
const router = useRouter();
const ticketSearchInput = ref(null);
const ticketSearchOpen = ref(false);

function selectTicket(item) {
  const ticketId = Number(item.ticket_id || item.id);
  const routeId = Number(item.source_id || item.ticket_id || item.id);
  if (!ticketId) return;

  store.prepareTicketSelection(ticketId, item);
  router.push({ name: 'sale', params: { ticketId: routeId || ticketId } });
  store.loadTicket(routeId || ticketId, item);
}

function openTicketSearch() {
  ticketSearchOpen.value = true;
  nextTick(() => {
    ticketSearchInput.value?.focus();
  });
}

function closeTicketSearch() {
  ticketSearchOpen.value = Boolean(store.ticketSearch);
}
</script>

<template>
  <section class="cashier-app-view is-active" data-cashier-view="tickets" aria-label="Εισιτήρια" aria-hidden="false">
    <div class="cashier-view-loader cashier-list-loader" v-if="store.ticketsLoading" role="status" aria-live="polite">
      <div class="cashier-list-loader-mark" aria-hidden="true">
        <span class="cashier-list-loader-spinner"></span>
      </div>
      <span class="cashier-list-loader-text">Φόρτωση εισιτηρίων...</span>
    </div>

    <template v-else>
      <div class="cashier-section-heading">
        <h1 id="cashier-tickets-title" class="cashier-app-title">Εισιτήρια</h1>
        <p class="cashier-app-status">Επιλέξτε μουσείο και εισιτήριο για έκδοση.</p>
      </div>

      <div class="cashier-tickets-filtering">
        <button class="cashier-location-button" type="button" @click="store.openSelect('building')">
          <AppIcon class="cashier-row-icon" name="pin" aria-hidden="true" />
          <span>
            <strong>{{ store.selectedBuildingTitle }}</strong>
            <small>{{ store.selectedBuildingAddress }}</small>
          </span>
          <AppIcon name="chevronDown" />
        </button>
        <div class="cashier-search" :class="{ 'is-filterless': !store.renderedTicketFilters.length }">

          <div class="cashier-ticket-filters" v-if="store.renderedTicketFilters.length">
            <button
                class="cashier-filter"
                :class="{ 'is-selected': filter.id === store.selectedTicketType }"
                type="button"
                v-for="filter in store.renderedTicketFilters"
                :key="filter.id"
                @click="store.selectTicketFilter(filter.id)"
            >
              {{ filter.label || filter.id }}
            </button>
          </div>
          <label
            class="cashier-searchbox cashier-expand-search"
            :class="{ 'is-expanded': ticketSearchOpen || store.ticketSearch }"
            for="cashier-ticket-search"
            @click="openTicketSearch"
            @focusin="ticketSearchOpen = true"
            @focusout="closeTicketSearch"
          >
            <AppIcon name="search" aria-hidden="true" />
            <input ref="ticketSearchInput" id="cashier-ticket-search" type="search" autocomplete="off" placeholder="Αναζήτηση" aria-label="Αναζήτηση" v-model="store.ticketSearch" @input="store.debouncedSearchTickets">
          </label>
        </div>

      </div>


      <div class="cashier-scroll-fade">
        <div class="cashier-ticket-list">
          <p class="cashier-muted" v-if="!store.ticketResults.length">Δεν βρέθηκαν διαθέσιμα εισιτήρια.</p>
          <template v-else>
            <TicketCard
              v-for="item in store.ticketResults"
              :key="`${item.type || item.post_type}:${item.source_id || item.id}:${item.ticket_id || item.id}`"
              :item="item"
              @select="selectTicket"
            />
          </template>
        </div>
      </div>
    </template>
  </section>
</template>
