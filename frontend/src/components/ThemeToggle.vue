<script setup lang="ts">
import { computed } from 'vue'
import { useTheme } from '@/composables/useTheme'

const { activeTheme, toggleTheme } = useTheme()

// Names the action rather than the current state, which is what a screen
// reader user needs in order to know what pressing the button will do.
const label = computed(() =>
  activeTheme.value === 'dark' ? 'Switch to light theme' : 'Switch to dark theme',
)
</script>

<template>
  <button type="button" class="theme-toggle" :title="label" @click="toggleTheme">
    <svg
      v-if="activeTheme === 'dark'"
      class="theme-toggle__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      focusable="false"
    >
      <circle cx="12" cy="12" r="4.25" />
      <path
        d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4L6 18M18 6l1.4-1.4"
      />
    </svg>

    <svg
      v-else
      class="theme-toggle__icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      focusable="false"
    >
      <path d="M20.5 14.8A8.6 8.6 0 0 1 9.2 3.5a8.6 8.6 0 1 0 11.3 11.3Z" />
    </svg>

    <span class="theme-toggle__label">{{ label }}</span>
  </button>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.theme-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  color: var(--color-text-muted);
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: $radius-md;
  transition:
    color $transition-fast,
    background-color $transition-fast,
    border-color $transition-fast;

  &:hover {
    color: var(--color-text);
    border-color: var(--color-primary);
  }

  &:focus-visible {
    @include focus-ring;
  }

  &__icon {
    width: 1.25rem;
    height: 1.25rem;
    fill: none;
    stroke: currentcolor;
    stroke-width: 1.75;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  &__label {
    @include visually-hidden;
  }
}
</style>
