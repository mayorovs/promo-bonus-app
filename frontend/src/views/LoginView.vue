<script setup lang="ts">
import { ref } from 'vue'
import LoginForm from '@/components/auth/LoginForm.vue'
import AppHeader from '@/components/ui/AppHeader.vue'
import { useAuth } from '@/composables/useAuth'
import { toApiError } from '@/services/apiClient'
import type { LoginCredentials } from '@/types/auth'

const { signIn } = useAuth()

const loading = ref(false)
const errorMessage = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

async function handleSubmit(credentials: LoginCredentials): Promise<void> {
  loading.value = true
  errorMessage.value = null
  fieldErrors.value = {}

  try {
    await signIn(credentials)
  } catch (error) {
    const apiError = toApiError(error)

    fieldErrors.value = apiError.fieldErrors

    // Validation messages are shown on the fields themselves; anything else,
    // such as wrong credentials, needs a general message.
    errorMessage.value =
      Object.keys(apiError.fieldErrors).length > 0 ? null : apiError.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login">
    <AppHeader />

    <main class="login__main">
      <section class="login__card" aria-labelledby="login-heading">
        <h1 id="login-heading" class="login__title">Sign in</h1>
        <p class="login__subtitle">
          Enter your details to claim and manage promo bonuses.
        </p>

        <LoginForm
          :loading="loading"
          :error-message="errorMessage"
          :field-errors="fieldErrors"
          @submit="handleSubmit"
        />
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
