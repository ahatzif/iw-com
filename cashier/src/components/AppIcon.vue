<script setup>
import { icons } from '../utils/icons.js';

defineProps({
  name: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <span v-html="icons[name] || ''"></span>
</template>
