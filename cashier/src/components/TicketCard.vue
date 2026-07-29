<script setup>
import AppIcon from './AppIcon.vue';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
  interactive: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['select']);

function ticketThumb(item) {
  return item?.thumb || item?.image?.thumb || item?.image?.url || '';
}

function ticketInfoUrl(item) {
  return item?.permalink || item?.ticket_permalink || '';
}

function selectTicket() {
  if (!props.interactive) return;
  emit('select', props.item);
}

function handleKeydown(event) {
  if (!props.interactive) return;
  if (event.target?.closest?.('a')) return;
  if (event.key !== 'Enter' && event.key !== ' ') return;
  event.preventDefault();
  selectTicket();
}
</script>

<template>
  <div
    class="cashier-ticket"
    :class="{ 'cashier-ticket--selected': selected, 'is-static': !interactive }"
    :role="interactive ? 'button' : undefined"
    :tabindex="interactive ? 0 : undefined"
    @click="selectTicket"
    @keydown="handleKeydown"
  >
    <span class="cashier-ticket-thumb" :class="{ 'cashier-ticket-thumb--placeholder': !ticketThumb(item) }" aria-hidden="true">
      <img v-if="ticketThumb(item)" :src="ticketThumb(item)" alt="">
      <AppIcon v-else name="ticket" />
    </span>
    <span class="cashier-ticket-copy">
      <strong>{{ item.display_title || item.title || '' }}</strong>
      <span class="cashier-ticket-meta" v-if="item.date_label || item.building_title || item.price_label || ticketInfoUrl(item)">
        <span v-if="item.date_label">
          {{ item.date_label }} ·
        </span>
        <span v-if="item.price_label">
          {{ item.price_label }} ·
        </span>
        <a v-if="ticketInfoUrl(item)" :href="ticketInfoUrl(item)" target="_blank" rel="noopener noreferrer" @click.stop @keydown.stop>
          Πληροφορίες
        </a>
      </span>
    </span>
    <AppIcon v-if="interactive" class="cashier-ticket-chevron" name="chevronRight" />
  </div>
</template>
