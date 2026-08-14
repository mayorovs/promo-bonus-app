<script setup lang="ts">
import type { PromoClaimStatus } from '@/types/promo'

interface Props {
  disabled?: boolean
}

withDefaults(defineProps<Props>(), {
  disabled: false,
})

/** Null lists every status. */
const selected = defineModel<PromoClaimStatus | null>({ required: true })

interface FilterOption {
  value: PromoClaimStatus | null
  label: string
}

const options: FilterOption[] = [
  { value: null, label: 'All' },
  { value: 'applied', label: 'Applied' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'revoked', label: 'Revoked' },
]
</script>

<template>
  <div class="filters" role="group" aria-label="Filter history by status">
    <button
      v-for="option in options"
      :key="option.label"
      type="button"
      class="filters__option"
      :class="{ 'filters__option--active': selected === option.value }"
      :aria-pressed="selected === option.value"
      :disabled="disabled"
      @click="selected = option.value"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: $space-3xs;

  &__option {
    @include caption;

    padding: $space-3xs $space-2xs;
    font-weight: $font-weight-medium;
    color: var(--color-text-muted);
    background-color: transparent;
    border: 1px solid var(--color-border);
    border-radius: $radius-pill;
    transition:
      color $transition-fast,
      background-color $transition-fast,
      border-color $transition-fast;

    &:hover:not(:disabled):not(&--active) {
      color: var(--color-text);
      border-color: var(--color-text-muted);
    }

    &:focus-visible {
      @include focus-ring;
    }

    &:disabled {
      cursor: not-allowed;
      opacity: 0.55;
    }

    &--active {
      color: var(--color-on-primary);
      background-color: var(--color-primary);
      border-color: var(--color-primary);
    }
  }
}
</style>
