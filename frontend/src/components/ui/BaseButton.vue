<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  type?: 'button' | 'submit'
  loading?: boolean
  disabled?: boolean
  block?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'button',
  loading: false,
  disabled: false,
  block: false,
})

// Loading implies disabled, so a slow request cannot be submitted twice.
const isDisabled = computed(() => props.disabled || props.loading)
</script>

<template>
  <button
    :type="type"
    class="button"
    :class="{ 'button--block': block }"
    :disabled="isDisabled"
    :aria-busy="loading || undefined"
  >
    <span v-if="loading" class="button__spinner" aria-hidden="true"></span>
    <span v-if="loading" class="button__status">Loading</span>
    <span class="button__label"><slot /></span>
  </button>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.button {
  @include label;

  display: inline-flex;
  gap: $space-2xs;
  align-items: center;
  justify-content: center;
  min-height: 2.75rem;
  padding: $space-2xs $space-md;
  color: var(--color-on-primary);
  background-color: var(--color-primary);
  border: 1px solid transparent;
  border-radius: $radius-md;
  transition:
    background-color $transition-fast,
    opacity $transition-fast;

  &:hover:not(:disabled) {
    background-color: var(--color-primary-hover);
  }

  &:focus-visible {
    @include focus-ring;
  }

  &:disabled {
    cursor: not-allowed;
    opacity: 0.55;
  }

  &--block {
    width: 100%;
  }

  &__spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid currentcolor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
  }

  // Announced to screen readers; the spinner alone conveys nothing to them.
  &__status {
    @include visually-hidden;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
