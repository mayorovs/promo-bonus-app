<script setup lang="ts">
// Purely decorative: whatever is loading supplies the wording, so the loader
// itself stays out of the accessibility tree.
</script>

<template>
  <span class="spinner" aria-hidden="true">
    <span class="spinner__dot"></span>
    <span class="spinner__dot"></span>
    <span class="spinner__dot"></span>
  </span>
</template>

<style scoped lang="scss">
.spinner {
  // Everything scales from one value, so the same loader suits a button and a
  // full page alike, and the dots take their colour from the parent.
  --dot-size: calc(var(--spinner-size, 1rem) * 0.3);

  display: inline-flex;
  gap: calc(var(--dot-size) * 0.75);
  align-items: center;
  vertical-align: middle;

  &__dot {
    width: var(--dot-size);
    height: var(--dot-size);
    background-color: currentcolor;
    border-radius: 50%;
    animation: dot-pulse 1.05s ease-in-out infinite;

    // The stagger is what makes the row read as one movement travelling left
    // to right rather than three separate blinks.
    &:nth-child(2) {
      animation-delay: 0.15s;
    }

    &:nth-child(3) {
      animation-delay: 0.3s;
    }
  }
}

@keyframes dot-pulse {
  0%,
  80%,
  100% {
    opacity: 0.3;
    transform: scale(0.7);
  }

  40% {
    opacity: 1;
    transform: scale(1);
  }
}

// Without this the dots would freeze mid-animation at partial opacity, which
// reads as broken rather than still.
@media (prefers-reduced-motion: reduce) {
  .spinner__dot {
    animation: none;
    opacity: 1;
    transform: none;
  }
}
</style>
