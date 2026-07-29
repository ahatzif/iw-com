<script setup>
import { inject, nextTick, ref, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  visitor: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  categoryKey: {
    type: String,
    default: '',
  },
  showType: {
    type: Boolean,
    default: true,
  },
});

const store = inject('cashierStore');
const categoryButton = ref(null);
const firstInput = ref(null);
const lastInput = ref(null);
const memberButton = ref(null);

function focusValidationTarget() {
  const field = store.visitorValidationFocusField(props.visitor);
  const target = {
    category: categoryButton.value,
    first: firstInput.value,
    last: lastInput.value,
    member: memberButton.value,
  }[field];

  if (!target) return;

  target.scrollIntoView?.({ block: 'center', behavior: 'smooth' });

  try {
    target.focus({ preventScroll: true });
  } catch (error) {
    target.focus?.();
  }
}

watch(
  () => store.validationFocusTarget?.token,
  async () => {
    await nextTick();
    focusValidationTarget();
  },
);
</script>

<template>
  <div
    class="cashier-visitor"
    :class="{
      'cashier-visitor--no-type': !showType,
      'cashier-visitor--member-ticket': store.visitorRequiresMemberVerification(visitor),
      'cashier-visitor--member-verified': store.visitorRequiresMemberVerification(visitor) && store.visitorHasActiveMemberVerification(visitor) && !store.visitorIsMemberCompanion(visitor),
      'cashier-visitor--member-companion': store.visitorIsMemberCompanion(visitor),
    }"
  >
    <label v-if="showType && !(store.visitorRequiresMemberVerification(visitor) && store.visitorHasActiveMemberVerification(visitor))">
      <button
        ref="categoryButton"
        class="cashier-select-trigger"
        :class="{ 'is-validation-target': store.visitorHasValidationFocus(visitor, 'category') }"
        type="button"
        @click="store.openVisitorCategory(index, categoryKey)"
      >
        <span>
          <strong>{{ store.visitorCategoryLabel(visitor) }}</strong>
        </span>
        <AppIcon class="cashier-row-chevron" name="chevronDown" />
      </button>
    </label>
    <div class="cashier-companion-label" v-if="store.visitorIsMemberCompanion(visitor)">
      ΣΥΝΟΔΟΣ ΜΕΛΟΥΣ
    </div>
    <label v-if="store.visitorRequiresManualName(visitor)">
      <input
        type="text"
        v-model="visitor.first"
        maxlength="80"
        :placeholder="store.visitorRequiresManualName(visitor) ? 'Όνομα *' : 'Όνομα (προαιρετικά)'"
        :required="store.visitorRequiresManualName(visitor)"
        :aria-required="store.visitorRequiresManualName(visitor)"
        :class="{ 'is-validation-target': store.visitorHasValidationFocus(visitor, 'first') }"
        @input="store.clearValidationFocusTarget()"
      >
    </label>
    <label v-if="store.visitorRequiresManualName(visitor)">
      <input
        type="text"
        v-model="visitor.last"
        maxlength="80"
        :placeholder="store.visitorRequiresManualName(visitor) ? 'Επώνυμο *' : 'Επώνυμο (προαιρετικά)'"
        :required="store.visitorRequiresManualName(visitor)"
        :aria-required="store.visitorRequiresManualName(visitor)"
        :class="{ 'is-validation-target': store.visitorHasValidationFocus(visitor, 'last') }"
        @input="store.clearValidationFocusTarget()"
      >
    </label>
    <div
      class="cashier-visitor-member"
      :class="{
        'is-valid': store.visitorHasActiveMemberVerification(visitor),
        'is-validation-target': store.visitorHasValidationFocus(visitor, 'member'),
      }"
      v-if="store.visitorRequiresMemberVerification(visitor) && !store.visitorIsMemberCompanion(visitor)"
    >
      <AppIcon :name="store.visitorHasActiveMemberVerification(visitor) ? 'check' : 'search'" aria-hidden="true" />
      <span>
        <strong>{{ store.visitorMemberVerificationTitle(visitor) }}</strong>
        <small>{{ store.visitorMemberVerificationSubtitle(visitor) }}</small>
      </span>
      <span class="cashier-visitor-member-actions">
        <button
          class="cashier-secondary"
          type="button"
          v-if="store.canAddMemberCompanion(visitor)"
          @click="store.addMemberCompanion(index)"
        >
          Προσθήκη συνοδού
        </button>
        <button
          ref="memberButton"
          class="cashier-secondary"
          type="button"
          @click="store.openMemberVerification(index)"
        >
          {{ store.visitorHasActiveMemberVerification(visitor) ? 'Αλλαγή' : 'Έλεγχος' }}
        </button>
      </span>
    </div>
    <button class="cashier-icon-button" type="button" :disabled="!store.canRemoveVisitor(index)" aria-label="Αφαίρεση" @click="store.removeVisitor(index)">
      <AppIcon name="minus" />
    </button>
  </div>
</template>
