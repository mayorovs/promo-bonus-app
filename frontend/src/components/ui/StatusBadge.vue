<script setup lang="ts">
interface Props {
  variant?: 'success' | 'danger' | 'neutral'
}

withDefaults(defineProps<Props>(), {
  variant: 'neutral',
})
</script>

<template>
  <span class="badge" :class="`badge--${variant}`">
    <slot />
  </span>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.badge {
  @include caption;

  display: inline-flex;
  align-items: center;
  padding: 0 $space-2xs;
  font-weight: $font-weight-medium;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  white-space: nowrap;
  border: 1px solid transparent;
  border-radius: $radius-pill;

  &--success {
    color: var(--color-success);
    background-color: var(--color-success-surface);
    border-color: var(--color-success);
  }

  &--danger {
    color: var(--color-danger);
    background-color: var(--color-danger-surface);
    border-color: var(--color-danger);
  }

  &--neutral {
    color: var(--color-text-muted);
    background-color: var(--color-surface-muted);
    border-color: var(--color-border);
  }
}
</style>
