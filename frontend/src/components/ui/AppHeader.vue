<script setup lang="ts">
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
</script>

<template>
  <header class="app-header">
    <div class="app-header__brand">
      <span class="app-header__mark" aria-hidden="true">PB</span>
      <span class="app-header__wordmark">Promo Bonus</span>
    </div>

    <div class="app-header__actions">
      <!-- Page specific actions, such as signing out. -->
      <slot />
      <ThemeToggle />
    </div>
  </header>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.app-header {
  display: flex;
  gap: $space-sm;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  max-width: $layout-max-width;
  margin: 0 auto;
  padding: $space-sm;

  &__brand {
    display: flex;
    gap: $space-2xs;
    align-items: center;
  }

  &__mark {
    display: grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    font-size: $font-size-caption;
    font-weight: $font-weight-semibold;
    letter-spacing: 0.04em;
    color: var(--color-on-primary);
    background-color: var(--color-primary);
    border-radius: $radius-md;
  }

  &__wordmark {
    @include label;

    color: var(--color-text);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  &__actions {
    display: flex;
    gap: $space-2xs;
    align-items: center;
  }
}
</style>
