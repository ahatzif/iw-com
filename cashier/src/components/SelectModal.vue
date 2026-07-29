<script setup>
import { inject, nextTick, ref, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const store = inject('cashierStore');
const searchInput = ref(null);

watch(
  () => store.selectSheet.open,
  async (isOpen) => {
    if (!isOpen || !store.selectSheet.searchable) return;
    await nextTick();
    searchInput.value?.focus();
  }
);
</script>

<template>
  <div class="cashier-select-layer is-open" v-if="store.selectSheet.open">
    <button class="cashier-select-backdrop" type="button" aria-label="Κλείσιμο" @click="store.closeSelect"></button>
    <section
      class="cashier-select-sheet"
      :class="{
        'cashier-select-sheet--calendar': store.selectSheet.kind === 'date',
        'cashier-select-sheet--time': store.selectSheet.kind === 'time',
      }"
      role="dialog"
      aria-modal="true"
      aria-labelledby="cashier-select-title"
      tabindex="-1"
      @keydown.esc="store.closeSelect"
    >
      <button class="cashier-select-close" type="button" aria-label="Κλείσιμο" @click="store.closeSelect">
        <AppIcon name="x" aria-hidden="true" />
      </button>
      <div class="cashier-select-handle" aria-hidden="true"></div>
      <header class="cashier-select-sheet-head">
        <h2 id="cashier-select-title">{{ store.selectSheet.title }}</h2>
        <AppIcon :name="store.selectSheet.icon || 'pin'" aria-hidden="true" />
      </header>
      <div class="cashier-calendar" v-if="store.selectSheet.kind === 'date'">
        <div class="cashier-calendar-head">
          <button class="cashier-icon-button" type="button" :disabled="!store.calendarCanGoPrev" aria-label="Προηγούμενος μήνας" @click="store.changeCalendarMonth(-1)">
            <AppIcon name="chevronLeft" aria-hidden="true" />
          </button>
          <strong>{{ store.calendarMonthTitle }}</strong>
          <button class="cashier-icon-button" type="button" :disabled="!store.calendarCanGoNext" aria-label="Επόμενος μήνας" @click="store.changeCalendarMonth(1)">
            <AppIcon name="chevronRight" aria-hidden="true" />
          </button>
        </div>
        <div class="cashier-calendar-weekdays" aria-hidden="true">
          <span v-for="day in store.calendarWeekdays" :key="day">{{ day }}</span>
        </div>
        <div class="cashier-calendar-grid">
          <template v-for="day in store.calendarDays" :key="day.key">
            <span class="cashier-calendar-day cashier-calendar-day--empty" v-if="day.empty" aria-hidden="true"></span>
            <button
              v-else
              class="cashier-calendar-day"
              :class="[
                day.selected ? 'is-selected' : '',
                day.disabled ? 'is-disabled' : '',
                day.holidayLabel ? 'is-holiday' : '',
                day.hintVisible ? 'is-hint-visible' : '',
                day.availabilityState ? `cashier-calendar-day--${day.availabilityState}` : '',
              ]"
              type="button"
              :disabled="day.disabled && !day.holidayLabel"
              :aria-pressed="day.selected"
              :aria-disabled="day.disabled ? 'true' : 'false'"
              :aria-label="`${day.label} ${store.calendarMonthTitle}, ${day.availabilityLabel}${day.holidayLabel ? `, ${day.holidayLabel}` : ''}`"
              :title="day.holidayLabel || day.availabilityLabel"
              @click="day.disabled ? store.showCalendarHint(day.date) : store.chooseSelectOption(day.date)"
            >
              <span>{{ day.label }}</span>
              <i class="cashier-calendar-holiday-mark" v-if="day.holidayLabel" aria-hidden="true">×</i>
              <small class="cashier-calendar-hint" v-if="day.holidayLabel">{{ day.holidayLabel }}</small>
            </button>
          </template>
        </div>
        <div class="cashier-calendar-legend" aria-hidden="true">
          <span><i class="cashier-calendar-dot cashier-calendar-dot--sold-out"></i>Εξαντλημένο</span>
          <span><i class="cashier-calendar-dot cashier-calendar-dot--low"></i>Περιορισμένο</span>
          <span><i class="cashier-calendar-dot cashier-calendar-dot--available"></i>Διαθέσιμο</span>
        </div>
      </div>
      <div class="cashier-select-search" v-else-if="store.selectSheet.searchable">
        <AppIcon name="search" aria-hidden="true" />
        <input ref="searchInput" type="search" v-model="store.selectSheet.search" :placeholder="store.selectSheet.searchPlaceholder || 'Αναζήτηση'" autocomplete="off">
      </div>
      <div class="cashier-select-options" v-if="store.selectSheet.kind !== 'date'" :class="{ 'cashier-select-options--time': store.selectSheet.kind === 'time' }">
        <p class="cashier-select-empty" v-if="!store.filteredSelectOptions.length">Δεν βρέθηκαν επιλογές.</p>
        <button
          class="cashier-select-option"
          :class="[
            { 'is-selected': item.selected },
            store.selectSheet.kind === 'time' ? 'cashier-select-option--slot' : '',
            item.availabilityState ? `cashier-select-option--${item.availabilityState}` : '',
          ]"
          type="button"
          v-for="item in store.filteredSelectOptions"
          :key="item.value"
          :disabled="item.disabled"
          @click="store.chooseSelectOption(item.value)"
        >
          <span class="cashier-select-radio" aria-hidden="true"></span>
          <span>
            <strong>{{ item.label }}</strong>
            <small v-if="item.subtitle">{{ item.subtitle }}</small>
          </span>
        </button>
      </div>
      <div class="cashier-calendar-legend cashier-slot-legend" v-if="store.selectSheet.kind === 'time'" aria-hidden="true">
        <span><i class="cashier-calendar-dot cashier-calendar-dot--sold-out"></i>Εξαντλημένο</span>
        <span><i class="cashier-calendar-dot cashier-calendar-dot--low"></i>Περιορισμένο</span>
        <span><i class="cashier-calendar-dot cashier-calendar-dot--available"></i>Διαθέσιμο</span>
      </div>
    </section>
  </div>
</template>
