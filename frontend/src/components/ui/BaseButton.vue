<script setup lang="ts">
import { computed } from 'vue'
import BaseSpinner from '@/components/ui/BaseSpinner.vue'

interface Props {
  type?: 'button' | 'submit'
  variant?: 'primary' | 'secondary'
  loading?: boolean
  disabled?: boolean
  block?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'button',
  variant: 'primary',
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
    :class="[`button--${variant}`, { 'button--block': block }]"
    :disabled="isDisabled"
    :aria-busy="loading || undefined"
  >
    <BaseSpinner v-if="loading" />
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
  border: 1px solid transparent;
  border-radius: $radius-md;
  transition:
    color $transition-fast,
    background-color $transition-fast,
    border-color $transition-fast,
    opacity $transition-fast;

  &:focus-visible {
    @include focus-ring;
  }

  &:disabled {
    cursor: not-allowed;
    opacity: 0.55;
  }

  &--primary {
    color: var(--color-on-primary);
    background-color: var(--color-primary);

    &:hover:not(:disabled) {
      background-color: var(--color-primary-hover);
    }
  }

  &--secondary {
    color: var(--color-text);
    background-color: transparent;
    border-color: var(--color-border);

    &:hover:not(:disabled) {
      border-color: var(--color-primary);
      color: var(--color-primary);
    }
  }

  &--block {
    width: 100%;
  }

  // Announced to screen readers; the spinner alone conveys nothing to them.
  &__status {
    @include visually-hidden;
  }
}
</style>
