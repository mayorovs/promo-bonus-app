<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'error' | 'success'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'error',
})

// A failure interrupts the screen reader; a confirmation waits its turn.
const role = computed(() => (props.variant === 'error' ? 'alert' : 'status'))
</script>

<template>
  <div class="alert" :class="`alert--${variant}`" :role="role">
    <slot />
  </div>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.alert {
  @include body-sm;

  padding: $space-2xs $space-xs;
  border: 1px solid transparent;
  border-radius: $radius-md;

  &--error {
    color: var(--color-danger);
    background-color: var(--color-danger-surface);
    border-color: var(--color-danger);
  }

  &--success {
    color: var(--color-success);
    background-color: var(--color-success-surface);
    border-color: var(--color-success);
  }
}
</style>
