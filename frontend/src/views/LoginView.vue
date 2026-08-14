<script setup lang="ts">
import { ref } from 'vue'
import LoginForm from '@/components/auth/LoginForm.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'
import type { LoginCredentials } from '@/types/auth'

// The page owns the request state the form reacts to. The API call itself is
// wired up in the next step.
const loading = ref(false)

function handleSubmit(_credentials: LoginCredentials): void {
  // Deliberately not connected to the API yet.
}
</script>

<template>
  <div class="login">
    <header class="login__bar">
      <div class="login__brand">
        <span class="login__mark" aria-hidden="true">PB</span>
        <span class="login__wordmark">Promo Bonus</span>
      </div>

      <ThemeToggle />
    </header>

    <main class="login__main">
      <section class="login__card" aria-labelledby="login-heading">
        <h1 id="login-heading" class="login__title">Sign in</h1>
        <p class="login__subtitle">
          Enter your details to claim and manage promo bonuses.
        </p>

        <LoginForm :loading="loading" @submit="handleSubmit" />
      </section>
    </main>
  </div>
</template>

<style scoped lang="scss">
@use '@/styles/abstracts' as *;

.login {
  display: flex;
  flex-direction: column;
  min-height: 100vh;

  &__bar {
    display: flex;
    gap: $space-sm;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: $layout-max-width;
    margin: 0 auto;
    padding: $space-sm;
  }

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

  &__main {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    padding: $space-sm $space-sm $space-xl;
  }

  &__card {
    width: 100%;
    max-width: $layout-form-width;
    padding: $space-md $space-sm;
    background-color: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: $radius-lg;
    box-shadow: var(--shadow-md);

    @include respond-from('sm') {
      padding: $space-lg $space-md;
    }
  }

  &__title {
    margin-bottom: $space-3xs;
  }

  &__subtitle {
    @include body-sm;

    margin-bottom: $space-md;
    color: var(--color-text-muted);
  }
}
</style>
