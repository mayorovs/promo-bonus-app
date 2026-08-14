<template>
  <main class="landing">
    <p class="landing__eyebrow">Promo bonuses</p>
    <h1 class="landing__title">Promo Bonus App</h1>
    <p class="landing__text">
      The design system is in place. Authentication and the promo code features
      follow in the next steps.
    </p>
  </main>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.landing {
  max-width: $layout-max-width;
  margin: 0 auto;
  padding: $space-lg $space-sm;

  @include respond-from('md') {
    padding: $space-xl $space-md;
  }

  &__eyebrow {
    @include label;

    margin-bottom: $space-3xs;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  &__title {
    margin-bottom: $space-xs;
  }

  &__text {
    color: var(--color-text-muted);
  }
}
</style>
