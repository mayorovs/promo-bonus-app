<script setup lang="ts">
// Purely decorative: whatever is loading supplies the wording, so the spinner
// itself stays out of the accessibility tree.
</script>

<template>
  <span class="spinner" aria-hidden="true"></span>
</template>

<style scoped lang="scss">
.spinner {
  display: inline-block;
  // Size and colour come from the caller, so one spinner suits a button and a
  // full page alike.
  width: var(--spinner-size, 1rem);
  height: var(--spinner-size, 1rem);
  border: 2px solid currentcolor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
